<?php

/**
 * https://businessbloomer.com/woocommerce-easily-get-order-info-total-items-etc-from-order-object/
 * @package   Software License Manager
 * @author    Michel Velis
 * @license   GPL-2.0+
 * @link      http://epikly.com
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

global $post, $woocommerce, $product;

$slm_options = get_option('slm_plugin_options');
$affect_downloads = $slm_options['slm_woo_affect_downloads'] == 1 ? true : false;
//add_action('woocommerce_checkout_update_order_meta', 'slm_add_lic_key_meta_update');
add_action('woocommerce_admin_order_data_after_billing_address', 'slm_add_lic_key_meta_display', 10, 1);
add_action('woocommerce_order_status_completed', 'slm_order_completed', 81);
if ($affect_downloads == true) {
    add_action('woocommerce_order_status_completed', 'wc_slm_access_expiration', 82);
}
add_action('woocommerce_order_details_after_order_table', 'slm_order_details', 10, 1);
// add_action('woocommerce_thankyou', 'slm_show_msg', 80);
add_action('woocommerce_order_status_completed', 'wc_slm_on_complete_purchase', 10);
add_filter('woocommerce_hidden_order_itemmeta', 'slm_hide_order_meta', 10, 1);
add_action('woocommerce_after_order_itemmeta', 'slm_display_nice_item_meta', 10, 3);

/**
 * Disable display of some metadata
 * @param array $hide_meta - list of meta data to hide
 * @return array modified list of meta data to hide
 * @since 4.5.5
 */
function slm_hide_order_meta($hide_meta)
{
    $hide_meta[] = '_slm_lic_key';
    $hide_meta[] = '_slm_lic_type';
    return $hide_meta;
}

/**
 * Display order meta data in Order items table - in the nice way
 * @param int $item_id
 * @param object $item
 * @param object $product
 *
 * @since 4.5.5
 * do_action( 'woocommerce_after_order_itemmeta', $item_id, $item, $product );
 */
function slm_display_nice_item_meta($item_id, $item, $product)
{
    ?>
    <div class="view">
        <?php if ($meta_data = wc_get_order_item_meta($item_id, '_slm_lic_key', false)) : ?>
            <table cellspacing="0" class="display_meta">
                <?php
                $admin_link = get_admin_url() . 'admin.php?page=slm_manage_license&edit_record=';
                foreach ($meta_data as $meta) :
                    $lic_key = $meta;
                    $lic_id = wc_slm_get_license_id($lic_key);
                    if (!empty($lic_id)) {
                        $cur_link = '<a href="' . $admin_link . $lic_id . '" target="_blank">' . $lic_key . '</a>';
                    } else {
                        $cur_link = $lic_key . ' - Licence not exists anymore';
                    }
                    ?>
                    <tr>
                        <th><?php echo 'Licence key: '; ?></th>
                        <td><?php echo $cur_link; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

function wc_slm_on_complete_purchase($order_id)
{
    //SLM_Helper_Class::write_log('loading wc_slm_on_complete_purchase');
    if (SLM_SITE_HOME_URL != '' && WOO_SLM_API_SECRET != '') {
        wc_slm_create_license_keys($order_id);
    }
}

function wc_slm_create_license_keys($order_id)
{

    // SLM_Helper_Class::write_log('loading wc_slm_create_license_keys');

    $order = wc_get_order($order_id);
    $purchase_id_ = $order->get_id();

    // SLM_Helper_Class::write_log('purchase_id_ -- '.$purchase_id_ );
    // SLM_Helper_Class::write_log('purchase_id_ -- '.$user_id  );

    global $user_id;

    $user_id = $order->get_user_id();
    $user_info = get_userdata($user_id);
    $get_user_meta = get_user_meta($user_id);
    $payment_meta['user_info']['first_name'] = $get_user_meta['billing_first_name'][0];
    $payment_meta['user_info']['last_name'] = $get_user_meta['billing_last_name'][0];
    $payment_meta['user_info']['email'] = $get_user_meta['billing_email'][0];
    $payment_meta['user_info']['company'] = $get_user_meta['billing_company'][0];

    // SLM_Helper_Class::write_log('user_id -- '.$user_id  );

    // Collect license keys
    $licenses = array();
    $items = $order->get_items();


    foreach ($items as $item => $values) {
        $download_id = $product_id = $values['product_id'];
        $product = $values->get_product();
        if ($product->is_type('slm_license')) {
            $download_quantity = absint($values['qty']);
            //Get all existing licence keys of the product
            $order_item_lic_key = $values->get_meta('_slm_lic_key', false);
            $lic_to_add = $download_quantity - count($order_item_lic_key);
            //Create keys only if there are not keys created already
            for ($i = 1; $i <= $lic_to_add; $i++) {
                /**
                 * Calculate Expire date
                 * @since 1.0.3
                 */
                $expiration = '';

                $renewal_period = (int)wc_slm_get_licensing_renewal_period($product_id);
                $renewal_term = wc_slm_get_licensing_renewal_period_term($product_id);

                $slm_billing_length = $renewal_period;
                $slm_billing_interval = $renewal_term;

                if ($renewal_period == 'onetime') {
                    $expiration = '0000-00-00';
                }
                // elseif ($renewal_period == 30) {
                // 	$renewal_period = date('Y-m-d', strtotime('+' . 31 . ' days'));
                // }
                else {
                    $expiration = date('Y-m-d', strtotime('+' . $renewal_period . ' ' . $renewal_term));
                }
                // SLM_Helper_Class::write_log('renewal_period -- '.$renewal_period  );
                // SLM_Helper_Class::write_log('exp -- ' . $expiration);
                // SLM_Helper_Class::write_log('term -- ' . $renewal_term);

                // Sites allowed get license meta from variation
                $sites_allowed = wc_slm_get_sites_allowed($product_id);

                if (!$sites_allowed) {
                    $sites_allowed_error = __('License could not be created: Invalid sites allowed number.', 'softwarelicensemanager');
                    $int = wc_insert_payment_note($purchase_id_, $sites_allowed_error);
                    break;
                }

                // Get the custumer ID
                // $user_id = $order->get_user_id();
                $order_data = $order->get_data(); // The Order data

                ## Access Order Items data properties (in an array of values) ##
                $item_data = $values->get_data();
                $product_name = $item_data['name'];
                $product_id = $item_data['product_id'];
                $_license_current_version = get_post_meta($product_id, '_license_current_version', true);
                $_license_until_version = get_post_meta($product_id, '_license_until_version', true);
                $amount_of_licenses_devices = wc_slm_get_devices_allowed($product_id);
                $current_version = (int)get_post_meta($product_id, '_license_current_version', true);
                $license_type = get_post_meta($product_id, '_license_type', true);
                $lic_item_ref = get_post_meta($product_id, '_license_item_reference', true);

                // Transaction id
                $transaction_id = wc_get_payment_transaction_id($product_id);

                // Build item name
                $item_name = $product->get_title();

                // Build parameters
                $api_params = array();
                $api_params['slm_action'] = 'slm_create_new';
                $api_params['secret_key'] = KEY_API;
                $api_params['first_name'] = (isset($payment_meta['user_info']['first_name'])) ? $payment_meta['user_info']['first_name'] : '';
                $api_params['last_name'] = (isset($payment_meta['user_info']['last_name'])) ? $payment_meta['user_info']['last_name'] : '';
                $api_params['email'] = (isset($payment_meta['user_info']['email'])) ? $payment_meta['user_info']['email'] : '';
                $api_params['company_name'] = $payment_meta['user_info']['company'];
                $api_params['purchase_id_'] = $purchase_id_;
                $api_params['product_ref'] = $product_id; // TODO: get product id
                $api_params['txn_id'] = $purchase_id_;
                $api_params['max_allowed_domains'] = $sites_allowed;
                $api_params['max_allowed_devices'] = $amount_of_licenses_devices;
                $api_params['date_created'] = date('Y-m-d');
                $api_params['date_expiry'] = $expiration;
                $api_params['slm_billing_length'] = $slm_billing_length;
                $api_params['slm_billing_interval'] = $slm_billing_interval;
                $api_params['until'] = $_license_until_version;
                $api_params['current_ver'] = $_license_current_version;
                $api_params['subscr_id'] = $order->get_customer_id();
                $api_params['lic_type'] = $license_type;
                $api_params['item_reference'] = $lic_item_ref;

                //access_expires
                //SLM_Helper_Class::write_log('license_type -- ' . $license_type );
                // Send query to the license manager server
                $url = SLM_SITE_HOME_URL . '?' . http_build_query($api_params);
                $url = str_replace(array('http://', 'https://'), '', $url);
                $url = 'http://' . $url;
                $response = wp_remote_get($url, array('timeout' => 20, 'sslverify' => false));
                $license_key = wc_slm_get_license_key($response);

                // Collect license keys
                if ($license_key) {
                    $licenses[] = array(
                        'item' => $item_name,
                        'key' => $license_key,
                        'expires' => $expiration,
                        'type' => $license_type,
                        'item_ref' => $lic_item_ref,
                        'slm_billing_length' => $slm_billing_length,
                        'slm_billing_interval' => $slm_billing_interval,
                        'status' => 'pending',
                        'version' => $_license_current_version,
                        'until' => $_license_until_version
                    );
                    $item_id = $values->get_id();
                    wc_add_order_item_meta($item_id, '_slm_lic_key', $license_key);
                    wc_add_order_item_meta($item_id, '_slm_lic_type', $license_type);
                }
            }
        }
    }

    if (count($licenses) > 0) {
        // Payment note
        wc_slm_payment_note($order_id, $licenses);

        // Assign licenses

        //What does this do? The meta is not used in the plugin anywhere
        //wc_slm_assign_licenses($order_id, $licenses);
    }
}

function wc_slm_get_license_key($response)
{
    // Check for error in the response
    if (is_wp_error($response)) {
        return false;
    }
    // Get License data
    $json = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', utf8_encode(wp_remote_retrieve_body($response)));
    $license_data = json_decode($json);

    if (!isset($license_data->key)) {
        return false;
    }
    // Prepare note text
    return $license_data->key;
}

function wc_slm_get_license_id($license)
{
    global $wpdb;
    $license_id = $wpdb->get_row("SELECT ID, license_key FROM " . $wpdb->prefix . "lic_key_tbl" . " WHERE license_key = '" . $license . "' ORDER BY id DESC LIMIT 0,1");
    return $license_id->ID;
}

function wc_slm_payment_note($order_id, $licenses)
{
    if ($licenses && count($licenses) != 0) {
        $message = __('License Key(s) generated', 'softwarelicensemanager');

        foreach ($licenses as $license) {
            $license_key = $license['key'];
            $message .= '<br />' . $license['item'] . ': <a href="' . get_admin_url() . 'admin.php?page=slm_manage_license&edit_record=' . wc_slm_get_license_id($license_key) . '">' . $license_key . '</a>';

            //These data are irrelevant - they work only when the order is completed and just for one licence key

            /* add_post_meta($order_id, 'slm_wc_license_order_key', 	$license_key);
            add_post_meta($order_id, 'slm_wc_license_expires', 		$license[ 'expires']);
            add_post_meta($order_id, 'slm_wc_license_type', 		$license[ 'type']);
            add_post_meta($order_id, 'slm_wc_license_item_ref',		$license[ 'item_ref']);
            add_post_meta($order_id, 'slm_wc_license_status', 		$license['status']);
            add_post_meta($order_id, 'slm_wc_license_version', 		$license[ 'version']);
            add_post_meta($order_id, 'slm_wc_until_version', 		$license['until']); */

            //SLM_Helper_Class::write_log($license_key);
        }
    } else {
        $message = __('License Key(s) could not be created.', 'softwarelicensemanager');
    }

    // Save note
    $int = wc_insert_payment_note($order_id, $message);
}


function wc_slm_access_expiration($order_id, $lic_expiry = '')
{
    global $wpdb;

    $order = wc_get_order($order_id);
    $items = $order->get_items();
    foreach ($items as $item_key => $item_details) {
        $product_id = $item_details['product_id'];
        $product = wc_get_product($product_id);
        if ($product->is_type('slm_license')) {
            //Get any existing licence key
            $order_item_lic_key = $item_details->get_meta('_slm_lic_key', true);
            if (!empty($order_item_lic_key)) {
                $licence = get_licence_by_key($order_item_lic_key);
                if (!empty($licence)) {
                    $lic_expiry = $licence['date_expiry'];
                    if ($lic_expiry == '0000-00-00') {
                        $lic_expiry = 'NULL';
                    } else {
                        $lic_expiry = "'" . $lic_expiry . "'";
                    }
                    $query = "UPDATE " . $wpdb->prefix . "woocommerce_downloadable_product_permissions SET access_expires = " . $lic_expiry . " WHERE order_id = " . $order_id . " AND product_id = " . $product_id . ";";
                    $wpdb->query($query);
                }
            }
        }
    }
    //SLM_Helper_Class::write_log('log:'  . $query );
}

/**
 * Get licence info from given it's key
 * @param array $licence_key - licence key for which to retrieve licence
 * @return array all the licence fields
 * @since 4.5.5
 */
function get_licence_by_key($licence_key)
{
    global $wpdb;

    if (empty($licence_key)) {
        return false;
    } else {
        $licence_key = esc_attr($licence_key);
    }
    $lic_keys_table = SLM_TBL_LICENSE_KEYS;
    $sql_prep = $wpdb->prepare("SELECT * FROM $lic_keys_table WHERE license_key = %s ORDER BY id DESC LIMIT 0,1", $licence_key);
    $record = $wpdb->get_row($sql_prep, ARRAY_A, 0);
    return $record;
}

function wc_slm_assign_licenses($order_id, $licenses)
{
    if (count($licenses) != 0) {
        add_post_meta($order_id, '_wc_slm_payment_licenses', $licenses);
    }
}


function wc_slm_get_sites_allowed($product_id)
{
    $wc_slm_sites_allowed = absint(get_post_meta($product_id, '_domain_licenses', true));
    if (empty($wc_slm_sites_allowed)) {
        return false;
    }
    return $wc_slm_sites_allowed;
}

function wc_slm_get_lic_type($product_id)
{
    $_license_type = absint(get_post_meta($product_id, '_license_type', true));
    if (empty($_license_type)) {
        return false;
    }
    return $_license_type;
}

function wc_slm_get_devices_allowed($product_id)
{
    $_devices_licenses = absint(get_post_meta($product_id, '_devices_licenses', true));
    if (empty($_devices_licenses)) {
        return false;
    }
    return $_devices_licenses;
}

function wc_slm_get_licenses_qty($product_id)
{
    $amount_of_licenses = absint(get_post_meta($product_id, '_amount_of_licenses', true));
    if (empty($amount_of_licenses)) {
        return false;
    }
    return $amount_of_licenses;
}

function wc_slm_get_licensing_renewal_period($product_id)
{
    $_license_renewal_period = absint(get_post_meta($product_id, '_license_renewal_period', true));
    if (empty($_license_renewal_period)) {
        return 0;
    }
    return $_license_renewal_period;
}

//_license_renewal_period_term

function wc_slm_get_licensing_renewal_period_term($product_id)
{
    $term = get_post_meta($product_id, '_license_renewal_period_term', true);
    return $term;
}

function wc_slm_is_licensing_enabled($download_id)
{
    $licensing_enabled = absint(get_post_meta($download_id, '_wc_slm_licensing_enabled', true));
    // Set defaults
    if ($licensing_enabled) {
        return true;
    } else {
        return false;
    }
}

function wc_insert_payment_note($order_id, $msg)
{
    $order = new WC_Order($order_id);
    $order->add_order_note($msg);
}

function wc_get_payment_transaction_id($order_id)
{
    return get_post_meta($order_id, '_transaction_id', true);
}

function slm_order_completed($order_id)
{

    global $user_id, $wpdb;
    $get_user_info = '';
    $order = wc_get_order($order_id);
    $purchase_id_ = $order->get_id();
    $order_data = $order->get_data(); // The Order data
    $order_billing_email = $order_data['billing']['email'];

    // if wp billing is empty
    if ($order_billing_email == '') {
        $get_user_info = get_userdata(get_current_user_id());
        $order_billing_email = $get_user_info->user_email;
    }

    $billing_address = $order_billing_email;

    // The text for the note
    $note = __("Order confirmation email sent to: <a href='mailto:" . $billing_address . "'>" . $billing_address . "</a>");
    // Add the note
    $order->add_order_note($note);
    // Save the data
    $order->save();
    //SLM_Helper_Class::write_log($to_email . 'License details'. $message . $headers );
}

function slm_show_msg($order_id)
{
    $order_id = new WC_Order($order_id);
    $purchase_id_ = $order_id->get_id();
    $order = wc_get_order($order_id);
    $items = $order->get_items();

    foreach ($items as $item) {
        $product_name = $item->get_name();
        $product_id = $item->get_product_id();
        $product_variation_id = $item->get_variation_id();
        $amount_of_licenses = wc_slm_get_sites_allowed($product_id);

        // is a licensed product
        //var_dump(get_post_meta($product_id));

        if ($amount_of_licenses) {
            echo '<div class="woocommerce-order-details"> <h2 class="woocommerce-order-details__title">' . __('My subscriptions', 'softwarelicensemanager') . '</h2> <table class="woocommerce-table woocommerce-table--order-details shop_table order_details"> <thead> <tr> <th class="woocommerce-table__product-name product-name"' . __('My Account', 'softwarelicensemanager') . '</th> </tr> </thead> <tbody> <tr class="woocommerce-table__line-item order_item"> <td class="woocommerce-table__product-name product-name" > ' . __('You can see and manage your licenses inside your account', 'softwarelicensemanager') . ' <a href="/my-account/my-licenses/">' . __('Manage Licenses', 'softwarelicensemanager') . '</a></td> </tr> </tbody> </table> </div>';
        }
    }
}

/**
 * Update the order meta with field value
 */

function slm_add_lic_key_meta_update($order_id)
{
    if (!empty($_POST['slm_wc_license_order_key'])) {
        update_post_meta($order_id, 'slm_wc_license_order_key', sanitize_text_field($_POST['slm_wc_license_order_key']));
    }
    if (!empty($_POST['slm_wc_license_expires'])) {
        update_post_meta($order_id, 'slm_wc_license_expires', sanitize_text_field($_POST['slm_wc_license_expires']));
    }
    if (!empty($_POST['slm_wc_license_type'])) {
        update_post_meta($order_id, 'slm_wc_license_type', sanitize_text_field($_POST['slm_wc_license_type']));
    }

    if (!empty($_POST['slm_wc_license_status'])) {
        update_post_meta($order_id, 'slm_wc_license_status', sanitize_text_field($_POST['slm_wc_license_status']));
    }

    if (!empty($_POST['slm_wc_license_item_ref'])) {
        update_post_meta($order_id, 'slm_wc_license_item_ref', sanitize_text_field($_POST['slm_wc_license_item_ref']));
    }

    if (!empty($_POST['slm_wc_license_version'])) {
        update_post_meta($order_id, 'slm_wc_license_version', sanitize_text_field($_POST['slm_wc_license_version']));
    }
}

/**
 * Display field value on the order edit page
 */

function slm_add_lic_key_meta_display($order)
{
    if (!empty(get_post_meta($order->get_id(), 'slm_wc_license_order_key', true))) {
        echo '<p><strong>' . __('License key', 'softwarelicensemanager') . ':</strong> <br/>' . get_post_meta($order->get_id(), 'slm_wc_license_order_key', true) . '</p>';
        echo '<p><strong>' . __('License expiration', 'softwarelicensemanager') . ':</strong> <br/>' . get_post_meta($order->get_id(), 'slm_wc_license_expires', true) . '</p>';
        echo '<p><strong>' . __('License type', 'softwarelicensemanager') . ':</strong> <br/>' . get_post_meta($order->get_id(), 'slm_wc_license_type', true) . '</p>';
        echo '<p><strong>' . __('License item reference', 'softwarelicensemanager') . ':</strong> <br/>' . get_post_meta($order->get_id(), 'slm_wc_license_item_ref', true) . '</p>';
        echo '<p><strong>' . __('License status', 'softwarelicensemanager') . ':</strong> <br/>' . get_post_meta($order->get_id(), 'slm_wc_license_status', true) . '</p>';
        echo '<p><strong>' . __('License current version', 'softwarelicensemanager') . ':</strong> <br/>' . get_post_meta($order->get_id(), 'slm_wc_license_version', true) . '</p>';
        echo '<p><strong>' . __('Supported until version', 'softwarelicensemanager') . ':</strong> <br/>' . get_post_meta($order->get_id(), 'slm_wc_until_version', true) . '</p>';
    }
}

/**
 * Display values on the order details page
 */

function slm_order_details($order)
{
    global $wpdb;

    $items = $order->get_items();
    $licences = array();
    foreach ($items as $item_key => $item_details) {
        $product = $item_details->get_product();
        if ($product->is_type('slm_license')) {
            if ($lic_keys = wc_get_order_item_meta($item_details->get_id(), '_slm_lic_key', false)) {
                $lic_types = wc_get_order_item_meta($item_details->get_id(), '_slm_lic_type', false);
                $licences = array_map(function ($keys, $types) {
                    return array(
                        'lic_key' => $keys,
                        'lic_type' => $types
                    );
                }, $lic_keys, $lic_types);
            }
        }
    }
    if ($licences) {
        echo '
			<h2 class="woocommerce-order-details__title">' . __('License details', 'softwarelicensemanager') . '</h2>
			<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
				<thead>
					<tr>
						<th class="woocommerce-table__product-name product-name">' . __('License key', 'softwarelicensemanager') . '</th>
						<th class="woocommerce-table__product-table product-total">' . __('Type', 'softwarelicensemanager') . '</th>
					</tr>
				</thead>
				<tbody>
		';
        foreach ($licences as $lic_row) {
            echo '
					<tr class="woocommerce-table__line-item order_item">
						<td class="woocommerce-table__product-name product-name">
							' . $lic_row['lic_key'] . ' - <a href="' . get_permalink(wc_get_page_id('myaccount')) . '/my-licenses"> ' . __('view my licenses', 'softwarelicensemanager') . '</a>
						</td>
						<td class="woocommerce-table__product-total product-total">
							' . $lic_row['lic_type'] . '
						</td>
					</tr>
			';
        }
        echo '
				</tbody>
			</table>
		';
    }
}

/**
 * @snippet       Add Content to the Customer Processing Order Email - WooCommerce
 * https://businessbloomer.com/woocommerce-add-extra-content-order-email/
 */
add_action('woocommerce_email_before_order_table', 'slm_add_license_to_order_confirmation', 20, 4);

function slm_add_license_to_order_confirmation($order, $sent_to_admin, $plain_text, $email)
{
    if ($email->id == 'customer_completed_order') {
        $items = $order->get_items();
        $licences = array();
        foreach ($items as $item_key => $item_details) {
            $product = $item_details->get_product();
            if ($product->is_type('slm_license')) {
                $meta_data = wc_get_order_item_meta($item_details->get_id(), '_slm_lic_key', false);
                foreach ($meta_data as $meta_row) {
                    $licences[] = array(
                        'product' => $product->get_name(),
                        'lic_key' => $meta_row,
                    );
                }
            }
        }
        if ($licences) {
            echo '
				<table class="td" cellspacing="0" cellpadding="6" border="1" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; width: 100%; font-family:"Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;">
					<thead>
						<tr>
							<th class="td" colspan="2" scope="col" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left;">
								' . __('License keys', 'softwarelicensemanager') . '
							</th>
						</tr>
					</thead>
					<tbody>
			';
            foreach ($licences as $lic_row) {
                echo '
						<tr>
							<td class="td" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left;">
								' . $lic_row['product'] . '
							</td>
							<td class="td" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left;">
								' . $lic_row['lic_key'] . '
							</td>
						</tr>
				';
            }
            echo '
					</tbody>
				</table>
				<br><br>
			';
        }
    }
}
