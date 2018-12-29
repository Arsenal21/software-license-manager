<?php
/*
Plugin Name: Software License Manager
Version: 4.6
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

//Short name/slug "SLM" or "slm"
define('SLM_VERSION',       '4.6');
define('SLM_DB_VERSION',    '2.2');
define('SLM_FOLDER',        dirname(plugin_basename(__FILE__)));
define('SLM_URL',           plugins_url('' ,__FILE__));
define('SLM_ASSETS_URL',    plugins_url('' ,__FILE__) . '/public/assets/');
define('SLM_PATH',          plugin_dir_path(__FILE__));
define('SLM_LIB',           SLM_PATH . 'includes/');
define('SLM_ADMIN',         SLM_PATH . 'admin/');
define('SLM_ADMIN_ADDONS',  SLM_PATH . 'admin/includes/');
define('SLM_PUBLIC',        SLM_PATH . 'public/');
define('SLM_SITE_HOME_URL', home_url());
define('SLM_SITE_URL',      site_url());

if( file_exists( SLM_LIB .  'slm_plugin_core.php') ) {
    require_once( SLM_LIB . 'slm_plugin_core.php');
}

// check for updates from github
require_once SLM_ADMIN . 'update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/michelve/software-license-manager/',
    __FILE__,
    'software-license-manager'
);