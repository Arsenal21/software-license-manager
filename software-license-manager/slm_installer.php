<?php
//***** Installer *****
global $wpdb;
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

//***Installer variables***
$lic_key_table    = SLM_TBL_LICENSE_KEYS;
$lic_domain_table = SLM_TBL_LIC_DOMAIN;

$charset_collate = '';
if ( ! empty( $wpdb->charset ) ) {
	$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
} else {
	$charset_collate = 'DEFAULT CHARSET=utf8';
}
if ( ! empty( $wpdb->collate ) ) {
	$charset_collate .= " COLLATE $wpdb->collate";
}

$lk_tbl_sql = 'CREATE TABLE ' . $lic_key_table . " (
      id int(12) NOT NULL auto_increment,
      license_key varchar(255) NOT NULL,
      max_allowed_domains int(12) NOT NULL,
      lic_status ENUM('pending', 'active', 'blocked', 'expired') NOT NULL DEFAULT 'pending',
      first_name varchar(32) NOT NULL default '',
      last_name varchar(32) NOT NULL default '',
      email varchar(64) NOT NULL,
      company_name varchar(100) NOT NULL default '',
      txn_id varchar(64) NOT NULL default '',
      manual_reset_count varchar(128) NOT NULL default '',
      date_created date NOT NULL DEFAULT '0000-00-00',
      date_renewed date NOT NULL DEFAULT '0000-00-00',
      date_expiry date NOT NULL DEFAULT '0000-00-00',
      product_ref varchar(255) NOT NULL default '',
      subscr_id varchar(128) NOT NULL default '',
      user_ref varchar(255) NOT NULL default '',
      PRIMARY KEY (id),
      KEY `max_allowed_domains` (`max_allowed_domains`)
      )" . $charset_collate . ';';
dbDelta( $lk_tbl_sql );

$ld_tbl_sql = 'CREATE TABLE ' . $lic_domain_table . ' (
      id INT NOT NULL AUTO_INCREMENT ,
      lic_key_id INT NOT NULL ,
      lic_key varchar(255) NOT NULL ,
      registered_domain text NOT NULL ,
      item_reference varchar(255) NOT NULL,
      PRIMARY KEY ( id ),
      KEY `lic_key_id` (`lic_key_id`)
      )' . $charset_collate . ';';
dbDelta( $ld_tbl_sql );

update_option( 'wp_lic_mgr_db_version', WP_LICENSE_MANAGER_DB_VERSION );

// Add default options
$options = array(
	'lic_creation_secret'     => uniqid( '', true ),
	'lic_prefix'              => '',
	'default_max_domains'     => '1',
	'lic_verification_secret' => uniqid( '', true ),
	'enable_debug'            => '',
);
add_option( 'slm_plugin_options', $options );
