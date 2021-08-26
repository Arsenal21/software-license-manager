<?php

class SLM_Init_Time_Tasks {

	function __construct() {
		$this->load_scripts();

		//Add other init time operations here
		add_action( 'slm_daily_cron_event', array( &$this, 'slm_daily_cron_event_handler' ) );

		//View debug log
		if ( ! empty( $_REQUEST['slm_view_log'] ) ) {
			check_admin_referer( 'slm_view_debug_log', 'slm_view_debug_log_nonce' );
			SLM_Debug_Logger::get_instance()->view_log();
		}
	}

	function load_scripts() {
		//Load all common scripts and styles only
		wp_enqueue_script( 'jquery' );

		//Load all admin side scripts and styles only
		if ( is_admin() ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'wplm-custom-admin-js', WP_LICENSE_MANAGER_URL . '/js/wplm-custom-admin.js', array( 'jquery-ui-dialog' ) );//admin only custom js code

			if ( isset( $_GET['page'] ) && $_GET['page'] == 'wp_lic_mgr_addedit' ) {//Only include if we are in the license add/edit interface
				wp_enqueue_style( 'jquery-ui-style', WP_LICENSE_MANAGER_URL . '/css/jquery-ui.css' );
			}
			//wp_enqueue_style('dialogStylesheet', includes_url().'css/jquery-ui-dialog.css');
		}
	}

	function slm_daily_cron_event_handler() {
		$options = get_option( 'slm_plugin_options' );

		do_action( 'slm_daily_cron_event_triggered' );

		if ( isset( $options['enable_auto_key_expiry'] ) && $options['enable_auto_key_expiry'] == '1' ) {
			//Do the auto key expiry task
			SLM_Debug_Logger::log_debug_st( 'SLM daily cronjob - auto expiry of license key is enabled.' );
			SLM_Utility::do_auto_key_expiry();
		}

		//Do any ohter daily cronjob tasks.

	}

}//End of class
