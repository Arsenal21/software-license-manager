<?php
/*
Plugin Name: License Manager
Version: v1.9
Plugin URI: http://tipsandtricks-hq.com
Author: Tips and Tricks HQ
Author URI: http://www.tipsandtricks-hq.com/
Description: Software license management solution for your web applications (WordPress plugins, Themes, PHP based membership script etc.)
*/
define('WP_LICENSE_MANAGER_VERSION', "1.9");
include_once('wp_license_manager1.php');

//Installer
function wp_lic_manager_install ()
{
    require_once(dirname(__FILE__).'/lic_manager_installer.php');
}
register_activation_hook(__FILE__,'wp_lic_manager_install');
