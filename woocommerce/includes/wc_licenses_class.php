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


// Step 1: Register the endpoint and add it to WooCommerce’s query vars
add_filter('woocommerce_get_query_vars', function($query_vars) {
    $query_vars['my-licenses'] = 'my-licenses';
    return $query_vars;
});

// Flush rewrite rules to ensure the endpoint is recognized on activation
function slm_flush_rewrite_rules() {
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'slm_flush_rewrite_rules');

// Step 2: Add the My Licenses link to WooCommerce account menu
function slm_add_my_licenses_endpoint($items) {
    if (SLM_API_Utility::get_slm_option('slm_woo')) {
        // Add "My Licenses" item just before "Log out"
        $logout = $items['customer-logout']; // Store the "Log out" item
        unset($items['customer-logout']); // Remove "Log out" temporarily

        // Add "My Licenses" to the array
        $items['my-licenses'] = __('My Licenses', 'slmplus');

        // Re-add "Log out" to the end
        $items['customer-logout'] = $logout;
    }

    return $items;
}
add_filter('woocommerce_account_menu_items', 'slm_add_my_licenses_endpoint');

// Step 3: Display content based on endpoint value
add_action('woocommerce_account_my-licenses_endpoint', function($value) {

    SLM_Helper_Class::write_log('slm_add_my_licenses_endpoint loaded');

    if ($value === 'view') {
        // Use $_GET instead of get_query_var to directly retrieve the URL parameter
        $license_id = isset($_GET['slm_lic']) ? $_GET['slm_lic'] : false;
        SLM_Helper_Class::write_log('license_id var ' . $license_id);

        if ($license_id) {
            SLM_Helper_Class::write_log('license_id call2 ' . $license_id);
            slm_license_view($license_id);
        }
        else {
            echo '<p>' . __('Invalid license or access denied.', 'slmplus') . '</p>';
             SLM_Helper_Class::write_log('user id ' . get_current_user_id());
        }
    } else {
        // Display the licenses table if no specific value is passed
        slm_display_license_table();
    }
});

// Step 4: Display the main licenses table
function slm_display_license_table() {

    SLM_Helper_Class::write_log('slm_display_license_table loaded');

    $user_id = get_current_user_id();
    $user_email = wp_get_current_user()->user_email;

    global $wpdb;

    $licenses = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT license_key, purchase_id_, lic_status FROM " . SLM_TBL_LICENSE_KEYS . " 
        WHERE email = %s OR subscr_id = %d",
        $user_email, $user_id
    ));

    if ($licenses) {
        echo '<table class="shop_table shop_table_responsive my_account_orders">';
        echo '<thead><tr>';
        echo '<th>' . __('Order ID', 'slmplus') . '</th>';
        echo '<th>' . __('License Key', 'slmplus') . '</th>';
        echo '<th>' . __('Status', 'slmplus') . '</th>';
        echo '<th>' . __('Action', 'slmplus') . '</th>';
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
                echo '<td>' . __('Manual', 'slmplus') . '</td>';
            }
        
            echo '<td>' . esc_html($license_key) . '</td>';
        
            // Display the status with custom badge classes
            echo '<td><span class="slm-status-badge status-' . esc_attr(strtolower($status)) . '">' . esc_html($status) . '</span></td>';
        
            echo '<td><a href="' . esc_url($action_link) . '">' . __('View', 'slmplus') . '</a></td>';
            echo '</tr>';
        }
        
        

        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No licenses found.</p>';
    }
}


SLM_Helper_Class::write_log('file loaded');


// Step 5: Display individual license details
function slm_license_view($encoded_license_id) {
    global $wpdb;
    $user_email = wp_get_current_user()->user_email;
    $user_id = get_current_user_id();

    // Decode the license key and trim any whitespace
    $license_id = trim(base64_decode($encoded_license_id));

    // Log the decoded license key, user email, and user ID
    SLM_Helper_Class::write_log('Decoded License Key: ' . $license_id);
    SLM_Helper_Class::write_log('User Email: ' . $user_email);
    SLM_Helper_Class::write_log('User ID (subscr_id): ' . $user_id);

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
    SLM_Helper_Class::write_log('SQL Query: ' . $query);

    // Execute the query
    $license = $wpdb->get_row($query);

    // Check if license was found
    if (!$license) {
        echo '<p>' . __('Invalid license or access denied.', 'slmplus') . '</p>';
        SLM_Helper_Class::write_log('error');
        return;
    }

    // Back Button with dynamic URL generation
    $back_url = wc_get_account_endpoint_url('my-licenses');
    echo '<a href="' . esc_url($back_url) . '" class="button">' . __('Back to My Licenses', 'slmplus') . '</a>';


    // Define the fields and labels for dynamic generation
    $slm_license_fields = [
        'license_key' => __('License Key', 'slmplus'),
        'lic_status' => __('Status', 'slmplus'),
        'lic_type' => __('License Type', 'slmplus'),
        'purchase_id_' => __('Order ID', 'slmplus'),
        'date_created' => __('Date Created', 'slmplus'),
        'date_activated' => __('Date Activated', 'slmplus'),
        'date_renewed' => __('Date Renewed', 'slmplus'),
        'date_expiry' => __('Date Expiry', 'slmplus'),
        'product_ref' => __('Product Reference', 'slmplus'),
        'subscr_id' => __('Subscription ID', 'slmplus'),
        'max_allowed_domains' => __('Max Allowed Domains', 'slmplus'),
        'max_allowed_devices' => __('Max Allowed Devices', 'slmplus'),
        'company_name' => __('Company Name', 'slmplus'),
    ];

    // Display license details header
    echo '<h3 class="slm_view_lic">' . __('License Details', 'slmplus') . '</h3>';
    echo '<table class="shop_table shop_table_responsive my_account_orders">';
    echo '<tbody>';

    // Loop through each field and output its label and value dynamically
    foreach ($slm_license_fields as $field_key => $field_label) {
        $field_value = isset($license->$field_key) ? esc_html($license->$field_key) : ''; // Retrieve and escape the field value
        echo '<tr><th>' . $field_label . '</th><td>' . $field_value . '</td></tr>';
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
    echo '<h2>' . __('Activations', 'slmplus') . '</h2>';
    
    // Check if there are any activations
    if (empty($activations)) {
        echo '<p>' . __('No activations found.', 'slmplus') . '</p>';
    } else {
        // Display the table header if activations exist
        echo '<table class="shop_table shop_table_responsive my_account_orders">';
        echo '<thead><tr><th>' . __('ID', 'slmplus') . '</th><th>' . __('Type', 'slmplus') . '</th><th>' . __('Origin', 'slmplus') . '</th><th>' . __('Action', 'slmplus') . '</th></tr></thead>';
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
            echo '<button type="submit" name="delete_activation" class="button">' . __('Delete', 'slmplus') . '</button>';
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
        echo '<p>' . __('Activation successfully deleted. Reload Page.', 'slmplus') . '</p>';
    } else {
        echo '<p>' . __('Failed to delete activation. Please try again.', 'slmplus') . '</p>';
    }
}










}
