<?php


// ---- ---- ----
// A. Define a cron job interval if it doesn't exist

add_filter('cron_schedules', 'slm_check_every_minute');

function slm_check_every_minute($schedules)
{
    $schedules['every_minute'] = array(
        'interval' => 1*60,
        'display'  => __('Every 1 minute'),
    );
    return $schedules;
}


// send automatic scheduled email
if (!wp_next_scheduled('slm_expired_send_email_reminder')) {
    wp_schedule_event(time(), 'every_minute', 'slm_expired_send_email_reminder');
}
add_action('slm_expired_send_email_reminder', 'run_slm_lic_check');


function run_slm_lic_check(){
    SLM_Utility::check_for_expired_lic();
}