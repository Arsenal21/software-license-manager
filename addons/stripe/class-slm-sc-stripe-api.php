<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_SC_Stripe_API {

	const ENDPOINT           = 'https://api.stripe.com/v1/';
	const STRIPE_API_VERSION = '2020-08-27';

	private static $secret_key = '';

	public static function set_secret_key($secret_key){
            self::$secret_key = $secret_key;
	}

	public static function get_secret_key(){
            if(!self::$secret_key){
                $options = slm_stripe_checkout_get_option();
                $secret_key = $options['stripe_secret_key'];
                if(SLM_STRIPE_CHECKOUT_TESTMODE){
                    $secret_key = $options['stripe_test_secret_key'];
                }
                self::set_secret_key($secret_key);
            }
            return self::$secret_key;
	}

	public static function get_user_agent(){
            $app_info = array(
                    'name'    => 'SLM Stripe Checkout',
                    'version' => SLM_STRIPE_CHECKOUT_VERSION,
                    'url'     => 'https://wordpress.org/plugins/wp-stripe-checkout/',
            );

            return array(
                    'lang'         => 'php',
                    'lang_version' => phpversion(),
                    'publisher'    => 'naa986',
                    'uname'        => php_uname(),
                    'application'  => $app_info,
            );
	}

	public static function get_headers() {
            $user_agent = self::get_user_agent();
            $app_info   = $user_agent['application'];

            return apply_filters(
                'wp_sc_stripe_request_headers',
                array(
                        'Authorization'              => 'Basic ' . base64_encode(self::get_secret_key().':'),
                        'Stripe-Version'             => self::STRIPE_API_VERSION,
                        'User-Agent'                 => $app_info['name'].'/'.$app_info['version'].' ('.$app_info['url'].')',
                        'X-Stripe-Client-User-Agent' => json_encode($user_agent),
                )
            );
	}

	public static function request($request, $api = 'charges', $method = 'POST') {

            slm_stripe_checkout_debug_log("{$api} request: ", true);
            slm_stripe_checkout_debug_log_array($request, true);

            $headers = self::get_headers();

            $response = wp_safe_remote_post(
                self::ENDPOINT . $api,
                array(
                    'method'  => $method,
                    'headers' => $headers,
                    'body'    => $request,
                    'timeout' => 70,
                )
            );

            if(is_wp_error($response) || empty($response['body'])){
                slm_stripe_checkout_debug_log('Error Response: ', false);
                slm_stripe_checkout_debug_log_array($response, false);
                wp_die(__('There was a problem connecting to the payment gateway.', 'wp-stripe-checkout').print_r($response, true));
            }

            $parsed_response = json_decode($response['body']);
            slm_stripe_checkout_debug_log_array($parsed_response, true);
            // Handle response
            if (!empty($parsed_response->error)) {
                $error_msg = (!empty($parsed_response->error->code)) ? $parsed_response->error->code : 'stripe_error: ' . $parsed_response->error->message;
                slm_stripe_checkout_debug_log($error_msg, false);
                wp_die($error_msg);
            }
            else {
                return $parsed_response;
            }
	}

	public static function retrieve($api) {

            slm_stripe_checkout_debug_log("Retrieve {$api}", true);

            $response = wp_safe_remote_get(
                self::ENDPOINT . $api,
                array(
                    'method'  => 'GET',
                    'headers' => self::get_headers(),
                    'timeout' => 70,
                )
            );

            if(is_wp_error($response) || empty($response['body'])){
                slm_stripe_checkout_debug_log('Error Response: ', false);
                slm_stripe_checkout_debug_log_array($response, false);
                wp_die(__('There was a problem connecting to the payment gateway.', 'wp-stripe-checkout').print_r($response, true));
            }

            $parsed_response = json_decode($response['body']);
            slm_stripe_checkout_debug_log_array($parsed_response, true);
            // Handle response
            if (!empty($parsed_response->error)) {
                $error_msg = (!empty($parsed_response->error->code)) ? $parsed_response->error->code : 'stripe_error: ' . $parsed_response->error->message;
                slm_stripe_checkout_debug_log($error_msg, false);
                wp_die($error_msg);
            }
            else {
                return $parsed_response;
            }
	}
}
