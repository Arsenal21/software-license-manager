<?php

//Defines
global $wpdb;
define('WPLM_TBL_LICENSE_KEYS', $wpdb->prefix . "lic_key_tbl");
define('WPLM_TBL_LIC_DOMAIN', $wpdb->prefix . "lic_reg_domain_tbl");
define('SLM_MANAGEMENT_PERMISSION', 'manage_options');
define('SLM_MAIN_MENU_SLUG', 'slm');

//Includes
include_once('includes/slm_init_time_tasks.php');
include_once('lic_db_access.php');
//Include admin side only files
if (is_admin()){
    include_once('menu/slm-admin-init.php');
    include_once('menu/includes/wp-license-mgr-list-table.php'); //Load our own WP List Table class
}

//Action hooks
add_action('init', 'wp_lic_mgr_init');
add_action('plugins_loaded', 'wp_lic_mgr_plugins_loaded_handler');


//Do init time tasks
function wp_lic_mgr_init()
{
    new SLM_Init_Time_Tasks();
}

//Do plugins loaded time tasks
function wp_lic_mgr_plugins_loaded_handler()
{
    //Runs when plugins_loaded action gets fired
    if(is_admin()){
        //Check if db update needed
        if (get_option('wp_lic_mgr_db_version') != WP_LICENSE_MANAGER_DB_VERSION) {
            require_once(dirname(__FILE__).'/slm_installer.php');
        }
    }
}

//TODO - need to move this to an ajax handler file
add_action('wp_ajax_del_dom', 'del_reg_dom');
function del_reg_dom(){
	$reg_table = WP_LICENSE_MANAGER_REG_DOMAIN_TABLE_NAME;
	global $wpdb;
	$ret = $wpdb->query("DELETE FROM $reg_table WHERE id =" . $_GET['id']);
	echo ($ret)? 'success' :'failed';	
	exit(0);
}
