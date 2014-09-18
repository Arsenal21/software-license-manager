<?php
if(isset($_REQUEST['secret_key']))
{
	include_once('../../../../wp-load.php');
	include_once('../lic_db_access.php');
	
	$right_secret_key =  get_option('wp_lic_mgr_verification_secret_key');

	$received_secret_key = $_REQUEST['secret_key'];
    if ($received_secret_key != $right_secret_key)
    {
        echo "Error\n";
        echo "Invalid verification secret key!\n";
        exit(0);
    }
    $fields = array();
    $fields['lic_key'] = trim($_REQUEST['license_key']);
    $fields['registered_domain'] =  trim($_REQUEST['registered_domain']);//gethostbyaddr($_SERVER['REMOTE_ADDR']);
    
    global $wpdb;
    $tbl_name = WP_LICENSE_MANAGER_LICENSE_TABLE_NAME;
    $reg_table = WP_LICENSE_MANAGER_REG_DOMAIN_TABLE_NAME;
    $key = $fields['lic_key'];
    $retLic = $wpdb->get_row("SELECT * FROM $tbl_name WHERE license_key = '$key'", OBJECT);
    $reg_domains = $wpdb->get_results(" SELECT * FROM $reg_table WHERE lic_key= '$key'", OBJECT );
    if($retLic){
    	if($retLic->lic_status !=='active'){
    		echo "Error\n";
    		echo "License is " .$retLic->lic_status. ".\n";    		
    		exit(0);
    	}
    	if(floor($retLic->max_allowed_domains)>count($reg_domains)){
    		foreach($reg_domains as $reg_domain){
    			if(isset($_REQUEST['migrate_from'])&&(trim($_REQUEST['migrate_from'])==$reg_domain->registered_domain)){
    				$wpdb->update($reg_table, array('registered_domain'=>$fields['registered_domain']),array('registered_domain'=>trim($_REQUEST['migrate_from'])));
    				echo "Success\n";
    				echo "Registered domain has been updated.\n";
    				exit(0);
    			}
    			if($fields['registered_domain'] == $reg_domain->registered_domain){
		            echo "Error\n";
		            echo "License key already in use on ".$reg_domain->registered_domain."\n";
		            exit(0);    				
    			}
    		}
    		$fields['lic_key_id'] = $retLic->id;
    		$wpdb->insert($reg_table, $fields);
            echo "Success\n";
            echo "License key verification passed!\n"; 
            exit(0);   		
    	}
    	else{
	        echo "Error\n";
	        echo "Reached Maximum Allowable Domains!\n";
	        exit(0);    		
    	}     	
    }
    else{
        echo "Error\n";
        echo "Invalid License Key!\n";
    }
}
else
{
    echo "Error\n";
    echo "Verification secret key is required!\n";
}
