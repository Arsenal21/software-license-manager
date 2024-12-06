<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

/**
 *
 * @package   SLM Plus
 * @author    Michel Velis
 * @license   GPL-2.0+
 * @link      http://epikly.com
 */


//Includes - utilities and cron jobs
include_once(ABSPATH . 'wp-admin/includes/plugin.php');
require_once(SLM_LIB . 'slm-utility.php');
require_once(SLM_CRONS . 'slm-tasks.php');

// Includes for essential plugin components
require_once(SLM_LIB . 'slm-debug-logger.php');
require_once(SLM_LIB . 'slm-error-codes.php');
require_once(SLM_LIB . 'slm-init-time-tasks.php');
require_once(SLM_LIB . 'slm-api-listener.php');
require_once(SLM_LIB . 'slm-scripts.php');
require_once(SLM_LIB . 'slm-shortcodes.php');
require_once(SLM_LIB . 'slm-blocks.php');

// Admin-only includes
if (is_admin()) {
    require_once SLM_ADMIN . 'slm-admin-init.php';
}

if (!function_exists('hyphenate')) {
    function hyphenate($str)
    {
        return implode("-", str_split($str, 5));
    }
}


// WP eStores integration
if (SLM_Helper_Class::slm_get_option('slm_wpestores') == 1) {
    // require_once(SLM_ADMIN . 'includes/wpestores/slm-wpestores.php');
}

// Activation and deactivation hooks
function activate_slm_plus()
{
    require_once SLM_LIB . 'class-slm-activator.php';
    $slm_activator->activate();
}

function deactivate_slm_plus()
{
    require_once SLM_LIB . 'class-slm-deactivator.php';
    $slm_deactivator->deactivate();
}

register_activation_hook(__FILE__, 'activate_slm_plus');
register_deactivation_hook(__FILE__, 'deactivate_slm_plus');

// Improved and Shortened License Key Generator Function
function slm_get_license($lic_key_prefix = '')
{
    global $wpdb;
    $max_retries = 5; // Set the maximum number of retries
    $retry_count = 0;
    $license_key = '';

    // Use the constant to define the license table
    $license_table = SLM_TBL_LICENSE_KEYS;

    // Generate a unique license key
    while ($retry_count < $max_retries) {
        // Generate a strong, random base using random_int() and uniqid
        $random_base = uniqid(random_int(1000, 9999), true);

        // Combine random base with the current GMT date and time and the Unix timestamp for additional uniqueness
        $combined_string = $random_base . gmdate('Y-m-d H:i:s') . time();

        // Create a sha256 hash of the combined string
        $hashed_string = substr(hash('sha256', $combined_string), 0, 32); // Take first 32 characters of the sha256 hash

        // Ensure the prefix is added correctly
        $license_key = strtoupper($hashed_string);

        // Add dashes every 4 characters
        $license_key_with_dashes = implode('-', str_split($license_key, 4));

        // Add the prefix to the formatted key
        $license_key_with_prefix = $lic_key_prefix . $license_key_with_dashes;

        // Check if the generated license key already exists in the database
        $existing_license = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $license_table WHERE license_key = %s", $license_key_with_prefix));

        // If the license doesn't exist, break out of the loop and return the key
        if ($existing_license == 0) {
            return $license_key_with_prefix;
        }

        // If the license already exists, increment the retry count and try again
        $retry_count++;
    }

    // If we exceed the retry limit, return false or handle the error as needed
    error_log('Failed to generate a unique license key after ' . $max_retries . ' attempts.');
    return false;
}


// Example hyphenate function (assuming hyphenates every 4 characters)
function hyphenate($string)
{
    return implode('-', str_split($string, 4)); // This splits the string into chunks of 4 characters and adds hyphens
}

// Action hooks
add_action('init', 'slmplus_init_handler');
add_action('plugins_loaded', 'slmplus_plugins_loaded_handler');
add_action('wp_ajax_del_registered_devices', 'slmplus_del_registered_devices');
add_action('wp_ajax_del_registered_domain', 'slmplus_del_registered_domain');
add_action('wp_ajax_del_activation', 'slmplus_remove_activation');
// Initialize plugin on plugins_loaded action
add_action('plugins_loaded', array('SLM_Tabbed_Plugin', 'get_object'));

// Initialize debug logger
$slm_debug_logger = new SLM_Debug_Logger();

// Init-time tasks
function slmplus_init_handler()
{
    $init_task = new SLM_Init_Time_Tasks();
    $api_listener = new SLM_API_Listener();
}

// Plugins loaded tasks
function slmplus_plugins_loaded_handler()
{
    if (is_admin() && get_option('slm_db_version') != SLM_DB_VERSION) {
        require_once(SLM_LIB . 'class-slm-installer.php');
        // TODO - Implement DB update logic here
    }
}

// Singleton pattern for the plugin
class SLM_Tabbed_Plugin
{
    private static $classobj = NULL;

    public static function get_object()
    {
        if (self::$classobj === NULL) {
            self::$classobj = new self();
        }
        return self::$classobj;
    }

    private function __construct() {}
}

// AJAX handlers
function slmplus_del_registered_domain()
{
    global $wpdb;
    $id = strip_tags($_GET['id']);
    $ret = $wpdb->query($wpdb->prepare("DELETE FROM " . SLM_TBL_LIC_DOMAIN . " WHERE id = %d", $id));
    echo ($ret) ? 'success' : 'failed';
    exit;
}

function slmplus_del_registered_devices()
{
    global $wpdb;
    $id = strip_tags($_GET['id']);
    $ret = $wpdb->query($wpdb->prepare("DELETE FROM " . SLM_TBL_LIC_DEVICES . " WHERE id = %d", $id));
    echo ($ret) ? 'success' : 'failed';
    exit;
}

function slmplus_remove_activation()
{
    global $wpdb;
    $id = strip_tags($_GET['id']);
    $activation_type = strip_tags($_GET['activation_type']);

    $table = ($activation_type == 'Devices') ? SLM_TBL_LIC_DEVICES : SLM_TBL_LIC_DOMAIN;
    $ret = $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE id = %d", $id));
    echo ($ret) ? 'success' : 'failed';
    exit;
}

// Debugging functions
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

// WooCommerce integration
if (SLM_Helper_Class::slm_get_option('slm_woo') == 1 && is_plugin_active('woocommerce/woocommerce.php')) {
    require_once(SLM_WOO . 'includes/wc_licenses_class.php');
    require_once(SLM_WOO . 'includes/slm-meta-boxes.php');
    require_once SLM_WOO . 'includes/register-template.php';
    require_once SLM_WOO . 'includes/purchase.php';
    require_once SLM_WOO . 'includes/create-license-orders.php';
    require_once SLM_WOO . 'includes/hooks/license-checkout-hooks.php';
    // Build WooCommerce tabs
    SLM_Utility::slm_woo_build_tab();
}

add_action('wp_ajax_slm_user_search', 'slm_user_search');
function slm_user_search()
{
    global $wpdb;

    $value = sanitize_text_field($_POST['value']);

    // Direct SQL Query to improve performance
    $query = $wpdb->prepare(
        "
        SELECT u.ID, u.display_name, u.user_email, 
            IFNULL(um_first_name.meta_value, um_billing_first_name.meta_value) AS first_name,
            IFNULL(um_last_name.meta_value, um_billing_last_name.meta_value) AS last_name
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->prefix}usermeta um_first_name ON um_first_name.user_id = u.ID AND um_first_name.meta_key = 'first_name'
        LEFT JOIN {$wpdb->prefix}usermeta um_last_name ON um_last_name.user_id = u.ID AND um_last_name.meta_key = 'last_name'
        LEFT JOIN {$wpdb->prefix}usermeta um_billing_first_name ON um_billing_first_name.user_id = u.ID AND um_billing_first_name.meta_key = 'billing_first_name'
        LEFT JOIN {$wpdb->prefix}usermeta um_billing_last_name ON um_billing_last_name.user_id = u.ID AND um_billing_last_name.meta_key = 'billing_last_name'
        WHERE (um_first_name.meta_value LIKE %s OR um_last_name.meta_value LIKE %s OR um_billing_first_name.meta_value LIKE %s OR um_billing_last_name.meta_value LIKE %s)
        LIMIT 10
        ",
        '%' . $wpdb->esc_like($value) . '%',
        '%' . $wpdb->esc_like($value) . '%',
        '%' . $wpdb->esc_like($value) . '%',
        '%' . $wpdb->esc_like($value) . '%'
    );

    $users = $wpdb->get_results($query);
    $results = [];

    foreach ($users as $user) {
        $results[] = [
            'ID'           => $user->ID,
            'display_name' => $user->display_name ?: "{$user->first_name} {$user->last_name}",
            'first_name'   => $user->first_name,
            'last_name'    => $user->last_name,
            'email'        => $user->user_email,
            'company_name' => get_user_meta($user->ID, 'company_name', true),
            'subscr_id'    => $user->ID, // Pass user ID as the subscription ID
        ];
    }

    wp_send_json_success($results);
}
