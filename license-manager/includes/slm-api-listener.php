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
            //Handle the licene activation API query
            
        }
    }
    
    function deactivation_api_listener()
    {
        if(isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) == 'slm_deactivate'){
            //Handle the licene deactivation API query
            
        }        
        
    }    

    function check_api_listener()
    {
        if(isset($_REQUEST['slm_action']) && trim($_REQUEST['slm_action']) == 'slm_check'){
            //Handle the licene check API query
            
        }           
    }  
}