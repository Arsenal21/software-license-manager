<?php
/**
 * Runs on Uninstall of Software License Manager
 *
 * @package   Software License Manager
 * @author    Michel Velis
 * @license   GPL-2.0+
 * @link      http://epikly.com
 */
class Software_License_Manager_Activator {

    public static function slm_db_install{
        //Installer function
        require_once( SLM_LIB . 'class-software-license-manager-slm-installer.php');
    }

    public static function activate() {
        //Do installer task
        $this->slm_db_install();

        //schedule a daily cron event
        wp_schedule_event(time(), 'daily', 'slm_daily_cron_event');
        do_action('slm_activation_complete');
    }
}
$slm_activator = new Software_License_Manager_Activator();