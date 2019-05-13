<?php

class SLM_Init_Time_Tasks{
    function __construct(){
        $this->load_scripts();
        //Add other init time operations here
        add_action ('slm_daily_cron_event', array(&$this, 'slm_daily_cron_event_handler'));
    }

    function load_scripts(){
        //Load all common scripts and styles only
        wp_enqueue_script('jquery');
        //Load all admin side scripts and styles only
        if(is_admin()){
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_script('wplm-custom-admin-js', SLM_ASSETS_URL . 'js/wplm-custom-admin.js', array( 'jquery-ui-dialog' ));//admin only custom js code
            if (isset($_GET['page']) && $_GET['page'] == 'slm_manage_license') {//Only include if we are in the license add/edit interface
                wp_enqueue_style('jquery-ui-style', SLM_ASSETS_URL .'css/jquery-ui.css');
            }
        }
    }

    function slm_daily_cron_event_handler(){
        $options = get_option('slm_plugin_options');
        do_action('slm_daily_cron_event_triggered');
        if ( isset($options['enable_auto_key_expiry']) && $options['enable_auto_key_expiry'] == '1'){
            //Do the auto key expiry task
            SLM_Debug_Logger::log_debug_st("SLM daily cronjob - auto expiry of license key is enabled.");
            SLM_Utility::do_auto_key_expiry();
        }
        //Do any ohter daily cronjob tasks.
    }
}