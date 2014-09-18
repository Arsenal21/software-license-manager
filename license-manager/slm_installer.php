<?php
//***** Installer *****
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');	

//***Installer variables***
$lic_key_table = SLM_TBL_LICENSE_KEYS;
$lic_domain_table = SLM_TBL_LIC_DOMAIN;

$lk_tbl_sql = "CREATE TABLE " . $lic_key_table . " (
      id int(12) NOT NULL auto_increment,
      license_key varchar(255) NOT NULL,
      max_allowed_domains int(12) NOT NULL,
      lic_status ENUM('active', 'blocked', 'expired') NOT NULL DEFAULT 'active',         
      first_name varchar(32) NOT NULL default '',
      last_name varchar(32) NOT NULL default '',
      email varchar(64) NOT NULL,
      company_name varchar(100) NOT NULL default '',
      txn_id varchar(64) NOT NULL default '',
      manual_reset_count varchar(128) NOT NULL default '',
      date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
      date_renewed datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
      date_expiry datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
      PRIMARY KEY (id)
      )ENGINE=MyISAM DEFAULT CHARSET=utf8;";
dbDelta($lk_tbl_sql);

$ld_tbl_sql = "CREATE TABLE " .$lic_domain_table. " (
      id INT NOT NULL AUTO_INCREMENT ,
      lic_key_id INT NOT NULL ,
      lic_key varchar(255) NOT NULL ,
      registered_domain VARCHAR( 100 ) NOT NULL ,
      PRIMARY KEY ( id )
      )ENGINE=MyISAM DEFAULT CHARSET=utf8;";
dbDelta($ld_tbl_sql);

// Add default options
update_option("wp_lic_mgr_db_version", WP_LICENSE_MANAGER_DB_VERSION);

