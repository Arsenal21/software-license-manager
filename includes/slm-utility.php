<?php

/*
 * Contains some utility functions for the plugin.
 */

// Helper Class

// define the wp_mail_failed callback
function action_wp_mail_failed($wp_error)
{
    return error_log(print_r($wp_error, true));
}

// add the action
add_action('wp_mail_failed', 'action_wp_mail_failed', 10, 1);


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

    static function output_api_response($args)
    {
        //Log to debug file (if enabled)
        global $slm_debug_logger;
        $slm_debug_logger->log_debug('API Response - Result: ' . $args['result'] . ' Message: ' . $args['message']);

        //Send response
        $args = apply_filters('slm_ap_response_args', $args);
        $args = apply_filters('slm_api_response_args', $args);

        header('Content-Type: application/json');
        echo json_encode($args);
        exit(0);
    }

    static function verify_secret_key()
    {
        $slm_options                = get_option('slm_plugin_options');
        $right_secret_key           = $slm_options['lic_verification_secret'];
        $received_secret_key        = strip_tags($_REQUEST['secret_key']);
        if ($received_secret_key    != $right_secret_key) {
            $args = (array(
                'result'        => 'error',
                'message'       => 'Verification API secret key is invalid',
                'error_code'    => SLM_Error_Codes::VERIFY_KEY_INVALID
            ));
            self::output_api_response($args);
        }
    }

    static function verify_secret_key_for_creation()
    {
        $slm_options                = get_option('slm_plugin_options');
        $right_secret_key           = $slm_options['lic_creation_secret'];
        $received_secret_key        = strip_tags($_REQUEST['secret_key']);
        if ($received_secret_key    != $right_secret_key) {
            $args = (array(
                'result'        => 'error',
                'message'       => 'License Creation API secret key is invalid',
                'error_code'    => SLM_Error_Codes::CREATE_KEY_INVALID
            ));
            self::output_api_response($args);
        }
    }

    static function insert_license_data_internal($fields)
    {
        global $wpdb;
        $tbl_name   = SLM_TBL_LICENSE_KEYS;
        $fields     = array_filter($fields); //Remove any null values.
        $result     = $wpdb->insert($tbl_name, $fields);
    }
}

class SLM_Utility {

    static function check_for_expired_lic($lic_key=''){
        global $wpdb, $first_name, $body, $date_expiry, $license_key, $expiration_reminder_text;

        $headers                    = array('Content-Type: text/html; charset=UTF-8');
        $response                   = '';
        $sql_query                  = $wpdb->get_results("SELECT * FROM " . SLM_TBL_LICENSE_KEYS . " WHERE date_expiry < NOW() AND NOT date_expiry='00000000' ORDER BY date_expiry ASC;", ARRAY_A);
        $subject                    = get_bloginfo('name') . ' - Your license has expired';
        $expiration_reminder_text   = SLM_Helper_Class::slm_get_option( 'expiration_reminder_text');

        //SLM_Helper_Class::write_log('Found: ' . $expiration_reminder_text);

        if (count( $sql_query) > 0) {

            foreach ($sql_query as $expired_licenses) {

                // TODO move to template
                include SLM_LIB . 'mails/expired.php';

                $id                     = $expired_licenses['id'];
                $license_key            = $expired_licenses['license_key'];
                $first_name             = $expired_licenses['first_name'];
                $last_name              = $expired_licenses['last_name'];
                $email                  = $expired_licenses['email'];
                $date_expiry            = $expired_licenses['date_expiry'];


                if(SLM_Helper_Class::slm_get_option('enable_auto_key_expiration') == 1 ){
                    global $wpdb;
                    $data = array('lic_status' => 'expired');
                    $where = array('id' => $id);
                    $updated = $wpdb->update(SLM_TBL_LICENSE_KEYS , $data, $where);

                    self::create_log($license_key, 'set to expired');

                    //SLM_Helper_Class::write_log('Found: ' . $license_key);
                    self::slm_check_sent_emails($license_key, $email, $subject, $body, $headers);
                    self::create_log($license_key, 'sent expiration email notification');
                }

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
            if ($expiry_date == '0000-00-00' || $expiry_date == '00000000' || $expiry_date == ''){
                SLM_Debug_Logger::log_debug_st("This key (".$key.") doesn't have a valid expiration date set. The expiration of this key will not be checked.");
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

    static function slm_get_lic_email($license) {
        // DOC: https://www.smashingmagazine.com/2011/09/interacting-with-the-wordpress-database/
        global $wpdb;
        $lic_key_table = SLM_TBL_LICENSE_KEYS;
        $email = $wpdb->get_var("SELECT email FROM $lic_key_table WHERE license_key='$license'");
        return $email;
    }

    static function slm_send_mail($to, $subject, $message, $bgcolor) {
        // send activation email
        $headers[] = 'From: '.get_bloginfo('name').' <'.get_bloginfo('admin_email').'>';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        $body = self::slm_email_template($message, $bgcolor);
        wp_mail($to, $subject, $body, $headers);
    }

    static function slm_email_template($message, $bgcolor = ''){
        if ($bgcolor == 'success'){
            $color = '#eceff0';
        }

        if (empty($bgcolor)){
            $color = '#eceff0';
        }

        if ($bgcolor == 'error'){
            $color = '#e23b2f';
        }


        $template = '<?xml version="1.0" encoding="UTF-8"?> <html xmlns="http://www.w3.org/1999/xhtml" style="background-color: '.$color.'; padding: 0; margin: 0;"> <head> <style type="text/css"> body, html { font-family: Helvetica, Arial; font-size: 13px; background-color: '.$color.'; background: '.$color.'; padding: 0px; margin: 0px; } a.schedule_btn, .schedule_btn { display: inline-block; background: #e93e40; color: #fff; text-decoration: none; padding: 6px 12px; text-align: center; border-radius: 2px; font-size: 16px; font-weight: 600; margin: 36px 0; } p.legal, .legal { text-align: center; font-size: 13px; font-family: "Open Sans, Helvetica, Arial, sans-serif; line-height: 22px; color: #aaacad; font-weight: 300 } p { font-size: 16px; font-weight: 300; color: #2d2d31; line-height: 26px; font-family: "Open Sans, helvetica, arial, sans-serif; } h2, h3, h5, h4, h6, h1 { color: #6b6e6f; font-size: 19px; padding: 0 0 15px 0; font-family: "Open Sans, Helvetica, Arial, Sans-serif; } </style> <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet" type="text/css" /> <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet" type="text/css" /> <title>Epikly</title> </head> <body style="word-wrap: break-word; -webkit-nbsp-mode: space; line-break: after-white-space; background-color: '.$color.'"> <div style="background-color: '.$color.' !important; font-family: " Open Sans,Helvetica,Arial, sans-serif, Helvetica; margin: 0px; padding: 16px 0 80px 0px; word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space; background-position: initial initial; background-repeat: initial initial;" bgcolor="'.$color.'" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0"> <br /> <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <tbody> <tr> <td align="center" style="background-color:'.$color.'; color:#FFFFFF;" valign="top"> <!-- Content table --> <table align="center" border="0" cellpadding="0" cellspacing="0" width="600"> <tbody> <tr> <td align="left" colspan="2" width="500" style="background-color:'.$color.';"> <div class="main" style="min-width: 320px;max-width: 500px;margin: 62px auto;background: #ffffff;padding: 35px 45px;-webkit-box-shadow: 1px 12px 15px -9px rgba(0,0,0,0.32); -moz-box-shadow: 1px 12px 15px -9px rgba(0,0,0,0.32); box-shadow: 1px 12px 15px -9px rgba(0,0,0,0.32);"> <br> <div class="logo" style="text-align: center; max-width: 160px; margin: 0 auto;"> <a href="'.get_home_url().'"> <img src="'.SLM_Utility::slm_get_icon_url('3x', 'verified.png').'" alt=""> </a> </div> <br> <h2 style="color: #6b6e6f;font-size: 19px;padding: 0 0 15px 0;font-family: Open Sans,Helvetica, Arial, Sans-serif; text-align: center">License key was activated successfully !</h2> <p style="font-size: 16px;font-weight: 300;color: #2d2d31;line-height: 26px;font-family: Open Sans,helvetica,arial,sans-serif;"> '.$message.' </p> <p>Regards, </p> <div class="signature"> <p style="color: #89898c; font-size: 14px; margin: 36px 0;line-height: 20px;"> <strong> '.get_bloginfo( 'name' ).' </strong> <br /> <a href="mailto: '.get_bloginfo( 'admin_email' ).'"> '.get_bloginfo( 'admin_email' ).'</a> </p> </div> </div> <div class="clear" style="height: 1px; clear: both;float: none; display: block; padding: 1px"> </div> <div class="more-support" style="min-width: 320px;max-width: 500px;margin: 0px auto;padding: 24px 0px;"> <p class="legal" style="text-align: center; font-size: 13px; font-family: Open Sans,Helvetica, Arial, sans-serif; line-height: 22px; color: #aaacad; font-weight: 300">The content of this email is confidential and intended for the recipient specified in message only. It is strictly forbidden to share any part of this message with any third party, without a written consent of the sender. If you received this message by mistake, please reply to this message and follow with its deletion, so that we can ensure such a mistake does not occur in the future.</p> <p class="legal" style="text-align: center; font-size: 13px; font-family: Open Sans,Helvetica, Arial, sans-serif; line-height: 22px; color: #aaacad; font-weight: 300">Questions? We are always here to help. Contact <a href="mailto: '.get_bloginfo( 'admin_email' ).'"> '.get_bloginfo( 'admin_email' ).'</a> or simply reply to this e-mail. </p> </div> </td> </tr> </tbody> </table> </td> </tr> </tbody> </table> </div> </body> </html>';
        return $template;

    }

    static function count_licenses($status){
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;
        $get_lic_status = $wpdb->get_var("SELECT COUNT(*) FROM $license_table WHERE lic_status = '" . $status . "'");
        return $get_lic_status;
    }
    static function slm_get_icon_url($size, $filename){
        return SLM_ASSETS_URL . 'icons/' . $size . '/' .$filename;
    }

    static function count_logrequest()
    {
        global $wpdb;
        $license_table = SLM_TBL_LIC_LOG;
        $getlogs = $wpdb->get_var("SELECT COUNT(*) FROM $license_table");
        return $getlogs;
    }

    static function count_emailsent()
    {
        global $wpdb;
        $license_table = SLM_TBL_EMAILS;
        $getlogs = $wpdb->get_var("SELECT COUNT(*) FROM $license_table");
        return $getlogs;
    }

    static function getstats_licenses($date_created, $interval)
    {
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;
        $query = $wpdb->get_var("SELECT COUNT(*) FROM $license_table WHERE $date_created >= DATE_ADD(CURDATE(), INTERVAL -" . $interval . " DAY)");
        return $query;
    }

    static function get_total_licenses(){
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;
        $license_count = $wpdb->get_var("SELECT COUNT(*) FROM  " . $license_table . "");
        return  $license_count;
    }

    static function get_lic_expiringsoon(){
        global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;
        $license_count = $wpdb->get_var("SELECT COUNT(*) FROM $license_table WHERE date_expiry BETWEEN DATE_SUB( CURDATE( ) ,INTERVAL 1 MONTH ) AND DATE_SUB( CURDATE( ) ,INTERVAL 0 MONTH );");
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

        $result = $wpdb->get_results(" SELECT * FROM  $slm_log_table ORDER BY id DESC LIMIT $amount");

        foreach ($result as $license) {
            echo '<tr>
                    <td>
                    <strong> '. $license->first_name . ' ' .$license->last_name .' </strong><br>
                    <a href="' . admin_url('admin.php?page=slm_manage_license&edit_record=' . $license->id . '') . '">' . $license->license_key . ' </td>
                </tr>';
        }
    }

    static function slm_get_licinfo ($api_action, $license_key){
        $api_url = get_site_url() . '/?secret_key=' . SLM_Helper_Class::slm_get_option('lic_verification_secret') . '&slm_action='.$api_action.'&license_key='.$license_key;
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
        <div class="table-responsive"> <table class="table table-striped table-hover table-sm"> <thead> <tr> <th scope="col">ID</th> <th scope="col">Request</th> </tr> </thead> <tbody>
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

    static function get_license_activation($license_key, $tablename, $item_name, $allow_removal = true) {
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
                        <div class="input-group mb-3 lic-entry-<?php echo $activation->id;?>">
                            <?php
                                if($item_name =='Devices'){
                                    echo '<input type="text" class="form-control" placeholder="' .$activation->registered_devices .'" aria-label="' .$activation->registered_devices .'" aria-describedby="' .$activation->registered_devices .'" value="' .$activation->registered_devices .'"  readonly>';
                                }
                                else {
                                    echo '<input type="text" class="form-control" placeholder="' .$activation->registered_domain .'" aria-label="' .$activation->registered_domain .'" aria-describedby="' .$activation->registered_domain .'" value="' .$activation->registered_domain .'" readonly>';
                                }
                            ?>
                            <?php if ($allow_removal ==true) : ?>
                            <div class="input-group-append">
                                <button class="btn btn-danger deactivate_lic_key" type="button" data-lic_key="<?php echo $activation->lic_key; ?>'" id="<?php echo $activation->id; ?>" data-id="<?php echo $activation->id; ?>"> Remove</button>
                            </div>
                            <?php endif; ?>
                        </div>

                            <?php $count++; ?>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php else : ?>
                <?php echo '<div class="alert alert-danger" role="alert">Not registered yet</div>'; ?>
            <?php endif; ?>
        </div>
    <?php
    }


    static function slm_woo_build_tab() {
        do_action( 'woocommerce_before_add_to_cart_form' );

        add_filter( 'woocommerce_product_tabs', 'slm_woo_product_tab' );
        function slm_woo_product_tab( $tabs ) {
            global $product;

            if( $product->is_type( 'slm_license' ) ) {
                $tabs['shipping'] = array(
                    'title'     => __( 'License information', 'softwarelicensemanager' ),
                    'priority'  => 50,
                    'callback'  => 'slm_woo_tab_lic_info'
                );
            }
            return $tabs;
        }

        function slm_woo_tab_lic_info() {
            global $product;
                // The new tab content
                echo '<h2>License information</h2>';
                echo 'License type: ' . get_post_meta($product->get_id(), '_license_type', true ) . '<br>';
                echo 'Domains allowed: ' . get_post_meta($product->get_id(), '_domain_licenses', true ) . '<br>';
                echo 'Devices allowed: ' . get_post_meta($product->get_id(), '_devices_licenses', true ) . '<br>';
                echo 'Renews every ' . get_post_meta($product->get_id(), '_license_renewal_period', true ) . ' ' . get_post_meta($product->get_id(), '_license_renewal_period_term', true ) . '<br>';
        }
    }
}