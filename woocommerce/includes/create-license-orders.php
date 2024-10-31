<?php

/**
 * SLM Plus WooCommerce Integration
 * @package   SLM Plus
 * @since     4.5.5
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

global $post, $woocommerce, $product;
$slm_options = get_option('slm_plugin_options'); // Retrieve plugin options

add_action('wp_ajax_slm_generate_licenses', 'slm_generate_licenses_callback');

function slm_generate_licenses_callback() {
    check_ajax_referer('slm_generate_licenses_nonce', 'security');

    global $wpdb;
    $response_data = ['html' => ''];
    $success_count = 0;
    $failure_count = 0;
    $skipped_orders = [];
    $generated_licenses = [];
    $skipped_reasons = [];

    // Retrieve Product ID and Subscription Type from the request
    $default_product_id = isset($_POST['slm_product_id']) ? absint($_POST['slm_product_id']) : 38; // Fallback to 38 if not provided
    $slm_lic_type = isset($_POST['subscription_type']) ? sanitize_text_field($_POST['subscription_type']) : 'subscription';

    SLM_Helper_Class::write_log("Starting license generation with Product ID: {$default_product_id} and License Type: {$slm_lic_type}.");

    // Query to get WooCommerce orders without a license key
    $orders_without_license = $wpdb->get_results("
        SELECT p.ID as order_id
        FROM {$wpdb->prefix}posts p
        LEFT JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = p.ID AND pm.meta_key = '_license_key'
        WHERE p.post_type = 'shop_order'
        AND (pm.meta_value IS NULL OR pm.meta_value = '')
        GROUP BY p.ID
    ");

    foreach ($orders_without_license as $order_data) {
        $order_id   = $order_data->order_id;
        $order      = wc_get_order($order_id);

        // Only proceed for orders that are completed or processing
        if (!in_array($order->get_status(), ['completed', 'processing'])) {
            $skipped_orders[] = $order_id;
            $skipped_reasons[$order_id] = 'Status not completed/processing';
            continue;
        }

        // Gather customer info and order details
        $first_name     = $order->get_billing_first_name();
        $last_name      = $order->get_billing_last_name();
        $email          = $order->get_billing_email();
        $purchase_id    = $order->get_id();
        $txn_id         = $order->get_transaction_id();
        $company_name   = $order->get_billing_company();
        $date_created   = $order->get_date_created()->date('Y-m-d');
        $user_id        = $order->get_user_id();

        // Billing length and interval for subscription
        $slm_billing_length = SLM_API_Utility::get_slm_option('slm_billing_length');
        $slm_billing_interval = SLM_API_Utility::get_slm_option('slm_billing_interval');
        $subscr_id = $user_id;

        // Calculate the expiry date based on billing interval and length
        $date_expiry = date('Y-m-d', strtotime("+$slm_billing_interval $slm_billing_length", strtotime($date_created)));
        $order_items = $order->get_items();

        if (count($order_items) === 0) {
            // Handle empty orders by adding a default product item

            $product = wc_get_product($default_product_id);
            if (!$product) {
                SLM_Helper_Class::write_log("Error: Product with ID {$default_product_id} does not exist.");
                continue;
            }

            $item = new WC_Order_Item_Product();
            $item->set_product_id($default_product_id);
            $item->set_name($product->get_name());
            $item->set_quantity(1);
            $item->set_total($product->get_price());

            // Check if a license key already exists for this item
            if ($item->meta_exists('_slm_lic_key')) {
                $skipped_orders[] = $order_id;
                $skipped_reasons[$order_id] = 'Already has a license';
                continue;
            }

            // License data for API call
            $license_data = [
                'slm_action'            => 'slm_create_new',
                'lic_status'            => 'pending',
                'lic_type'              => $slm_lic_type,
                'first_name'            => $first_name,
                'last_name'             => $last_name,
                'email'                 => $email,
                'purchase_id_'          => $purchase_id,
                'txn_id'                => $txn_id,
                'company_name'          => $company_name,
                'max_allowed_domains'   => SLM_DEFAULT_MAX_DOMAINS,
                'max_allowed_devices'   => SLM_DEFAULT_MAX_DEVICES,
                'date_created'          => $date_created,
                'date_expiry'           => $date_expiry,
                'product_ref'           => $product->get_name(),
                'current_ver'           => SLM_API_Utility::get_slm_option('license_current_version'),
                'subscr_id'             => $subscr_id,
                'item_reference'        => $order_id,
                'slm_billing_length'    => $slm_billing_length,
                'slm_billing_interval'  => $slm_billing_interval,
                'secret_key'            => KEY_API
            ];

            // Call the API to generate the license key
            $license_key = '';
            $response = wp_remote_post(SLM_API_URL, [
                'method'    => 'POST',
                'body'      => $license_data,
                'timeout'   => 45,
                'sslverify' => false,
            ]);

            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $api_response = json_decode($body, true);

                if ($api_response && isset($api_response['result']) && $api_response['result'] === 'success') {
                    $license_key = sanitize_text_field($api_response['key']);
                    $success_count++;

                    // Add license meta data and note to the order
                    $item->add_meta_data('_slm_lic_key', $license_key, true);
                    $item->add_meta_data('_slm_lic_type', $slm_lic_type, true);
                    $order->add_order_note(
                        sprintf(__('License Key generated: %s', 'slmplus'), $license_key)
                    );

                    // Collect for response message
                    $generated_licenses[] = [
                        'license_key' => $license_key,
                        'order_id' => $order_id,
                    ];
                } else {
                    $failure_count++;
                }
            } else {
                $failure_count++;
            }

            $order->add_item($item);
            $order->save();

        } else {
            // Process orders with items
            foreach ($order_items as $item) {
                $product_id = $item->get_product_id();
                $product_name = $item->get_name();

                // Skip if the item already has a license key
                if ($item->meta_exists('_slm_lic_key')) {
                    $skipped_orders[] = $order_id;
                    $skipped_reasons[$order_id] = 'Already has a license';
                    continue;
                }

                // License data for API call
                $license_data = [
                    'slm_action'            => 'slm_create_new',
                    'lic_status'            => 'pending',
                    'lic_type'              => $slm_lic_type,
                    'first_name'            => $first_name,
                    'last_name'             => $last_name,
                    'email'                 => $email,
                    'purchase_id_'          => $purchase_id,
                    'txn_id'                => $txn_id,
                    'company_name'          => $company_name,
                    'max_allowed_domains'   => SLM_DEFAULT_MAX_DOMAINS,
                    'max_allowed_devices'   => SLM_DEFAULT_MAX_DEVICES,
                    'date_created'          => $date_created,
                    'date_expiry'           => $date_expiry,
                    'product_ref'           => $product_name,
                    'current_ver'           => SLM_API_Utility::get_slm_option('license_current_version'),
                    'subscr_id'             => $subscr_id,
                    'item_reference'        => $order_id,
                    'slm_billing_length'    => $slm_billing_length,
                    'slm_billing_interval'  => $slm_billing_interval,
                    'secret_key'            => KEY_API
                ];

                $license_key = '';
                $response = wp_remote_post(SLM_API_URL, [
                    'method'    => 'POST',
                    'body'      => $license_data,
                    'timeout'   => 45,
                    'sslverify' => false,
                ]);

                if (!is_wp_error($response)) {
                    $body = wp_remote_retrieve_body($response);
                    $api_response = json_decode($body, true);

                    if ($api_response && isset($api_response['result']) && $api_response['result'] === 'success') {
                        $license_key = sanitize_text_field($api_response['key']);
                        $success_count++;
                        $generated_licenses[] = ['license_key' => $license_key, 'order_id' => $order_id];

                        $item->add_meta_data('_slm_lic_key', $license_key, true);
                        $item->add_meta_data('_slm_lic_type', $slm_lic_type, true);
                        $order->add_order_note(
                            sprintf(__('License Key generated: %s', 'slmplus'), $license_key)
                        );
                    } else {
                        $failure_count++;
                    }
                } else {
                    $failure_count++;
                }
                $item->save(); // Save item meta
            }
            $order->save();
        }
    }

    // Log grouped skipped orders
    if (!empty($skipped_orders)) {
        foreach ($skipped_orders as $order_id) {
            SLM_Helper_Class::write_log("Skipping Order ID {$order_id}: {$skipped_reasons[$order_id]}.");
        }
    }

    // Summarize counts in log
    SLM_Helper_Class::write_log("License generation completed. Success: {$success_count}, Failures: {$failure_count}, Skipped: " . count($skipped_orders));
    
    // Generate HTML response for display
    if (!empty($skipped_orders)) {
        $response_data['html'] .= '<li><strong>' . sprintf(__('%d orders were skipped:', 'slmplus'), count($skipped_orders)) . '</strong><ul>';
        foreach ($skipped_orders as $order_id) {
            $order_link = admin_url('post.php?post=' . $order_id . '&action=edit');
            $response_data['html'] .= '<li>' . sprintf(__('Order ID %d was skipped due to: %s. <a href="%s" target="_blank">View Order</a>', 'slmplus'), $order_id, esc_html($skipped_reasons[$order_id]), esc_url($order_link)) . '</li>';
        }
        $response_data['html'] .= '</ul></li>';
    }

    if ($success_count > 0) {
        $response_data['html'] .= '<li><strong>' . sprintf(__('%d licenses generated successfully:', 'slmplus'), $success_count) . '</strong><ul>';
        foreach ($generated_licenses as $license_data) {
            $order_link = admin_url('post.php?post=' . $license_data['order_id'] . '&action=edit');
            $response_data['html'] .= '<li>' . sprintf(__('License Key: %s for Order ID %d - <a href="%s" target="_blank">View Order</a>', 'slmplus'), esc_html($license_data['license_key']), $license_data['order_id'], esc_url($order_link)) . '</li>';
        }
        $response_data['html'] .= '</ul></li>';
    }

    if ($failure_count > 0) {
        $response_data['html'] .= '<li><strong>' . sprintf(__('%d licenses failed to generate.', 'slmplus'), $failure_count) . '</strong></li>';
    }

    // Return the JSON response
    wp_send_json_success($response_data);

}
