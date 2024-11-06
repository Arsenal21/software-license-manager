<?php
/**
 * Deactivator for SLM Plus
 *
 * This class handles tasks that need to be performed when the plugin is deactivated.
 * For example, it clears any scheduled cron jobs associated with the plugin.
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

// Deactivation handler class
class SLM_Deactivator {

    /**
     * Runs during plugin deactivation.
     * This method clears the daily cron job and triggers a custom deactivation action.
     */
    public static function deactivate() {
        // Clear the daily cron event if it exists
        wp_clear_scheduled_hook('slm_daily_cron_event');

        // Trigger any additional actions related to plugin deactivation
        do_action('slm_deactivation_complete');
    }
}

// Instantiate the deactivator class
$slm_deactivator = new SLM_Deactivator();
