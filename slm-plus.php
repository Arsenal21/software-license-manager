<?php
/*
Plugin Name: SLM Plus
Version: 6.1.5
Plugin URI: https://github.com/michelve/software-license-manager/
Author: Michel Velis
Author URI: https://github.com/michelve/
Description: A comprehensive software license management solution for web applications including WordPress plugins, themes, and PHP-based software. Seamlessly integrates with WooCommerce to offer license key generation, management, and validation. Ideal for developers managing software licenses across multiple platforms with built-in multilingual support and performance optimization.
Text Domain: slmplus
Domain Path: /i18n/languages/
WC tested up to: 6.7
Requires at least: 5.6
Stable tag: 6.7
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Contributors: Tips and Tricks HQ
*/

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit; // Secure the plugin by blocking direct access
}

// Load plugin textdomain for multilingual support
function slmplus_load_textdomain() {
    load_plugin_textdomain('slmplus', false, dirname(plugin_basename(__FILE__)) . '/i18n/languages');
}
add_action('plugins_loaded', 'slmplus_load_textdomain');

// Global variables for database interaction
global $wpdb, $slm_debug_logger;

// Define constants for plugin paths, URLs, and database tables
define('SLM_VERSION',               '6.1.5');
define('SLM_DB_VERSION',            '5.0.9');
define('SLM_REWRITE_VERSION',       '3.0.5');
define('SLM_FOLDER',                dirname(plugin_basename(__FILE__)));
define('SLM_URL',                   plugins_url('', __FILE__));
define('SLM_ASSETS_URL',            SLM_URL . '/public/assets/');
define('SLM_PATH',                  plugin_dir_path(__FILE__));
define('SLM_LIB',                   SLM_PATH . 'includes/');
define('SLM_WOO',                   SLM_PATH . 'woocommerce/');
define('SLM_ADDONS',                SLM_PATH . 'addons/');
define('SLM_ADMIN',                 SLM_PATH . 'admin/');
define('SLM_ADMIN_ADDONS',          SLM_ADMIN . 'includes/');
define('SLM_CRONS',                 SLM_ADMIN_ADDONS . 'cronjobs/');
define('SLM_PUBLIC',                SLM_PATH . 'public/');
define('SLM_TEMPLATES',             SLM_PATH . 'templates/');
define('SLM_SITE_HOME_URL',         get_home_url());
define('SLM_SITE_URL',              get_site_url() . '/');
define('SLM_TBL_LICENSE_KEYS',      $wpdb->prefix . "lic_key_tbl");
define('SLM_TBL_EMAILS',            $wpdb->prefix . "lic_emails_tbl");
define('SLM_TBL_LIC_DOMAIN',        $wpdb->prefix . "lic_reg_domain_tbl");
define('SLM_TBL_LIC_DEVICES',       $wpdb->prefix . "lic_reg_devices_tbl");
define('SLM_TBL_LIC_LOG',           $wpdb->prefix . "lic_log_tbl");
define('SLM_TBL_LICENSE_STATUS',    $wpdb->prefix . "lic_status_tbl");
define('SLM_MANAGEMENT_PERMISSION', 'manage_options');
define('SLM_MAIN_MENU_SLUG',        'slm_overview');
define('SLM_MENU_ICON',             'dashicons-lock');
define('SLM_API_URL',               SLM_SITE_URL);

// Load core plugin functionalities
if (file_exists(SLM_LIB . 'slm-plugin-core.php')) {
    require_once SLM_LIB . 'slm-plugin-core.php';
}

function slm_settings_link($links){
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=slm_settings')) . '">' . __('Settings') . '</a>';
   // $github_link = '<a href="' . esc_url('https://github.com/michelve/software-license-manager') . '" target="_blank">' . __('GitHub') . '</a>';
    $links[] = $settings_link;
    //$links[] = $github_link;
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'slm_settings_link');

define('SLM_DEFAULT_MAX_DOMAINS', SLM_API_Utility::get_slm_option('default_max_domains'));
define('SLM_DEFAULT_MAX_DEVICES', SLM_API_Utility::get_slm_option('default_max_devices'));

// Use native WordPress function for setting options
define('WOO_SLM_API_SECRET',    SLM_API_Utility::get_slm_option('lic_creation_secret'));
define('KEY_API',               SLM_API_Utility::get_slm_option('lic_creation_secret'));
define('VERIFY_KEY_API',        SLM_API_Utility::get_slm_option('lic_verification_secret'));
define('KEY_API_PREFIX',        SLM_API_Utility::get_slm_option('lic_prefix'));

// Auto-updater integration for GitHub updates
if (file_exists('plugin-update-checker/plugin-update-checker.php')) {
    require 'plugin-update-checker/plugin-update-checker.php';
    $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
        'https://github.com/michelve/software-license-manager',
        __FILE__,
        'software-license-manager'
    );
}
