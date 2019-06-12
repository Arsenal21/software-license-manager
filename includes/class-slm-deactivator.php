<?php
/**
 * Runs on Uninstall of Software License Manager
 *
 * @package   Software License Manager
 * @author    Michel Velis
 * @license   GPL-2.0+
 * @link      http://epikly.com
 */
//Deactivation handler
class SLM_Deactivator {
    public static function deactivate() {
        //Clear the daily cron event
        wp_clear_scheduled_hook('slm_daily_cron_event');
        do_action('slm_deactivation_complete');
    }
}
$slm_deactivator = new SLM_Deactivator();