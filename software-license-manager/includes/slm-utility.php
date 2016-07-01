<?php

/*
 * Contains some utility functions for the plugin.
 */
class SLM_Utility {

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
            }

            
        }
    }

}

