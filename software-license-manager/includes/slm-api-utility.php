<?php

class SLM_API_Utility {
	/*
	 * The args array can contain the following:
	 * result (success or error)
	 * message (a message describing the outcome of the action
	 */

	static function output_api_response( $args ) {
		//Log to debug file (if enabled)
		global $slm_debug_logger;
		$slm_debug_logger->log_debug( 'API Response - Result: ' . $args['result'] . ' Message: ' . $args['message'] );

		$args = apply_filters( 'slm_ap_response_args', $args );//TODO - delete this (has a typo). Use the following filter instead.
		$args = apply_filters( 'slm_api_response_args', $args );

		//Send response
		header( 'Content-Type: application/json' );
		echo json_encode( $args );
		exit( 0 );
	}

	static function verify_secret_key() {
		$slm_options         = get_option( 'slm_plugin_options' );
		$right_secret_key    = $slm_options['lic_verification_secret'];
		$received_secret_key = sanitize_text_field( $_REQUEST['secret_key'] );
		if ( $received_secret_key != $right_secret_key ) {
			$args = ( array(
				'result'     => 'error',
				'message'    => 'Verification API secret key is invalid',
				'error_code' => SLM_Error_Codes::VERIFY_KEY_INVALID,
			) );
			self::output_api_response( $args );
		}
	}

	static function verify_secret_key_for_creation() {
		$slm_options         = get_option( 'slm_plugin_options' );
		$right_secret_key    = $slm_options['lic_creation_secret'];
		$received_secret_key = sanitize_text_field( $_REQUEST['secret_key'] );
		if ( $received_secret_key != $right_secret_key ) {
			$args = ( array(
				'result'     => 'error',
				'message'    => 'License Creation API secret key is invalid',
				'error_code' => SLM_Error_Codes::CREATE_KEY_INVALID,
			) );
			self::output_api_response( $args );
		}
	}

	static function insert_license_data_internal( $fields ) {
		/* The fields array should have values for the following keys
		  //$fields['license_key']
		  //$fields['lic_status']
		  //$fields['first_name']
		  //$fields['last_name']
		  //$fields['email']
		  //$fields['company_name']
		  //$fields['txn_id']
		  //$fields['max_allowed_domains']
		 */
		global $wpdb;
		$tbl_name = SLM_TBL_LICENSE_KEYS;
		$fields   = array_filter( $fields );//Remove any null values.
		$result   = $wpdb->insert( $tbl_name, $fields );
	}

}
