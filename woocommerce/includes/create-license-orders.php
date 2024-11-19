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

function slm_generate_licenses_callback()
{
    check_ajax_referer('slm_generate_licenses_nonce', 'security');

    global $wpdb;
    $response_data      = ['html' => ''];
    $success_count      = 0;
    $failure_count      = 0;
    $skipped_orders     = [];
    $skipped_reasons    = [];
    $generated_licenses = [];

    // Retrieve Product ID and Subscription Type from the request
    $default_product_id = isset($_POST['slm_product_id']) ? absint($_POST['slm_product_id']) : null;
    $slm_lic_type = isset($_POST['subscription_type']) && in_array($_POST['subscription_type'], ['subscription', 'lifetime'])
        ? sanitize_text_field($_POST['subscription_type'])
        : 'subscription';

    //SLM_Helper_Class::write_log("Starting license generation with Product ID: {$default_product_id} and License Type: {$slm_lic_type}.");

    // Check if Product ID is missing; if so, log an error, add an error response, and exit.
    if (empty($default_product_id)) {
        //SLM_Helper_Class::write_log('Error: Product ID is missing in the request.');

        // Track failure and skip reason for the response
        $failure_count++;
        $skipped_orders[] = 0;
        $skipped_reasons[0] = __('Product ID is missing in the request.', 'slm-plus');

        // Return early with a JSON error response for AJAX display
        $response_data['html'] .= '<li><strong>Error:</strong> Product ID is missing in the request. Please provide a valid product ID.</li>';
        wp_send_json_error($response_data);
        return;
    }

    // Check if the Product ID corresponds to an existing WooCommerce product
    $product = wc_get_product($default_product_id);
    if (!$product) {
        //SLM_Helper_Class::write_log("Error: Product with ID $default_product_id does not exist in WooCommerce.");

        // Track failure and skip reason for the response
        $failure_count++;
        $skipped_orders[] = 0;
        $skipped_reasons[0] = __('The provided Product ID does not correspond to a valid WooCommerce product. Please check the ID and try again.', 'slm-plus');

        // Return early with a JSON error response for AJAX display
        $response_data['html'] .= '<li><strong>Error:</strong> The provided Product ID does not correspond to a valid WooCommerce product. Please check the ID and try again.</li>';
        wp_send_json_error($response_data);
        return;
    }

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

        // Check license type and set expiration date accordingly
        if ($slm_lic_type === 'lifetime') {
            $date_expiry = date('Y-m-d', strtotime("+120 years", strtotime($date_created)));
        } else {
            // Calculate expiration based on billing interval and length
            switch ($slm_billing_interval) {
                case 'years':
                    $date_expiry = date('Y-m-d', strtotime("+$slm_billing_length years", strtotime($date_created)));
                    break;
                case 'months':
                    $date_expiry = date('Y-m-d', strtotime("+$slm_billing_length months", strtotime($date_created)));
                    break;
                case 'days':
                    $date_expiry = date('Y-m-d', strtotime("+$slm_billing_length days", strtotime($date_created)));
                    break;
                default:
                    $date_expiry = $date_created;
            }
        }

        //SLM_Helper_Class::write_log("Interval: {$slm_billing_interval} - Length: {$slm_billing_length}");

        $order_items = $order->get_items();

        if (count($order_items) === 0) {
            // Handle empty orders by adding a default product item

            $product = wc_get_product($default_product_id);
            if (!$product) {
                //SLM_Helper_Class::write_log("Error: Product with ID {$default_product_id} does not exist.");
                continue;
            }

            $item = new WC_Order_Item_Product();
            $item->set_product_id($default_product_id);
            $item->set_name($product->get_name());
            $item->set_quantity(1);
            $item->set_total($product->get_price());

            if ($item->meta_exists('_slm_lic_key')) {
                $skipped_orders[] = $order_id;
                $skipped_reasons[$order_id] = 'Already has a license';
                continue;
            }

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
                'until'                 => SLM_API_Utility::get_slm_option('license_until_version'),
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

                    $item->add_meta_data('License Key', $license_key, true);
                    $item->add_meta_data('License Type', $slm_lic_type, true);

                    $order->add_order_note(
                        // Translators: %s is the generated license key
                        sprintf(__('License Key generated: %s', 'slm-plus'), $license_key)
                    );
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
            foreach ($order_items as $item) {
                $product_id = $item->get_product_id();
                $product_name = $item->get_name();

                if ($item->meta_exists('_slm_lic_key')) {
                    $skipped_orders[] = $order_id;
                    $skipped_reasons[$order_id] = 'Already has a license';
                    continue;
                }

                $license_data['product_ref'] = $product_name;
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

                        $item->add_meta_data('License Key', $license_key, true);
                        $item->add_meta_data('License Type', $slm_lic_type, true);

                        $order->add_order_note(
                            // Translators: %s is the generated license key
                            sprintf(__('License Key generated: %s', 'slm-plus'), $license_key)
                        );
                    } else {
                        $failure_count++;
                    }
                } else {
                    $failure_count++;
                }
                $item->save();
            }
            $order->save();
        }
    }

    if (!empty($skipped_orders)) {
        $response_data['html'] .= '<li><strong>' . sprintf(
            // Translators: %1$d is the number of orders skipped
            __('%1$d orders were skipped:', 'slm-plus'),
            count($skipped_orders)
        ) . '</strong><ul>';
        foreach ($skipped_orders as $order_id) {
            if ($order_id !== 0) {
                $order_link = admin_url('post.php?post=' . $order_id . '&action=edit');
                // Translators: %1$d is the order ID, %2$s is the reason why the order was skipped, %3$s is the order view link
                $response_data['html'] .= '<li>' . sprintf(__('Order ID %1$d was skipped due to: %2$s. <a href="%3$s" target="_blank">View Order</a>', 'slm-plus'), $order_id, esc_html($skipped_reasons[$order_id]), esc_url($order_link)) . '</li>';
            } else {
                $response_data['html'] .= '<li>' . esc_html($skipped_reasons[0]) . '</li>';
            }
        }
        $response_data['html'] .= '</ul></li>';
    }

    if ($success_count > 0) {
        $response_data['html'] .= '<li><strong>' . sprintf(
            // Translators: %1$d is the number of successfully generated licenses
            __('%1$d licenses generated successfully:', 'slm-plus'),
            $success_count
        ) . '</strong><ul>';
        foreach ($generated_licenses as $license_data) {
            $order_link = admin_url('post.php?post=' . $license_data['order_id'] . '&action=edit');
            // Translators: %1$s is the license key, %2$d is the order ID, %3$s is the order view link
            $response_data['html'] .= '<li>' . sprintf(__('License Key: %1$s for Order ID %2$d - <a href="%3$s" target="_blank">View Order</a>', 'slm-plus'), esc_html($license_data['license_key']), $license_data['order_id'], esc_url($order_link)) . '</li>';
        }
        $response_data['html'] .= '</ul></li>';
    }

    if ($failure_count > 0) {
        $response_data['html'] .= '<li><strong>' . sprintf(
            // Translators: %1$d is the number of licenses that failed to generate
            __('%1$d licenses failed to generate.', 'slm-plus'),
            $failure_count
        ) . '</strong></li>';
    }

    wp_send_json_success($response_data);
}
