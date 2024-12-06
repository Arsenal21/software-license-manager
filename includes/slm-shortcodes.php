<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}


add_action('admin_init', function () {
    if (is_user_logged_in() && current_user_can('manage_options') && isset($_GET['slm_clear_transients'])) {
        global $wpdb;
        $like_pattern = '%slm_rate_limit_%';
        $sql = "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_$like_pattern' OR option_name LIKE '_transient_timeout_$like_pattern'";
        $wpdb->query($sql);

        add_action('admin_notices', function () {
            echo '<div class="notice notice-success"><p>All rate-limiting transients have been cleared.</p></div>';
        });
    }
});


// Custom Hooks
// Hooks added to customize the text or logic:

// slm_invalid_email_message: Customize the invalid email message.
// slm_success_message: Customize the success message.
// slm_no_license_message: Customize the "no license found" message.
// slm_license_email_message: Modify the email message body.
// slm_license_email_subject: Modify the email subject.
// slm_form_label: Change the form label text.
// slm_form_button_text: Change the form button text.

class SLM_Forgot_License {
    /**
     * Initialize the class and hooks.
     */
    public function __construct() {
        // Register shortcode.
        add_shortcode('slm_forgot_license', [$this, 'render_shortcode']);
    }

    /**
     * Render the shortcode.
     *
     * @return string
     */
    public function render_shortcode() {
        // Check if form is submitted.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['slm_forgot_license_nonce'])) {
            return $this->handle_form_submission();
        }

        // Output the form.
        return $this->render_form();
    }

    /**
     * Handle form submission.
     *
     * @return string
     */
    protected function handle_form_submission() {
        // Verify nonce for security.
        if (!isset($_POST['slm_forgot_license_nonce']) || 
            !wp_verify_nonce($_POST['slm_forgot_license_nonce'], 'slm_forgot_license_action')) {
            return '<p>Invalid request. Please try again.</p>';
        }

        // Sanitize and validate email.
        $email = sanitize_email($_POST['slm_forgot_license_email']);
        if (!is_email($email)) {
            return apply_filters('slm_invalid_email_message', '<p>Invalid email address.</p>');
        }

        // Rate limiting: prevent repeated submissions from the same IP.
        $ip = $_SERVER['REMOTE_ADDR'];
        if ($this->is_rate_limited($ip)) {
            return '<p>You are submitting requests too quickly. Please try again later.</p>';
        }

        // Retrieve licenses.
        $licenses = SLM_Utility::get_licenses_by_email($email);

        if (!empty($licenses)) {
            // Prepare and send the email.
            $message = apply_filters('slm_license_email_message', $this->generate_email_message($licenses), $licenses);
            wp_mail($email, apply_filters('slm_license_email_subject', 'Your Licenses'), $message);

            return apply_filters('slm_success_message', '<p>Your licenses have been sent to your email.</p>');
        }

        return apply_filters('slm_no_license_message', '<p>No licenses found for the provided email.</p>');
    }

    /**
     * Check if the IP is rate-limited.
     *
     * @param string $ip
     * @return bool
     */
    protected function is_rate_limited($ip) {
        // Allow administrators to bypass rate limiting and clear transients.
        if (is_user_logged_in() && current_user_can('manage_options')) {
            $this->clear_transients();
            return false; // Admins are not rate-limited.
        }
    
        $key = 'slm_rate_limit_' . $ip;
        $limit = 3; // Max submissions allowed per hour.
        $time_frame = HOUR_IN_SECONDS;
    
        $attempts = get_transient($key);
    
        if ($attempts === false) {
            set_transient($key, 1, $time_frame);
            return false;
        }
    
        if ($attempts >= $limit) {
            return true;
        }
    
        set_transient($key, $attempts + 1, $time_frame);
        return false;
    }
    
    /**
     * Clear all relevant transients.
     */
    protected function clear_transients() {
        global $wpdb;
    
        // Search and delete all transients related to rate limiting.
        $like_pattern = '%slm_rate_limit_%';
        $sql = "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_$like_pattern' OR option_name LIKE '_transient_timeout_$like_pattern'";
        $wpdb->query($sql);
    }
    
    
    

    /**
     * Generate the email message.
     *
     * @param array $licenses
     * @return string
     */
    protected function generate_email_message($licenses) {
        $message = "Here are your licenses:\n\n";

        foreach ($licenses as $license) {
            $message .= "License Key: {$license['license_key']}\n";
            // $message .= "Product: {$license['product_ref']}\n";
            $message .= "Status: {$license['lic_status']}\n\n";
        }

        return $message;
    }

    /**
     * Render the form HTML.
     *
     * @return string
     */
    protected function render_form() {
        ob_start();
        ?>
        <form method="POST" class="slm-forgot-license-form">
            <label for="slm_email"><?php echo esc_html(apply_filters('slm_form_label', 'Enter your email address:')); ?></label>
            <input type="email" id="slm_email" name="slm_forgot_license_email" required>
            <?php wp_nonce_field('slm_forgot_license_action', 'slm_forgot_license_nonce'); ?>
            <button type="submit"><?php echo esc_html(apply_filters('slm_form_button_text', 'Retrieve License')); ?></button>
        </form>
        <?php
        return ob_get_clean();
    }
}


class SLM_List_Licenses_FE {
    /**
     * Initialize the class and hooks.
     */
    public function __construct() {
        // Register shortcode.
        add_shortcode('slm_list_licenses', [$this, 'render_shortcode']);
    }

    /**
     * Render the shortcode.
     *
     * @return string
     */
    public function render_shortcode() {
        // Check if the user is logged in.
        if (!is_user_logged_in()) {
            return '<p>' . __('You must be logged in to view your licenses.', 'slm-plus') . '</p>';
        }

        // Get the current user's email.
        $current_user = wp_get_current_user();
        $email = $current_user->user_email;

        // Retrieve licenses for the user.
        $licenses = $this->get_licenses_by_email($email);

        // Render the licenses table or a message if none are found.
        if (empty($licenses)) {
            return apply_filters('slm_no_license_message', '<p>' . __('No licenses found for your account.', 'slm-plus') . '</p>');
        }

        return $this->render_licenses_table($licenses);
    }

    /**
     * Retrieve licenses by email from the database.
     *
     * @param string $email
     * @return array
     */
    protected function get_licenses_by_email($email) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'lic_key_tbl'; // Update to match your actual table name.
        $query = $wpdb->prepare(
            "SELECT license_key, product_ref, lic_status, date_expiry, date_activated, max_allowed_domains, max_allowed_devices 
             FROM $table_name WHERE email = %s",
            $email
        );

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Render the licenses table HTML.
     *
     * @param array $licenses
     * @return string
     */
    protected function render_licenses_table($licenses) {
        ob_start();
        ?>
        <table class="slm-licenses-table">
            <thead>
                <tr>
                    <th><?php echo esc_html(__('License Key', 'slm-plus')); ?></th>
                    <th><?php echo esc_html(__('Product', 'slm-plus')); ?></th>
                    <th><?php echo esc_html(__('Status', 'slm-plus')); ?></th>
                    <th><?php echo esc_html(__('Activated On', 'slm-plus')); ?></th>
                    <th><?php echo esc_html(__('Expiry Date', 'slm-plus')); ?></th>
                    <th><?php echo esc_html(__('Max Domains', 'slm-plus')); ?></th>
                    <th><?php echo esc_html(__('Max Devices', 'slm-plus')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($licenses as $license): ?>
                    <tr>
                        <td><?php echo esc_html($license['license_key']); ?></td>
                        <td><?php echo esc_html($license['product_ref']); ?></td>
                        <td><?php echo esc_html($license['lic_status']); ?></td>
                        <td><?php echo esc_html($license['date_activated'] ?: __('N/A', 'slm-plus')); ?></td>
                        <td><?php echo esc_html($license['date_expiry'] ?: __('N/A', 'slm-plus')); ?></td>
                        <td><?php echo esc_html($license['max_allowed_domains']); ?></td>
                        <td><?php echo esc_html($license['max_allowed_devices']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }
}

// Initialize
new SLM_Forgot_License();
new SLM_List_Licenses_FE();

