<?php
/**
 * Runs on Uninstall of Software License Manager
 *
 * @package   Software License Manager
 * @author    Michel Velis
 * @license   GPL-2.0+
 * @link      http://epikly.com
 */
class SLM_Activator {

    public static function slm_db_install(){
        //Installer function
        require_once SLM_LIB . 'class-slm-installer.php';
    }

    public static function activate() {
        //Do installer task
        self::slm_db_install();

        //schedule a daily cron event
        wp_schedule_event(time(), 'daily', 'slm_daily_cron_event');
        do_action('slm_activation_complete');
    }
}
$slm_activator = new SLM_Activator();