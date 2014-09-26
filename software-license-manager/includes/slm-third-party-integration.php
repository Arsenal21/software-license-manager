<?php

/* * ********************************* */
/* * * WP eStore Plugin Integration ** */
/* * ********************************* */
add_filter('eStore_notification_email_body_filter', 'slm_handle_estore_email_body_filter', 10, 3);

function slm_handle_estore_email_body_filter($body, $payment_data, $cart_items) {
    global $slm_debug_logger, $wpdb;
    $slm_debug_logger->log_debug("WP eStore integration - checking if a license key needs to be created for this transaction.");
    $products_table_name = $wpdb->prefix . "wp_eStore_tbl";
    $slm_data = "";

    foreach ($cart_items as $current_cart_item) {
        $prod_id = $current_cart_item['item_number'];
        $retrieved_product = $wpdb->get_row("SELECT * FROM $products_table_name WHERE id = '$prod_id'", OBJECT);
        $package_product = eStore_is_package_product($retrieved_product);
        if ($package_product) {
            $slm_debug_logger->log_debug('Checking license key generation for package/bundle product.');
            $product_ids = explode(',', $retrieved_product->product_download_url);
            foreach ($product_ids as $id) {
                $id = trim($id);
                $retrieved_product_for_specific_id = $wpdb->get_row("SELECT * FROM $products_table_name WHERE id = '$id'", OBJECT);
                $slm_data .= slm_estore_check_and_generate_key($retrieved_product_for_specific_id, $payment_data, $cart_items);
            }
        } else {
            $slm_debug_logger->log_debug('Checking license key generation for single item product.');
            $slm_data .= slm_estore_check_and_generate_key($retrieved_product, $payment_data, $cart_items);
        }
    }

    $body = str_replace("{slm_data}", $slm_data, $body);
    return $body;
}

function slm_estore_check_and_generate_key($retrieved_product, $payment_data, $cart_items) {
    global $slm_debug_logger;
    $license_data = '';

    if ($retrieved_product->create_license == 1) {
        $slm_debug_logger->log_debug('Need to create a license key for this product (' . $retrieved_product->id . ')');
        $slm_key = slm_estore_create_license($payment_data, $cart_items);
        $license_data = "\n" . __('Item Name: ', 'slm') . $retrieved_product->name . " - " . __('License Key: ', 'slm') . $slm_key;
        $slm_debug_logger->log_debug('Liense data: ' . $license_data);
        $license_data = apply_filters('slm_estore_item_license_data', $license_data);
    }
    return $license_data;
}

function slm_estore_create_license($payment_data, $cart_items) {
    global $slm_debug_logger;

    $options = get_option('slm_plugin_options');
    $lic_key_prefix = $options['lic_prefix'];

    $fields = array();
    $fields['license_key'] = uniqid($lic_key_prefix);
    $fields['lic_status'] = 'pending';
    $fields['first_name'] = $payment_data['first_name'];
    $fields['last_name'] = $payment_data['last_name'];
    $fields['email'] = $payment_data['payer_email'];
    $fields['company_name'] = $payment_data['company_name'];
    $fields['txn_id'] = $payment_data['txn_id'];
    $fields['date_created'] = date ("Y-m-d");//Today's date
    $fields['max_allowed_domains'] = $options['default_max_domains']; //TODO - later take from estore's product configuration

    $slm_debug_logger->log_debug('Inserting license data into the license manager DB table.');
    SLM_API_Utility::insert_license_data_internal($fields);

    return $fields['license_key'];
}

/************************************/
/*** End of WP eStore integration ***/
/************************************/