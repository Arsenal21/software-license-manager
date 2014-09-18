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

            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-widget');
            wp_enqueue_script('jquery-ui-position');
            wp_enqueue_script('jquery-ui-mouse');
            wp_enqueue_script('jquery-ui-dialog');
        }        
    }
    
}//End of class