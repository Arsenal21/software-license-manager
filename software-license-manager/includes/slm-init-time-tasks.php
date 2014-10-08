<?php

class SLM_Init_Time_Tasks{
    
    function __construct(){
        $this->load_scripts();
        //Add other init time operations here        
        
    }
    
    function load_scripts()
    {
        //Load all common scripts and styles only
        wp_enqueue_script('jquery');

        //Load all admin side scripts and styles only
        if(is_admin())
        {
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_script('wplm-custom-admin-js', WP_LICENSE_MANAGER_URL . '/js/wplm-custom-admin.js', array( 'jquery-ui-dialog' ));//admin only custom js code
            
            if (isset($_GET['page']) && $_GET['page'] == 'wp_lic_mgr_addedit') {//Only include if we are in the license add/edit interface
                wp_enqueue_style('jquery-ui-style', WP_LICENSE_MANAGER_URL .'/css/jquery-ui.css');
            }
            //wp_enqueue_style('dialogStylesheet', includes_url().'css/jquery-ui-dialog.css');            
        }        
    }
    
}//End of class