<?php
//***** Installer *****
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');	

//***Installer variables***
$wp_lic_mgr_db_version = "1.2";
global $wpdb;
$lic_key_table = $wpdb->prefix . "lic_key_tbl";
$lic_domain_table = $wpdb->prefix . "lic_reg_domain_tbl";
//***Installer***
if($wpdb->get_var("SHOW TABLES LIKE '$lic_key_table'") != $lic_key_table)
{
   $sql = "CREATE TABLE " . $lic_key_table . " (
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
         PRIMARY KEY (id)
      )ENGINE=MyISAM DEFAULT CHARSET=utf8;";
   dbDelta($sql);
   // Add default options
   add_option("wp_lic_mgr_db_version", $wp_lic_mgr_db_version);
}

if($wpdb->get_var("SHOW TABLES LIKE '$lic_domain_table'") != $lic_domain_table)
{
   $sql = "CREATE TABLE " .$lic_domain_table. " (
		 id INT NOT NULL AUTO_INCREMENT ,
		 lic_key_id INT NOT NULL ,
		 lic_key varchar(255) NOT NULL ,
		 registered_domain VARCHAR( 100 ) NOT NULL ,
		 PRIMARY KEY ( id )
	  )ENGINE=MyISAM DEFAULT CHARSET=utf8;";
   dbDelta($sql);
   // Add default options
   add_option("wp_lic_mgr_db_version", $wp_lic_mgr_db_version);
}

//***Upgrader***
$installed_ver = get_option( "wp_lic_mgr_db_version" );
if( $installed_ver != $wp_lic_mgr_db_version )
{
   $sql = "CREATE TABLE " . $lic_key_table . " (
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
         PRIMARY KEY (id)
      )ENGINE=MyISAM DEFAULT CHARSET=utf8;";
   dbDelta($sql);
   $sql = "CREATE TABLE " .$lic_domain_table. " (
		 id INT NOT NULL AUTO_INCREMENT ,
		 lic_key_id INT NOT NULL ,
		 lic_key varchar(255) NOT NULL ,
		 registered_domain VARCHAR( 100 ) NOT NULL ,
		 PRIMARY KEY ( id )
	  )ENGINE=MyISAM DEFAULT CHARSET=utf8;";
   dbDelta($sql);
   
   // Add default options for update
   update_option("wp_lic_mgr_db_version", $wp_lic_mgr_db_version);
}
?>