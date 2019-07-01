<?php

add_filter('cron_schedules', 'slm_check_expiration_daily');
add_action('slm_expired_send_email_reminder', 'slm_run_lic_check');

// for dev
function slm_check_expiration_daily($schedules){
    $schedules['slm_daily'] = array(
        'interval' => 21600*4,
        'display'  => __('Every day'),
    );
    return $schedules;
}

// send automatic scheduled email
if (!wp_next_scheduled('slm_expired_send_email_reminder')) {
    wp_schedule_event(time(), 'slm_daily', 'slm_expired_send_email_reminder');
}

function slm_run_lic_check(){
    SLM_Utility::check_for_expired_lic();
}


