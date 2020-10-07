<?php

/**
 * Runs on Uninstall of Software License Manager
 *
 * @package   Software License Manager
 * @author    Michel Velis
 * @license   GPL-2.0+
 * @link      http://epikly.com
 */
global $wpdb;
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

//***Installer variables***
$lic_key_table      = SLM_TBL_LICENSE_KEYS;
$lic_domain_table   = SLM_TBL_LIC_DOMAIN;
$lic_devices_table  = SLM_TBL_LIC_DEVICES;
$lic_log_tbl        = SLM_TBL_LIC_LOG;
$lic_emails_table   = SLM_TBL_EMAILS;

//***Database version check */
$used_db_version = get_option('slm_db_version', SLM_DB_VERSION);

$charset_collate = '';
if (!empty($wpdb->charset)) {
      $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
} else {
      $charset_collate = "DEFAULT CHARSET=utf8";
}
if (!empty($wpdb->collate)) {
      $charset_collate .= " COLLATE $wpdb->collate";
}

$lk_tbl_sql = "CREATE TABLE " . $lic_key_table . " (
      id int(12) NOT NULL auto_increment,
      license_key varchar(255) NOT NULL,
      max_allowed_domains int(40) NOT NULL,
      max_allowed_devices int(40) NOT NULL,
      lic_status ENUM('pending', 'active', 'blocked', 'expired') NOT NULL DEFAULT 'pending',
      lic_type ENUM('none', 'subscription', 'lifetime') NOT NULL DEFAULT 'subscription',
      first_name varchar(32) NOT NULL default '',
      last_name varchar(32) NOT NULL default '',
      email varchar(64) NOT NULL,
      item_reference varchar(255) NOT NULL,
      company_name varchar(100) NOT NULL default '',
      txn_id varchar(64) NOT NULL default '',
      manual_reset_count varchar(128) NOT NULL default '',
      purchase_id_ varchar(255) NOT NULL default '',
      date_created date NOT NULL DEFAULT '0000-00-00',
      date_activated date NOT NULL DEFAULT '0000-00-00',
      date_renewed date NOT NULL DEFAULT '0000-00-00',
      date_expiry date NOT NULL DEFAULT '0000-00-00',
      reminder_sent varchar(255) NOT NULL default '0',
      reminder_sent_date date NOT NULL DEFAULT '0000-00-00',
      product_ref varchar(255) NOT NULL default '',
      until varchar(255) NOT NULL default '',
      current_ver varchar(255) NOT NULL default '',
      subscr_id varchar(128) NOT NULL default '',
      slm_billing_length varchar(255) NOT NULL,
      slm_billing_interval ENUM('days', 'months', 'years', 'onetime') NOT NULL DEFAULT 'days',
      PRIMARY KEY (id)
      )" . $charset_collate . ";";
dbDelta($lk_tbl_sql);

// // thanks to @MechComp
if (version_compare($used_db_version, '4.1.3', '<=')) {
      $lk_tbl_sql = "
      ALTER TABLE " . $lic_key_table . " ADD item_reference varchar(255) NOT NULL;
      ALTER TABLE " . $lic_key_table . " ADD slm_billing_length varchar(255) NOT NULL;
      ALTER TABLE " . $lic_key_table . " ADD slm_billing_interval ENUM('days', 'months', 'years', 'onetime') NOT NULL DEFAULT 'days',
      ";
      dbDelta($lk_tbl_sql);
}

// set default value for reference item when they are empty
if (version_compare($used_db_version, '4.1.3', '<=')) {
      // get empty values
      $item_result = $wpdb->get_results("SELECT id FROM `$lic_key_table` WHERE `item_reference` LIKE '' ORDER BY id");

      foreach ($item_result as $reference_item) {
            $item_id = $reference_item->id;
            // update and set default value
            $update_reference = "UPDATE $lic_key_table SET item_reference='default' WHERE id='" . $item_id . "';";
            dbDelta($update_reference);
      }
}

$ld_tbl_sql = "CREATE TABLE " . $lic_domain_table . " (
      id INT NOT NULL AUTO_INCREMENT ,
      lic_key_id INT NOT NULL ,
      lic_key varchar(255) NOT NULL ,
      registered_domain text NOT NULL ,
      registered_devices text NOT NULL ,
      item_reference varchar(255) NOT NULL,
      PRIMARY KEY ( id )
      )" . $charset_collate . ";";
dbDelta($ld_tbl_sql);


$slm_emails_tbl = "CREATE TABLE " . $lic_emails_table . " (
      id INT NOT NULL AUTO_INCREMENT ,
      lic_key varchar(255) NOT NULL ,
      sent_to varchar(255) NOT NULL ,
      status varchar(255) NOT NULL ,
      sent text NOT NULL ,
      date_sent date NOT NULL DEFAULT '0000-00-00',
      disable_notifications text NOT NULL ,
      PRIMARY KEY ( id )
      )" . $charset_collate . ";";
dbDelta($slm_emails_tbl);


$log_tbl_sql = "CREATE TABLE " . $lic_log_tbl . " (
      id INT NOT NULL AUTO_INCREMENT ,
      license_key varchar(255) NOT NULL ,
      slm_action varchar(255) NOT NULL ,
      time date NOT NULL DEFAULT '0000-00-00',
      source varchar(255) NOT NULL ,
      PRIMARY KEY ( id )
      )" . $charset_collate . ";";
dbDelta($log_tbl_sql);

$ldv_tbl_sql = "CREATE TABLE " . $lic_devices_table . " (
      id INT NOT NULL AUTO_INCREMENT ,
      lic_key_id INT NOT NULL ,
      lic_key varchar(255) NOT NULL ,
      registered_devices text NOT NULL ,
      registered_domain text NOT NULL ,
      item_reference varchar(255) NOT NULL,
      PRIMARY KEY ( id )
      )" . $charset_collate . ";";
dbDelta($ldv_tbl_sql);

// Add default options
$options = array(
      'lic_creation_secret'     => SLM_Utility::create_secret_keys(),
      'lic_prefix'              => 'SLM-',
      'default_max_domains'     => '2',
      'default_max_devices'     => '2',
      'lic_verification_secret' => SLM_Utility::create_secret_keys(),
      'enable_debug'            => '',
      'slm_woo'                 => '1',
      'slm_woo_downloads'       => '',
      'slm_woo_affect_downloads' => '1',
      'slm_wpestores'           => '',
      'slm_stats'               => '1',
      'slm_adminbar'            => '1',
      'slm_multiple_items'      => '',
      'slm_conflictmode'        => '1',
      'slm_front_conflictmode'  => '1',
      'enable_auto_key_expiration' => '1',
      'slm_dl_manager'          => '',
      'allow_user_activation_removal'  => '1',
      'expiration_reminder_text' => 'Your account has reverted to Basic with limited functionality. Renew today to keep using it on all of your devices and enjoy the valuable features. It’s a smart investment'
);

//Bugfix - Prevention of overwriting existing settings
// thanks to @MechComp
$old_options = get_option('slm_plugin_options');
if ($old_options != false) {
      $options = array_merge($options, $old_options);
}

update_option('slm_plugin_options', $options);
update_option("slm_db_version", SLM_DB_VERSION);
