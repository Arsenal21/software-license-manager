<?php
/*
Plugin Name: Software License Manager
Version: 4.10.3
Plugin URI: https://github.com/michelve/software-license-manager/
Author: Michel Velis
Author URI: https://www.epikly.com/
Description: Software license management solution for your web applications (WordPress plugins, Themes, Applications, PHP based membership script etc.). Supports WooCommerce.
Author2: <a href="https://www.tipsandtricks-hq.com/">Tips and Tricks HQ</a>
Text Domain: slm
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

global $wpdb, $slm_debug_logger;

//Short name/slug "SLM" or "slm"
define('SLM_VERSION',               '4.10.3');
define('SLM_DB_VERSION',            '2.7.2');
define('SLM_REWRITE_VERSION',       '1.1.0');
define('WOO_SLM_VER',               SLM_VERSION);
define('WOO_SLM_API_URL',           get_site_url().'/');
define('SLM_FOLDER',                dirname(plugin_basename(__FILE__)));
define('SLM_URL',                   plugins_url('' ,__FILE__));
define('SLM_ASSETS_URL',            plugins_url('' ,__FILE__) . '/public/assets/');
define('SLM_PATH',                  plugin_dir_path(__FILE__));
define('SLM_LIB',                   SLM_PATH . 'includes/');
define('SLM_WOO',                   SLM_PATH . 'woocommerce/');
define('SLM_ADMIN',                 SLM_PATH . 'admin/');
define('SLM_ADMIN_ADDONS',          SLM_PATH . 'admin/includes/');
define('SLM_PUBLIC',                SLM_PATH . 'public/');
define('SLM_TEAMPLATES',            SLM_PATH . 'templates/');
define('SLM_SITE_HOME_URL',         home_url());
define('SLM_SITE_URL',              site_url());
define('SLM_TBL_LICENSE_KEYS',      $wpdb->prefix . "lic_key_tbl");
define('SLM_TBL_LIC_DOMAIN',        $wpdb->prefix . "lic_reg_domain_tbl");
define('SLM_TBL_LIC_DEVICES',       $wpdb->prefix . "lic_reg_devices_tbl");
define('SLM_MANAGEMENT_PERMISSION', 'manage_options');
define('SLM_MAIN_MENU_SLUG',        'slm_overview');
define('SLM_MENU_ICON',             'dashicons-lock');

if( file_exists( SLM_LIB .  'slm_plugin_core.php') ) {
    require_once SLM_LIB . 'slm_plugin_core.php';
}

define('WOO_SLM_API_SECRET',    SLM_Helper_Class::slm_get_option('lic_creation_secret'));
define('KEY_API',               SLM_Helper_Class::slm_get_option('lic_creation_secret'));
define('KEY_API_PREFIX',        SLM_Helper_Class::slm_get_option('lic_prefix'));
