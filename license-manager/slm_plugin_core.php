<?php

//Defines
global $wpdb;
define('WPLM_TBL_LICENSE_KEYS', $wpdb->prefix . "lic_key_tbl");
define('WPLM_TBL_LIC_DOMAIN', $wpdb->prefix . "lic_reg_domain_tbl");

//Includes
include_once('lic_db_access.php');
//Include admin side only files
if (is_admin()){
    include_once('menu/includes/wp-license-mgr-list-table.php'); //Load our own WP List Table class
}

//Action hooks
add_action('init', 'wp_lic_mgr_init');
add_action('plugins_loaded', 'wp_lic_mgr_plugins_loaded_handler');


function wp_lic_mgr_init()
{
    //Load all common scripts and styles only
    wp_enqueue_script('jquery');

    if(is_admin())//Load all admin side scripts and styles only
    {
            wp_enqueue_script('jquery-ui-datepicker');

            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-widget');
            wp_enqueue_script('jquery-ui-position');
            wp_enqueue_script('jquery-ui-mouse');
            wp_enqueue_script('jquery-ui-dialog');
    }
}


function wp_lic_mgr_plugins_loaded_handler()
{
    //Runs when plugins_loaded action gets fired
    if(is_admin()){
        //Check if db update needed
        if (get_option('wp_lic_mgr_db_version') != WP_LICENSE_MANAGER_DB_VERSION) {
            require_once(dirname(__FILE__).'/lic_manager_installer.php');
        }
    }
}

add_action('wp_ajax_del_dom', 'del_reg_dom');
function del_reg_dom(){
	$reg_table = WP_LICENSE_MANAGER_REG_DOMAIN_TABLE_NAME;
	global $wpdb;
	$ret = $wpdb->query("DELETE FROM $reg_table WHERE id =" . $_GET['id']);
	echo ($ret)? 'success' :'failed';	
	exit(0);
}

//Add the Admin Menus
define("LIC_MGR_MANAGEMENT_PERMISSION", "edit_themes");
if (is_admin())
{
    function wp_lic_mgr_add_admin_menu()
    {
        add_menu_page("License Mgr", "License Mgr", LIC_MGR_MANAGEMENT_PERMISSION, __FILE__, "wp_lic_mgr_manage_licenses_menu");
        add_submenu_page(__FILE__, "Manage Licenses", "Manage Licenses", LIC_MGR_MANAGEMENT_PERMISSION, __FILE__, "wp_lic_mgr_manage_licenses_menu");
        add_submenu_page(__FILE__, "Add/Edit Licenses", "Add/Edit Licenses", LIC_MGR_MANAGEMENT_PERMISSION, 'wp_lic_mgr_addedit', "wp_lic_mgr_add_licenses_menu");
        add_submenu_page(__FILE__, "Settings", "Settings", LIC_MGR_MANAGEMENT_PERMISSION, 'wp_lic_mgr_settings', "wp_lic_mgr_settings_menu");
        add_submenu_page(__FILE__, "Admin Functions", "Admin Functions", LIC_MGR_MANAGEMENT_PERMISSION, 'wp_lic_mgr_admin_fnc', "wp_lic_mgr_admin_fnc_menu");
        add_submenu_page(__FILE__, "Integration Help", "Integration Help", LIC_MGR_MANAGEMENT_PERMISSION, 'lic_mgr_integration_help_page', "lic_mgr_integration_help_menu");
    }
    //Include menus
    require_once(dirname(__FILE__).'/menu/lic_manage_licenses.php');
    require_once(dirname(__FILE__).'/menu/lic_add_licenses.php');
    require_once(dirname(__FILE__).'/menu/lic_settings.php');
    require_once(dirname(__FILE__).'/menu/wp_lic_mgr_admin_fnc.php');    
    require_once(dirname(__FILE__).'/menu/lic_mgr_integration_help_page.php');
}

// Insert the options page to the admin menu
if (is_admin())
{
    add_action('admin_menu','wp_lic_mgr_add_admin_menu');
}
