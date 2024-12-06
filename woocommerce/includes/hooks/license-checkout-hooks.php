<?php
/**
 * SLM Plus License Checkout Hooks
 * 
 * Handles WooCommerce hooks for processing license renewals and custom checkout.
 */

if (!defined('ABSPATH')) {
    exit;
}

function slm_debug_log($message) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        SLM_Helper_Class::write_log($message);
    }
}


/**
 * Save the renewal license key during checkout.
 */
/**
 * Save the renewal license key during checkout.
 */
add_action('woocommerce_checkout_update_order_meta', 'slm_save_renewal_key_to_order');
function slm_save_renewal_key_to_order($order_id) {
    $renew_license_key = isset($_POST['billing_license_renewal']) ? sanitize_text_field($_POST['billing_license_renewal']) : '';
    if (!empty($renew_license_key)) {
        update_post_meta($order_id, '_renew_license_key', $renew_license_key);
        slm_debug_log("Renewal License Key saved to Order ID {$order_id}: {$renew_license_key}");
    }
}

/**
 * Display the renewal key on the admin order page.
 */
add_action('woocommerce_admin_order_data_after_billing_address', 'slm_display_renewal_key_in_admin');
function slm_display_renewal_key_in_admin($order) {
    $renew_license_key = get_post_meta($order->get_id(), '_renew_license_key', true);
    if (!empty($renew_license_key)) {
        echo '<p><strong>' . esc_html__('Renewal License Key:', 'slm-plus') . '</strong> ' . esc_html($renew_license_key) . '</p>';
    }
}

/**
 * Add the renewal key to WooCommerce email notifications.
 */
add_action('woocommerce_email_order_meta', 'slm_add_renewal_key_to_email', 10, 3);
function slm_add_renewal_key_to_email($order, $sent_to_admin, $plain_text) {
    $renew_license_key = get_post_meta($order->get_id(), '_renew_license_key', true);

    if (!empty($renew_license_key)) {
        echo '<p><strong>' . esc_html__('Renewal License Key:', 'slm-plus') . '</strong> ' . esc_html($renew_license_key) . '</p>';
    }
}




/**
 * Remove the "Additional Information" section on the custom checkout page.
 */
add_action('template_redirect', 'slm_remove_additional_info_on_custom_checkout');
function slm_remove_additional_info_on_custom_checkout() {
    // Check if the current page is your custom checkout page
    if (is_page_template('license-checkout.php')) {
        add_filter('woocommerce_enable_order_notes_field', '__return_false');
    }
}

/**
 * Handle license renewal during order processing.
 */
add_action('woocommerce_order_status_completed', 'slm_process_license_renewal', 10, 1);
function slm_process_license_renewal($order_id) {
    $renew_license_key = get_post_meta($order_id, '_renew_license_key', true);

    if (!empty($renew_license_key)) {
        // Log the renewal process
        SLM_Helper_Class::write_log("Processing renewal for License Key {$renew_license_key} on Order ID {$order_id}");

        // Call the renewal function from purchase.php
        wc_slm_renew_license(wc_get_order($order_id));
    } else {
        // Log and fallback to new license creation
        SLM_Helper_Class::write_log("No renewal key found. Proceeding with new license creation for Order ID {$order_id}");
        wc_slm_create_new_license(wc_get_order($order_id));
    }
}


add_action('woocommerce_add_to_cart', 'slm_clear_old_renew_license_key', 5);
function slm_clear_old_renew_license_key() {
    if (WC()->session->get('renew_license_key')) {
        WC()->session->__unset('renew_license_key');
        slm_debug_log("Cleared old renewal license key from session.");
    }
}


add_action('woocommerce_before_cart_item_quantity_zero', 'slm_clear_renew_license_key_on_cart_change', 10);
function slm_clear_renew_license_key_on_cart_change($cart_item_key) {
    $cart = WC()->cart->get_cart();

    if (isset($cart[$cart_item_key]['renew_license_key'])) {
        unset(WC()->cart->cart_contents[$cart_item_key]['renew_license_key']);
        SLM_Helper_Class::write_log("License key removed from cart item: {$cart_item_key}");
    }
}

add_filter('woocommerce_cart_item_display', 'slm_reset_license_key_on_cart_change', 10, 3);
function slm_reset_license_key_on_cart_change($cart_item_html, $cart_item, $cart_item_key) {
    if (isset($cart_item['renew_license_key'])) {
        $current_license = $cart_item['renew_license_key'];

        // Check if license key is mismatched or invalid
        if (!slm_is_valid_license($current_license)) {
            WC()->cart->cart_contents[$cart_item_key]['renew_license_key'] = null;
            SLM_Helper_Class::write_log("Invalid or mismatched license key cleared from cart.");
        }
    }

    return $cart_item_html;
}

function slm_is_valid_license($license_key) {
    global $wpdb;
    $lic_key_table = $wpdb->prefix . 'lic_key_tbl';
    $license = $wpdb->get_var($wpdb->prepare("SELECT license_key FROM $lic_key_table WHERE license_key = %s", $license_key));
    return !empty($license);
}

/**
 * Restrict cart to one license product.
 */
add_filter('woocommerce_add_to_cart_validation', 'slm_restrict_cart_quantity', 10, 5);
function slm_restrict_cart_quantity($passed, $product_id, $quantity, $variation_id = '', $cart_item_data = []) {
    // Ensure WooCommerce is initialized
    if (!WC()->cart) {
        return $passed;
    }

    // Get the product being added
    $product = wc_get_product($product_id);

    // Check if the product is a license product
    if ($product && $product->is_type('slm_license')) {
        // Loop through existing cart items
        foreach (WC()->cart->get_cart() as $cart_item) {
            if ($cart_item['data']->is_type('slm_license')) {
                // Add notice only once
                if (!wc_has_notice(__('You can only add one license product (either new or renewal) to your cart at a time.', 'slm-plus'))) {
                    wc_add_notice(__('You can only add one license product (either new or renewal) to your cart at a time.', 'slm-plus'), 'error');
                }
                return false;
            }
        }
    }

    return $passed;
}

/**
 * Validate cart before checkout to ensure only one license product.
 */
add_action('woocommerce_check_cart_items', 'slm_validate_cart_before_checkout');
function slm_validate_cart_before_checkout() {
    if (!WC()->cart) {
        return;
    }

    $license_count = 0;

    // Count license products in the cart
    foreach (WC()->cart->get_cart() as $cart_item) {
        if ($cart_item['data']->is_type('slm_license')) {
            $license_count++;
        }
    }

    // If more than one license product is in the cart, display an error and block checkout
    if ($license_count > 1) {
        if (!wc_has_notice(__('You can only have one license product (new or renewal) in your cart.', 'slm-plus'))) {
            wc_add_notice(__('You can only have one license product (new or renewal) in your cart.', 'slm-plus'), 'error');
        }
    }
}

/**
 * Automatically remove extra license products if multiple are added to the cart.
 */
add_action('woocommerce_before_calculate_totals', 'slm_remove_extra_license_products', 10, 1);
function slm_remove_extra_license_products($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    $license_product_key = null;

    // Loop through the cart to find license products
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];

        if ($product && $product->is_type('slm_license')) {
            // Keep the first license product, remove the rest
            if ($license_product_key === null) {
                $license_product_key = $cart_item_key;
            } else {
                $cart->remove_cart_item($cart_item_key);
                wc_add_notice(__('Only one license product is allowed in the cart. Extra items have been removed.', 'slm-plus'), 'notice');
            }
        }
    }
}



add_filter('woocommerce_cart_item_quantity', 'slm_limit_cart_quantity', 10, 3);

function slm_limit_cart_quantity($product_quantity, $cart_item_key, $cart_item) {
    if ($cart_item['data']->is_type('slm_license')) {
        $product_quantity = sprintf(
            '<input type="number" name="cart[%s][qty]" value="1" readonly="readonly" class="input-text qty text" />',
            $cart_item_key
        );
    }

    return $product_quantity;
}



add_filter('woocommerce_cart_item_quantity', 'slm_disable_quantity_change', 10, 3);

function slm_disable_quantity_change($quantity, $cart_item_key, $cart_item) {
    if ($cart_item['data']->is_type('slm_license')) {
        // Display quantity as non-editable text
        return '<span>' . esc_html($cart_item['quantity']) . '</span>';
    }

    return $quantity;
}

/**
 * Handle license product restrictions in cart.
 */
add_action('woocommerce_check_cart_items', 'slm_remove_existing_license_product');
function slm_remove_existing_license_product() {
    $license_product_key = null;

    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        if ($product->is_type('slm_license')) {
            if ($license_product_key === null) {
                $license_product_key = $cart_item_key;
            } else {
                WC()->cart->remove_cart_item($cart_item_key);
                slm_debug_log("Removed additional license product from the cart.");
            }
        }
    }
}




add_filter('woocommerce_add_cart_item_data', 'slm_add_renew_license_key_to_cart', 10, 2);

function slm_add_renew_license_key_to_cart($cart_item_data, $product_id) {
    if (isset($_POST['renew_license_key']) && !empty($_POST['renew_license_key'])) {
        $renew_license_key = sanitize_text_field($_POST['renew_license_key']);
        $cart_item_data['renew_license_key'] = $renew_license_key;

        // Save to session for later use
        WC()->session->set('renew_license_key', $renew_license_key);
        slm_debug_log("Renewal License Key added to cart item data and session: {$renew_license_key}");
    }

    return $cart_item_data;
}



add_filter('woocommerce_get_cart_item_from_session', 'slm_get_renew_license_key_from_session', 10, 2);

function slm_get_renew_license_key_from_session($cart_item, $values) {
    if (isset($values['renew_license_key'])) {
        $cart_item['renew_license_key'] = $values['renew_license_key'];
        slm_debug_log("Renewal License Key retrieved from session for cart item: {$values['renew_license_key']}");
    }

    return $cart_item;
}


add_action('woocommerce_check_cart_items', 'slm_validate_license_cart');

function slm_validate_license_cart() {
    $license_count = 0;

    foreach (WC()->cart->get_cart() as $cart_item) {
        if ($cart_item['data']->is_type('slm_license')) {
            $license_count++;
        }
    }

    if ($license_count > 1) {
        wc_add_notice(__('You can only have one license (new or renewal) in your cart.', 'slm-plus'), 'error');
    }
}


/**
 * Redirect to custom cart page for license products.
 */
add_filter('woocommerce_add_to_cart_redirect', 'slm_redirect_to_custom_cart_page');
function slm_redirect_to_custom_cart_page($url) {
    if (isset($_POST['add-to-cart']) && !empty($_POST['add-to-cart'])) {
        $product_id = intval($_POST['add-to-cart']);
        $product = wc_get_product($product_id);

        if ($product && $product->is_type('slm_license')) {
            $custom_cart_url = home_url('/license-cart');
            if (isset($_POST['renew_license_key']) && !empty($_POST['renew_license_key'])) {
                $renew_license_key = sanitize_text_field($_POST['renew_license_key']);
                $custom_cart_url = add_query_arg('renew_license_key', $renew_license_key, $custom_cart_url);
            }
            return $custom_cart_url;
        }
    }
    return $url;
}


/**
 * Customize WooCommerce checkout fields.
 */
/**
 * Customize WooCommerce checkout fields.
 */
add_filter('woocommerce_checkout_fields', 'slm_customize_checkout_fields');
function slm_customize_checkout_fields($fields) {
    // Retrieve the renewal license key from the session
    $renew_license_key = WC()->session->get('renew_license_key');
    SLM_Helper_Class::write_log("Renew license key retrieved in customize_checkout_fields: {$renew_license_key}");

    // Add the renewal license field if the key is set
    if (!empty($renew_license_key)) {
        $fields['billing']['billing_license_renewal'] = array(
            'type'              => 'text',
            'label'             => esc_html__('This order includes a license renewal for:', 'slm-plus'),
            'placeholder'       => '',
            'class'             => array('form-row-wide'),
            'custom_attributes' => array('readonly' => 'readonly'),
            'priority'          => 29, // Position it above "Company Name"
        );

        // Force the value of the field to the session key
        add_filter('woocommerce_checkout_get_value', function ($value, $input) use ($renew_license_key) {
            if ($input === 'billing_license_renewal') {
                SLM_Helper_Class::write_log("Forcing value for billing_license_renewal: {$renew_license_key}");
                return $renew_license_key;
            }
            return $value;
        }, 10, 2);
    }

    return $fields;
}


// Set renew license key in session during redirect.
add_action('init', function () {
    if (isset($_GET['renew_license']) && isset($_GET['product_id'])) {
        // Ensure WooCommerce session exists
        if (class_exists('WooCommerce') && WC()->session) {
            $renew_license_key = sanitize_text_field($_GET['renew_license']);
            WC()->session->set('renew_license_key', $renew_license_key);
            SLM_Helper_Class::write_log("Renew license key set in session during redirect: {$renew_license_key}");
        } else {
            SLM_Helper_Class::write_log("WooCommerce session not initialized. Cannot set renew license key.");
        }
    }
});

// Clear the renew license key from session on specific conditions.
add_action('wp_loaded', function () {
    if (class_exists('WooCommerce') && WC()->session) {
        // Example: Clear session key after completing the process or on specific conditions
        if (isset($_GET['clear_renew_key'])) { // Example condition
            $renew_license_key = WC()->session->get('renew_license_key');
            WC()->session->__unset('renew_license_key');
            SLM_Helper_Class::write_log("Renew license key cleared from session: {$renew_license_key}");
        }
    } else {
        SLM_Helper_Class::write_log("WooCommerce session not available. Cannot clear renew license key.");
    }
});

/**
 * Display a notice on the checkout page for license renewal.
 */
add_action('woocommerce_before_checkout_form', function () {
    $renew_license_key = WC()->session->get('renew_license_key');
});




/**
 * Clear renewal license session data when the cart is empty.
 */
add_action('woocommerce_cart_updated', 'slm_clear_session_if_cart_empty');
function slm_clear_session_if_cart_empty() {
    // Ensure WooCommerce session is initialized
    if (class_exists('WooCommerce') && WC()->session) {
        // Check if the cart is empty
        if (WC()->cart->is_empty()) {
            // Clear the renew license key from the session
            if (WC()->session->get('renew_license_key')) {
                $renew_license_key = WC()->session->get('renew_license_key');
                WC()->session->__unset('renew_license_key');
                SLM_Helper_Class::write_log("Cart is empty. Cleared renew license key from session: {$renew_license_key}");
            }

            // Optionally, clear WooCommerce cookies
            WC()->session->destroy_session();
            SLM_Helper_Class::write_log("Cart is empty. WooCommerce session and cookies cleared.");
        }
    }
}

add_action('woocommerce_before_cart', function () {
    if (WC()->cart->is_empty()) {
        wc_add_notice(__('Your cart is empty. Session data has been cleared.', 'slm-plus'), 'notice');
    }
});

add_action('woocommerce_cart_is_empty', function () {
    if (WC()->session && WC()->session->get('renew_license_key')) {
        WC()->session->__unset('renew_license_key');
        SLM_Helper_Class::write_log("Cleared renew_license_key as cart is empty.");
    }
});
