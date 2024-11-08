<?php
/**
 * Runs on Uninstall of SLM Plus
 *
 * @package   SLM Plus
 * @author    Michel Velis
 * @license   GPL-2.0+
 * @link      http://epikly.com
 */

// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// Check user permissions
if (!current_user_can('activate_plugins')) {
    return;
}

global $wpdb;

// Delete all related options
$slm_options = array(
    'slm_db_version',
    'slm_plugin_options',
    'slm_lic_creation_secret',
    'slm_backup_dir_hash',
    'slm_woo_affect_downloads',
    'slm_woo_enable_my_licenses_page',
);

foreach ($slm_options as $option) {
    delete_option($option);
}

// Sanitize and list all tables related to the plugin
$tables_to_drop = array(
    $wpdb->prefix . 'lic_key_tbl',
    $wpdb->prefix . 'lic_reg_domain_tbl',
    $wpdb->prefix . 'lic_reg_devices_tbl',
    $wpdb->prefix . 'lic_log_tbl',
    $wpdb->prefix . 'slm_license_status',
    $wpdb->prefix . 'slm_subscribers_tbl',
    $wpdb->prefix . 'slm_activations_tbl',
);

// Drop custom database tables
foreach ($tables_to_drop as $table) {
    $wpdb->query("DROP TABLE IF EXISTS `$table`");
}

// Delete Custom Post Type posts and related metadata
$post_types = array('slm_manage_license', 'slm_license_product'); // Add any other custom post types if needed
foreach ($post_types as $post_type) {
    $wpdb->query(
        $wpdb->prepare(
            "DELETE posts, meta FROM {$wpdb->posts} posts
            LEFT JOIN {$wpdb->postmeta} meta ON posts.ID = meta.post_id
            WHERE posts.post_type = %s",
            $post_type
        )
    );
}

// Clean orphaned postmeta entries
$wpdb->query(
    "DELETE pm FROM {$wpdb->postmeta} pm
    LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    WHERE p.ID IS NULL"
);

// Clean orphaned term relationships if there are custom taxonomies involved
$wpdb->query(
    "DELETE tr FROM {$wpdb->term_relationships} tr
    LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
    WHERE p.ID IS NULL"
);

// Delete custom user meta related to the plugin (if applicable)
$user_meta_keys = array(
    'slm_user_license_data',
    // Add any other related user meta keys here
);

foreach ($user_meta_keys as $meta_key) {
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
            $meta_key
        )
    );
}
