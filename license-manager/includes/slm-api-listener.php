<?php

/*
 * This class listens for API query and executes the API requests
 * Available API Actions
 * 1) slm_create_new
 * 2) slm_activate
 * 3) slm_deactivate
 * 4) slm_check
 */

class SLM_API_Listener
{
    function __construct(){
        
        if(isset($_REQUEST['slm_action']) && isset($_REQUEST['secret_key']))
        {
            //This is an API query for the license manager. Handle the query.
            $this->creation_api_listener();
            $this->activation_api_listener();
            $this->deactivation_api_listener();
            $this->check_api_listener();
        }
    }

    function creation_api_listener()
    {
        //TODO - implement this later
        if(isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) == 'slm_create_new'){
            //Handle the licene creation API query
            
        }
    }
    
    /*
     * Query Parameters
     * 1) slm_action = slm_create_new
     * 2) secret_key
     * 3) license_key
     * 4) registered_domain (optional)
     */
    function activation_api_listener()
    {
        if(isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) == 'slm_activate'){
            //Handle the license activation API query
            $slm_options = get_option('slm_plugin_options');
            $right_secret_key = $slm_options['lic_verification_secret'];
            $received_secret_key = $_REQUEST['secret_key'];
            if ($received_secret_key != $right_secret_key) {
                echo "Error\n";
                echo "Invalid verification secret key!\n";
                exit(0);
            }
            $fields = array();
            $fields['lic_key'] = trim($_REQUEST['license_key']);
            $fields['registered_domain'] = trim($_REQUEST['registered_domain']); //gethostbyaddr($_SERVER['REMOTE_ADDR']);

            global $wpdb;
            $tbl_name = SLM_TBL_LICENSE_KEYS;
            $reg_table = SLM_TBL_LIC_DOMAIN;
            $key = $fields['lic_key'];
            $retLic = $wpdb->get_row("SELECT * FROM $tbl_name WHERE license_key = '$key'", OBJECT);
            $reg_domains = $wpdb->get_results("SELECT * FROM $reg_table WHERE lic_key= '$key'", OBJECT);
            if ($retLic) {
                if ($retLic->lic_status !== 'active') {
                    echo "Error\n";
                    echo "License is " . $retLic->lic_status . ".\n";
                    exit(0);
                }
                if (floor($retLic->max_allowed_domains) > count($reg_domains)) {
                    foreach ($reg_domains as $reg_domain) {
                        if (isset($_REQUEST['migrate_from']) && (trim($_REQUEST['migrate_from']) == $reg_domain->registered_domain)) {
                            $wpdb->update($reg_table, array('registered_domain' => $fields['registered_domain']), array('registered_domain' => trim($_REQUEST['migrate_from'])));
                            echo "Success\n";
                            echo "Registered domain has been updated.\n";
                            exit(0);
                        }
                        if ($fields['registered_domain'] == $reg_domain->registered_domain) {
                            echo "Error\n";
                            echo "License key already in use on " . $reg_domain->registered_domain . "\n";
                            exit(0);
                        }
                    }
                    $fields['lic_key_id'] = $retLic->id;
                    $wpdb->insert($reg_table, $fields);
                    echo "Success\n";
                    echo "License key verification passed!\n";
                    exit(0);
                } else {
                    echo "Error\n";
                    echo "Reached Maximum Allowable Domains!\n";
                    exit(0);
                }
            } else {
                echo "Error\n";
                echo "Invalid License Key!\n";
            }
 
        }
    }
    
    function deactivation_api_listener()
    {
        if(isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) == 'slm_deactivate'){
            //Handle the license deactivation API query
            if (isset($_REQUEST['secret_key'])) {
                $slm_options = get_option('slm_plugin_options');
                $right_secret_key = $slm_options['lic_verification_secret'];
                $received_secret_key = $_REQUEST['secret_key'];
                if ($received_secret_key != $right_secret_key) {
                    echo "Error\n";
                    echo "Secret key is invalid\n";
                    exit;
                }

                if (empty($_REQUEST['license_key'])) {
                    echo "Error\n";
                    echo "License key information is missing.\n";
                    exit;
                }
                if (empty($_REQUEST['registered_domain'])) {
                    echo "Error\n";
                    echo "Registered domain information is missing.\n";
                    exit;
                }
                $registered_domain = trim($_REQUEST['registered_domain']);
                $license_key = trim($_REQUEST['license_key']);
                global $wpdb;
                $registered_dom_table = SLM_TBL_LIC_DOMAIN;
                $where = array('lic_key' => $license_key, 'registered_domain' => $registered_domain);
                $delete = $wpdb->delete($registered_dom_table, $where);
                if($delete === false){
                    //TODO - log the error
                }else if($delete == 0){
                    echo __('The following domain is already inactive for your license license key:', 'slm').'<br />';
                    echo __('License Key - ', 'slm').$license_key.'<br />';
                    echo __('Domain name - ', 'slm').$registered_domain.'<br />';
                    exit(0);
                }else{
                    echo __('Success!', 'slm').'<br />';
                    echo __('The license key has been deactivated for the following domain:', 'slm').'<br />';
                    echo __('License Key - ', 'slm').$license_key.'<br />';
                    echo __('Domain name - ', 'slm').$registered_domain.'<br />';
                    exit(0);
                }

            }            
        }        
        
    }    

    function check_api_listener()
    {
        if(isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) == 'slm_check'){
            //Handle the license check API query
            
        }           
    }  
}