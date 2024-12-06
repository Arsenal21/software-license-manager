<?php

/**
 * @author Michel Velis <michel@epikly.com>
 * @link   https://github.com/michelve/software-license-manager
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
    
}

//slm_woo_downloads
function slm_remove_downloads_from_account_menu($items) {
    // Remove "Downloads" menu item.
    unset($items['downloads']);
    return $items;
}

function slm_disable_downloads_endpoint_redirect() {
    // Check if the current endpoint is "downloads" and if it's part of the My Account page.
    if (is_wc_endpoint_url('downloads')) {
        // Redirect to the My Account dashboard.
        wp_safe_redirect(wc_get_page_permalink('myaccount'));
        exit;
    }
}

$enable_downloads_page = SLM_API_Utility::get_slm_option('slm_woo_downloads');
// Check if the 'enable_downloads_page' option is enabled.
if ($enable_downloads_page == 1) {
    // If the option is set and enabled, trigger the action.
    add_action('template_redirect', 'slm_disable_downloads_endpoint_redirect');
    add_filter('woocommerce_account_menu_items', 'slm_remove_downloads_from_account_menu', 10);
}


// Register the endpoint and add it to WooCommerce’s query vars
add_filter('woocommerce_get_query_vars', function($query_vars) {
    $query_vars['my-licenses'] = 'my-licenses';
    return $query_vars;
});

// Flush rewrite rules to ensure the endpoint is recognized on activation
function slm_flush_rewrite_rules() {
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'slm_flush_rewrite_rules');

//Add the My Licenses link to WooCommerce account menu
function slm_add_my_licenses_endpoint($items) {
    if (SLM_API_Utility::get_slm_option('slm_woo')) {
        // Add "My Licenses" item just before "Log out"
        $logout = $items['customer-logout']; // Store the "Log out" item
        unset($items['customer-logout']); // Remove "Log out" temporarily

        // Add "My Licenses" to the array
        $items['my-licenses'] = __('My Licenses', 'slm-plus');

        // Re-add "Log out" to the end
        $items['customer-logout'] = $logout;
    }

    return $items;
}
add_filter('woocommerce_account_menu_items', 'slm_add_my_licenses_endpoint');

//Display content based on endpoint value
add_action('woocommerce_account_my-licenses_endpoint', function($value) {

    //SLM_Helper_Class::write_log('slm_add_my_licenses_endpoint loaded');

    if ($value === 'view') {
        // Use $_GET instead of get_query_var to directly retrieve the URL parameter
        $license_id = isset($_GET['slm_lic']) ? $_GET['slm_lic'] : false;
        //SLM_Helper_Class::write_log('license_id var ' . $license_id);

        if ($license_id) {
            //SLM_Helper_Class::write_log('license_id call2 ' . $license_id);
            slm_license_view($license_id);
        }
        else {
            echo '<p>' . esc_html__('Invalid license or access denied.', 'slm-plus') . '</p>';
             //SLM_Helper_Class::write_log('user id ' . get_current_user_id());
        }
    } else {
        // Display the licenses table if no specific value is passed
        slm_display_license_table();
    }
});

//Display the main licenses table
function slm_display_license_table() {

    //SLM_Helper_Class::write_log('slm_display_license_table loaded');

    $user_id = get_current_user_id();
    $user_email = wp_get_current_user()->user_email;
    
    global $wpdb;
    
    // Sanitize user inputs
    $user_email = sanitize_email($user_email);
    $user_id = intval($user_id);
    
    // Directly using the constant table name as it's not possible to prepare table names
    $table_name = SLM_TBL_LICENSE_KEYS;  // Ensure that SLM_TBL_LICENSE_KEYS is defined as a constant
    
    // Use prepare for query construction with placeholders for dynamic values
    $query = $wpdb->prepare(
        "SELECT DISTINCT license_key, purchase_id_, lic_status
         FROM $table_name
         WHERE email = %s OR subscr_id = %d",
        $user_email, $user_id
    );
    
    // Execute the query safely
    $licenses = $wpdb->get_results($query);    
    

    if ($licenses) {
        echo '<table class="shop_table shop_table_responsive my_account_orders">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Order ID', 'slm-plus') . '</th>';
        echo '<th>' . esc_html__('License Key', 'slm-plus') . '</th>';
        echo '<th>' . esc_html__('Status', 'slm-plus') . '</th>';
        echo '<th>' . esc_html__('Action', 'slm-plus') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';
    
        foreach ($licenses as $license) {
            $order_id = $license->purchase_id_;
            $license_key = $license->license_key;
            $status = $license->lic_status;
            $encoded_license = base64_encode($license_key);
            $action_link = esc_url(add_query_arg(['my-licenses' => 'view', 'slm_lic' => $encoded_license], site_url('/my-account/my-licenses')));
    
            echo '<tr>';
    
            // Display Order ID or Custom Message
            if (!empty($order_id)) {
                echo '<td>' . esc_html($order_id) . '</td>';
            } else {
                echo '<td>' . esc_html__('Manual', 'slm-plus') . '</td>';
            }
    
            echo '<td>' . esc_html($license_key) . '</td>';
    
            // Display the status with custom badge classes
            echo '<td><span class="slm-status-badge status-' . esc_attr(strtolower($status)) . '">' . esc_html($status) . '</span></td>';
    
            echo '<td><a href="' . esc_url($action_link) . '">' . esc_html__('View', 'slm-plus') . '</a></td>';
            echo '</tr>';
        }
    
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>' . esc_html__('No licenses found.', 'slm-plus') . '</p>';
    }
    
}


add_action('wp_loaded', function () {
    if (isset($_GET['renew_license']) && isset($_GET['product_id'])) {
        // Sanitize inputs
        $license_key = sanitize_text_field($_GET['renew_license']);
        $product_id = absint($_GET['product_id']);

        // Validate and process
        if (!empty($license_key) && !empty($product_id)) {
            slm_direct_renew_and_redirect($license_key, $product_id);
        }
    }
});



function slm_direct_renew_and_redirect($license_key, $product_id) {
    // Ensure WooCommerce is loaded
    if (!function_exists('wc_get_product')) {
        error_log('WooCommerce is not loaded. Cannot process renewal.');
        return;
    }

    // Prevent redirection loops by checking a custom flag
    if (isset($_GET['redirected']) && $_GET['redirected'] === 'true') {
        return; // Skip processing if already redirected
    }

    // Validate inputs
    if (empty($license_key) || empty($product_id)) {
        wc_add_notice(__('Invalid license or product.', 'slm-plus'), 'error');
        return;
    }

    // Safely retrieve the product
    $product = wc_get_product($product_id);
    if (!$product || !$product->is_type('simple')) {
        wc_add_notice(__('Invalid product for renewal.', 'slm-plus'), 'error');
        return;
    }

    // Delay cart operations
    add_action('woocommerce_cart_loaded_from_session', function () use ($license_key, $product_id) {
        $cart_item_data = [
            'renew_license_key' => sanitize_text_field($license_key), // Attach the license key
        ];
        $added_to_cart = WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);

        if (!$added_to_cart) {
            wc_add_notice(__('Failed to add product to cart.', 'slm-plus'), 'error');
            return;
        }

        // Build the redirect URL with a 'redirected' flag
        $redirect_url = add_query_arg([
            'renew_license' => $license_key,
            'product_id' => $product_id,
            'add-to-cart' => $product_id,
            'redirected' => 'true', // Add the flag to prevent loops
        ], site_url('/license-cart')); // Update to your cart URL

        // Redirect to the custom cart page
        wp_safe_redirect($redirect_url);
        exit;
    });
}

add_action('woocommerce_before_cart', function() {
    WC()->cart->get_cart_contents_count(); // Total cart items
    error_log(print_r(WC()->cart->get_cart(), true)); // Logs all cart items
});






// Example usage with enhanced safety checks
// Example usage with enhanced safety checks
add_action('init', function () {
    if (isset($_GET['renew_license']) && isset($_GET['product_id'])) {
        // Ensure WooCommerce is active
        if (!function_exists('wc_get_product')) {
            if (!is_plugin_active('woocommerce/woocommerce.php')) {
                error_log('WooCommerce is not active. Please enable it to use the SLM Plus plugin.');
                return;
            }
        }

        // Sanitize input values
        $license_key = sanitize_text_field($_GET['renew_license']);
        $product_id = absint($_GET['product_id']);

        // Validate required values
        if (!empty($license_key) && !empty($product_id)) {
            // Start WooCommerce session if not already initialized
            if (!WC()->session) {
                WC()->initialize_session_handler();
                WC()->session = new WC_Session_Handler();
                WC()->session->init();
                error_log('WooCommerce session initialized.');
            }

            // Store license key in session for later use in checkout
            WC()->session->set('renew_license_key', $license_key);

            // Log for debugging
            SLM_Helper_Class::write_log("Renew license key set in session during redirect: {$license_key}");

            // Redirect or process renewal
            slm_direct_renew_and_redirect($license_key, $product_id);
        } else {
            error_log('Missing or invalid license key or product ID for renewal.');
        }
    }
});



// Step 5: Display individual license details
function slm_license_view($encoded_license_id) {
    global $wpdb;
    $user_email = wp_get_current_user()->user_email;
    $user_id = get_current_user_id();

    // Decode the license key and trim any whitespace
    $license_id = trim(base64_decode($encoded_license_id));

    // Log the decoded license key, user email, and user ID
    //SLM_Helper_Class::write_log('Decoded License Key: ' . $license_id);
    //SLM_Helper_Class::write_log('User Email: ' . $user_email);
    //SLM_Helper_Class::write_log('User ID (subscr_id): ' . $user_id);

    // Construct the query based on whether user ID is available
    if ($user_id) {
        $query = $wpdb->prepare(
            "SELECT * FROM " . SLM_TBL_LICENSE_KEYS . " WHERE (email = %s OR subscr_id = %d) AND license_key = %s",
            $user_email, $user_id, $license_id
        );
    } else {
        $query = $wpdb->prepare(
            "SELECT * FROM " . SLM_TBL_LICENSE_KEYS . " WHERE email = %s AND license_key = %s",
            $user_email, $license_id
        );
    }

    // Log the SQL query for debugging
    //SLM_Helper_Class::write_log('SQL Query: ' . $query);

    // Execute the query
    $license = $wpdb->get_row($query);

    // Check if license was found
    if (!$license) {
        echo '<p>' . esc_html__('Invalid license or access denied.', 'slm-plus') . '</p>';
        //SLM_Helper_Class::write_log('error');
        return;
    }

    // Back Button with dynamic URL generation
    $back_url = wc_get_account_endpoint_url('my-licenses');
    echo '<a href="' . esc_url($back_url) . '" class="button">' . esc_html__('Back to My Licenses', 'slm-plus') . '</a>';

    // Define the fields and labels for dynamic generation
    $slm_license_fields = [
        'license_key' => __('License Key', 'slm-plus'),
        'lic_status' => __('Status', 'slm-plus'),
        'lic_type' => __('License Type', 'slm-plus'),
        'purchase_id_' => __('Order ID', 'slm-plus'),
        'date_created' => __('Date Created', 'slm-plus'),
        'date_activated' => __('Date Activated', 'slm-plus'),
        'date_renewed' => __('Date Renewed', 'slm-plus'),
        'date_expiry' => __('Date Expiry', 'slm-plus'),
        'product_ref' => __('Product Reference', 'slm-plus'),
        'subscr_id' => __('Subscription ID', 'slm-plus'),
        'subscr_id' => __('Subscription ID', 'slm-plus'),
        'max_allowed_domains' => __('Max Allowed Domains', 'slm-plus'),
        'associated_orders' => __('Associated Orders', 'slm-plus'),
        'company_name' => __('Company Name', 'slm-plus'),
    ];

    // Display license details header
    echo '<h3 class="slm_view_lic">' . esc_html__('License Details', 'slm-plus') . '</h3>';
    echo '<table class="shop_table shop_table_responsive my_account_orders">';
    echo '<tbody>';    

    // Loop through each field and output its label and value dynamically
    foreach ($slm_license_fields as $field_key => $field_label) {
        // Check if the field is set and get the value
        $field_value = isset($license->$field_key) ? esc_html($license->$field_key) : '';


        // Special handling for purchase_id_ to link to the order
        if ($field_key === 'purchase_id_') {
            if (!empty($field_value)) {
                // Generate the link to the specific order in the My Account section
                $order_link = wc_get_account_endpoint_url('view-order/' . $field_value);
                $field_value = '<a href="' . esc_url($order_link) . '">' . esc_html($field_value) . '</a>';
            } else {
                $field_value = __('No Order Information Available', 'slm-plus');
            }
        }


        if ($field_key === 'associated_orders') {
            if (!empty($field_value)) {
                // Fetch the associated orders using the provided function
                $associated_orders = SLM_Utility::slm_get_associated_orders($license->license_key);
        
                // Debugging: Log the retrieved value
                SLM_Helper_Class::write_log('Associated Orders Raw Data: ' . print_r($associated_orders, true));
        
                if (!empty($associated_orders) && is_array($associated_orders)) {
                    $links = [];
                    foreach ($associated_orders as $order_id) {
                        // Generate a link to the WooCommerce account orders page
                        $order_url = wc_get_endpoint_url('view-order', $order_id, wc_get_page_permalink('myaccount'));
                        $links[] = sprintf(
                            '<a href="%s">#%s</a>',
                            esc_url($order_url), // Sanitize the URL
                            esc_html($order_id)  // Escape and sanitize the order ID
                        );
                    }
        
                    // Join all links with a comma for display
                    $field_value = implode(', ', $links);
                } else {
                    $field_value = __('No Associated Orders Found (Data Issue)', 'slm-plus');
                }
            } else {
                $field_value = __('No Order Information Available', 'slm-plus');
            }
        }
        
        

        if ($field_key === 'lic_status') {
            $license_key = isset($license->license_key) ? esc_html($license->license_key) : '';
            $purchase_id = isset($license->purchase_id_) ? absint($license->purchase_id_) : 0;
        
            // Ensure date_expiry is checked safely
            $expiration_date = isset($license->date_expiry) ? strtotime($license->date_expiry) : false;
        
            // Determine if the license is expired
            $is_expired = (!empty($expiration_date) && $expiration_date < time()) || $license->lic_status === 'expired';
        
            if ($is_expired && !empty($license_key) && !empty($purchase_id)) {
                $product_id = 0; // Initialize product_id
        
                // Ensure WooCommerce functions are available
                if (!function_exists('wc_get_order')) {
                    // Include WooCommerce files to make functions accessible
                    if (defined('WC_ABSPATH')) {
                        include_once WC_ABSPATH . 'includes/wc-order-functions.php';
                        include_once WC_ABSPATH . 'includes/wc-product-functions.php';
                    } else {
                        // If WooCommerce is not loaded, skip further processing
                        $field_value = sprintf(
                            '<span class="status-expired">%s</span> <span class="warning">%s</span>',
                            __('Expired', 'slm-plus'),
                            __('WooCommerce not loaded.', 'slm-plus')
                        );
                        return;
                    }
                }
        
                // Retrieve the product ID associated with the license
                $order = wc_get_order($purchase_id);
        
                // Ensure order is valid and contains items
                if ($order) {
                    $items = $order->get_items();
                    $product_id = $items ? current($items)->get_product_id() : 0;
                }
        
                if (!empty($product_id)) {
                    // Generate the renewal URL
                    $renew_url = add_query_arg([
                        'renew_license' => $license_key,
                        'product_id' => $product_id,
                    ], site_url('/my-account/my-licenses'));
        
                    // Update field value to include Renew button
                    $field_value = sprintf(
                        '<span class="status-expired">%s</span> <a href="%s" class="button renew-button">%s</a>',
                        __('Expired', 'slm-plus'),
                        esc_url($renew_url),
                        __('Renew', 'slm-plus')
                    );
                } else {
                    // Handle missing product ID case (e.g., display a warning)
                    $field_value = sprintf(
                        '<span class="status-expired">%s</span> <span class="warning">%s</span>',
                        __('Expired', 'slm-plus'),
                        __('Product not found for renewal.', 'slm-plus')
                    );
                }
            }
        }

        // Special handling for product_ref to link to the product page
        if ($field_key === 'product_ref') {
            if (!empty($field_value)) {
                // Retrieve the product URL and name by product ID
                $product_id = $field_value;
                $product_url = get_permalink($product_id);
                $product_name = get_the_title($product_id);

                if ($product_url && $product_name) {
                    // Format as "#ID - ProductName"
                    $field_value = sprintf(
                        '<a href="%s">#%s - %s</a>',
                        esc_url($product_url),         // Sanitize the URL
                        esc_html($product_id),         // Escape and sanitize the product ID
                        esc_html($product_name)        // Escape and sanitize the product name
                    );
                } else {
                    $field_value = __('Product Not Found', 'slm-plus');
                }
            } else {
                $field_value = __('No Product Information Available', 'slm-plus');
            }
        }

        // Special handling for date fields with '0000-00-00' as value
        if (($field_key === 'date_activated' || $field_key === 'date_renewed') && $field_value === '0000-00-00') {
            $field_value = ($field_key === 'date_activated') ? __('Not activated yet', 'slm-plus') : __('Not renewed yet', 'slm-plus');
        }

        echo '<tr><th>' . esc_html($field_label) . '</th><td>' . $field_value . '</td></tr>';
    }
    echo '</tbody>';
    echo '</table>'; 


    global $wpdb;

    // Define the license key for querying activations
    $license_key = $license->license_key;
    
    // Fetch all domain and device activations associated with the license key
    $domains = $wpdb->get_results($wpdb->prepare(
        "SELECT id, 'domain' AS type, registered_domain AS origin FROM " . SLM_TBL_LIC_DOMAIN . " WHERE lic_key = %s",
        $license_key
    ));
    
    $devices = $wpdb->get_results($wpdb->prepare(
        "SELECT id, 'device' AS type, registered_devices AS origin FROM " . SLM_TBL_LIC_DEVICES . " WHERE lic_key = %s",
        $license_key
    ));
    
    // Merge domains and devices into a single array
    $activations = array_merge($domains, $devices);
    
    // Display the "Activations" section header
    echo '<h2>' . esc_html__('Activations', 'slm-plus') . '</h2>';
    
    // Check if there are any activations
    if (empty($activations)) {
        echo '<p>' . esc_html__('No activations found.', 'slm-plus') . '</p>';
    } else {
        // Display the table header if activations exist
        echo '<table class="shop_table shop_table_responsive my_account_orders">';
        echo '<thead><tr><th>' . esc_html__('ID', 'slm-plus') . '</th><th>' . esc_html__('Type', 'slm-plus') . '</th><th>' . esc_html__('Origin', 'slm-plus') . '</th><th>' . esc_html__('Action', 'slm-plus') . '</th></tr></thead>';
        echo '<tbody>';

        // Loop through each activation and display in the table
        foreach ($activations as $activation) {
            echo '<tr>';
            echo '<td>' . esc_html($activation->id) . '</td>';
            echo '<td>' . esc_html(ucfirst($activation->type)) . '</td>';
            echo '<td>' . esc_html($activation->origin) . '</td>';
            echo '<td><form method="post" action="">';
            echo '<input type="hidden" name="activation_id" value="' . esc_attr($activation->id) . '">';
            echo '<input type="hidden" name="activation_type" value="' . esc_attr($activation->type) . '">';
            echo '<button type="submit" name="delete_activation" class="button">' . esc_html__('Delete', 'slm-plus') . '</button>';
            echo '</form></td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';

    }

    // Handle the deletion request
    if (isset($_POST['delete_activation'])) {
        $activation_id = intval($_POST['activation_id']);
        $activation_type = sanitize_text_field($_POST['activation_type']);

        // Determine the table based on the activation type
        $table = ($activation_type === 'domain') ? SLM_TBL_LIC_DOMAIN : SLM_TBL_LIC_DEVICES;

        // Delete the activation record from the relevant table
        $deleted = $wpdb->delete($table, ['id' => $activation_id], ['%d']);

        // Display a confirmation or error message
        if ($deleted) {
            echo '<p class="slm-notice">' . esc_html__('Activation successfully deleted. Reload Page.', 'slm-plus') . '</p>';
        } else {
            echo '<p class="slm-notice">' . esc_html__('Failed to delete activation. Please try again.', 'slm-plus') . '</p>';
        }

    }
}
