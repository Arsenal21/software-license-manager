<?php

//Defines
global $wpdb;
define('SLM_TBL_LICENSE_KEYS', $wpdb->prefix . "lic_key_tbl");
define('SLM_TBL_LIC_DOMAIN', $wpdb->prefix . "lic_reg_domain_tbl");
define('SLM_MANAGEMENT_PERMISSION', 'manage_options');
define('SLM_MAIN_MENU_SLUG', 'slm-main');
define('SLM_MENU_ICON', 'dashicons-lock');

//Includes
include_once('includes/slm-debug-logger.php');
include_once('includes/slm-init-time-tasks.php');
include_once('includes/slm-api-utility.php');
include_once('includes/slm-api-listener.php');
include_once('includes/slm-third-party-integration.php');
//Include admin side only files
if (is_admin()) {
    include_once('menu/slm-admin-init.php');
    include_once('menu/includes/wp-license-mgr-list-table.php'); //Load our own WP List Table class
}

//Action hooks
add_action('init', 'slm_init_handler');
add_action('plugins_loaded', 'slm_plugins_loaded_handler');

//Initialize debug logger
global $slm_debug_logger;
$slm_debug_logger = new SLM_Debug_Logger();

//Do init time tasks
function slm_init_handler() {
    $init_task = new SLM_Init_Time_Tasks();
    $api_listener = new SLM_API_Listener();
}

//Do plugins loaded time tasks
function slm_plugins_loaded_handler() {
    //Runs when plugins_loaded action gets fired
    if (is_admin()) {
        //Check if db update needed
        if (get_option('wp_lic_mgr_db_version') != WP_LICENSE_MANAGER_DB_VERSION) {
            require_once(dirname(__FILE__) . '/slm_installer.php');
        }
    }

}

//TODO - need to move this to an ajax handler file
add_action('wp_ajax_del_reistered_domain', 'slm_del_reg_dom');
function slm_del_reg_dom() {
    global $wpdb;
    $reg_table = SLM_TBL_LIC_DOMAIN;
    $id = strip_tags($_GET['id']);
    $ret = $wpdb->query("DELETE FROM $reg_table WHERE id='$id'");
    echo ($ret) ? 'success' : 'failed';
    exit(0);
}
