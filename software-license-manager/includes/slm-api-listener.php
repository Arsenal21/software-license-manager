<?php

/*
 * This class listens for API query and executes the API requests
 * Available API Actions
 * 1) slm_create_new
 * 2) slm_activate
 * 3) slm_deactivate
 * 4) slm_check
 */

class SLM_API_Listener {

	function __construct() {

		if ( isset( $_REQUEST['slm_action'] ) && isset( $_REQUEST['secret_key'] ) ) {

			//Log debug
			global $slm_debug_logger;
			$action_type = sanitize_text_field( $_REQUEST['slm_action'] );
			$ip_address  = SLM_Utility::get_ip_address();
			$slm_debug_logger->log_debug( 'API - Request received from IP: ' . $ip_address . ', Action type: ' . $action_type );

			//Trigger an action hook
			do_action( 'slm_api_listener_init' );

			//This is an API query for the license manager. Handle the query.
			$this->creation_api_listener();
			$this->activation_api_listener();
			$this->deactivation_api_listener();
			$this->check_api_listener();
		}
	}

	function creation_api_listener() {
		if ( isset( $_REQUEST['slm_action'] ) && trim( $_REQUEST['slm_action'] ) == 'slm_create_new' ) {
			//Handle the licene creation API query
			global $slm_debug_logger;

			$options        = get_option( 'slm_plugin_options' );
			$lic_key_prefix = $options['lic_prefix'];

			SLM_API_Utility::verify_secret_key_for_creation(); //Verify the secret key first.

			$slm_debug_logger->log_debug( 'API - license creation (slm_create_new) request received.' );

			//Action hook
			do_action( 'slm_api_listener_slm_create_new' );

			$fields = array();
			if ( isset( $_REQUEST['license_key'] ) && ! empty( $_REQUEST['license_key'] ) ) {
				$fields['license_key'] = SLM_Utility::sanitize_strip_trim_slm_text( $_REQUEST['license_key'] );//Use the key you pass via the request
			} else {
				$fields['license_key'] = uniqid( $lic_key_prefix );//Use random generated key
			}
			$fields['lic_status']   = isset( $_REQUEST['lic_status'] ) ? wp_unslash( SLM_Utility::sanitize_strip_trim_slm_text( $_REQUEST['lic_status'] ) ) : 'pending';
			$fields['first_name']   = wp_unslash( SLM_Utility::sanitize_strip_trim_slm_text( $_REQUEST['first_name'] ) );
			$fields['last_name']    = wp_unslash( SLM_Utility::sanitize_strip_trim_slm_text( $_REQUEST['last_name'] ) );
			$fields['email']        = sanitize_email( $_REQUEST['email'] );
			$fields['company_name'] = isset( $_REQUEST['company_name'] ) ? wp_unslash( SLM_Utility::sanitize_strip_trim_slm_text( $_REQUEST['company_name'] ) ) : '';
			$fields['txn_id']       = SLM_Utility::sanitize_strip_trim_slm_text( $_REQUEST['txn_id'] );
			if ( empty( $_REQUEST['max_allowed_domains'] ) ) {
				$fields['max_allowed_domains'] = $options['default_max_domains'];
			} else {
				$fields['max_allowed_domains'] = intval( $_REQUEST['max_allowed_domains'] );
			}
			$fields['date_created'] = isset( $_REQUEST['date_created'] ) ? sanitize_text_field( $_REQUEST['date_created'] ) : date( 'Y-m-d' );
			$fields['date_expiry']  = isset( $_REQUEST['date_expiry'] ) ? sanitize_text_field( $_REQUEST['date_expiry'] ) : '';
			$fields['product_ref']  = isset( $_REQUEST['product_ref'] ) ? wp_unslash( SLM_Utility::sanitize_strip_trim_slm_text( $_REQUEST['product_ref'] ) ) : '';
                        $fields['subscr_id'] = isset( $_REQUEST['subscr_id'] ) ? wp_unslash( SLM_Utility::sanitize_strip_trim_slm_text( $_REQUEST['subscr_id'] ) ) : '';
                        $fields['user_ref'] = isset( $_REQUEST['user_ref'] ) ? wp_unslash( SLM_Utility::sanitize_strip_trim_slm_text( $_REQUEST['user_ref'] ) ) : '';

			global $wpdb;
			$tbl_name = SLM_TBL_LICENSE_KEYS;
			$result   = $wpdb->insert( $tbl_name, $fields );
			if ( $result === false ) {
				//error inserting
				$args = ( array(
					'result'     => 'error',
					'message'    => 'License creation failed',
					'error_code' => SLM_Error_Codes::CREATE_FAILED,
				) );
				SLM_API_Utility::output_api_response( $args );
			} else {
				$args = ( array(
					'result'  => 'success',
					'message' => 'License successfully created',
					'key'     => $fields['license_key'],
				) );
				SLM_API_Utility::output_api_response( $args );
			}
		}
	}

	/*
	 * Query Parameters
	 * 1) slm_action = slm_create_new
	 * 2) secret_key
	 * 3) license_key
	 * 4) registered_domain (optional)
	 */

	function activation_api_listener() {
		if ( isset( $_REQUEST['slm_action'] ) && trim( $_REQUEST['slm_action'] ) == 'slm_activate' ) {
			//Handle the license activation API query
			global $slm_debug_logger;

			SLM_API_Utility::verify_secret_key(); //Verify the secret key first.

			$slm_debug_logger->log_debug( 'API - license activation (slm_activate) request received.' );

			//Action hook
			do_action( 'slm_api_listener_slm_activate' );

			$fields                      = array();
			$fields['lic_key']           = SLM_Utility::sanitize_strip_trim_slm_text( $_REQUEST['license_key'] );
			$fields['registered_domain'] = isset($_REQUEST['registered_domain']) ? trim( wp_unslash( sanitize_text_field( $_REQUEST['registered_domain'] ) ) ) : '';
			$fields['item_reference']    = isset($_REQUEST['item_reference']) ? trim( sanitize_text_field( $_REQUEST['item_reference'] ) ) : '';
			$slm_debug_logger->log_debug( 'License key: ' . $fields['lic_key'] . ' Domain: ' . $fields['registered_domain'] );

			global $wpdb;
			$tbl_name  = SLM_TBL_LICENSE_KEYS;
			$reg_table = SLM_TBL_LIC_DOMAIN;
			$key       = $fields['lic_key'];
			$sql_prep1 = $wpdb->prepare( "SELECT * FROM $tbl_name WHERE license_key = %s", $key );
			$retLic    = $wpdb->get_row( $sql_prep1, OBJECT );

			$sql_prep2   = $wpdb->prepare( "SELECT * FROM $reg_table WHERE lic_key = %s", $key );
			$reg_domains = $wpdb->get_results( $sql_prep2, OBJECT );
			if ( $retLic ) {
				if ( $retLic->lic_status == 'blocked' ) {
                                        //Trigger action hook
                                        do_action( 'slm_api_listener_slm_activate_key_blocked', $key );

					$args = ( array(
						'result'     => 'error',
						'message'    => 'Your License key is blocked',
						'error_code' => SLM_Error_Codes::LICENSE_BLOCKED,
					) );
					SLM_API_Utility::output_api_response( $args );
				} elseif ( $retLic->lic_status == 'expired' ) {
                                        //Trigger action hook
                                        do_action( 'slm_api_listener_slm_activate_key_expired', $key );

					$args = ( array(
						'result'     => 'error',
						'message'    => 'Your License key has expired',
						'error_code' => SLM_Error_Codes::LICENSE_EXPIRED,
					) );
					SLM_API_Utility::output_api_response( $args );
				}

				if ( count( $reg_domains ) < floor( $retLic->max_allowed_domains ) ) {
					foreach ( $reg_domains as $reg_domain ) {
						if ( isset( $_REQUEST['migrate_from'] ) && ( trim( $_REQUEST['migrate_from'] ) == $reg_domain->registered_domain ) ) {
							$wpdb->update( $reg_table, array( 'registered_domain' => $fields['registered_domain'] ), array( 'registered_domain' => trim( sanitize_text_field( $_REQUEST['migrate_from'] ) ) ) );
							$args = ( array(
								'result'  => 'success',
								'message' => 'Registered domain has been updated',
							) );
							SLM_API_Utility::output_api_response( $args );
						}
						if ( $fields['registered_domain'] == $reg_domain->registered_domain ) {
							$args = ( array(
								'result'     => 'error',
								'message'    => 'License key already in use on ' . $reg_domain->registered_domain,
								'error_code' => SLM_Error_Codes::LICENSE_IN_USE,
							) );
							SLM_API_Utility::output_api_response( $args );
						}
					}
					$fields['lic_key_id'] = $retLic->id;
					$wpdb->insert( $reg_table, $fields );

					$slm_debug_logger->log_debug( 'Updating license key status to active.' );
					$data    = array( 'lic_status' => 'active' );
					$where   = array( 'id' => $retLic->id );
					$updated = $wpdb->update( $tbl_name, $data, $where );

					$args = ( array(
						'result'  => 'success',
						'message' => 'License key activated',
					) );
					SLM_API_Utility::output_api_response( $args );
				} else {

					//Lets loop through the domains to see if it is being used on an existing domain or not.
					foreach ( $reg_domains as $reg_domain ) {
						if ( $fields['registered_domain'] == $reg_domain->registered_domain ) {
							//Not used on an existing domain. Return error: LICENSE_IN_USE_ON_DOMAIN_AND_MAX_REACHED
							$args = ( array(
								'result'     => 'error',
								'message'    => 'Reached maximum activation. License key already in use on ' . $reg_domain->registered_domain,
								'error_code' => SLM_Error_Codes::LICENSE_IN_USE_ON_DOMAIN_AND_MAX_REACHED,
							) );
							SLM_API_Utility::output_api_response( $args );
						}
					}

					//Not used on an existing domain. Return error: REACHED_MAX_DOMAINS
					$args = ( array(
						'result'     => 'error',
						'message'    => 'Reached maximum allowable domains',
						'error_code' => SLM_Error_Codes::REACHED_MAX_DOMAINS,
					) );
					SLM_API_Utility::output_api_response( $args );
				}
			} else {
				$args = ( array(
					'result'     => 'error',
					'message'    => 'Invalid license key',
					'error_code' => SLM_Error_Codes::LICENSE_INVALID,
				) );
				SLM_API_Utility::output_api_response( $args );
			}
		}
	}

	function deactivation_api_listener() {
		if ( isset( $_REQUEST['slm_action'] ) && trim( $_REQUEST['slm_action'] ) == 'slm_deactivate' ) {
			//Handle the license deactivation API query
			global $slm_debug_logger;

			SLM_API_Utility::verify_secret_key(); //Verify the secret key first.

			$slm_debug_logger->log_debug( 'API - license deactivation (slm_deactivate) request received.' );

			//Action hook
			do_action( 'slm_api_listener_slm_deactivate' );

			if ( empty( $_REQUEST['registered_domain'] ) ) {
				$args = ( array(
					'result'     => 'error',
					'message'    => 'Registered domain information is missing',
					'error_code' => SLM_Error_Codes::DOMAIN_MISSING,
				) );
				SLM_API_Utility::output_api_response( $args );
			}
			$registered_domain = trim( wp_unslash( sanitize_text_field( $_REQUEST['registered_domain'] ) ) );
			$license_key       = SLM_Utility::sanitize_strip_trim_slm_text( $_REQUEST['license_key'] );
			$slm_debug_logger->log_debug( 'License key: ' . $license_key . ' Domain: ' . $registered_domain );

			global $wpdb;
			$registered_dom_table = SLM_TBL_LIC_DOMAIN;
			$sql_prep             = $wpdb->prepare( "DELETE FROM $registered_dom_table WHERE lic_key=%s AND registered_domain=%s", $license_key, $registered_domain );
			$delete               = $wpdb->query( $sql_prep );
			if ( $delete === false ) {
				$slm_debug_logger->log_debug( 'Error - failed to delete the registered domain from the database.' );
			} elseif ( $delete == 0 ) {
				$args = ( array(
					'result'     => 'error',
					'message'    => 'The license key on this domain is already inactive',
					'error_code' => SLM_Error_Codes::DOMAIN_ALREADY_INACTIVE,
				) );
				SLM_API_Utility::output_api_response( $args );
			} else {
				$args = ( array(
					'result'  => 'success',
					'message' => 'The license key has been deactivated for this domain',
				) );
				SLM_API_Utility::output_api_response( $args );
			}
		}
	}

	function check_api_listener() {
		if ( isset( $_REQUEST['slm_action'] ) && trim( $_REQUEST['slm_action'] ) == 'slm_check' ) {
			//Handle the license check API query
			global $slm_debug_logger;

			SLM_API_Utility::verify_secret_key(); //Verify the secret key first.

			$slm_debug_logger->log_debug( 'API - license check (slm_check) request received.' );

			$fields            = array();
			$fields['lic_key'] = SLM_Utility::sanitize_strip_trim_slm_text( $_REQUEST['license_key'] );
			$slm_debug_logger->log_debug( 'License key: ' . $fields['lic_key'] );

			//Action hook
			do_action( 'slm_api_listener_slm_check' );

			global $wpdb;
			$tbl_name  = SLM_TBL_LICENSE_KEYS;
			$reg_table = SLM_TBL_LIC_DOMAIN;
			$key       = $fields['lic_key'];
			$sql_prep1 = $wpdb->prepare( "SELECT * FROM $tbl_name WHERE license_key = %s", $key );
			$retLic    = $wpdb->get_row( $sql_prep1, OBJECT );

			$sql_prep2   = $wpdb->prepare( "SELECT * FROM $reg_table WHERE lic_key = %s", $key );
			$reg_domains = $wpdb->get_results( $sql_prep2, OBJECT );
			if ( $retLic ) {//A license key exists
				$args = apply_filters(
					'slm_check_response_args',
					array(
						'result'              => 'success',
						'message'             => 'License key details retrieved.',
						'license_key'         => $retLic->license_key,
						'status'              => $retLic->lic_status,
						'max_allowed_domains' => $retLic->max_allowed_domains,
						'email'               => $retLic->email,
						'registered_domains'  => $reg_domains,
						'date_created'        => $retLic->date_created,
						'date_renewed'        => $retLic->date_renewed,
						'date_expiry'         => $retLic->date_expiry,
                                                'date'                => date("Y-m-d"),
						'product_ref'         => $retLic->product_ref,
						'first_name'          => $retLic->first_name,
						'last_name'           => $retLic->last_name,
						'company_name'        => $retLic->company_name,
						'txn_id'              => $retLic->txn_id,
						'subscr_id'           => $retLic->subscr_id,
					)
				);
				//Output the license details
				SLM_API_Utility::output_api_response( $args );
			} else {
				$args = ( array(
					'result'     => 'error',
					'message'    => 'Invalid license key',
					'error_code' => SLM_Error_Codes::LICENSE_INVALID,
				) );
				SLM_API_Utility::output_api_response( $args );
			}
		}
	}

}
