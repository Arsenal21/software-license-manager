<?php

/*
 * Contains some utility functions for the plugin.
 */

// Helper Class
class SLM_Helper_Class {

    public static function slm_get_option($option)
    {
        $option_name    = '';
        $slm_opts       = get_option('slm_plugin_options');
        $option_name    = $slm_opts[$option];
        return $option_name;
    }
    static function write_log($log)
    {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}
$slm_helper = new SLM_Helper_Class();

class SLM_Utility {

    static function check_for_expired_lic($lic_key=''){
        global $wpdb, $first_name, $body, $date_expiry, $license_key, $expiration_reminder_text;

        $headers                    = array('Content-Type: text/html; charset=UTF-8');
        $response                   = '';
        $sql_query                  = $wpdb->get_results("SELECT * FROM " . SLM_TBL_LICENSE_KEYS . " WHERE date_expiry < NOW() ORDER BY date_expiry ASC;", ARRAY_A);
        $subject                    = get_bloginfo('name') . ' - Your license has expired';
        $expiration_reminder_text   = SLM_Helper_Class::slm_get_option( 'expiration_reminder_text');

        //SLM_Helper_Class::write_log('Found: ' . $expiration_reminder_text);

        if (count( $sql_query) > 0) {

            foreach ($sql_query as $expired_licenses) {

                // TODO move to template
                include SLM_LIB . 'mails/expired.php';

                $license_key            = $expired_licenses['license_key'];
                $first_name             = $expired_licenses['first_name'];
                $last_name              = $expired_licenses['last_name'];
                $email                  = $expired_licenses['email'];
                $date_expiry            = $expired_licenses['date_expiry'];

                //SLM_Helper_Class::write_log('Found: ' . $license_key);
                self::slm_check_sent_emails($license_key, $email, $subject, $body, $headers);
                self::create_log($license_key, 'sent expiration email notification');

                //SLM_Helper_Class::write_log('DB record logged');
                $response = 'Reminder message was sent to: ' . $license_key;
                //SLM_Helper_Class::write_log($response);
            }
        }
        else {
            SLM_Helper_Class::write_log('array is empty');
            $response = 'array is empty';
        }
        return $response;
    }

    static function slm_check_sent_emails($license_key, $email, $subject, $body, $headers)
    {
        global $wpdb;
        $query           = 'SELECT * FROM ' . SLM_TBL_EMAILS . ' WHERE lic_key = "' . $license_key . '";';
        $lic_log_results = $wpdb->get_results($query, ARRAY_A);

        if (count($lic_log_results) > 0) {
            foreach ($lic_log_results as $license) {
                if ($license["lic_key"] != $license_key) {
                    // TODO: use mail class from include
                    wp_mail($email, $subject, $body, $headers);
                    self::create_email_log($license_key, $email, 'success', 'yes', date("Y/m/d"));
                    return '200'; //reminder was never sent before, first time (record does not exist)
                }
                else {
                    //reminder was sent before
                    return '400';
                }
            }
        }
        else {
            // array or results are empty (lic key was not found)
            // TODO: use mail class from include
            wp_mail($email, $subject, $body, $headers);
            self::create_email_log($license_key, $email, 'success', 'yes', date("Y/m/d"));
            return '300';
        }
    }

    static function do_auto_key_expiry() {
        global $wpdb;
        $current_date = (date ("Y-m-d"));
        $tbl_name = SLM_TBL_LICENSE_KEYS;

        $sql_prep = $wpdb->prepare("SELECT * FROM $tbl_name WHERE lic_status !=%s", 'expired');//Load the non-expired keys
        $licenses = $wpdb->get_results($sql_prep, OBJECT);
        if(!$licenses){
            SLM_Debug_Logger::log_debug_st("do_auto_key_expiry() - no license keys found.");
            return false;
        }

        foreach($licenses as $license){
            $key = $license->license_key;
            $expiry_date = $license->date_expiry;
            if ($expiry_date == '0000-00-00'){
                SLM_Debug_Logger::log_debug_st("This key (".$key.") doesn't have a valid expiry date set. The expiry of this key will not be checked.");
                continue;
            }

            $today_dt = new DateTime($current_date);
            $expire_dt = new DateTime($expiry_date);

            if ($today_dt > $expire_dt) {
                //This key has reached the expiry. So expire this key.
                SLM_Debug_Logger::log_debug_st("This key (".$key.") has expired. Expiry date: ".$expiry_date.". Setting license key status to expired.");
                $data = array('lic_status' => 'expired');
                $where = array('id' => $license->id);
                $updated = $wpdb->update($tbl_name, $data, $where);

                do_action('slm_license_key_expired',$license->id);
                self::check_for_expired_lic( $key);
            }

        }
    }

    static function get_user_info($by, $value) {
       $user =  get_user_by( $by, $value);
       return $user;
    }

    static function get_days_remaining( $date1 ){

        $future = strtotime($date1);
        $now = time();
        $timeleft = $future - $now;
        $daysleft = round((($timeleft / 24) / 60) / 60);
        return $daysleft;
    }

    /*
     * Deletes a license key from the licenses table
     */
    static function delete_license_key_by_row_id($key_row_id) {
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;

        //First delete the registered domains entry of this key (if any).
        SLM_Utility::delete_registered_domains_of_key($key_row_id);

        //Now, delete the key from the licenses table.
        $wpdb->delete( $license_table, array( 'id' => $key_row_id ) );

    }

    static function count_licenses($status){
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;
        $get_lic_status = $wpdb->get_var("SELECT COUNT(*) FROM $license_table WHERE lic_status = '" . $status . "'");
        return $get_lic_status;
    }

    static function get_total_licenses(){
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;
        $license_count = $wpdb->get_var("SELECT COUNT(*) FROM  " . $license_table . "");
        return  $license_count;
    }

    static function block_license_key_by_row_id($key_row_id){
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;
        //Now, delete the key from the licenses table.
        $wpdb->update( $license_table, array('lic_status' => 'blocked'), array('id' => $key_row_id));

    }

    static function expire_license_key_by_row_id($key_row_id){
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;

        //Now, delete the key from the licenses table.
        $wpdb->update($license_table, array('lic_status' => 'expired'), array('id' => $key_row_id));
    }

    static function active_license_key_by_row_id($key_row_id)
    {
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;
        $current_date = date('Y/m/d');
        // 'lic_status' => ''. $current_date.''

        $wpdb->update($license_table, array('lic_status' => 'active'), array('id' => $key_row_id));
        $wpdb->update($license_table, array('date_activated' => '' . $current_date . ''), array('id' => $key_row_id));
    }

    /*
     * Deletes any registered domains info from the domain table for the given key's row id.
     */
    static function delete_registered_domains_of_key($key_row_id) {
        global $slm_debug_logger;
        global $wpdb;
        $reg_table = SLM_TBL_LIC_DOMAIN;
        $sql_prep = $wpdb->prepare("SELECT * FROM $reg_table WHERE lic_key_id = %s", $key_row_id);
        $reg_domains = $wpdb->get_results($sql_prep, OBJECT);
        foreach ($reg_domains as $domain) {
            $row_to_delete = $domain->id;
            $wpdb->delete( $reg_table, array( 'id' => $row_to_delete ) );
            $slm_debug_logger->log_debug("Registered domain with row id (".$row_to_delete.") deleted.");
        }
    }

    static function create_secret_keys() {
        $key = strtoupper(implode('-', str_split(substr(strtolower(md5(microtime() . rand(1000, 9999))), 0, 32), 8)));
        return hash('sha256', $key);
    }

    static function create_log($license_key, $action){
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
            'time'          => date("Y/m/d"),
            'source'        => $origin
        );

        $wpdb->insert( $slm_log_table, $log_data );

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
        SLM_Helper_Class::write_log('email log created for '. $lic_key);
    }



    static function slm_wp_dashboards_stats($amount){
        global $wpdb;
        $slm_log_table  = SLM_TBL_LICENSE_KEYS;

        $result = $wpdb->get_results(" SELECT * FROM  $slm_log_table LIMIT $amount");

        foreach ($result as $license) {
            echo '<tr>
                    <td>
                    <strong> '. $license->first_name . ' ' .$license->last_name .' </strong><br>
                    <a href="' . admin_url('admin.php?page=slm_manage_license&edit_record=' . $license->id . '') . '">' . $license->license_key . ' </td>
                </tr>';
        }
    }

    static function get_subscriber_licenses(){
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
                            <td scope="row"><a href="' . admin_url('admin.php?page=slm_manage_license&edit_record=' . $slm_user["id"] . '') . '"> view </a></td>
                        </tr>';
            }
        }
    }

    static function get_lic_activity($license_key){
        global $wpdb;
        $slm_log_table  = SLM_TBL_LIC_LOG;

        echo '
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm">
                <thead>
                    <tr>
                    <th scope="col">Request ID</th>
                    <th scope="col">Info</th>
                    </tr>
                </thead>
                <tbody>
        ';
        $activity = $wpdb->get_results( "SELECT * FROM " . $slm_log_table . " WHERE license_key='" .  $license_key."';");
        foreach ($activity as $log) {
            echo '
                <tr>' .
                    '<th scope="row">' . $log->id . '</th>' .
                    '<td> <span class="badge badge-primary">' . $log->slm_action  . '</span>' .
                    '<p class="text-muted"> <b>Source: </b> ' . $log->source .
                    '</p><p class="text-muted"> <b>Time: </b> ' . $log->time . '</td>
                </tr>';
        }
        echo '
                </tbody>
            </table>
        </div>';
    }



}

