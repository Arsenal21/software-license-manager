<?php
/**
 * Activator for SLM Plus
 *
 * This class handles tasks that need to be performed when the plugin is activated.
 * It schedules cron jobs and triggers the database installation process.
 *
 * @package   SLM Plus
 * @author    Michel Velis
 * @license   GPL-2.0+
 * @link      http://epikly.com
 */

// Prevent direct access to this file
if (!defined('WPINC')) {
    die;
}

class SLM_Activator {

    /**
     * Install the plugin's database tables.
     * This function includes the database installer class.
     */
    public static function slm_db_install() {
        require_once SLM_LIB . 'class-slm-installer.php';
    }

    /**
     * Runs during plugin activation.
     * This method installs the necessary database tables and schedules a daily cron event.
     */
    public static function activate() {
        // Run database installer
        self::slm_db_install();

        // Schedule a daily cron event for license management tasks
        if (!wp_next_scheduled('slm_daily_cron_event')) {
            wp_schedule_event(time(), 'daily', 'slm_daily_cron_event');
        }

        // Trigger any additional actions related to plugin activation
        do_action('slm_activation_complete');
    }
}

// Instantiate the activator class
$slm_activator = new SLM_Activator();
