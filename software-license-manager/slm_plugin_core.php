<?php

//Defines
global $wpdb;
define( 'SLM_TBL_LICENSE_KEYS', $wpdb->prefix . 'lic_key_tbl' );
define( 'SLM_TBL_LIC_DOMAIN', $wpdb->prefix . 'lic_reg_domain_tbl' );
define( 'SLM_MANAGEMENT_PERMISSION', apply_filters( 'slm_management_permission_role', 'manage_options' ) );
define( 'SLM_MAIN_MENU_SLUG', 'slm-main' );
define( 'SLM_MENU_ICON', 'dashicons-lock' );

//Includes
require_once 'includes/slm-debug-logger.php';
require_once 'includes/slm-error-codes.php';
require_once 'includes/slm-utility.php';
require_once 'includes/slm-init-time-tasks.php';
require_once 'includes/slm-api-utility.php';
require_once 'includes/slm-api-listener.php';
require_once 'includes/slm-third-party-integration.php';
//Include admin side only files
if ( is_admin() ) {
	include_once 'menu/slm-admin-init.php';
}

//Action hooks
add_action( 'init', 'slm_init_handler' );
add_action( 'plugins_loaded', 'slm_plugins_loaded_handler' );

//Initialize debug logger
global $slm_debug_logger;
$slm_debug_logger = new SLM_Debug_Logger();

//Do init time tasks
function slm_init_handler() {
	$init_task    = new SLM_Init_Time_Tasks();
	$api_listener = new SLM_API_Listener();
}

//Do plugins loaded time tasks
function slm_plugins_loaded_handler() {
	//Runs when plugins_loaded action gets fired
	if ( is_admin() ) {
		//Check if db update needed
		if ( get_option( 'wp_lic_mgr_db_version' ) != WP_LICENSE_MANAGER_DB_VERSION ) {
			require_once dirname( __FILE__ ) . '/slm_installer.php';
		}
	}

}

//TODO - need to move this to an ajax handler file
add_action( 'wp_ajax_slm_delete_domain', 'slm_del_reg_dom' );
function slm_del_reg_dom() {
	$out = array( 'status' => 'fail' );

	if ( ! current_user_can( 'administrator' ) ) {
		wp_send_json( $out );
	}

	global $wpdb;

	$lic_id    = filter_input( INPUT_POST, 'lic_id', FILTER_SANITIZE_NUMBER_INT, FILTER_VALIDATE_INT );
	$domain_id = filter_input( INPUT_POST, 'domain_id', FILTER_SANITIZE_NUMBER_INT, FILTER_VALIDATE_INT );

	if ( empty( $lic_id ) || empty( $domain_id ) ) {
		wp_send_json( $out );
	}

	$reg_table = SLM_TBL_LIC_DOMAIN;

	if ( ! check_ajax_referer( sprintf( 'slm_delete_domain_lic_%s_id_%s', $lic_id, $domain_id ), false, false ) ) {
		wp_send_json( $out );
	}

        do_action( 'slm_before_registered_domain_delete', $domain_id );
  
	$wpdb->query( $wpdb->prepare( "DELETE FROM $reg_table WHERE id=%d", $domain_id ) ); //phpcs:ignore

	$out['status'] = 'success';
        $out = apply_filters( 'slm_registered_domain_delete_response', $out );
  
	wp_send_json( $out );
}
