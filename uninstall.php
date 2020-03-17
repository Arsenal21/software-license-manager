<?php
/**
 * Runs on Uninstall of Software License Manager
 *
 * @package   Software License Manager
 * @author    Michel Velis
 * @license   GPL-2.0+
 * @link      http://epikly.com
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit(); // Exit if accessed directly
}

if (!current_user_can('activate_plugins')) {
    return;
}

global $wpdb;

// Delete Options
$slm_options = array(
    'slm_db_version',
    'slm_plugin_options',
);

foreach ($slm_options as $option) {
    if (get_option($option)) {
        delete_option($option);
    }
}

// Delete Custom Post Type posts
$wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type IN ( 'slm_manage_license' );");
$wpdb->query("DELETE FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE wp.ID IS NULL;");


// Delete Tables
$wpdb->query("DROP TABLE IF EXISTS" . $wpdb->prefix . "lic_key_tbl");
$wpdb->query("DROP TABLE IF EXISTS" . $wpdb->prefix . "lic_reg_domain_tbl");
$wpdb->query("DROP TABLE IF EXISTS" . $wpdb->prefix . "lic_reg_devices_tbl");
$wpdb->query("DROP TABLE IF EXISTS" . $wpdb->prefix . "lic_log_tbl");