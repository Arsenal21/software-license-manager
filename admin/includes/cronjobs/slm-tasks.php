<?php

// Add custom schedule interval for daily checks
add_filter('cron_schedules', 'slm_add_daily_cron_schedule');
function slm_add_daily_cron_schedule($schedules) {
    // Define a daily interval (24 hours = 86400 seconds)
    $schedules['slm_daily'] = array(
        'interval' => 86400,
        'display'  => __('Every 24 Hours', 'slmplus'),
    );
    return $schedules;
}

// Schedule the event if not already scheduled
if (!wp_next_scheduled('slm_expired_send_email_reminder')) {
    wp_schedule_event(time(), 'slm_daily', 'slm_expired_send_email_reminder');
}

// Run license check and send email reminders
add_action('slm_expired_send_email_reminder', 'slm_run_license_check');

function slm_run_license_check() {
    try {
        // Run the expiration check, ensuring it returns an array with both expired and reinstated licenses
        $result = SLM_Utility::check_for_expired_lic();

        // Validate the structure of the returned result
        if (!is_array($result) || !isset($result['expired_licenses'], $result['reinstated_licenses'])) {
            SLM_Helper_Class::write_log('Unexpected result format from check_for_expired_lic.');
            return []; // Return empty array if result format is unexpected
        }

        // Process and log expired licenses if any
        if (!empty($result['expired_licenses'])) {
            $expired_license_keys = [];
            foreach ($result['expired_licenses'] as $license) {
                // Assuming each license in expired_licenses is a license key
                if (is_string($license)) {
                    $expired_license_keys[] = $license;
                } elseif (is_array($license) && isset($license['license_key'])) {
                    $expired_license_keys[] = $license['license_key'];
                }
            }
            SLM_Helper_Class::write_log('Expired licenses: ' . implode(', ', $expired_license_keys));
        } else {
            SLM_Helper_Class::write_log('No expired licenses found.');
        }

        // Process and log reinstated licenses if any
        if (!empty($result['reinstated_licenses'])) {
            $reinstated_license_keys = [];
            foreach ($result['reinstated_licenses'] as $license) {
                // Assuming each license in reinstated_licenses is a license key
                if (is_string($license)) {
                    $reinstated_license_keys[] = $license;
                } elseif (is_array($license) && isset($license['license_key'])) {
                    $reinstated_license_keys[] = $license['license_key'];
                }
            }
            SLM_Helper_Class::write_log('Reinstated licenses: ' . implode(', ', $reinstated_license_keys));
        } else {
            SLM_Helper_Class::write_log('No licenses were reinstated.');
        }

        // Return the full result array
        return $result;
    } catch (Exception $e) {
        // Log error if the check fails
        SLM_Helper_Class::write_log('Error in slm_run_license_check: ' . $e->getMessage());
        return []; // Return empty array if an error occurred
    }
}


// Clear the scheduled event on plugin deactivation to avoid duplicate schedules
register_deactivation_hook(__FILE__, 'slm_clear_scheduled_events');
function slm_clear_scheduled_events() {
    $timestamp = wp_next_scheduled('slm_expired_send_email_reminder');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'slm_expired_send_email_reminder');
    }
}

// Add a custom admin page button to run the license check manually
add_action('admin_menu', 'slm_add_manual_license_check_page');
function slm_add_manual_license_check_page() {
    add_submenu_page(
        'tools.php', // Parent slug, 'Tools' menu
        __('Run License Check', 'slmplus'), // Page title
        __('Run License Check', 'slmplus'), // Menu title
        'manage_options', // Capability required
        'slm-manual-license-check', // Menu slug
        'slm_manual_license_check_page' // Callback function
    );
}

// Display the button, handle the manual check, and show results
function slm_manual_license_check_page() {
    // Check user capability
    if (!current_user_can('manage_options')) {
        return;
    }

    // Variable to store expired licenses
    $expired_licenses = array();

    // Security check with nonce
    if (isset($_POST['slm_manual_check']) && check_admin_referer('slm_manual_check_action', 'slm_manual_check_nonce')) {
        // Run the license check and get any expired licenses
        $expired_licenses = slm_run_license_check();
        
        if (!empty($expired_licenses)) {
            echo '<div class="updated"><p>' . __('License check completed. The following licenses have expired:', 'slmplus') . '</p></div>';
        } else {
            echo '<div class="updated"><p>' . __('License check completed. No expired licenses found.', 'slmplus') . '</p></div>';
        }
    }

    // Display the button in the admin area
    echo '<div class="wrap">';
    echo '<h2>' . __('Run License Check Manually', 'slmplus') . '</h2>';
    echo '<form method="post">';
    wp_nonce_field('slm_manual_check_action', 'slm_manual_check_nonce');
    echo '<input type="submit" name="slm_manual_check" class="button-primary" value="' . __('Run License Check', 'slmplus') . '">';
    echo '</form>';

    // Output expired licenses if available
    if (!empty($expired_licenses)) {
        echo '<h3>' . __('Expired Licenses:', 'slmplus') . '</h3>';
        echo '<ul>';
        foreach ($expired_licenses as $license) {
            echo '<li>' . esc_html($license) . '</li>';
        }
        echo '</ul>';
    }

    echo '</div>';
}
