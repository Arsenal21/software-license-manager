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
        wp_enqueue_style('jquery-ui-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/themes/smoothness/jquery-ui.css');
        //Load all admin side scripts and styles only
        if(is_admin())
        {
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-widget');
            wp_enqueue_script('jquery-ui-position');
            wp_enqueue_script('jquery-ui-mouse');
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_script('thickbox');
            wp_enqueue_style( 'dialogStylesheet', includes_url().'css/jquery-ui-dialog.css' );
            
            wp_enqueue_script('wplm-custom-admin-js', WP_LICENSE_MANAGER_URL . '/js/wplm-custom-admin.js', array( 'jquery-ui-dialog' ));//admin only custom js code
        }        
    }
    
}//End of class