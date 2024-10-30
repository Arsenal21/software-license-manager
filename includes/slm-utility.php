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

    /**
     * PHP Logger
     */

    static function console($data)
    {
        $output = $data;
        if (is_array($output))
            $output = implode(',', $output);

        // print the result into the JavaScript console
        echo "<script>console.log( 'PHP LOG: " . $output . "' );</script>";
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
    public static function slm_validate_date($date) {
        $date = sanitize_text_field($date);
        $timestamp = strtotime($date);
        if ($timestamp && date('Y-m-d', $timestamp) === $date) {
            return $date;
        }
        return ''; // Return an empty string if the date is invalid
    }

    public static function verify_secret_key()
    {
        $slm_options = get_option('slm_plugin_options');
        $right_secret_key = $slm_options['lic_verification_secret'] ?? '';
        $received_secret_key = sanitize_text_field($_REQUEST['secret_key'] ?? '');

        if ($received_secret_key !== $right_secret_key) {
            $args = array(
                'result' => 'error',
                'message' => 'Verification API secret key is invalid',
                'error_code' => SLM_Error_Codes::VERIFY_KEY_INVALID
            );
            self::output_api_response($args);
        }
    }
    public static function get_slm_option($option)
    {
        $slm_options_func =  get_option('slm_plugin_options', []);
        $option = $slm_options_func[$option];
        return $option;
    }


    public static function verify_secret_key_for_creation()
    {
        $slm_options = get_option('slm_plugin_options');
        $right_secret_key = $slm_options['lic_creation_secret'] ?? '';
        $received_secret_key = sanitize_text_field($_REQUEST['secret_key'] ?? '');

        if ($received_secret_key !== $right_secret_key) {
            $args = array(
                'result' => 'error',
                'message' => 'License Creation API secret key is invalid',
                'error_code' => SLM_Error_Codes::CREATE_KEY_INVALID
            );
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

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $response = '';
        $sql_query = $wpdb->get_results(
            "SELECT * FROM " . SLM_TBL_LICENSE_KEYS . " WHERE date_expiry < NOW() AND date_expiry != '00000000' ORDER BY date_expiry ASC;",
            ARRAY_A
        );
        $subject = get_bloginfo('name') . ' - Your license has expired';
        $expiration_reminder_text = SLM_Helper_Class::slm_get_option('expiration_reminder_text');

        if (count($sql_query) > 0) {
            foreach ($sql_query as $expired_licenses) {
                // TODO move to template
                include SLM_LIB . 'mails/expired.php';

                $id = intval($expired_licenses['id']);
                $license_key = sanitize_text_field($expired_licenses['license_key']);
                $first_name = sanitize_text_field($expired_licenses['first_name']);
                $last_name = sanitize_text_field($expired_licenses['last_name']);
                $email = sanitize_email($expired_licenses['email']);
                $date_expiry = sanitize_text_field($expired_licenses['date_expiry']);

                if (SLM_Helper_Class::slm_get_option('enable_auto_key_expiration') == 1) {
                    $data = array('lic_status' => 'expired');
                    $where = array('id' => $id);
                    $updated = $wpdb->update(SLM_TBL_LICENSE_KEYS, $data, $where);

                    self::create_log($license_key, 'set to expired');
                    self::slm_check_sent_emails($license_key, $email, $subject, $body, $headers);
                    self::create_log($license_key, 'sent expiration email notification');
                }

                $response = 'Reminder message was sent to: ' . $license_key;
            }
        } else {
            SLM_Helper_Class::write_log('No expired licenses found');
            $response = 'No expired licenses found';
        }
        return $response;
    }


    public static function slm_check_sent_emails($license_key, $email, $subject, $body, $headers)
    {
        global $wpdb;

        // Prepare the query to avoid SQL injection
        $query = $wpdb->prepare(
            'SELECT * FROM ' . SLM_TBL_EMAILS . ' WHERE lic_key = %s',
            $license_key
        );
        $lic_log_results = $wpdb->get_results($query, ARRAY_A);

        if (!empty($lic_log_results)) {
            foreach ($lic_log_results as $license) {
                if ($license['lic_key'] !== $license_key) {
                    // Send email if the license key does not match
                    wp_mail($email, $subject, $body, $headers);
                    self::create_email_log($license_key, $email, 'success', 'yes', current_time('mysql'));
                    return '200'; // Reminder was never sent before, first time (record does not exist)
                } else {
                    // Reminder was sent before
                    return '400';
                }
            }
        } else {
            // Array or results are empty (lic key was not found)
            wp_mail($email, $subject, $body, $headers);
            self::create_email_log($license_key, $email, 'success', 'yes', current_time('mysql'));
            return '300';
        }
    }


    public static function do_auto_key_expiry()
    {
        global $wpdb;
        $current_date = current_time('Y-m-d');
        $slm_lic_table = SLM_TBL_LICENSE_KEYS;

        // Load the non-expired keys
        $licenses = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $slm_lic_table WHERE lic_status != %s", 'expired'),
            OBJECT
        );

        if (empty($licenses)) {
            SLM_Debug_Logger::log_debug_st("do_auto_key_expiry() - no license keys found.");
            return false;
        }

        foreach ($licenses as $license) {
            $key = sanitize_text_field($license->license_key);
            $expiry_date = sanitize_text_field($license->date_expiry);

            if ($expiry_date === '0000-00-00' || $expiry_date === '00000000' || empty($expiry_date)) {
                SLM_Debug_Logger::log_debug_st("This key (" . $key . ") doesn't have a valid expiration date set. The expiration of this key will not be checked.");
                continue;
            }

            $today_dt = new DateTime($current_date);
            $expire_dt = new DateTime($expiry_date);

            if ($today_dt > $expire_dt) {
                // This key has reached the expiry. So expire this key.
                SLM_Debug_Logger::log_debug_st("This key (" . $key . ") has expired. Expiry date: " . $expiry_date . ". Setting license key status to expired.");
                $data = array('lic_status' => 'expired');
                $where = array('id' => intval($license->id));
                $updated = $wpdb->update($slm_lic_table, $data, $where);

                do_action('slm_license_key_expired', $license->id);
                self::check_for_expired_lic($key);
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
    
        // Convert the future date to timestamp
        $future = strtotime($date1);
    
        // Check if the date is valid
        if (!$future) {
            return 0; // Return 0 if the date is invalid
        }
    
        // Get the current timestamp
        $now = time();
    
        // Calculate the time difference in seconds
        $timeleft = $future - $now;
    
        // Convert time difference to days
        $daysleft = floor($timeleft / (60 * 60 * 24));
    
        // Ensure we don't return negative days if the date has passed
        if ($daysleft < 0) {
            $daysleft = 0;
        }
    
        // Retrieve the date format setting from WordPress settings
        $date_format = get_option('date_format');
    
        // Return the formatted date remaining with the number of days left
        return sprintf(
            __('%s days remaining until %s', 'slmplus'),
            $daysleft,
            date_i18n($date_format, $future)
        );
    }
    


    /*
 * Deletes a license key from the licenses table
 */
    public static function delete_license_key_by_row_id($key_row_id)
    {
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;

        // Sanitize the input
        $key_row_id = intval($key_row_id);

        // First delete the registered domains entry of this key (if any).
        SLM_Utility::delete_registered_domains_of_key($key_row_id);

        // Now, delete the key from the licenses table.
        $wpdb->delete($license_table, array('id' => $key_row_id));
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

        // Prepare the query
        $email = $wpdb->get_var($wpdb->prepare("SELECT email FROM $lic_key_table WHERE license_key = %s", $license));
        return $email;
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
        <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet" type="text/css" />
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
     * Deletes any registered domains info from the domain table for the given key's row id.
     */
    static function delete_registered_domains_of_key($key_row_id)
    {
        global $slm_debug_logger;
        global $wpdb;
        $reg_domain_table = SLM_TBL_LIC_DOMAIN;
        $sql_prep = $wpdb->prepare("SELECT * FROM $reg_domain_table WHERE lic_key_id = %s", $key_row_id);
        $reg_domains = $wpdb->get_results($sql_prep, OBJECT);
        foreach ($reg_domains as $domain) {
            $row_to_delete = $domain->id;
            $wpdb->delete($reg_domain_table, array('id' => $row_to_delete));
            $slm_debug_logger->log_debug("Registered domain with row id (" . $row_to_delete . ") deleted.");
        }
    }

    static function create_secret_keys()
    {
        $key = strtoupper(implode('-', str_split(substr(strtolower(md5(microtime() . rand(1000, 9999))), 0, 32), 8)));
        return hash('sha256', $key);
    }

    static function create_log($license_key, $action)
    {
        global $wpdb;
        $slm_log_table  = SLM_TBL_LIC_LOG;
        $origin = '';

        if (array_key_exists('HTTP_ORIGIN', $_SERVER)) {
            $origin = $_SERVER['HTTP_ORIGIN'];
        } else if (array_key_exists('HTTP_REFERER', $_SERVER)) {
            $origin = $_SERVER['HTTP_REFERER'];
        } else {
            $origin = $_SERVER['REMOTE_ADDR'];
        }

        $log_data = array(
            'license_key'   => $license_key,
            'slm_action'    => $action,
            'time'          => wp_date("Y/m/d"),
            'source'        => $origin
        );

        $wpdb->insert($slm_log_table, $log_data);
    }

    static function create_email_log($lic_key, $sent_to, $status, $sent, $date_sent)
    {
        global $wpdb;
        $slm_email_table  = SLM_TBL_EMAILS;

        $log_data = array(
            'lic_key'       => $lic_key,
            'sent_to'       => $sent_to,
            'status'        => $status,
            'sent'          => $sent,
            'date_sent'     => $date_sent
        );

        $wpdb->insert($slm_email_table, $log_data);
        SLM_Helper_Class::write_log('email log created for ' . $lic_key);
    }

    static function slm_wp_dashboards_stats($amount)
    {
        global $wpdb;
        $slm_log_table  = SLM_TBL_LICENSE_KEYS;

        $result = $wpdb->get_results(" SELECT * FROM  $slm_log_table ORDER BY id DESC LIMIT $amount");

        foreach ($result as $license) {
            echo '<tr>
                    <td>
                    <strong> ' . $license->first_name . ' ' . $license->last_name . ' </strong><br>
                    <a href="' . admin_url('admin.php?page=slm_manage_license&edit_record=' . $license->id . '') . '">' . $license->license_key . ' </td>
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

            echo '<h2>Listing all licenses related to ' . $email . '</h2>';

            $result_array = $wpdb->get_results("SELECT * FROM " . SLM_TBL_LICENSE_KEYS . " WHERE email LIKE '%" . $email . "%'  ORDER BY `email` DESC LIMIT 0,1000", ARRAY_A);

            foreach ($result_array as $slm_user) {
                echo '  <tr>
                            <td scope="row">' . $slm_user["id"] . '</td>
                            <td scope="row">' . $slm_user["license_key"] . '</td>
                            <td scope="row">' . $slm_user["lic_status"] . '</td>
                            <td scope="row"><a href="' . admin_url('admin.php?page=slm_manage_license&edit_record=' . $slm_user["id"] . '') . '">'. __(' view', 'slmplus'). ' </a></td>
                        </tr>';
            }
        }
    }

    static function get_lic_activity($license_key)
    {
        global $wpdb;
        $slm_log_table  = SLM_TBL_LIC_LOG;

        echo '
        <div class="table-responsive"> <table class="table table-striped table-hover table-sm"> <thead> <tr> <th scope="col">'. __('ID', 'slmplus'). '</th> <th scope="col">'. __('Request', 'slmplus'). '</th> </tr> </thead> <tbody>
        ';
        $activity = $wpdb->get_results("SELECT * FROM " . $slm_log_table . " WHERE license_key='" .  $license_key . "';");
        foreach ($activity as $log) {
            echo '
                <tr>' .
                '<th scope="row">' . $log->id . '</th>' .
                '<td> <span class="badge badge-primary">' . $log->slm_action  . '</span>' .
                '<p class="text-muted"> <b>'. __('Source:', 'slmplus'). ' </b> ' . $log->source .
                '</p><p class="text-muted"> <b>'. __('Time:', 'slmplus'). ' </b> ' . $log->time . '</td>
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
            <h5> <?php echo $item_name; ?> </h5>
            <?php
            global $wpdb;
            $sql_prep = $wpdb->prepare("SELECT * FROM $tablename WHERE lic_key = %s", $license_key);
            $activations = $wpdb->get_results($sql_prep, OBJECT);

            if (count($activations) > 0) : ?>
                <div id="slm_ajax_msg"></div>
                <div class="<?php echo $item_name; ?>_info">
                    <table cellpadding="0" cellspacing="0" class="table">
                        <?php
                        $count = 0;
                        foreach ($activations as $activation) : ?>
                            <div class="input-group mb-3 lic-entry-<?php echo $activation->id; ?>">
                                <?php
                                if ($item_name == 'Devices') {
                                    echo '<input type="text" class="form-control" placeholder="' . $activation->registered_devices . '" aria-label="' . $activation->registered_devices . '" aria-describedby="' . $activation->registered_devices . '" value="' . $activation->registered_devices . '"  readonly>';
                                } else {
                                    echo '<input type="text" class="form-control" placeholder="' . $activation->registered_domain . '" aria-label="' . $activation->registered_domain . '" aria-describedby="' . $activation->registered_domain . '" value="' . $activation->registered_domain . '" readonly>';
                                }
                                ?>
                                <?php if ($allow_removal == true) : ?>
                                    <div class="input-group-append">
                                        <button class="btn btn-danger deactivate_lic_key" type="button" data-lic_key="<?php echo $activation->lic_key; ?>'" id="<?php echo $activation->id; ?>" data-activation_type="<?php echo $activation_type;?>" data-id="<?php echo $activation->id; ?>"> Remove</button>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php $count++; ?>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php else : ?>
                <?php echo '<div class="alert alert-danger" role="alert">'.__('Not registered yet', 'slmplus').'</div>'; ?>
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
                    'title'     => __('License information', 'slmplus'),
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
            echo '<h2>'.__('License information', 'slmplus') .'</h2>';
            echo __('License type: ', 'slmplus')  . get_post_meta($product->get_id(), '_license_type', true) . '<br>';
            echo __('Domains allowed: ', 'slmplus') . get_post_meta($product->get_id(), '_domain_licenses', true) . '<br>';
            echo __('Devices allowed: ', 'slmplus') . get_post_meta($product->get_id(), '_devices_licenses', true) . '<br>';
            echo __('Renews every ', 'slmplus') . get_post_meta($product->get_id(), '_license_renewal_period_lenght', true) . ' ' . get_post_meta($product->get_id(), '_license_renewal_period_term', true) . '<br>';
        }
    }
}
