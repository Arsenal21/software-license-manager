<?php

class SLM_Init_Time_Tasks {

public function __construct() {
    $this->load_scripts();
    // Add other init time operations here
    add_action('slm_daily_cron_event', array($this, 'slm_daily_cron_event_handler'));
}

// Load common and admin-specific scripts and styles
public function load_scripts() {
    $plugin_version = '1.0.0'; // Replace with your plugin version if available

    wp_enqueue_script('jquery'); // Common scripts

    if (is_admin()) {
        wp_enqueue_script('jquery-ui-datepicker');
        // Enqueue admin-specific JS and add version and load in footer
        wp_enqueue_script('wplm-custom-admin-js', SLM_ASSETS_URL . 'js/wplm-custom-admin.js', array('jquery-ui-dialog'), $plugin_version, true); // true loads in footer
        if (isset($_GET['page']) && $_GET['page'] == 'slm_manage_license') { // Only include if in license management interface
            wp_enqueue_style('jquery-ui-style', SLM_ASSETS_URL . 'css/jquery-ui.css', array(), $plugin_version); // Add version for caching
        }
    }
}

// Daily cron event handler
public function slm_daily_cron_event_handler() {
    $options = get_option('slm_plugin_options');
    do_action('slm_daily_cron_event_triggered');

    if (isset($options['enable_auto_key_expiry']) && $options['enable_auto_key_expiry'] == '1') {
        // Perform auto key expiry task
        SLM_Debug_Logger::log_debug_st("SLM daily cronjob - auto expiry of license key is enabled.");
        SLM_Utility::do_auto_key_expiry();
    }
}
}
