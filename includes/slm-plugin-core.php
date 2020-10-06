<?php

/**
 * Runs on Uninstall of Software License Manager
 *
 * @package   Software License Manager
 * @author    Michel Velis
 * @license   GPL-2.0+
 * @link      http://epikly.com
 * https://api.github.com/repos/michelve/software-license-manager/tags
 */

//require_once(SLM_LIB . 'slm-wizard.php');
//require_once(SLM_LIB . 'wp-mail-class.php');


/**
 * Load plugin textdomain.
 */
add_action('init', 'slm_load_language');
function slm_load_language()
{
    load_plugin_textdomain('softwarelicensemanager', false, plugin_dir_path(__FILE__) . 'i18n/languages/');
}


//Includes - utilities and cron jobs
include_once(ABSPATH . 'wp-admin/includes/plugin.php');

require_once(SLM_LIB . 'slm-utility.php');
require_once(SLM_CRONS . 'slm-tasks.php');

add_filter('extra_plugin_headers', 'add_extra_headers');
add_filter('plugin_row_meta', 'filter_authors_row_meta', 1, 4);

function add_extra_headers()
{
    return array('Author2');
}
function hyphenate($str)
{
    return implode("-", str_split($str, 5));
}

function filter_authors_row_meta($plugin_meta, $plugin_file, $plugin_data, $status)
{
    if (empty($plugin_data['Author'])) {
        return $plugin_meta;
    }
    if (!empty($plugin_data['Author2'])) {
        $plugin_meta[1] = $plugin_meta[1] . ', ' . $plugin_data['Author2'];
    }
    return $plugin_meta;
}

function slm_settings_link($links)
{
    $links[] = '<a href="' .
        admin_url('admin.php?page=slm_settings') .
        '">' . __('Settings') . '</a>';
    $links[] = '<a href="https://github.com/michelve/software-license-manager" target="_blank">' . __('GitHub') . '</a>';
    return $links;
}

//Includes
require_once(SLM_LIB . 'slm-debug-logger.php');
require_once(SLM_LIB . 'slm-error-codes.php');
require_once(SLM_LIB . 'slm-init-time-tasks.php');
require_once(SLM_LIB . 'slm-api-listener.php');
require_once(SLM_LIB . 'slm-scripts.php');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-software-license-manager-activator.php
 */
function activate_software_license_manager()
{
    require_once SLM_LIB . 'class-slm-activator.php';
    $slm_activator->activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-software-license-manager-deactivator.php
 */

function deactivate_software_license_manager()
{
    require_once SLM_LIB . 'class-slm-deactivator.php';
    $slm_deactivator->deactivate();
}

function slm_get_license($lic_key_prefix = '')
{
    return strtoupper($lic_key_prefix  . hyphenate(md5(uniqid(rand(4, 10), true) . date('Y-m-d H:i:s') . time())));
}

register_activation_hook(__FILE__, 'activate_software_license_manager');
register_deactivation_hook(__FILE__, 'deactivate_software_license_manager');

// require_once SLM_LIB . 'admin-page-framework.php';

// Front end-menu
// TODO check for optional plugins

// Third Party Support

if (null !== SLM_Helper_Class::slm_get_option('slm_woo') && SLM_Helper_Class::slm_get_option('slm_woo') == 1) {
    /**
     * Check if WooCommerce is activated
     */
    if (is_plugin_active('woocommerce/woocommerce.php')) {
        require_once(SLM_WOO . 'includes/wc_licenses_class.php');
        require_once(SLM_WOO  . 'includes/wc-slm.php');
        // support for meta boxes
        require_once(SLM_WOO . 'includes/slm-meta-boxes.php');
    }
    // build woocommerce tabs
    SLM_Utility::slm_woo_build_tab();
}


if (null !== SLM_Helper_Class::slm_get_option('slm_wpestores') && SLM_Helper_Class::slm_get_option('slm_wpestores') == 1) {
    // wpestores PLugin Integration
    require_once(SLM_ADMIN  . 'includes/wpestores/slm-wpestores.php');
}

//Include admin side only files
if (is_admin()) {
    require_once SLM_ADMIN . 'slm-admin-init.php';
}

//Action hooks
add_action('init', 'slm_init_handler');
add_action('plugins_loaded', 'slm_plugins_loaded_handler');
add_action('wp_ajax_del_reistered_devices', 'slm_del_reg_devices');
add_action('wp_ajax_del_reistered_domain', 'slm_del_reg_dom');
// woo public facing
add_action('wp_ajax_del_activation', 'slm_remove_activation');

//Initialize debug logger
$slm_debug_logger   = new SLM_Debug_Logger();

//Do init time tasks
function slm_init_handler()
{
    $init_task      = new SLM_Init_Time_Tasks();
    $api_listener   = new SLM_API_Listener();
}

//Do plugins loaded time tasks
function slm_plugins_loaded_handler()
{
    //Runs when plugins_loaded action gets fired
    if (is_admin()) {
        //Check if db update needed
        if (get_option('slm_db_version') != SLM_DB_VERSION) {
            require_once(SLM_LIB . 'class-slm-installer.php');
            // TODO - $slm_activator->slm_db_install();
        }
    }
}

class slm_tabbed_plugin
{
    // singleton class variable
    static private $classobj = NULL;

    // singleton method
    public static function get_object()
    {
        if (NULL === self::$classobj) {
            self::$classobj = new self;
        }
        return self::$classobj;
    }

    private function __construct()
    {
    }
}

// initialize plugin
if (function_exists('add_action') && function_exists('register_activation_hook')) {
    add_action('plugins_loaded', array('slm_tabbed_plugin', 'get_object'));
}


//TODO - need to move this to an ajax handler file
function slm_del_reg_dom()
{
    global $wpdb;
    $reg_table  = SLM_TBL_LIC_DOMAIN;
    $id         = strip_tags($_GET['id']);
    $ret        = $wpdb->query($wpdb->prepare("DELETE FROM {$reg_table} WHERE id=%d", $id));
    echo ($ret) ? 'success' : 'failed';
    exit(0);
}

function slm_del_reg_devices()
{
    global $wpdb;
    $reg_table  = SLM_TBL_LIC_DEVICES;
    $id         = strip_tags($_GET['id']);
    $ret        = $wpdb->query($wpdb->prepare("DELETE FROM {$reg_table} WHERE id=%d", $id));
    echo ($ret) ? 'success' : 'failed';
    exit(0);
}

//TODO - need to move this to an ajax handler file
function slm_remove_activation()
{
    global $wpdb;
    $table              = '';
    $id                 = strip_tags($_GET['id']);
    $activation_type    = strip_tags($_GET['activation_type']);

    if ($activation_type == 'Devices') {
        $table = SLM_TBL_LIC_DEVICES;
    } else {
        $table = SLM_TBL_LIC_DOMAIN;
    }

    $ret        = $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE id=%d", $id));
    echo ($ret) ? 'success' : 'failed';
    exit(0);
}

function wc_print_pretty($args)
{
    echo '<pre>';
    print_r($args);
    echo '</pre>';
}

function wc_log($msg)
{
    $log = ABSPATH . DIRECTORY_SEPARATOR . 'slm_log.txt';
    file_put_contents($log, $msg . '', FILE_APPEND);
}
