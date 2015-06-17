<?php
/*
Plugin Name: Software License Manager
Version: v1.7
Plugin URI: https://www.tipsandtricks-hq.com/software-license-manager-plugin-for-wordpress
Author: Tips and Tricks HQ
Author URI: https://www.tipsandtricks-hq.com/
Description: Software license management solution for your web applications (WordPress plugins, Themes, PHP based membership script etc.)
*/

if(!defined('ABSPATH')){
    exit; //Exit if accessed directly
}

//Short name/slug "SLM" or "slm"

define('WP_LICENSE_MANAGER_VERSION', "1.7");
define('WP_LICENSE_MANAGER_DB_VERSION', '1.2');
define('WP_LICENSE_MANAGER_FOLDER', dirname(plugin_basename(__FILE__)));
define('WP_LICENSE_MANAGER_URL', plugins_url('',__FILE__));
define('WP_LICENSE_MANAGER_PATH', plugin_dir_path(__FILE__));
define('SLM_SITE_HOME_URL', home_url());
define('SLM_WP_SITE_URL', site_url());

include_once('slm_plugin_core.php');

//Installer
function slm_db_install ()
{
    require_once(dirname(__FILE__).'/slm_installer.php');
}
register_activation_hook(__FILE__,'slm_db_install');
