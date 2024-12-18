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

// Set charset and collation for database tables
$charset_collate = $wpdb->get_charset_collate();

$used_db_version = get_option('slm_db_version', '5.0.0');

// Check the current database version
$new_db_version = SLM_DB_VERSION;

// Table definitions
$lic_key_table = SLM_TBL_LICENSE_KEYS;

// Ensure backward compatibility updates are applied
if (version_compare($used_db_version, $new_db_version, '<')) {
    error_log("SLM: Starting database updates from version $used_db_version to $new_db_version.");

    // Check if the 'associated_orders' column exists
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $lic_key_table LIKE 'associated_orders'");

    if (empty($column_exists)) {
        error_log("SLM: Adding missing column 'associated_orders' to $lic_key_table.");

        // Add missing columns to the license keys table
        $lk_tbl_sql_mod = "
        ALTER TABLE $lic_key_table
        ADD COLUMN associated_orders TEXT DEFAULT NULL;
        ";

        $result = $wpdb->query($lk_tbl_sql_mod);

        if ($result === false) {
            error_log("SLM: Error adding 'associated_orders' column - " . $wpdb->last_error);
        } else {
            error_log("SLM: 'associated_orders' column added successfully.");
        }
    } else {
        error_log("SLM: Column 'associated_orders' already exists in $lic_key_table.");
    }

    // Add other missing columns (if required)
    $other_columns = array(
        'item_reference' => "VARCHAR(255) NOT NULL",
        'slm_billing_length' => "VARCHAR(255) NOT NULL",
        'slm_billing_interval' => "ENUM('days', 'months', 'years', 'onetime') NOT NULL DEFAULT 'days'",
        'wc_order_id' => "INT DEFAULT NULL",
        'payment_status' => "ENUM('pending', 'completed', 'failed') DEFAULT 'pending'",
        'renewal_attempts' => "INT DEFAULT 0",
        'lic_status' => "ENUM('pending', 'active', 'expired', 'suspended', 'blocked', 'trial') NOT NULL DEFAULT 'pending'"
    );

    foreach ($other_columns as $column => $definition) {
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $lic_key_table LIKE '$column'");
        if (empty($column_exists)) {
            // Add missing column
            $alter_query = "ALTER TABLE $lic_key_table ADD COLUMN $column $definition;";
            error_log("SLM: Adding missing column '$column' to $lic_key_table.");
            $result = $wpdb->query($alter_query);

            if ($result === false) {
                error_log("SLM: Error adding column '$column' - " . $wpdb->last_error);
            } else {
                error_log("SLM: Column '$column' added successfully.");
            }
        } else {
            // Check if the column definition needs to be updated (for example, updating lic_status ENUM values)
            $column_info = $wpdb->get_row("SHOW COLUMNS FROM $lic_key_table LIKE '$column'");
            if ($column === 'lic_status' && strpos($column_info->Type, "'trial'") === false) {
                // Update lic_status to include the new ENUM values
                $alter_query = "ALTER TABLE $lic_key_table MODIFY COLUMN $column $definition;";
                error_log("SLM: Updating column '$column' in $lic_key_table.");
                $result = $wpdb->query($alter_query);

                if ($result === false) {
                    error_log("SLM: Error updating column '$column' - " . $wpdb->last_error);
                } else {
                    error_log("SLM: Column '$column' updated successfully.");
                }
            } else {
                error_log("SLM: Column '$column' already exists and does not need updates.");
            }
        }
    }

    // Update the database version
    update_option("slm_db_version", $new_db_version);
    error_log("SLM database updated from version $used_db_version to $new_db_version.");
} else {
    error_log("SLM: No updates needed for backward compatibility. Current version: $used_db_version.");
}


// Create license statuses table if it doesn't exist
$status_table_sql = "CREATE TABLE IF NOT EXISTS " . $lic_status_table . " (
    id INT(11) NOT NULL AUTO_INCREMENT,
    status_key VARCHAR(255) NOT NULL,
    status_label VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_status_key (status_key)
) " . $charset_collate . ";";
dbDelta($status_table_sql);


// Insert default statuses if table is empty
$status_count = $wpdb->get_var("SELECT COUNT(*) FROM $lic_status_table");
if ($status_count == 0) {
    $default_statuses = array(
        array('status_key' => 'pending', 'status_label' => __('Pending', 'slm-plus')),
        array('status_key' => 'active', 'status_label' => __('Active', 'slm-plus')),
        array('status_key' => 'blocked', 'status_label' => __('Blocked', 'slm-plus')),
        array('status_key' => 'trial', 'status_label' => __('Trial', 'slm-plus')),
        array('status_key' => 'expired', 'status_label' => __('Expired', 'slm-plus'))
    );

    foreach ($default_statuses as $status) {
        $wpdb->insert($lic_status_table, $status);
    }
}

// Create or update the license keys table structure
$lk_tbl_sql = "CREATE TABLE IF NOT EXISTS " . $lic_key_table . " (
    id INT(12) NOT NULL AUTO_INCREMENT,
    license_key VARCHAR(255) NOT NULL,
    item_reference VARCHAR(255) NOT NULL,
    product_ref VARCHAR(255) NOT NULL DEFAULT '',
    subscr_id VARCHAR(128) NOT NULL DEFAULT '',
    txn_id VARCHAR(64) NOT NULL DEFAULT '',
    purchase_id_ VARCHAR(255) NOT NULL DEFAULT '',
    wc_order_id INT DEFAULT NULL,
    associated_orders TEXT DEFAULT NULL,
    first_name VARCHAR(32) NOT NULL DEFAULT '',
    last_name VARCHAR(32) NOT NULL DEFAULT '',
    email VARCHAR(64) NOT NULL,
    company_name VARCHAR(100) NOT NULL DEFAULT '',
    lic_status ENUM('pending', 'active', 'expired', 'suspended', 'blocked', 'trial') NOT NULL DEFAULT 'pending',
    lic_type ENUM('none', 'subscription', 'lifetime') NOT NULL DEFAULT 'subscription',
    max_allowed_domains INT NOT NULL,
    max_allowed_devices INT NOT NULL,
    manual_reset_count VARCHAR(128) NOT NULL DEFAULT '',
    current_ver VARCHAR(255) NOT NULL DEFAULT '',
    until VARCHAR(255) NOT NULL DEFAULT '',
    slm_billing_length VARCHAR(255) NOT NULL,
    slm_billing_interval ENUM('days', 'months', 'years', 'onetime') NOT NULL DEFAULT 'days',
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    renewal_attempts INT DEFAULT 0,
    date_created DATE NOT NULL DEFAULT '0000-00-00',
    date_activated DATE NOT NULL DEFAULT '0000-00-00',
    date_renewed DATE NOT NULL DEFAULT '0000-00-00',
    date_expiry DATE NOT NULL DEFAULT '0000-00-00',
    reminder_sent_date DATE NOT NULL DEFAULT '0000-00-00',
    reminder_sent VARCHAR(255) NOT NULL DEFAULT '0',
    PRIMARY KEY (id)
) " . $charset_collate . ";";


dbDelta($lk_tbl_sql);

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
    'expiration_reminder_text' => 'Your account has reverted to Basic with limited functionality. You can renew today to keep using it on all your devices and enjoy the valuable features. It is a smart investment.'
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

