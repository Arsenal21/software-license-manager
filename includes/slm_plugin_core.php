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
global $wpdb, $slm_debug_logger;

define('SLM_TBL_LICENSE_KEYS',  $wpdb->prefix . "lic_key_tbl");
define('SLM_TBL_LIC_DOMAIN',    $wpdb->prefix . "lic_reg_domain_tbl");
define('SLM_TBL_LIC_DEVICES',   $wpdb->prefix . "lic_reg_devices_tbl");
define('SLM_MANAGEMENT_PERMISSION', 'manage_options');
define('SLM_MAIN_MENU_SLUG', 'slm-main');
define('SLM_MENU_ICON', 'dashicons-lock');

// Helper Class
class SLM_Helper_Class {
    public static function slm_get_option($option){
        $option_name    = '';
        $slm_opts       = get_option('slm_plugin_options');
        $option_name    = $slm_opts[$option];
        return $option_name;
    }
    public static function write_log ( $log )  {
        if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }
}
$slm_helper = new SLM_Helper_Class();

add_filter('extra_plugin_headers', 'add_extra_headers');
add_filter('plugin_row_meta', 'filter_authors_row_meta', 1, 4);

function add_extra_headers(){
    return array('Author2');
}
function hyphenate($str) {
    return implode("-", str_split($str, 5));
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
    $slm_activator->activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-software-license-manager-deactivator.php
 */
function deactivate_software_license_manager() {
    require_once SLM_LIB . 'class-software-license-manager-deactivator.php';
    $slm_deactivator->deactivate();
}

register_activation_hook( __FILE__, 'activate_software_license_manager' );
register_deactivation_hook( __FILE__, 'deactivate_software_license_manager' );

//Includes
require_once( SLM_LIB .'slm-debug-logger.php');
require_once( SLM_LIB .'slm-error-codes.php');
require_once( SLM_LIB .'slm-utility.php');
require_once( SLM_LIB .'slm-init-time-tasks.php');
require_once( SLM_LIB .'slm-api-utility.php');
require_once( SLM_LIB .'slm-api-listener.php');
require_once( SLM_LIB .'slm-scripts.php');

// Front end-menu
// TODO check for optional plugins

// Third Party Support
if (null !== SLM_Helper_Class::slm_get_option('slm_woo') && SLM_Helper_Class::slm_get_option('slm_woo') == 1) {
    require_once( SLM_PUBLIC . 'slm-add-menu-frontend.php');
    // WordPress Plugin :: wc-software-license-manager
    require_once( SLM_ADMIN  . 'includes/woocommerce/wc-software-license-manager.php');
    // support for meta boxes
    require_once( SLM_LIB . 'slm-meta-boxes.php');
    require_once( SLM_LIB . 'slm-wc-order-post-type.php');
}

if (null !== SLM_Helper_Class::slm_get_option('slm_wpestores') && SLM_Helper_Class::slm_get_option('slm_wpestores') == 1) {
    // wpestores PLugin Integration
    require_once( SLM_ADMIN  . 'includes/wpestores/slm-wpestores.php');
}

//Include admin side only files
if (is_admin()) {
    require_once( SLM_ADMIN . 'slm-admin-init.php');
    require_once( SLM_ADMIN . 'includes/slm-list-table-class.php'); //Load our own WP List Table class
}

//Action hooks
add_action('init', 'slm_init_handler');
add_action('plugins_loaded', 'slm_plugins_loaded_handler');
add_action('wp_ajax_del_reistered_devices', 'slm_del_reg_devices');
add_action('wp_ajax_del_reistered_domain', 'slm_del_reg_dom');

//Initialize debug logger
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
            // TODO - $slm_activator->slm_db_install();
        }
    }
}

//TODO - need to move this to an ajax handler file
function slm_del_reg_dom() {
    global $wpdb;
    $reg_table  = SLM_TBL_LIC_DOMAIN;
    $id         = strip_tags($_GET['id']);
    $ret        = $wpdb->query($wpdb->prepare( "DELETE FROM {$reg_table} WHERE id=%d", $id ) );
    echo ($ret) ? 'success' : 'failed';
    exit(0);
}

function slm_del_reg_devices() {
    global $wpdb;
    $reg_table  = SLM_TBL_LIC_DEVICES;
    $id         = strip_tags($_GET['id']);
    $ret        = $wpdb->query($wpdb->prepare( "DELETE FROM {$reg_table} WHERE id=%d", $id ) );
    echo ($ret) ? 'success' : 'failed';
    exit(0);
}

/**
 * The permalink structure definition for API calls.
 */

// WIP
//add_action('init', 'slm_add_api_endpoint_rules', 10, 0);

function slm_add_api_endpoint_rules() {

    add_rewrite_rule( '^license/api/slm_action/check/([^/]*)/?',
        'index.php?slm_action=slm_check&secret_key=$matches[1]&license_key=$matches[2]',
        'top' );

    add_rewrite_rule(
        '^license/api/([^/]*)/?',
        'index.php?pagename=$matches[1]&param=foo',
        'top'
    );

    // If this was the first time, flush rules
    if ( get_option( 'slm_rewrite_rules' ) != SLM_REWRITE_VERSION ) {
        flush_rewrite_rules();
        update_option( 'slm_rewrite_rules', SLM_REWRITE_VERSION );
    }
}
