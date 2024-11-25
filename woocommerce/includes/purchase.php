<?php

/**
 * SLM Plus WooCommerce Integration
 * @package   SLM Plus
 * @author    Michel Velis
 * @license   GPL-2.0+
 * @since     4.5.5
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

add_action('woocommerce_order_details_after_order_table', 'slm_display_licenses_in_order_details', 10, 1);
add_action('woocommerce_order_status_completed', 'wc_slm_process_order_completion', 10);


function wc_slm_process_order_completion($order_id) {
    global $wpdb;

    if (empty($order_id) || !is_numeric($order_id)) {
        return SLM_Helper_Class::write_log("Invalid order ID provided: $order_id");
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return SLM_Helper_Class::write_log("Order not found for ID: $order_id");
    }

    // Check if license creation/renewal has already been processed
    $license_processed = get_post_meta($order_id, '_slm_license_processed', true);
    if (!empty($license_processed)) {
        SLM_Helper_Class::write_log("License creation/renewal already processed for Order ID: {$order_id}");
        return;
    }

    // Handle renewal or new license creation
    $renew_license_key = get_post_meta($order_id, '_renew_license_key', true);

    if (!empty($renew_license_key)) {
        // Log and renew license
        SLM_Helper_Class::write_log("Processing renewal for License Key: {$renew_license_key}");
        wc_slm_renew_license($order);
    } else {
        // Log and create a new license
        SLM_Helper_Class::write_log("Creating a new license for Order ID: {$order_id}");
        wc_slm_create_new_license($order);
    }

    // Mark the process as completed to prevent duplication
    update_post_meta($order_id, '_slm_license_processed', true);
}


function wc_slm_renew_license($order) {
    global $wpdb;

    // Retrieve the renew_license_key from order meta
    $renew_license_key = get_post_meta($order->get_id(), '_renew_license_key', true);

    if (empty($renew_license_key)) {
        return SLM_Helper_Class::write_log("No renew_license_key found for Order ID: " . $order->get_id());
    }

    // Define the license key table
    $lic_key_table = $wpdb->prefix . 'lic_key_tbl';

    // Fetch the license data
    $license_data = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $lic_key_table WHERE license_key = %s LIMIT 1",
            $renew_license_key
        ),
        ARRAY_A
    );

    if (!$license_data) {
        return SLM_Helper_Class::write_log("License not found for renewal. Order ID: " . $order->get_id());
    }

    if ($license_data['lic_status'] === 'blocked') {
        return SLM_Helper_Class::write_log("Blocked license cannot be renewed. License Key: {$renew_license_key}");
    }

    // Calculate the new expiration date
    $current_expiry_date = $license_data['date_expiry'];
    $new_expiration_date = date(
        'Y-m-d',
        strtotime($current_expiry_date . ' +' . $license_data['slm_billing_length'] . ' ' . $license_data['slm_billing_interval'])
    );

    // Loop through the order items to get the product ID
    $product_id = null;
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id(); // Get the product ID
        SLM_Helper_Class::write_log("Processing renewal for Product ID: {$product_id}, Order ID: " . $order->get_id());
        break; // Only one product ID is needed for a single license renewal
    }

    // Update the license data
    $updated = $wpdb->update(
        $lic_key_table,
        [
            'date_expiry' => $new_expiration_date,
            'lic_status'  => 'active',
            'date_renewed' => current_time('mysql'),
            'wc_order_id' => $order->get_id(),
            'txn_id' => $order->get_id(),
            'item_reference' => $product_id,
            'purchase_id_' => $order->get_id(),
        ],
        ['license_key' => $renew_license_key],
        ['%s', '%s', '%s', '%d', '%d', '%s', '%d'], // Added placeholders for product ID
        ['%s']
    );

    // Debugging: Check if update was successful
    if ($updated === false) {
        SLM_Helper_Class::write_log("Failed to renew license. License Key: {$renew_license_key}, Order ID: " . $order->get_id());
    } else {
        SLM_Helper_Class::write_log("License renewed successfully. License Key: {$renew_license_key}, Product ID: {$product_id}, Order ID: " . $order->get_id() . ", New Expiration Date: $new_expiration_date.");

        // Add the order to the associated orders
        $associated_updated = SLM_Utility::slm_add_associated_order($renew_license_key, $order->get_id());
        
        if ($associated_updated) {
            SLM_Helper_Class::write_log("Order ID: {$order->get_id()} successfully added to associated orders for License Key: {$renew_license_key}");
        } else {
            SLM_Helper_Class::write_log("Failed to add Order ID: {$order->get_id()} to associated orders for License Key: {$renew_license_key}");
        }
    }
}

function slm_display_licenses_in_order_details($order) {
    global $wpdb;

    // Fetch the WooCommerce order ID
    $order_id = $order->get_id();

    // Fetch license keys for this order from the license table
    $lic_key_table = $wpdb->prefix . 'lic_key_tbl';
    $licenses = $wpdb->get_results(
        $wpdb->prepare("SELECT license_key, lic_status FROM $lic_key_table WHERE wc_order_id = %d", $order_id),
        ARRAY_A
    );

    // If no licenses exist, return
    if (empty($licenses)) {
        return;
    }

    // Display licenses
    echo '<h2>' . esc_html__('License Information', 'slm-plus') . '</h2>';
    echo '<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
            <thead>
                <tr>
                    <th>' . esc_html__('License Key', 'slm-plus') . '</th>
                    <th>' . esc_html__('Status', 'slm-plus') . '</th>
                    <th>' . esc_html__('Actions', 'slm-plus') . '</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($licenses as $license) {
        $license_key = esc_html($license['license_key']);
        $status = esc_html(ucfirst($license['lic_status']));
        $license_url = esc_url(add_query_arg('license_key', $license_key, wc_get_page_permalink('myaccount') . 'my-licenses'));

        echo "<tr>
                <td>$license_key</td>
                <td>$status</td>
                <td><a href='$license_url'>" . esc_html__('View License', 'slm-plus') . "</a></td>
              </tr>";
    }

    echo '</tbody></table>';
}

function wc_slm_create_new_license($order) {
    global $wpdb;

    // Validate the order object
    if (!$order instanceof WC_Order) {
        SLM_Helper_Class::write_log("Invalid order object passed.");
        return;
    }

    // Check if a license has already been created for this order
    $license_created = get_post_meta($order->get_id(), '_slm_license_created', true);
    if (!empty($license_created)) {
        SLM_Helper_Class::write_log("License already created for Order ID: {$order->get_id()}");
        return;
    }

    $items = $order->get_items();
    $customer_id = $order->get_user_id();
    $billing_email = sanitize_email($order->get_billing_email());

    // Loop through the order items
    foreach ($items as $item_key => $values) {
        $product_id = $values->get_product_id();
        $product = $values->get_product();

        // Skip if product is not a license product
        if (!$product->is_type('slm_license')) {
            continue;
        }

        // Retrieve product custom fields
        $custom_fields = [
            'max_allowed_domains' => intval(get_post_meta($product_id, '_domain_licenses', true)),
            'max_allowed_devices' => intval(get_post_meta($product_id, '_devices_licenses', true)),
            'slm_billing_interval' => sanitize_text_field(get_post_meta($product_id, '_license_renewal_period_term', true)),
            'slm_billing_length' => intval(get_post_meta($product_id, '_license_renewal_period_length', true)),
            'current_ver' => sanitize_text_field(get_post_meta($product_id, '_license_current_version', true)),
            'until' => sanitize_text_field(get_post_meta($product_id, '_license_until_version', true)),
            'lic_type' => sanitize_text_field(get_post_meta($product_id, '_license_type', true)),
            'item_reference' => sanitize_text_field(get_post_meta($product_id, '_license_item_reference', true)),
        ];

        // Calculate expiration date
        $expiration_date = ($custom_fields['slm_billing_interval'] === 'onetime')
            ? date('Y-m-d', strtotime('+200 years'))
            : date('Y-m-d', strtotime('+' . $custom_fields['slm_billing_length'] . ' ' . $custom_fields['slm_billing_interval']));

        // Generate a new license key
        $new_license_key = slm_get_license(get_option('slm_license_prefix', 'SLM'));

        // Insert the license into the database
        $result = $wpdb->insert(
            SLM_TBL_LICENSE_KEYS, // Ensure constant is defined for table name
            [
                'license_key' => $new_license_key,
                'max_allowed_domains' => $custom_fields['max_allowed_domains'],
                'max_allowed_devices' => $custom_fields['max_allowed_devices'],
                'slm_billing_interval' => $custom_fields['slm_billing_interval'],
                'slm_billing_length' => $custom_fields['slm_billing_length'],
                'current_ver' => $custom_fields['current_ver'],
                'until' => $custom_fields['until'],
                'lic_type' => $custom_fields['lic_type'],
                'item_reference' => $product_id,
                'wc_order_id' => $order->get_id(),
                'product_ref' => $product_id,
                'email' => $billing_email,
                'first_name' => sanitize_text_field($order->get_billing_first_name()),
                'last_name' => sanitize_text_field($order->get_billing_last_name()),
                'date_created' => current_time('mysql'),
                'date_expiry' => $expiration_date,
                'lic_status' => 'pending',
                'purchase_id_' => $order->get_id(),
                'txn_id' => $order->get_id(),
                'subscr_id' => $customer_id,
            ]
        );

        if ($result === false) {
            SLM_Helper_Class::write_log("Failed to create license for Product ID: {$product_id} in Order ID: {$order->get_id()}. Error: {$wpdb->last_error}");
            continue;
        }

        // Associate license with WooCommerce order
        update_post_meta($order->get_id(), '_slm_license_key', $new_license_key);
        update_post_meta($order->get_id(), 'License Key', $new_license_key);

        SLM_Helper_Class::write_log("New license key created for Product ID: {$product_id} in Order ID: {$order->get_id()}");
    }

    // Mark the license as created
    update_post_meta($order->get_id(), '_slm_license_created', true);
    SLM_Helper_Class::write_log("License creation process completed for Order ID: {$order->get_id()}");
}
