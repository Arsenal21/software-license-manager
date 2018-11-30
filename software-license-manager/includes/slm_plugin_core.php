<?php
/**
 * Runs on Uninstall of Software License Manager
 *
 * @package   Software License Manager
 * @author    Michel Velis
 * @license   GPL-2.0+
 * @link      http://epikly.com
 */

//Defines
global $wpdb;
define('SLM_TBL_LICENSE_KEYS',  $wpdb->prefix . "lic_key_tbl");
define('SLM_TBL_LIC_DOMAIN',    $wpdb->prefix . "lic_reg_domain_tbl");
define('SLM_TBL_LIC_DEVICES',   $wpdb->prefix . "lic_reg_devices_tbl");
define('SLM_MANAGEMENT_PERMISSION', 'manage_options');
define('SLM_MAIN_MENU_SLUG', 'slm-main');
define('SLM_MENU_ICON', 'dashicons-lock');

// Helper Class
class SLM_Helper_Class {
    public function slm_get_option($option){
        $slm_opts       = get_option('slm_plugin_options');
        $option_name    = $slm_opts[$option];
        return $option_name;
    }
}

add_filter('extra_plugin_headers', 'add_extra_headers');
add_filter('plugin_row_meta', 'filter_authors_row_meta', 1, 4);

function add_extra_headers(){
    return array('Author2');
}
function filter_authors_row_meta($plugin_meta, $plugin_file, $plugin_data, $status ){
    if(empty($plugin_data['Author'])){
        return $plugin_meta;
    }
    if ( !empty( $plugin_data['Author2'] ) ) {
        $plugin_meta[1] = $plugin_meta[1] . ', ' . $plugin_data['Author2'];
    }
    return $plugin_meta;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-software-license-manager-activator.php
 */
function activate_software_license_manager() {
    require_once SLM_LIB . 'class-software-license-manager-activator.php';
    Software_License_Manager_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-software-license-manager-deactivator.php
 */
function deactivate_software_license_manager() {
    require_once SLM_LIB . 'class-software-license-manager-deactivator.php';
    Software_License_Manager_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_software_license_manager' );
register_deactivation_hook( __FILE__, 'deactivate_software_license_manager' );

//Includes
include_once( SLM_LIB .'slm-debug-logger.php');
include_once( SLM_LIB .'slm-error-codes.php');
include_once( SLM_LIB .'slm-utility.php');
include_once( SLM_LIB .'slm-init-time-tasks.php');
include_once( SLM_LIB .'slm-api-utility.php');
include_once( SLM_LIB .'slm-api-listener.php');

// Front end-menu
// TODO check for optional plugins

// Third Party Support
if (null !== SLM_Helper_Class::slm_get_option('slm_woo') && SLM_Helper_Class::slm_get_option('slm_woo') == 1) {
    include_once( SLM_PUBLIC . 'slm-add-menu-frontend.php');

    // WordPress Plugin :: wc-software-license-manager
    include_once( SLM_ADMIN  . 'includes/woocommerce/wc-software-license-manager.php');

    // support for meta boxes (variations only, this can be applied to single prodicts as well)
    include_once( SLM_LIB . 'slm-meta-boxes.php');
}

if (null !== SLM_Helper_Class::slm_get_option('slm_subscriptio') && SLM_Helper_Class::slm_get_option('slm_subscriptio') == 1) {
    // Subscriptio PLugin Intergration
    include_once( SLM_ADMIN  . 'includes/subscriptio/slm-subscriptio.php');
}

if (null !== SLM_Helper_Class::slm_get_option('slm_wpestores') && SLM_Helper_Class::slm_get_option('slm_wpestores') == 1) {
    // wpestores PLugin Intergration
    include_once( SLM_ADMIN  . 'includes/wpestores/slm-wpestores.php');
}


//Include admin side only files
if (is_admin()) {
    include_once( SLM_ADMIN . 'slm-admin-init.php');
    include_once( SLM_ADMIN . 'includes/slm-list-table-class.php'); //Load our own WP List Table class
}

//Action hooks
add_action('init', 'slm_init_handler');
add_action('plugins_loaded', 'slm_plugins_loaded_handler');
add_action('wp_ajax_del_reistered_devices', 'slm_del_reg_devices');
add_action('wp_ajax_del_reistered_domain', 'slm_del_reg_dom');

//Initialize debug logger
global $slm_debug_logger;
$slm_debug_logger   = new SLM_Debug_Logger();

//Do init time tasks
function slm_init_handler() {
    $init_task      = new SLM_Init_Time_Tasks();
    $api_listener   = new SLM_API_Listener();
}

//Do plugins loaded time tasks
function slm_plugins_loaded_handler() {
    //Runs when plugins_loaded action gets fired
    if (is_admin()) {
        //Check if db update needed
        if (get_option('wp_lic_mgr_db_version') != SLM_DB_VERSION) {
             require_once( SLM_LIB . 'class-software-license-manager-slm-installer.php');
            // TODO - Software_License_Manager_Activator::slm_db_install();
        }
    }
}

//TODO - need to move this to an ajax handler file
function slm_del_reg_dom() {
    global $wpdb;
    $reg_table = SLM_TBL_LIC_DOMAIN;
    $id = strip_tags($_GET['id']);
    $ret = $wpdb->query($wpdb->prepare( "DELETE FROM {$reg_table} WHERE id=%d", $id ) );
    echo ($ret) ? 'success' : 'failed';
    exit(0);
}

function slm_del_reg_devices() {
    global $wpdb;
    $reg_table = SLM_TBL_LIC_DEVICES;
    $id = strip_tags($_GET['id']);
    $ret = $wpdb->query($wpdb->prepare( "DELETE FROM {$reg_table} WHERE id=%d", $id ) );
    echo ($ret) ? 'success' : 'failed';
    exit(0);
}