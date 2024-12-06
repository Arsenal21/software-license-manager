<?php

/*
 * Contains some utility functions for the plugin.
 */

// Helper Class

// Define the wp_mail_failed callback
function action_wp_mail_failed($wp_error)
{
    if (is_wp_error($wp_error)) {
        error_log(print_r($wp_error->get_error_messages(), true));
    }
}
add_action('wp_mail_failed', 'action_wp_mail_failed', 10, 1);


class SLM_Helper_Class
{

    public static function slm_get_option($option)
    {
        $slm_opts = get_option('slm_plugin_options');
        if (is_array($slm_opts) && array_key_exists($option, $slm_opts)) {
            return $slm_opts[$option];
        }
        return '';
    }

    public static function write_log($log)
    {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
    public static function get_license_logs($license_key)
    {
        global $wpdb;
        $table_name = SLM_TBL_LIC_LOG;

        // Use a prepared statement for security
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE license_key = %s ORDER BY time DESC",
            $license_key
        );

        // Fetch results as an associative array
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * PHP Logger
     */

    static function console($data)
    {
        $output = $data;
        if (is_array($output))
            $output = implode(',', $output);

        // print the result into the JavaScript console
        echo "<script>console.log('PHP LOG: " . esc_js($output) . "');</script>";
    }
}

$slm_helper = new SLM_Helper_Class();


class SLM_API_Utility
{
    
    /*
     * The args array can contain the following:
     * result (success or error)
     * message (a message describing the outcome of the action
     */

    public static function output_api_response($args)
    {
        // Log to debug file (if enabled)
        global $slm_debug_logger;
        if (isset($slm_debug_logger)) {
            $slm_debug_logger->log_debug('API Response - Result: ' . esc_html($args['result']) . ' Message: ' . esc_html($args['message']));
        }

        // Send response
        $args = apply_filters('slm_ap_response_args', $args);
        $args = apply_filters('slm_api_response_args', $args);

        header('Content-Type: application/json');
        echo json_encode($args);
        exit;
    }

    /**
     * Validate date format to ensure it's in 'YYYY-MM-DD' format.
     * Returns the sanitized date or an empty string if invalid.
     */
    public static function slm_validate_date($date)
    {
        $date = sanitize_text_field($date);
        $timestamp = strtotime($date);
        if ($timestamp && date('Y-m-d', $timestamp) === $date) {
            return $date;
        }
        return ''; // Return an empty string if the date is invalid
    }

    public static function verify_secret_key()
    {
        $slm_options            = get_option('slm_plugin_options');
        $right_secret_key       = $slm_options['lic_verification_secret'] ?? '';
        $received_secret_key    = sanitize_text_field($_REQUEST['secret_key'] ?? '');
        $slm_action             = sanitize_text_field($_REQUEST['slm_action'] ?? '');

        // Case-sensitive comparison for the secret keys
        if ($received_secret_key !== $right_secret_key) {
            // Prepare the error response with case-sensitivity note
            $args = array(
                'result' => 'error',
                'message' => 'Verification API secret key is invalid. Note: The key is case-sensitive.',
                'slm_action' => $slm_action,
                'received_secret_key' => $received_secret_key,
                'error_code' => SLM_Error_Codes::VERIFY_KEY_INVALID
            );
            // Output the API response with the error
            self::output_api_response($args);
            SLM_Helper_Class::write_log('Verification API secret key is invalid. Note: The key is case-sensitive. ' . $slm_action);
        }
    }

    public static function get_slm_option($option)
    {
        // Retrieve the option value from the database
        $slm_options_func = get_option('slm_plugin_options', []);

        // Check if the option exists; if not, return an empty string
        if (!isset($slm_options_func[$option])) {
            return '';
        }

        // Get the option value and unslash it (removes slashes from the option value)
        $option_value = wp_unslash($slm_options_func[$option]);

        // Sanitize the option value (text field sanitization)
        $sanitized_option = sanitize_text_field($option_value);

        // Return the sanitized and unslashed option value
        return $sanitized_option;
    }


    public static function verify_secret_key_for_creation()
    {
        // Get the stored secret key from plugin options
        $slm_options = get_option('slm_plugin_options');
        $right_secret_key = $slm_options['lic_creation_secret'] ?? '';

        // Sanitize and retrieve the received secret key
        $received_secret_key = sanitize_text_field($_REQUEST['secret_key'] ?? '');

        // Case-sensitive comparison for the secret keys
        if ($received_secret_key !== $right_secret_key) {
            // Prepare the error response with case-sensitivity note
            $args = array(
                'result' => 'error',
                'message' => 'Invalid License Creation API Secret Key provided. Note: The key comparison is case-sensitive.',
                'error_code' => SLM_Error_Codes::CREATE_KEY_INVALID
            );
            // Output the API response with the error
            self::output_api_response($args);
        }
    }

    public static function insert_license_data_internal($fields)
    {
        global $wpdb;
        $slm_lic_table = SLM_TBL_LICENSE_KEYS;
        $fields = array_filter($fields); // Remove any null values.

        $wpdb->insert($slm_lic_table, $fields);
    }
}


class SLM_Utility
{
    public static function get_licenses_by_email($email) {
        global $wpdb;
    
        // Query the licenses table for entries matching the email.
        $table_name = SLM_TBL_LICENSE_KEYS; // Adjust table name if needed.
        $query = $wpdb->prepare(
            "SELECT license_key, product_ref, lic_status FROM $table_name WHERE email = %s",
            $email
        );
    
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Saves a backup of the plugin's database tables in a secure folder.
     */
    public static function renew_license($license_key, $order_id) {
        global $wpdb;
        $wpdb->update(SLM_TBL_LICENSE_KEYS, [
            'wc_order_id' => $order_id,
            'payment_status' => 'pending'
        ], ['license_key' => $license_key]);
    }
    
    public static function slm_save_backup_to_uploads()
    {
        global $wpdb;

        // Get the upload directory
        $upload_dir = wp_upload_dir();
        $unique_hash = slm_get_unique_hash(); // Generate or retrieve the unique hash
        $slm_backup_dir = $upload_dir['basedir'] . $unique_hash;

        // Create the slm-plus folder with hash if it doesn't exist
        if (!file_exists($slm_backup_dir)) {
            wp_mkdir_p($slm_backup_dir);
        }

        // Set backup file name and path
        $backup_file = $slm_backup_dir . '/slm_plugin_backup_' . gmdate('Y-m-d H:i:s') . '.sql';

        // Get plugin tables
        $backup_tables = [
            SLM_TBL_LICENSE_KEYS,
            SLM_TBL_LIC_DOMAIN,
            SLM_TBL_LIC_DEVICES,
            SLM_TBL_LIC_LOG,
            SLM_TBL_EMAILS,
            SLM_TBL_LICENSE_STATUS
        ];

        $sql = "";
        foreach ($backup_tables as $table) {
            // Get table structure
            $create_table_query = $wpdb->get_results("SHOW CREATE TABLE $table", ARRAY_N);
            $sql .= "\n\n" . $create_table_query[0][1] . ";\n\n";

            // Get table data
            $rows = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
            foreach ($rows as $row) {
                $values = array_map('esc_sql', array_values($row)); // Use esc_sql to escape the values
                $values = "'" . implode("','", $values) . "'";
                $sql .= "INSERT INTO $table VALUES ($values);\n";
            }
        }

        // Include the WordPress Filesystem API
        if (! function_exists('request_filesystem_credentials')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // Ensure the filesystem is ready
        if (! WP_Filesystem()) {
            request_filesystem_credentials(admin_url());
        }

        global $wp_filesystem;

        // Define the backup file path
        $backup_path = $upload_dir['basedir'] . '/' . $unique_hash . '/' . basename($backup_file);

        // Create the backup directory if it doesn't exist
        if (! is_dir(dirname($backup_path))) {
            $wp_filesystem->mkdir(dirname($backup_path));
        }

        // Save the SQL to the backup file using the WP Filesystem
        if ($wp_filesystem->put_contents($backup_path, $sql)) {
            $backup_url = $upload_dir['baseurl'] . '/' . $unique_hash . '/' . basename($backup_file);

            // Save backup info in plugin options
            $backup_info = [
                'url' => $backup_url,
                'date' => gmdate('Y-m-d H:i:s'),
            ];
            slm_update_option('slm_last_backup_info', $backup_info);

            echo '<div class="notice notice-success"><p>' . esc_html__('Backup created successfully! Download from: ', 'slm-plus') . '<a href="' . esc_url($backup_url) . '">' . esc_html(basename($backup_file)) . '</a></p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Error: Failed to create the backup file.', 'slm-plus') . '</p></div>';
        }
    }

    // Function to export a single license as a JSON file
    public static function export_license_to_json($license_id_or_key)
    {
        global $wpdb;

        // Fetch the custom directory path from options (saved with hash)
        $slm_options = get_option('slm_plugin_options');
        $custom_dir_hash = isset($slm_options['slm_backup_dir_hash']) ? $slm_options['slm_backup_dir_hash'] : '';

        // Get the WordPress upload directory
        $upload_dir = wp_upload_dir();
        $custom_dir = $upload_dir['basedir'] . $custom_dir_hash;

        // Ensure the directory exists
        if (!file_exists($custom_dir)) {
            wp_mkdir_p($custom_dir); // Create the directory if it doesn't exist
        }

        // Check if the input is a license ID or license key and fetch the license data accordingly
        if (is_numeric($license_id_or_key)) {
            // Fetch license by ID
            $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . SLM_TBL_LICENSE_KEYS . " WHERE id = %d", $license_id_or_key), ARRAY_A);
        } else {
            // Fetch license by key
            $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . SLM_TBL_LICENSE_KEYS . " WHERE license_key = %s", $license_id_or_key), ARRAY_A);
        }

        if ($data) {
            $license_key = $data['license_key'];

            // Prepare the file name as "license_key.json"
            $file_name = sanitize_file_name($license_key) . '.json';
            $file_path = $custom_dir . '/' . $file_name;

            // Encode the license data to JSON format
            $json_data = wp_json_encode($data, JSON_PRETTY_PRINT);

            // Save the JSON data to a file in the custom directory
            if (file_put_contents($file_path, $json_data)) {
                $file_url = $upload_dir['baseurl'] . $custom_dir_hash . '/' . $file_name;

                // Return the file URL for download
                return $file_url;
            } else {
                return false; // Return false if the file couldn't be saved
            }
        }

        return false; // Return false if no data was found
    }

    public static function check_for_expired_lic($lic_key = '')
    {
        global $wpdb;

        // Set up email headers and subject line
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $subject = get_bloginfo('name') . ' - Your license has expired';
        $expiration_reminder_text = SLM_Helper_Class::slm_get_option('expiration_reminder_text');
        $expired_licenses_list = [];
        $reinstated_licenses_list = [];

        // Query licenses marked as expired but with future expiration dates to correct their status
        $incorrectly_expired_query = $wpdb->prepare(
            "SELECT * FROM " . SLM_TBL_LICENSE_KEYS . " WHERE lic_status = %s AND date_expiry > NOW()",
            'expired'
        );
        $incorrectly_expired_licenses = $wpdb->get_results($incorrectly_expired_query, ARRAY_A);

        // Reinstate incorrectly expired licenses
        foreach ($incorrectly_expired_licenses as $license) {
            $license_key = sanitize_text_field($license['license_key']);
            $id = intval($license['id']);

            // Update license status to 'active'
            $wpdb->update(
                SLM_TBL_LICENSE_KEYS,
                ['lic_status' => 'active'],
                ['id' => $id]
            );

            self::create_log($license_key, 'status corrected to active');
            $reinstated_licenses_list[] = $license_key;
        }

        // Log reinstated licenses
        if (!empty($reinstated_licenses_list)) {
            SLM_Helper_Class::write_log('Reinstated licenses set to active: ' . implode(', ', $reinstated_licenses_list));
        }

        // Query expired licenses
        $expired_query = $wpdb->prepare(
            "SELECT * FROM " . SLM_TBL_LICENSE_KEYS . " WHERE date_expiry < NOW() AND date_expiry != %s ORDER BY date_expiry ASC;",
            '00000000'
        );
        $expired_licenses = $wpdb->get_results($expired_query, ARRAY_A);

        // Check if any expired licenses were found
        if (empty($expired_licenses)) {
            SLM_Helper_Class::write_log('No expired licenses found');
            return []; // Return an empty array if no licenses found
        }

        // Process each expired license
        foreach ($expired_licenses as $license) {
            $id = intval($license['id']);
            $license_key = sanitize_text_field($license['license_key']);
            // $first_name = sanitize_text_field($license['first_name']);
            // $last_name = sanitize_text_field($license['last_name']);
            $email = sanitize_email($license['email']);
            $date_expiry = sanitize_text_field($license['date_expiry']);

            // Include email template and generate the email body
            ob_start();
            include SLM_LIB . 'mails/expired.php';
            $body = ob_get_clean();

            // Check if auto-expiration is enabled and update the license status
            if (SLM_Helper_Class::slm_get_option('enable_auto_key_expiration') == 1) {
                $update_data = ['lic_status' => 'expired'];
                $where_clause = ['id' => $id];
                $wpdb->update(SLM_TBL_LICENSE_KEYS, $update_data, $where_clause);

                // Log and send expiration notification
                self::create_log($license_key, 'set to expired');
                $email_result = self::slm_check_sent_emails($license_key, $email, $subject, $body, $headers);
                if ($email_result === '200') {
                    self::create_log($license_key, 'sent expiration email notification');
                }
            }

            // Add license to the expired list
            $expired_licenses_list[] = $license_key;
        }

        // Log the total count of expired licenses
        SLM_Helper_Class::write_log('Expired licenses found and processed: ' . implode(', ', $expired_licenses_list));

        return [
            'expired_licenses' => $expired_licenses_list,
            'reinstated_licenses' => $reinstated_licenses_list
        ]; // Return both expired and reinstated licenses
    }


    // Define return codes for clarity
    const EMAIL_SENT_FIRST_TIME = '200';
    const EMAIL_ALREADY_SENT = '400';
    const EMAIL_SENT_RECORD_NOT_FOUND = '300';

    public static function slm_check_sent_emails($license_key, $email, $subject, $body, $headers)
    {
        global $wpdb;

        // Check if an email has already been sent for this license key
        $query = $wpdb->prepare(
            'SELECT COUNT(*) FROM ' . SLM_TBL_EMAILS . ' WHERE lic_key = %s',
            $license_key
        );
        $email_already_sent = $wpdb->get_var($query) > 0;

        // If email already sent, return status code without resending
        if ($email_already_sent) {
            return self::EMAIL_ALREADY_SENT;
        }

        // Send the email if it hasn't been sent before
        $mail_sent = wp_mail($email, $subject, $body, $headers);

        // Log the email status
        if ($mail_sent) {
            self::create_email_log($license_key, $email, 'success', 'yes', current_time('mysql'));
            return self::EMAIL_SENT_FIRST_TIME;
        } else {
            self::create_email_log($license_key, $email, 'failure', 'no', current_time('mysql'));
            return self::EMAIL_SENT_RECORD_NOT_FOUND;
        }
    }


    public static function do_auto_key_expiry()
    {
        global $wpdb;
        $current_date = current_time('Y-m-d');
        $slm_lic_table = SLM_TBL_LICENSE_KEYS;

        // Query for active (non-expired) licenses
        $licenses = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $slm_lic_table WHERE lic_status != %s", 'expired'),
            OBJECT
        );

        // Log and return if no licenses are found
        if (empty($licenses)) {
            SLM_Debug_Logger::log_debug_st("do_auto_key_expiry() - No active license keys found.");
            return false;
        }

        $today_dt = new DateTime($current_date);

        foreach ($licenses as $license) {
            $license_key = sanitize_text_field($license->license_key);
            $expiry_date = sanitize_text_field($license->date_expiry);

            // Skip if expiration date is invalid or empty
            if (empty($expiry_date) || in_array($expiry_date, ['0000-00-00', '00000000'])) {
                SLM_Debug_Logger::log_debug_st("License key ($license_key) has no valid expiration date set. Skipping expiry check.");
                continue;
            }

            // Check if the license has expired
            $expire_dt = new DateTime($expiry_date);
            if ($today_dt > $expire_dt) {
                // Update license status to 'expired'
                $data = ['lic_status' => 'expired'];
                $where = ['id' => intval($license->id)];
                $updated = $wpdb->update($slm_lic_table, $data, $where);

                // Log the expiry and trigger action if successfully updated
                if ($updated) {
                    SLM_Debug_Logger::log_debug_st("License key ($license_key) expired on $expiry_date. Status set to 'expired'.");
                    do_action('slm_license_key_expired', $license->id);

                    // Optional: Send expiry reminder email
                    self::check_for_expired_lic($license_key);
                } else {
                    SLM_Debug_Logger::log_debug_st("Failed to update status for expired license key ($license_key).");
                }
            }
        }

        return true;
    }



    public static function get_user_info($by, $value)
    {
        // Sanitize the input parameters
        $by = sanitize_key($by);
        $value = sanitize_text_field($value);

        // Get the user by specified criteria
        $user = get_user_by($by, $value);
        return $user;
    }

    public static function get_days_remaining($date1)
    {
        // Validate and sanitize the date input
        $date1 = sanitize_text_field($date1);

        // Retrieve the date format setting from WordPress settings
        $date_format = get_option('date_format');

        try {
            // Create DateTime objects for future and current dates
            $future_date = new DateTime($date1);
            $current_date = new DateTime();

            // Check if the future date is valid and in the future
            if ($future_date < $current_date) {
                return __('0 days remaining', 'slm-plus');
            }

            // Calculate the difference in days
            $interval = $current_date->diff($future_date);
            $days_remaining = (int) $interval->days;

            // Format and return the result
            return sprintf(
                // Translators: %1$s is the number of days remaining, %2$s is the formatted future date
                __('%1$s days remaining until %2$s', 'slm-plus'),
                $days_remaining,
                date_i18n($date_format, $future_date->getTimestamp())
            );
        } catch (Exception $e) {
            // Return 0 days remaining if date parsing fails
            return __('0 days remaining', 'slm-plus');
        }
    }


    public static function delete_license_key_by_row_id($key_row_id)
    {
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;

        // Sanitize the input
        $key_row_id = intval($key_row_id);

        // Retrieve the license key associated with this row id
        $license_key = $wpdb->get_var($wpdb->prepare("SELECT license_key FROM $license_table WHERE id = %d", $key_row_id));

        // Debug: Log the retrieved license key
        SLM_Helper_Class::write_log("License key retrieved: " . $license_key);

        // First, delete the registered domains entry of this key (if any)
        SLM_Utility::delete_registered_domains_of_key($key_row_id);
        SLM_Helper_Class::write_log("Registered domains for key $license_key deleted.");

        // Now, delete the key from the licenses table
        $wpdb->delete($license_table, array('id' => $key_row_id));
        SLM_Helper_Class::write_log("License with row ID $key_row_id deleted from the license table.");

        if ($license_key) {

            // Query to get WooCommerce orders using a custom WP_Query with meta_query
            $args = array(
                'post_type'      => 'shop_order',
                'posts_per_page' => -1,
                'meta_query'     => array(
                    'relation' => 'OR',
                    array(
                        'key'     => 'License Key',   // License Key meta field
                        'value'   => $license_key,
                        'compare' => '='
                    ),
                    array(
                        'key'     => '_slm_lic_key',  // Fallback License Key meta field
                        'value'   => $license_key,
                        'compare' => '='
                    )
                )
            );

            $order_query = new WP_Query($args);

            if ($order_query->have_posts()) {
                while ($order_query->have_posts()) {
                    $order_query->the_post();
                    $order_id = get_the_ID();

                    // Get order object
                    $order = wc_get_order($order_id);

                    // Debugging: Log the order ID
                    SLM_Helper_Class::write_log("Processing order ID: " . $order_id);

                    // Meta keys to be removed
                    $meta_keys = [
                        'License Key',
                        'License Type',
                        'Current Version',
                        'Until Version',
                        'Max Devices',
                        'Max Domains'
                    ];

                    // Remove order-level metadata
                    foreach ($meta_keys as $meta_key) {
                        $meta_value = $order->get_meta($meta_key, true); // Retrieve the metadata value
                        if ($meta_value) {
                            SLM_Helper_Class::write_log("Found meta key $meta_key with value: $meta_value. Deleting...");
                            $order->delete_meta_data($meta_key); // Remove meta data from the order
                        }
                    }

                    // Add a note to the order
                    $note_content = sprintf(__('License key %s was deleted on %s', 'slm-plus'), $license_key, date_i18n('F j, Y'));
                    $order->add_order_note($note_content);

                    // Process and reset license-related metadata from order items
                    foreach ($order->get_items() as $item_id => $item) {
                        // Remove item-level metadata for the specified keys
                        foreach ($meta_keys as $meta_key) {
                            $meta_value = $item->get_meta($meta_key, true); // Retrieve the metadata value
                            if ($meta_value) {
                                SLM_Helper_Class::write_log("Found meta key $meta_key in order item $item_id with value: $meta_value. Deleting...");
                                $item->delete_meta_data($meta_key); // Remove meta data from the order item
                            }
                        }

                        // Save the updated order item
                        $item->save();
                    }

                    // Save the updated order
                    $order->save();
                }

                wp_reset_postdata(); // Reset the post data after custom query
            } else {
                // Debugging: Log if no orders were found
                SLM_Helper_Class::write_log("No orders found for the license key: " . $license_key);
            }
        }
    }

    /**
     * Get license key by WooCommerce Order ID.
     *
     * @param int $order_id WooCommerce order ID.
     * @return string|null License key associated with the order ID, or null if not found.
     */
    public static function slm_get_license_by_order_id($order_id) {
        global $wpdb;
        $lic_key_table = SLM_TBL_LICENSE_KEYS;

        // Query to fetch the license key by order ID
        $query = $wpdb->prepare("SELECT license_key FROM $lic_key_table WHERE wc_order_id = %d", $order_id);
        $license_key = $wpdb->get_var($query);

        return $license_key ? sanitize_text_field($license_key) : null;
    }



    /**
     * Get associated orders for a license.
     *
     * @param mixed $identifier The license key (string) or license ID (integer).
     * @return array|null List of associated orders or null if none found.
     */
    public static function slm_get_associated_orders($identifier) {
        global $wpdb;
        $lic_key_table = SLM_TBL_LICENSE_KEYS;
    
        // Ensure identifier is valid
        if (empty($identifier)) {
            SLM_Helper_Class::write_log('Invalid identifier passed to slm_get_associated_orders: ' . print_r($identifier, true));
            return [];
        }
    
        // Prepare the query based on identifier type
        if (is_numeric($identifier)) {
            $query = $wpdb->prepare("SELECT associated_orders FROM $lic_key_table WHERE id = %d", $identifier);
        } else {
            $query = $wpdb->prepare("SELECT associated_orders FROM $lic_key_table WHERE license_key = %s", $identifier);
        }
    
        // Log the query
        SLM_Helper_Class::write_log('SQL Query: ' . $query);
    
        // Execute the query
        $result = $wpdb->get_var($query);
    
        // Debug the raw result
        SLM_Helper_Class::write_log('Raw associated_orders value: ' . print_r($result, true));
    
        // Process the result if not empty
        if (!empty($result)) {
            $orders = maybe_unserialize($result);
            SLM_Helper_Class::write_log('Unserialized associated_orders value: ' . print_r($orders, true));
    
            if (is_array($orders)) {
                return array_values(array_unique(array_map('intval', $orders)));
            }
        }
    
        // Return empty array if no valid data found
        return [];
    }
    
    
    

    /**
     * Add an order to the associated orders of a license.
     *
     * @param mixed $identifier The license key (string) or license ID (integer).
     * @param int $order_id The WooCommerce order ID to associate with the license.
     * @return bool True if the operation was successful, false otherwise.
     */
    public static function slm_add_associated_order($identifier, $order_id) {
        global $wpdb;
        $lic_key_table = SLM_TBL_LICENSE_KEYS;
    
        // Validate $order_id
        if (!is_numeric($order_id) || $order_id <= 0) {
            error_log("SLM: Invalid order ID provided: $order_id");
            return false;
        }
    
        // Fetch current license data
        if (is_numeric($identifier)) {
            // Identifier is a license ID
            $license_data = $wpdb->get_row(
                $wpdb->prepare("SELECT associated_orders, wc_order_id FROM $lic_key_table WHERE id = %d", $identifier),
                ARRAY_A
            );
        } else {
            // Identifier is a license key
            $license_data = $wpdb->get_row(
                $wpdb->prepare("SELECT associated_orders, wc_order_id FROM $lic_key_table WHERE license_key = %s", $identifier),
                ARRAY_A
            );
        }
    
        if (!$license_data) {
            error_log("SLM: License not found for identifier: $identifier");
            return false;
        }
    
        // Extract current associated orders and wc_order_id
        $associated_orders = maybe_unserialize($license_data['associated_orders']);
        $current_wc_order_id = $license_data['wc_order_id'];
    
        // Ensure $associated_orders is a valid array
        if (!is_array($associated_orders)) {
            $associated_orders = [];
        }
    
        // Add the old wc_order_id to the associated_orders array if it's valid
        if (!empty($current_wc_order_id) && !in_array($current_wc_order_id, $associated_orders, true)) {
            $associated_orders[] = $current_wc_order_id;
        }
    
        // Add the new order_id to the associated_orders array if not already present
        if (!in_array($order_id, $associated_orders, true)) {
            $associated_orders[] = $order_id;
        }
    
        // Serialize the updated orders for storage
        $updated_orders = maybe_serialize($associated_orders);
    
        // Prepare the query to update the database
        if (is_numeric($identifier)) {
            // Update based on license ID
            $query = $wpdb->prepare(
                "UPDATE $lic_key_table SET associated_orders = %s, wc_order_id = %d WHERE id = %d",
                $updated_orders,
                $order_id,
                $identifier
            );
        } else {
            // Update based on license key
            $query = $wpdb->prepare(
                "UPDATE $lic_key_table SET associated_orders = %s, wc_order_id = %d WHERE license_key = %s",
                $updated_orders,
                $order_id,
                $identifier
            );
        }
    
        // Execute the query
        $result = $wpdb->query($query);
    
        // Handle and log errors
        if ($result === false) {
            error_log("SLM: Failed to update associated orders for identifier: $identifier. Error: " . $wpdb->last_error);
            return false;
        }
    
        // Log success
        error_log("SLM: Successfully updated associated orders for identifier: $identifier with Order ID: $order_id");
        return true;
    }
    


    /*
    * Retrieves the email associated with a license key
    */
    public static function slm_get_lic_email($license)
    {
        global $wpdb;
        $lic_key_table = SLM_TBL_LICENSE_KEYS;

        // Sanitize the input
        $license = sanitize_text_field($license);

        // Prepare and execute the query to fetch the email
        $email = $wpdb->get_var(
            $wpdb->prepare("SELECT email FROM $lic_key_table WHERE license_key = %s", $license)
        );

        // Check if an email was found and is valid
        if ($email && is_email($email)) {
            return $email;
        } else {
            // Return a WP_Error if the email was not found or invalid
            return new WP_Error('license_not_found', __('License key not found or invalid email.', 'slm-plus'));
        }
    }


    /*
    * Sends an email with the specified parameters
    */
    public static function slm_send_mail($to, $subject, $message, $bgcolor)
    {
        // Sanitize inputs
        $to = sanitize_email($to);
        $subject = sanitize_text_field($subject);
        $message = sanitize_textarea_field($message);

        // Prepare headers
        $headers = array(
            'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>',
            'Content-Type: text/html; charset=UTF-8'
        );

        // Prepare the email body
        $body = self::slm_email_template($message, $bgcolor);

        // Send the email
        wp_mail($to, $subject, $body, $headers);
    }


    public static function slm_email_template($message, $bgcolor = '')
    {
        switch ($bgcolor) {
            case 'success':
                $color = '#eceff0';
                break;
            case 'error':
                $color = '#e23b2f';
                break;
            default:
                $color = '#eceff0';
                break;
        }

        $template = '<?xml version="1.0" encoding="UTF-8"?>
    <html xmlns="http://www.w3.org/1999/xhtml" style="background-color: ' . esc_attr($color) . '; padding: 0; margin: 0;">
    <head>
        <style type="text/css">
            body, html {
                font-family: Helvetica, Arial;
                font-size: 13px;
                background-color: ' . esc_attr($color) . ';
                background: ' . esc_attr($color) . ';
                padding: 0px;
                margin: 0px;
            }
            a.schedule_btn, .schedule_btn {
                display: inline-block;
                background: #e93e40;
                color: #fff;
                text-decoration: none;
                padding: 6px 12px;
                text-align: center;
                border-radius: 2px;
                font-size: 16px;
                font-weight: 600;
                margin: 36px 0;
            }
            p.legal, .legal {
                text-align: center;
                font-size: 13px;
                font-family: "Open Sans", Helvetica, Arial, sans-serif;
                line-height: 22px;
                color: #aaacad;
                font-weight: 300;
            }
            p {
                font-size: 16px;
                font-weight: 300;
                color: #2d2d31;
                line-height: 26px;
                font-family: "Open Sans", Helvetica, Arial, sans-serif;
            }
            h1, h2, h3, h4, h5, h6 {
                color: #6b6e6f;
                font-size: 19px;
                padding: 0 0 15px 0;
                font-family: "Open Sans", Helvetica, Arial, Sans-serif;
            }
        </style>
        <title>' . esc_html(get_bloginfo('name')) . '</title>
    </head>
    <body style="word-wrap: break-word; -webkit-nbsp-mode: space; line-break: after-white-space; background-color: ' . esc_attr($color) . '">
        <div style="background-color: ' . esc_attr($color) . ' !important; font-family: "Open Sans", Helvetica, Arial, sans-serif; margin: 0px; padding: 16px 0 80px 0px;">
            <br />
            <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tbody>
                    <tr>
                        <td align="center" valign="top" style="background-color:' . esc_attr($color) . '; color:#FFFFFF;">
                            <!-- Content table -->
                            <table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
                                <tbody>
                                    <tr>
                                        <td align="left" colspan="2" width="500" style="background-color:' . esc_attr($color) . ';">
                                            <div class="main" style="min-width: 320px; max-width: 500px; margin: 62px auto; background: #ffffff; padding: 35px 45px; -webkit-box-shadow: 1px 12px 15px -9px rgba(0,0,0,0.32); -moz-box-shadow: 1px 12px 15px -9px rgba(0,0,0,0.32); box-shadow: 1px 12px 15px -9px rgba(0,0,0,0.32);">
                                                <br>
                                                <div class="logo" style="text-align: center; max-width: 160px; margin: 0 auto;">
                                                    <a href="' . esc_url(get_home_url()) . '">
                                                        <img src="' . esc_url(SLM_Utility::slm_get_icon_url('3x', 'verified.png')) . '" alt="">
                                                    </a>
                                                </div>
                                                <br>
                                                <h2 style="color: #6b6e6f; font-size: 19px; padding: 0 0 15px 0; font-family: Open Sans, Helvetica, Arial, Sans-serif; text-align: center">License key was activated successfully!</h2>
                                                <p style="font-size: 16px; font-weight: 300; color: #2d2d31; line-height: 26px; font-family: Open Sans, Helvetica, Arial, sans-serif;">' . wp_kses_post($message) . '</p>
                                                <p>Regards,</p>
                                                <div class="signature">
                                                    <p style="color: #89898c; font-size: 14px; margin: 36px 0; line-height: 20px;">
                                                        <strong>' . esc_html(get_bloginfo('name')) . '</strong>
                                                        <br />
                                                        <a href="mailto:' . esc_attr(get_bloginfo('admin_email')) . '">' . esc_html(get_bloginfo('admin_email')) . '</a>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="clear" style="height: 1px; clear: both; float: none; display: block; padding: 1px;"></div>
                                            <div class="more-support" style="min-width: 320px; max-width: 500px; margin: 0px auto; padding: 24px 0px;">
                                                <p class="legal">The content of this email is confidential and intended for the recipient specified in message only. It is strictly forbidden to share any part of this message with any third party, without a written consent of the sender. If you received this message by mistake, please reply to this message and follow with its deletion, so that we can ensure such a mistake does not occur in the future.</p>
                                                <p class="legal">Questions? We are always here to help. Contact <a href="mailto:' . esc_attr(get_bloginfo('admin_email')) . '">' . esc_html(get_bloginfo('admin_email')) . '</a> or simply reply to this email.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </body>
    </html>';

        return $template;
    }

    public static function count_licenses($status)
    {
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;

        // Sanitize input
        $status = sanitize_text_field($status);

        // Prepare the SQL statement
        $query = $wpdb->prepare("SELECT COUNT(*) FROM $license_table WHERE lic_status = %s", $status);
        $get_lic_status = $wpdb->get_var($query);

        return $get_lic_status;
    }

    public static function slm_get_icon_url($size, $filename)
    {
        // Sanitize inputs
        $size = sanitize_text_field($size);
        $filename = sanitize_file_name($filename);

        return SLM_ASSETS_URL . 'icons/' . $size . '/' . $filename;
    }

    public static function count_logrequest()
    {
        global $wpdb;
        $license_table = SLM_TBL_LIC_LOG;

        $getlogs = $wpdb->get_var("SELECT COUNT(*) FROM $license_table");
        return $getlogs;
    }

    public static function count_emailsent()
    {
        global $wpdb;
        $license_table = SLM_TBL_EMAILS;

        $getlogs = $wpdb->get_var("SELECT COUNT(*) FROM $license_table");
        return $getlogs;
    }

    public static function getstats_licenses($date_created, $interval)
    {
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;

        // Sanitize inputs
        $date_created = sanitize_text_field($date_created);
        $interval = intval($interval);

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $license_table WHERE $date_created >= DATE_ADD(CURDATE(), INTERVAL -%d DAY)",
            $interval
        );

        return $wpdb->get_var($query);
    }

    public static function get_total_licenses()
    {
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;

        $license_count = $wpdb->get_var("SELECT COUNT(*) FROM $license_table");
        return $license_count;
    }

    public static function get_lic_expiringsoon()
    {
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;

        $license_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM $license_table WHERE date_expiry BETWEEN DATE_SUB(CURDATE(), INTERVAL 1 MONTH) AND CURDATE()"
        );

        return $license_count;
    }

    public static function block_license_key_by_row_id($key_row_id)
    {
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;

        // Sanitize input
        $key_row_id = intval($key_row_id);

        $wpdb->update($license_table, array('lic_status' => 'blocked'), array('id' => $key_row_id));
    }

    public static function expire_license_key_by_row_id($key_row_id)
    {
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;

        // Sanitize input
        $key_row_id = intval($key_row_id);

        $wpdb->update($license_table, array('lic_status' => 'expired'), array('id' => $key_row_id));
    }

    public static function active_license_key_by_row_id($key_row_id)
    {
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;
        $current_date = current_time('Y-m-d');

        // Sanitize input
        $key_row_id = intval($key_row_id);

        $wpdb->update($license_table, array('lic_status' => 'active'), array('id' => $key_row_id));
        $wpdb->update($license_table, array('date_activated' => $current_date), array('id' => $key_row_id));
    }

    /*
 * Deletes any registered domains and related entries for the given license key's row id.
 */
    static function delete_registered_domains_of_key($key_row_id)
    {
        global $slm_debug_logger;
        global $wpdb;

        // Table constants
        $reg_domain_table = SLM_TBL_LIC_DOMAIN;
        $device_table = SLM_TBL_LIC_DEVICES;
        $log_table = SLM_TBL_LIC_LOG;
        $email_table = SLM_TBL_EMAILS;

        // Retrieve the license key associated with this row id
        $license_key = $wpdb->get_var($wpdb->prepare("SELECT license_key FROM " . SLM_TBL_LICENSE_KEYS . " WHERE id = %d", $key_row_id));

        if ($license_key) {
            // Step 1: Delete from registered domains table
            $reg_domains = $wpdb->get_results($wpdb->prepare("SELECT id FROM $reg_domain_table WHERE lic_key_id = %d", $key_row_id));
            foreach ($reg_domains as $domain) {
                $wpdb->delete($reg_domain_table, array('id' => $domain->id));
                $slm_debug_logger->log_debug("Registered domain with row id (" . $domain->id . ") deleted.");
            }

            // Step 2: Delete from devices table
            $deleted_devices = $wpdb->delete($device_table, array('lic_key' => $license_key), array('%s'));
            $slm_debug_logger->log_debug("$deleted_devices entries deleted from devices table for license key ($license_key).");

            // Step 3: Delete from log table
            $deleted_logs = $wpdb->delete($log_table, array('license_key' => $license_key), array('%s'));
            $slm_debug_logger->log_debug("$deleted_logs entries deleted from log table for license key ($license_key).");

            // Step 4: Delete from emails table
            $deleted_emails = $wpdb->delete($email_table, array('lic_key' => $license_key), array('%s'));
            $slm_debug_logger->log_debug("$deleted_emails entries deleted from emails table for license key ($license_key).");
        } else {
            $slm_debug_logger->log_debug("No license key found for row id ($key_row_id). Deletion aborted.");
        }
    }


    static function create_secret_keys()
    {
        // Generate secure random bytes (32 bytes = 256 bits)
        $random_bytes = openssl_random_pseudo_bytes(32); // 32 bytes (256 bits)

        // Convert the random bytes into a hexadecimal string (64 chars)
        $random_string = bin2hex($random_bytes);

        // Make the entire string uppercase
        $key = strtoupper($random_string);

        return $key;
    }

    public static function create_log($license_key, $action)
    {
        global $wpdb;
        $slm_log_table = SLM_TBL_LIC_LOG;

        // Sanitize inputs
        $license_key = sanitize_text_field($license_key);
        $action = sanitize_text_field($action);

        // Determine the request origin
        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            $origin = sanitize_text_field($_SERVER['HTTP_ORIGIN']);
        } elseif (!empty($_SERVER['HTTP_REFERER'])) {
            $origin = sanitize_text_field($_SERVER['HTTP_REFERER']);
        } else {
            $origin = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }

        // Prepare log data
        $log_data = array(
            'license_key' => $license_key,
            'slm_action'  => $action,
            'time'        => current_time('mysql'), // Standardized date-time format
            'source'      => $origin,
        );

        // Insert log data into the database
        $inserted = $wpdb->insert($slm_log_table, $log_data);

        // Check for insertion errors
        if ($inserted === false) {
            error_log("Failed to insert log for license key: $license_key, action: $action. Error: " . $wpdb->last_error);
        }
    }


    public static function create_email_log($lic_key, $sent_to, $status, $sent, $date_sent = null)
    {
        global $wpdb;
        $slm_email_table = SLM_TBL_EMAILS;

        // Sanitize inputs
        $lic_key = sanitize_text_field($lic_key);
        $sent_to = sanitize_email($sent_to);
        $status = sanitize_text_field($status);
        $sent = sanitize_text_field($sent);
        $date_sent = $date_sent ? sanitize_text_field($date_sent) : current_time('mysql');

        // Prepare log data
        $log_data = array(
            'lic_key'   => $lic_key,
            'sent_to'   => $sent_to,
            'status'    => $status,
            'sent'      => $sent,
            'date_sent' => $date_sent,
        );

        // Insert log data into the database
        $inserted = $wpdb->insert($slm_email_table, $log_data);

        // Check for insertion success and log accordingly
        if ($inserted !== false) {
            SLM_Helper_Class::write_log("Email log created for license key: $lic_key");
        } else {
            error_log("Failed to create email log for license key: $lic_key. Error: " . $wpdb->last_error);
        }
    }

    static function slm_wp_dashboards_stats($amount)
    {
        global $wpdb;
        $slm_log_table  = SLM_TBL_LICENSE_KEYS;

        $result = $wpdb->get_results(" SELECT * FROM  $slm_log_table ORDER BY id DESC LIMIT $amount");

        foreach ($result as $license) {
            echo '<tr>
                <td>
                <strong>' . esc_html($license->first_name) . ' ' . esc_html($license->last_name) . '</strong><br>
                <a href="' . esc_url(admin_url('admin.php?page=slm_manage_license&edit_record=' . $license->id)) . '">' . esc_html($license->license_key) . '</a>
                </td>
            </tr>';
        }
    }

    static function slm_get_licinfo($api_action, $license_key)
    {
        $api_url = get_site_url() . '/?secret_key=' . SLM_Helper_Class::slm_get_option('lic_verification_secret') . '&slm_action=' . $api_action . '&license_key=' . $license_key;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $json = json_decode($response);
        return $json;
    }

    static function get_subscriber_licenses()
    {
        global $wpdb;
        $email = $_GET['email'];
        $manage_subscriber = $_GET['manage_subscriber'];

        if (isset($email) && isset($manage_subscriber) && current_user_can('edit_pages')) {

            echo '<h2>Listing all licenses related to ' . esc_html($email) . '</h2>';

            $result_array = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM " . SLM_TBL_LICENSE_KEYS . " WHERE email LIKE %s ORDER BY `email` DESC LIMIT 0,1000",
                    '%' . $wpdb->esc_like($email) . '%'
                ),
                ARRAY_A
            );


            foreach ($result_array as $slm_user) {
                echo '  <tr>
                    <td scope="row">' . esc_html($slm_user["id"]) . '</td>
                    <td scope="row">' . esc_html($slm_user["license_key"]) . '</td>
                    <td scope="row">' . esc_html($slm_user["lic_status"]) . '</td>
                    <td scope="row"><a href="' . esc_url(admin_url('admin.php?page=slm_manage_license&edit_record=' . $slm_user["id"])) . '">' . esc_html__('View', 'slm-plus') . ' </a></td>
                </tr>';
            }
        }
    }

    static function get_lic_activity($license_key)
    {
        global $wpdb;
        $slm_log_table  = SLM_TBL_LIC_LOG;

        echo '
       <div class="table-responsive">
            <table class="table table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th scope="col">' . esc_html__('ID', 'slm-plus') . '</th>
                        <th scope="col">' . esc_html__('Request', 'slm-plus') . '</th>
                    </tr>
                </thead>
                <tbody>
        ';
        $activity = $wpdb->get_results("SELECT * FROM " . $slm_log_table . " WHERE license_key='" .  $license_key . "';");
        foreach ($activity as $log) {
            echo '
            <tr>' .
                '<th scope="row">' . esc_html($log->id) . '</th>' .
                '<td> <span class="badge badge-primary">' . esc_html($log->slm_action) . '</span>' .
                '<p class="text-muted"> <b>' . esc_html__('Source:', 'slm-plus') . ' </b> ' . esc_html($log->source) .
                '</p><p class="text-muted"> <b>' . esc_html__('Time:', 'slm-plus') . ' </b> ' . esc_html($log->time) . '</td>
            </tr>';
        }
        echo '
                </tbody>
            </table>
        </div>';
    }

    static function get_license_activation($license_key, $tablename, $item_name, $activation_type, $allow_removal = true)
    {
?>
        <div class="table">
            <h5> <?php echo esc_html($item_name); ?> </h5>
            <?php
            global $wpdb;
            $sql_prep = $wpdb->prepare("SELECT * FROM $tablename WHERE lic_key = %s", $license_key);
            $activations = $wpdb->get_results($sql_prep, OBJECT);

            if (count($activations) > 0) : ?>
                <div id="slm_ajax_msg"></div>
                <div class="<?php echo esc_attr($item_name); ?>_info">
                    <table cellpadding="0" cellspacing="0" class="table">
                        <?php
                        $count = 0;
                        foreach ($activations as $activation) : ?>
                            <div class="input-group mb-3 lic-entry-<?php echo esc_attr($activation->id); ?>">
                                <?php
                                if ($item_name == 'Devices') {
                                    echo '<input type="text" class="form-control" placeholder="' . esc_attr($activation->registered_devices) . '" aria-label="' . esc_attr($activation->registered_devices) . '" aria-describedby="' . esc_attr($activation->registered_devices) . '" value="' . esc_attr($activation->registered_devices) . '" readonly>';
                                } else {
                                    echo '<input type="text" class="form-control" placeholder="' . esc_attr($activation->registered_domain) . '" aria-label="' . esc_attr($activation->registered_domain) . '" aria-describedby="' . esc_attr($activation->registered_domain) . '" value="' . esc_attr($activation->registered_domain) . '" readonly>';
                                }
                                ?>
                                <?php if ($allow_removal == true) : ?>
                                    <div class="input-group-append">
                                        <button class="btn btn-danger deactivate_lic_key" type="button" data-lic_key="<?php echo esc_attr($activation->lic_key); ?>" id="<?php echo esc_attr($activation->id); ?>" data-activation_type="<?php echo esc_attr($activation_type); ?>" data-id="<?php echo esc_attr($activation->id); ?>"> Remove</button>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php $count++; ?>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php else : ?>
                <?php echo '<div class="alert alert-danger" role="alert">' . esc_html__('Not registered yet', 'slm-plus') . '</div>'; ?>
            <?php endif; ?>
        </div>
<?php
    }


    static function slm_woo_build_tab()
    {
        do_action('woocommerce_before_add_to_cart_form');

        add_filter('woocommerce_product_tabs', 'slm_woo_product_tab');
        function slm_woo_product_tab($tabs)
        {
            global $product;

            if ($product->is_type('slm_license')) {
                $tabs['shipping'] = array(
                    'title'     => __('License information', 'slm-plus'),
                    'priority'  => 50,
                    'callback'  => 'slm_woo_tab_lic_info'
                );
            }
            return $tabs;
        }

        function slm_woo_tab_lic_info()
        {
            global $product;
            // The new tab content
            echo '<h2>' . esc_html__('License information', 'slm-plus') . '</h2>';
            echo esc_html__('License type: ', 'slm-plus') . esc_html(get_post_meta($product->get_id(), '_license_type', true)) . '<br>';
            echo esc_html__('Domains allowed: ', 'slm-plus') . esc_html(get_post_meta($product->get_id(), '_domain_licenses', true)) . '<br>';
            echo esc_html__('Devices allowed: ', 'slm-plus') . esc_html(get_post_meta($product->get_id(), '_devices_licenses', true)) . '<br>';
            echo esc_html__('Renews every ', 'slm-plus') . esc_html(get_post_meta($product->get_id(), '_license_renewal_period_lenght', true)) . ' ' . esc_html(get_post_meta($product->get_id(), '_license_renewal_period_term', true)) . '<br>';
        }
    }
}
