<?php
/*
Plugin Name: Software License Manager
Version: 4.3
Plugin URI: https://www.tipsandtricks-hq.com/software-license-manager-plugin-for-wordpress
Author: Tips and Tricks HQ
Author URI: https://www.tipsandtricks-hq.com/
Description: Software license management solution for your web applications (WordPress plugins, Themes, Applications, PHP based membership script etc.). Supports WooCommerce and Subscriptio Plugin.
Author2: <a href="https://epikly.com/">Michel Velis</a>
Text Domain: slm
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

//Short name/slug "SLM" or "slm"
define('SLM_VERSION', "4.3");
define('SLM_DB_VERSION', '1.9');
define('SLM_FOLDER', dirname(plugin_basename(__FILE__)));
define('SLM_URL', plugins_url('',__FILE__));
define('SLM_ASSETS_URL', plugins_url('',__FILE__).'/public/assets/');
define('SLM_PATH', plugin_dir_path(__FILE__));
define('SLM_LIB', SLM_PATH .'includes/');
define('SLM_ADMIN', SLM_PATH .'admin/');
define('SLM_ADMIN_ADDONS', SLM_PATH .'admin/includes/');
define('SLM_PUBLIC', SLM_PATH .'public/');
define('SLM_SITE_HOME_URL', home_url());
define('SLM_SITE_URL', site_url());

if( file_exists( SLM_LIB . 'slm_plugin_core.php') ) {
    require_once( SLM_LIB . 'slm_plugin_core.php');
}