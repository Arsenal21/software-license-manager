<?php
/**
 * Runs on Uninstall of Software License Manager
 *
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

add_action('woocommerce_order_status_completed', 'wc_slm_on_complete_purchase', 10);
function wc_slm_on_complete_purchase($order_id) {

	if (WOO_SLM_API_URL != '' && WOO_SLM_API_SECRET != '') {
		wc_slm_create_license_keys($order_id);
	}
}

/**
 * Create license key
 *
 * @since 1.0.0
 * @return void
 */
function wc_slm_create_license_keys($order_id) {

	$_order 	=  new WC_Order($order_id);
	$order_id 	=  new WC_Order( $order_id );

	global $purchase_id;
	$purchase_id_ 	= $order_id->id;
	$user_id 		= $_order->get_user_id();
	$user_info 		= get_userdata($user_id);
	$get_user_meta 	= get_user_meta($user_id);
	$payment_meta['user_info']['first_name'] = $get_user_meta['billing_first_name'][0];
	$payment_meta['user_info']['last_name']  = $get_user_meta['billing_last_name'][0];
	$payment_meta['user_info']['email'] 	 = $get_user_meta['billing_email'][0];
	$payment_meta['user_info']['company'] 	 = $get_user_meta['billing_company'][0];

	// Collect license keys
	$licenses = array();
	$items = $_order->get_items();


	foreach ($items as $item => $values) {
		$download_id 	= $product_id = $values['product_id'];
		$product 		= new WC_Product($product_id);
		$variation_id 	= new WC_Product_Variation($product_id);

			//if ($product->is_downloadable() && $product->has_file()) {

				$download_quantity = absint($values['qty']);
				for ($i = 1; $i <= $download_quantity; $i++) {
					/**
					 * Calculate Expire date
					 * @since 1.0.3
					 */
					$renewal_period = (int) wc_slm_get_licensing_renewal_period($product_id);

					if ($renewal_period == 0) {
						$renewal_period = '0000-00-00';
					}
					else {
						$renewal_period = date('Y-m-d', strtotime('+' . $renewal_period . ' years'));
					}
					// Sites allowed get license meta from variation
					$sites_allowed 		= wc_slm_get_sites_allowed($product_id);
					$amount_of_licenses = wc_slm_get_licenses_qty($product_id);

					if (!$sites_allowed) {
						$sites_allowed_error = __('License could not be created: Invalid sites allowed number.', 'wc-slm');
						$int = wc_insert_payment_note($order_id, $sites_allowed_error);
						break;
					}

					// Get an instance of the WC_Order object (same as before)
					$order = wc_get_order( $order_id );
					// Get the order ID
					$order_id = $order->get_id();
					// Get the custumer ID
					$order_id = $order->get_user_id();
					$order_data = $order->get_data(); // The Order data

					// Iterating through each WC_Order_Item objects
					foreach( $order-> get_items() as $item_key => $item_values ){

					    ## Using WC_Order_Item methods ##
					    $item_id 			= $item_values->get_id();
					    $item_name 			= $item_values->get_name();
					    $item_type 			= $item_values->get_type();

					    ## Access Order Items data properties (in an array of values) ##
					    $item_data 			= $item_values->get_data();
					    $product_name 		= $item_data['name'];
					    $product_id 		= $item_data['product_id'];
					    $variation_id 		= $item_data['variation_id'];
					    $quantity 			= $item_data['quantity'];
					    $tax_class 			= $item_data['tax_class'];
					    $line_subtotal 		= $item_data['subtotal'];
					    $line_subtotal_tax 	= $item_data['subtotal_tax'];
					    $line_total 		= $item_data['total'];
					    $line_total_tax 	= $item_data['total_tax'];
					    $post_object 		= get_post($variation_id);
					    $amount_of_licenses = get_post_meta( $post_object->ID, 'amount_of_licenses', true);
					    $amount_of_licenses_devices = get_post_meta( $post_object->ID, 'amount_of_licenses_devices', true);
					}

					// Transaction id
					$transaction_id = wc_get_payment_transaction_id($product_id);

					// Build item name
					$item_name = $product->get_title();

					// Build parameters
					$api_params = array();
					$api_params['slm_action'] 			= 'slm_create_new';
					$api_params['secret_key'] 			= KEY_API;
					$api_params['first_name'] 			= (isset($payment_meta['user_info']['first_name'])) ? $payment_meta['user_info']['first_name'] : '';
					$api_params['last_name'] 			= (isset($payment_meta['user_info']['last_name'])) ? $payment_meta['user_info']['last_name'] : '';
					$api_params['email'] 				= (isset($payment_meta['user_info']['email'])) ? $payment_meta['user_info']['email'] : '';
					$api_params['company_name'] 		= $payment_meta['user_info']['company'];
					$api_params['purchase_id_'] 		= $purchase_id_;
					$api_params['product_ref'] 			= $product_id; // TODO: get product id
					$api_params['txn_id'] 				= $purchase_id_;
					$api_params['max_allowed_domains'] 	= $amount_of_licenses;
					$api_params['max_allowed_devices'] 	= $amount_of_licenses_devices;
					$api_params['date_created'] 		= date('Y-m-d');
					$api_params['date_expiry'] 			= $renewal_period;

					// Send query to the license manager server
					$url 			= 'http://' . WOO_SLM_API_URL . '?' . http_build_query($api_params);
					$url 			= str_replace(array('http://', 'https://'), '', $url);
					$url 			= 'http://' . $url;
					$response 		= wp_remote_get($url, array('timeout' => 20, 'sslverify' => false));
					$license_key 	= wc_slm_get_license_key($response);

					// Collect license keys
					if ($license_key) {
						$licenses[] = array(
							'item' 		=> $item_name,
							'key' 		=> $license_key,
							'expires' 	=> $renewal_period,
						);
					}
				}
	}

	//wc_slm_print_pretty($payment_meta);

	// Payment note

	wc_slm_payment_note($order_id, $licenses);

	// Assign licenses
	wc_slm_assign_licenses($order_id, $licenses);
}

/**
 * Get generated license key
 *
 * @since 1.0.0
 * @return mixed
 */
function wc_slm_get_license_key($response) {
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

/**
 * Leave payment not for license creation
 *
 * @since 1.0.0
 * @return void
 */
function wc_slm_payment_note($order_id, $licenses) {

	if ($licenses && count($licenses) != 0) {
		$message = __('License Key(s) generated', 'wc-slm');
		foreach ($licenses as $license) {
			$message .= '<br />' . $license['item'] . ': ' . $license['key'];
		}
	}
	else {
		$message = __('License Key(s) could not be created.', 'wc-slm');
	}

	// Save note
	$int = wc_insert_payment_note($order_id, $message);
}

/**
 * Assign generated license keys to payments
*/
function wc_slm_assign_licenses($order_id, $licenses) {

	if (count($licenses) != 0) {
		update_post_meta($order_id, '_wc_slm_payment_licenses', $licenses);
	}
}

/**
 * Get sites allowed from download.
 *
 * @since  1.0.0
 * @return mixed
 */
function wc_slm_get_sites_allowed($product_id) {

	$wc_slm_sites_allowed = absint(get_post_meta($product_id, '_wc_slm_sites_allowed', true));

	if (empty($wc_slm_sites_allowed)) {
		return false;
	}

	return $wc_slm_sites_allowed;
}

function wc_slm_get_licenses_qty($product_id) {

	$amount_of_licenses = absint(get_post_meta($product_id, '_amount_of_licenses', true));

	if (empty($amount_of_licenses)) {
		return false;
	}

	return $amount_of_licenses;
}

/**
 * Get sites allowed from download.
 *
 * @since  1.0.0
 * @return mixed
 */
function wc_slm_get_licensing_renewal_period($product_id) {

	$wc_slm_sites_allowed = absint(get_post_meta($product_id, '_wc_slm_licensing_renewal_period', true));

	if (empty($wc_slm_sites_allowed)) {
		return 0;
	}

	return $wc_slm_sites_allowed;
}

/**
 * Check if licensing for a certain download is enabled
 *
 * @since  1.0.0
 * @return bool
 */
function wc_slm_is_licensing_enabled($download_id) {

	$licensing_enabled = absint(get_post_meta($download_id, '_wc_slm_licensing_enabled', true));

	// Set defaults
	if ($licensing_enabled) {
		return true;
	} else {
		return false;
	}
}

function wc_insert_payment_note($order_id, $msg) {
	$order = new WC_Order($order_id);
	$order->add_order_note($msg);
}

function wc_get_payment_transaction_id($order_id) {
	return get_post_meta($order_id, '_transaction_id', true);
}

