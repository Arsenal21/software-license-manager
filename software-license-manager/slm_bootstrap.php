<?php
/*
Plugin Name: Software License Manager
Version: 4.5.8
Plugin URI: https://www.tipsandtricks-hq.com/software-license-manager-plugin-for-wordpress
Author: Tips and Tricks HQ
Author URI: https://www.tipsandtricks-hq.com/
Description: Software license management solution for your web applications (WordPress plugins, Themes, PHP based membership script etc.)
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; //Exit if accessed directly
}

//Short name/slug "SLM" or "slm"

define( 'WP_LICENSE_MANAGER_VERSION', '4.5.8' );
define( 'WP_LICENSE_MANAGER_DB_VERSION', '1.6' );
define( 'WP_LICENSE_MANAGER_FOLDER', dirname( plugin_basename( __FILE__ ) ) );
define( 'WP_LICENSE_MANAGER_URL', plugins_url( '', __FILE__ ) );
define( 'WP_LICENSE_MANAGER_PATH', plugin_dir_path( __FILE__ ) );
define( 'SLM_SITE_HOME_URL', home_url() );
define( 'SLM_WP_SITE_URL', site_url() );

require_once 'slm_plugin_core.php';

//Activation handler
function slm_activate_handler() {
	//Do installer task
	slm_db_install();

	//schedule a daily cron event
	wp_schedule_event( time(), 'daily', 'slm_daily_cron_event' );

	do_action( 'slm_activation_complete' );
}
register_activation_hook( __FILE__, 'slm_activate_handler' );

//Deactivation handler
function slm_deactivate_handler() {
	//Clear the daily cron event
	wp_clear_scheduled_hook( 'slm_daily_cron_event' );

	do_action( 'slm_deactivation_complete' );
}
register_deactivation_hook( __FILE__, 'slm_deactivate_handler' );

//Installer function
function slm_db_install() {
	//run the installer
	require_once dirname( __FILE__ ) . '/slm_installer.php';
}

// Add the settings link in the plugin's menu of WP Dashboard.
function slm_add_settings_link( $links, $file ) {
    if ( $file == plugin_basename( __FILE__ ) ) {
	$settings_link = '<a href="admin.php?page=wp_lic_mgr_settings">' . (__( "Settings", "slm" )) . '</a>';
	array_unshift( $links, $settings_link );
    }
    return $links;
}
add_filter( 'plugin_action_links', 'slm_add_settings_link', 10, 2 );