<?php

/*
 * Contains some utility functions for the plugin.
 */
class SLM_Utility {

	static function do_auto_key_expiry() {
		global $wpdb;
		$current_date = ( date( 'Y-m-d' ) );
		$tbl_name     = SLM_TBL_LICENSE_KEYS;

		$sql_prep = $wpdb->prepare( "SELECT * FROM $tbl_name WHERE lic_status !=%s", 'expired' );//Load the non-expired keys
		$licenses = $wpdb->get_results( $sql_prep, OBJECT );
		if ( ! $licenses ) {
			SLM_Debug_Logger::log_debug_st( 'do_auto_key_expiry() - no license keys found.' );
			return false;
		}

		foreach ( $licenses as $license ) {
			$key         = $license->license_key;
			$expiry_date = $license->date_expiry;
			if ( $expiry_date == '0000-00-00' ) {
				SLM_Debug_Logger::log_debug_st( 'This key (' . $key . ") doesn't have a valid expiry date set. The expiry of this key will not be checked." );
				continue;
			}

			$today_dt  = new DateTime( $current_date );
			$expire_dt = new DateTime( $expiry_date );
			if ( $today_dt > $expire_dt ) {
				//This key has reached the expiry. So expire this key.
				SLM_Debug_Logger::log_debug_st( 'This key (' . $key . ') has expired. Expiry date: ' . $expiry_date . '. Setting license key status to expired.' );
				$data    = array( 'lic_status' => 'expired' );
				$where   = array( 'id' => $license->id );
				$updated = $wpdb->update( $tbl_name, $data, $where );
				do_action( 'slm_license_key_expired', $license->id );//Trigger the license expired action hook.
			}
		}
	}

	/*
	 * Deletes a license key from the licenses table
	 */
	static function delete_license_key_by_row_id( $key_row_id ) {
		global $wpdb;
		$license_table = SLM_TBL_LICENSE_KEYS;

		//First delete the registered domains entry of this key (if any).
		SLM_Utility::delete_registered_domains_of_key( $key_row_id );

		//Now, delete the key from the licenses table.
		$wpdb->delete( $license_table, array( 'id' => $key_row_id ) );

	}

	/*
	 * Deletes any registered domains info from the domain table for the given key's row id.
	 */
	static function delete_registered_domains_of_key( $key_row_id ) {
		global $slm_debug_logger;
		global $wpdb;
		$reg_table   = SLM_TBL_LIC_DOMAIN;
		$sql_prep    = $wpdb->prepare( "SELECT * FROM $reg_table WHERE lic_key_id = %s", $key_row_id );
		$reg_domains = $wpdb->get_results( $sql_prep, OBJECT );
		foreach ( $reg_domains as $domain ) {
			$row_to_delete = $domain->id;
			$wpdb->delete( $reg_table, array( 'id' => $row_to_delete ) );
			//$slm_debug_logger->log_debug("Registered domain with row id (".$row_to_delete.") deleted.");
		}
	}


	/**
	 * Get remote IP address.
	 * @link http://stackoverflow.com/questions/1634782/what-is-the-most-accurate-way-to-retrieve-a-users-correct-ip-address-in-php
	 *
	 * @param bool $ignore_private_and_reserved Ignore IPs that fall into private or reserved IP ranges.
	 * @return mixed IP address as a string or null, if remote IP address cannot be determined (or is ignored).
	 */
	static function get_ip_address( $ignore_private_and_reserved = false ) {
		$flags = $ignore_private_and_reserved ? ( FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) : 0;
		foreach ( array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' ) as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
					$ip = trim( $ip ); // just to be safe

					if ( filter_var( $ip, FILTER_VALIDATE_IP, $flags ) !== false ) {
						return $ip;
					}
				}
			}
		}
		return '';
	}

	static function sanitize_strip_trim_slm_text( $text ) {
		$text = strip_tags( $text );
		$text = htmlspecialchars( $text );
		$text = sanitize_text_field( $text );
		$text = trim( $text );
		return $text;
	}
}

