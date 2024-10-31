<?php

/**
 * Runs on Installation of SLM Plus
 *
 * @package   SLM Plus
 * @author    Michel Velis
 * @license   GPL-2.0+
 * @link      http://epikly.com
 */
global $wpdb;
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

// Define table names
$lic_key_table      = SLM_TBL_LICENSE_KEYS;
$lic_domain_table   = SLM_TBL_LIC_DOMAIN;
$lic_devices_table  = SLM_TBL_LIC_DEVICES;
$lic_log_tbl        = SLM_TBL_LIC_LOG;
$lic_emails_table   = SLM_TBL_EMAILS;
$lic_status_table   = SLM_TBL_LICENSE_STATUS; // New Status Table

// Check the current database version
$used_db_version = get_option('slm_db_version', SLM_DB_VERSION);

// Set charset and collation for database tables
$charset_collate = $wpdb->get_charset_collate();

// Create license statuses table if it doesn't exist
$status_table_sql = "CREATE TABLE IF NOT EXISTS " . $lic_status_table . " (
      id INT NOT NULL AUTO_INCREMENT,
      status_key VARCHAR(255) NOT NULL,
      status_label VARCHAR(255) NOT NULL,
      PRIMARY KEY (id)
)" . $charset_collate . ";";
dbDelta($status_table_sql);

// Insert default statuses if table is empty
$status_count = $wpdb->get_var("SELECT COUNT(*) FROM $lic_status_table");
if ($status_count == 0) {
    $default_statuses = array(
        array('status_key' => 'pending', 'status_label' => __('Pending', 'slmplus')),
        array('status_key' => 'active', 'status_label' => __('Active', 'slmplus')),
        array('status_key' => 'blocked', 'status_label' => __('Blocked', 'slmplus')),
        array('status_key' => 'expired', 'status_label' => __('Expired', 'slmplus'))
    );

    foreach ($default_statuses as $status) {
        $wpdb->insert($lic_status_table, $status);
    }
}

// Create or update the license keys table structure
$lk_tbl_sql = "CREATE TABLE IF NOT EXISTS " . $lic_key_table . " (
    id int(12) NOT NULL AUTO_INCREMENT,
    license_key varchar(255) NOT NULL,
    max_allowed_domains int(40) NOT NULL,
    max_allowed_devices int(40) NOT NULL,
    lic_status varchar(255) NOT NULL DEFAULT 'pending',  /* Store status_key here */
    lic_type ENUM('none', 'subscription', 'lifetime') NOT NULL DEFAULT 'subscription',
    first_name varchar(32) NOT NULL DEFAULT '',
    last_name varchar(32) NOT NULL DEFAULT '',
    email varchar(64) NOT NULL,
    item_reference varchar(255) NOT NULL,
    company_name varchar(100) NOT NULL DEFAULT '',
    txn_id varchar(64) NOT NULL DEFAULT '',
    manual_reset_count varchar(128) NOT NULL DEFAULT '',
    purchase_id_ varchar(255) NOT NULL DEFAULT '',
    date_created date NOT NULL DEFAULT '0000-00-00',
    date_activated date NOT NULL DEFAULT '0000-00-00',
    date_renewed date NOT NULL DEFAULT '0000-00-00',
    date_expiry date NOT NULL DEFAULT '0000-00-00',
    reminder_sent varchar(255) NOT NULL DEFAULT '0',
    reminder_sent_date date NOT NULL DEFAULT '0000-00-00',
    product_ref varchar(255) NOT NULL DEFAULT '',
    until varchar(255) NOT NULL DEFAULT '',
    current_ver varchar(255) NOT NULL DEFAULT '',
    subscr_id varchar(128) NOT NULL DEFAULT '',
    slm_billing_length varchar(255) NOT NULL,
    slm_billing_interval ENUM('days', 'months', 'years', 'onetime') NOT NULL DEFAULT 'days',
    PRIMARY KEY (id)
)" . $charset_collate . ";";

dbDelta($lk_tbl_sql);

// Handle backward compatibility for version 4.1.3 or earlier
if (version_compare($used_db_version, '4.1.3', '<=')) {
    // Alter the table if needed
    $lk_tbl_sql = "
    ALTER TABLE $lic_key_table
    ADD COLUMN IF NOT EXISTS item_reference varchar(255) NOT NULL,
    ADD COLUMN IF NOT EXISTS slm_billing_length varchar(255) NOT NULL,
    ADD COLUMN IF NOT EXISTS slm_billing_interval ENUM('days', 'months', 'years', 'onetime') NOT NULL DEFAULT 'days';
    ";
    dbDelta($lk_tbl_sql);
}

// Create domains table if not exists
$ld_tbl_sql = "CREATE TABLE IF NOT EXISTS " . $lic_domain_table . " (
      id INT NOT NULL AUTO_INCREMENT,
      lic_key_id INT NOT NULL,
      lic_key varchar(255) NOT NULL,
      registered_domain text NOT NULL,
      registered_devices text NOT NULL,
      item_reference varchar(255) NOT NULL,
      PRIMARY KEY (id)
)" . $charset_collate . ";";
dbDelta($ld_tbl_sql);

// Create emails table if not exists
$slm_emails_tbl = "CREATE TABLE IF NOT EXISTS " . $lic_emails_table . " (
      id INT NOT NULL AUTO_INCREMENT,
      lic_key varchar(255) NOT NULL,
      sent_to varchar(255) NOT NULL,
      status varchar(255) NOT NULL,
      sent text NOT NULL,
      date_sent date NOT NULL DEFAULT '0000-00-00',
      disable_notifications text NOT NULL,
      PRIMARY KEY (id)
)" . $charset_collate . ";";
dbDelta($slm_emails_tbl);

// Create log table if not exists
$log_tbl_sql = "CREATE TABLE IF NOT EXISTS " . $lic_log_tbl . " (
      id INT NOT NULL AUTO_INCREMENT,
      license_key varchar(255) NOT NULL,
      slm_action varchar(255) NOT NULL,
      time date NOT NULL DEFAULT '0000-00-00',
      source varchar(255) NOT NULL,
      PRIMARY KEY (id)
)" . $charset_collate . ";";
dbDelta($log_tbl_sql);

// Create devices table if not exists
$ldv_tbl_sql = "CREATE TABLE IF NOT EXISTS " . $lic_devices_table . " (
      id INT NOT NULL AUTO_INCREMENT,
      lic_key_id INT NOT NULL,
      lic_key varchar(255) NOT NULL,
      registered_devices text NOT NULL,
      registered_domain text NOT NULL,
      item_reference varchar(255) NOT NULL,
      PRIMARY KEY (id)
)" . $charset_collate . ";";
dbDelta($ldv_tbl_sql);

// Add new options if they don't exist and preserve old settings
$new_options = array(
    'lic_creation_secret' => SLM_Utility::create_secret_keys(),
    'lic_verification_secret' => SLM_Utility::create_secret_keys(),
    'lic_prefix' => 'SLM-',
    'default_max_domains' => '2',
    'default_max_devices' => '2',
    'enable_debug' => '',
    'slm_woo' => '1',
    'license_current_version' => '1.0.0',
    'license_until_version' => '2.1.0',
    'slm_wc_lic_generator' => '1',
    'slm_woo_downloads' => '',
    'slm_woo_affect_downloads' => '1',
    'slm_wpestores' => '',
    'slm_stats' => '1',
    'slm_billing_length' => '1',
    'slm_billing_interval' => 'years',
    'slm_adminbar' => '1',
    'slm_multiple_items' => '',
    'slm_conflictmode' => '1',
    'slm_front_conflictmode' => '1',
    'enable_auto_key_expiration' => '1',
    'slm_backup_dir_hash' => '/slm-plus-' . wp_generate_password(8, false, false),
    'slm_dl_manager' => '',
    'allow_user_activation_removal' => '1',
    'expiration_reminder_text' => 'Your account has reverted to Basic with limited functionality. Renew today to keep using it on all of your devices and enjoy the valuable features. It is a smart investment.'
);

// Retrieve existing options to merge them with the new ones
$existing_options = get_option('slm_plugin_options', []);
$merged_options = array_merge($new_options, $existing_options);

// Update options with merged settings
update_option('slm_plugin_options', $merged_options);

// Create the backup directory if it doesn't already exist
$upload_dir = wp_upload_dir(); // Get the WordPress upload directory
$backup_dir_path = $upload_dir['basedir'] . $merged_options['slm_backup_dir_hash'];

// Check if the directory exists, and if not, create it
if (!file_exists($backup_dir_path)) {
    wp_mkdir_p($backup_dir_path);
}

// Update the database version
update_option("slm_db_version", SLM_DB_VERSION);
