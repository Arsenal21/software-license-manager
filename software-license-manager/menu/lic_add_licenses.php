<?php

function wp_lic_mgr_add_licenses_menu() {
    global $wpdb;
    //initialise some variables
    $id = '';
    $license_key = '';
    $max_domains = 1;
    $license_status = '';
    $first_name = '';
    $last_name = '';
    $email = '';
    $company_name = '';
    $txn_id = '';
    $reset_count = '';
    $created_date = '';
    $renewed_date = '';
    $expiry_date = '';
    $current_date = (date ("Y-m-d"));

    $slm_options = get_option('slm_plugin_options');
    
    echo '<div class="wrap">';
    echo '<h2>Add/Edit Licenses</h2>';
    echo '<div id="poststuff"><div id="post-body">';

    //If product is being edited, grab current product info
    if (isset($_GET['edit_record'])) {
        $errors = '';
        $id = $_GET['edit_record'];
        $lk_table = SLM_TBL_LICENSE_KEYS;
        $sql_prep = $wpdb->prepare("SELECT * FROM $lk_table WHERE id = %s", $id);
        $record = $wpdb->get_row($sql_prep, OBJECT);
        $license_key = $record->license_key;
        $max_domains = $record->max_allowed_domains;
        $license_status = $record->lic_status;
        $first_name = $record->first_name;
        $last_name = $record->last_name;
        $email = $record->email;
        $company_name = $record->company_name;
        $txn_id = $record->txn_id;
        $reset_count = $record->manual_reset_count;
        $created_date = $record->date_created;
        $renewed_date = $record->date_renewed;
        $expiry_date = $record->date_expiry;
    }
    
    
    if (isset($_POST['save_record'])) {
        
        //TODO - do some validation
        $license_key = $_POST['license_key'];
        $max_domains = $_POST['max_allowed_domains'];
        $license_status = $_POST['lic_status'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $company_name = $_POST['company_name'];
        $txn_id = $_POST['txn_id'];
        $reset_count = $_POST['manual_reset_count'];
        $created_date = $_POST['date_created'];
        $renewed_date = $_POST['date_renewed'];
        $expiry_date = $_POST['date_expiry'];
        
        if(empty($created_date)){
            $created_date = $current_date;
        }
        if(empty($renewed_date)){
            $renewed_date = $current_date;
        }
        if(empty($expiry_date)){
            $expiry_date = $current_date;
        }
        
        //Save the entry to the database
        $fields = array();
        $fields['license_key'] = $license_key;
        $fields['max_allowed_domains'] = $max_domains;
        $fields['lic_status'] = $license_status;
        $fields['first_name'] = $first_name;
        $fields['last_name'] = $last_name;
        $fields['email'] = $email;
        $fields['company_name'] = $company_name;
        $fields['txn_id'] = $txn_id;
        $fields['manual_reset_count'] = $reset_count;
        $fields['date_created'] = $created_date;
        $fields['date_renewed'] = $renewed_date;
        $fields['date_expiry'] = $expiry_date;

        $id = isset($_POST['edit_record'])?$_POST['edit_record']:'';
        $lk_table = SLM_TBL_LICENSE_KEYS;
        if (empty($id)) {//Insert into database
            $result = $wpdb->insert( $lk_table, $fields);
            $id = $wpdb->insert_id;
            if($result === false){
                $errors .= __('Record could not be inserted into the database!', 'slm');
            }
        } else { //Update record
            $where = array('id'=>$id);
            $updated = $wpdb->update($lk_table, $fields, $where);
            if($updated === false){
                //TODO - log error
                $errors .= __('Update of the license key table failed!', 'slm');
            }
        }

        if(empty($errors)){
            $message = "Record successfully saved!";
            echo '<div id="message" class="updated fade"><p>';
            echo $message;
            echo '</p></div>';
        }else{
            echo '<div id="message" class="error">' . $errors . '</div>';        }
    }

?>    
    <style type="text/css">
        .del{
            cursor: pointer;
            color:red;	
        }
    </style>
    You can add a new license or edit an existing one from this interface.
    <br /><br />

    <div class="postbox">
        <h3><label for="title">License Details </label></h3>
        <div class="inside">

            <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                <table class="form-table">

                    <?php
                    if ($id != '') {
                        echo '<input name="edit_record" type="hidden" value="' . $id . '" />';
                    } else {
                        if(!isset($editing_record)){//Create an empty object
                            $editing_record = new stdClass();
                        }
                        //Auto generate unique key
                        $lic_key_prefix = $slm_options['lic_prefix'];
                        if (!empty($lic_key_prefix)) {
                            $license_key = uniqid($lic_key_prefix);
                        } else {
                            $license_key = uniqid();
                        }
                    }
                    ?>

                    <tr valign="top">
                        <th scope="row">License Key</th>
                        <td><input name="license_key" type="text" id="license_key" value="<?php echo $license_key; ?>" size="30" />
                            <br/>The unique license key. When adding a new record it automatically generates a unique key in this field for you. You can change this value to customize the key if you like.</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Maximum Allowed Domains</th>
                        <td><input name="max_allowed_domains" type="text" id="max_allowed_domains" value="<?php echo $max_domains; ?>" size="5" /><br/>Number of domains in which this license can be used.</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">License Status</th>
                        <td>
                            <select name="lic_status">    
                                <option value="pending" <?php if ($license_status == 'pending') echo 'selected="selected"'; ?> >Pending</option>
                                <option value="active" <?php if ($license_status == 'active') echo 'selected="selected"'; ?> >Active</option>
                                <option value="blocked" <?php if ($license_status == 'blocked') echo 'selected="selected"'; ?> >Blocked</option>
                                <option value="expired" <?php if ($license_status == 'expired') echo 'selected="selected"'; ?> >Expired</option>
                            </select>
                        </td></tr>

                    <?php
                    if ($id != '') {
                        global $wpdb;
                        $reg_table = SLM_TBL_LIC_DOMAIN;
                        $sql_prep = $wpdb->prepare("SELECT * FROM $reg_table WHERE lic_key_id = %s", $id);
                        $reg_domains = $wpdb->get_results($sql_prep, OBJECT);
                        ?>
                        <tr valign="top">
                            <th scope="row">Registered Domains</th>
                            <td><?php
                                if (count($reg_domains) > 0) {
                                    ?>
                                    <div style="background: red;width: 100px;color:white; font-weight: bold;padding-left: 10px;" id="reg_del_msg"></div>
                                    <div style="overflow:auto; height:157px;width:250px;border:1px solid #ccc;">
                                        <table cellpadding="0" cellspacing="0">
                                            <?php
                                            $count = 0;
                                            foreach ($reg_domains as $reg_domain) {
                                                ?>
                                                <tr <?php echo ($count % 2) ? 'class="alternate"' : ''; ?>>
                                                    <td height="5"><?php echo $reg_domain->registered_domain; ?></td> 
                                                    <td height="5"><span class="del" id=<?php echo $reg_domain->id ?>>X</span></td>
                                                </tr>
                                                <?php
                                                $count++;
                                            }
                                            ?>
                                        </table>         
                                    </div>
                                    <?php
                                } else {
                                    echo "Not Registered Yet.";
                                }
                                ?>
                            </td>
                        </tr>
                    <?php } ?>

                    <tr valign="top">
                        <th scope="row">First Name</th>
                        <td><input name="first_name" type="text" id="first_name" value="<?php echo $first_name; ?>" size="20" /><br/>License user's first name</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Last Name</th>
                        <td><input name="last_name" type="text" id="last_name" value="<?php echo $last_name; ?>" size="20" /><br/>License user's last name</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Email Address</th>
                        <td><input name="email" type="text" id="email" value="<?php echo $email; ?>" size="30" /><br/>License user's email address</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Company Name</th>
                        <td><input name="company_name" type="text" id="company_name" value="<?php echo $company_name; ?>" size="30" /><br/>License user's company name</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Unique Transaction ID</th>
                        <td><input name="txn_id" type="text" id="txn_id" value="<?php echo $txn_id; ?>" size="30" /><br/>The unique transaction ID associated with this license key</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Manual Reset Count</th>
                        <td><input name="manual_reset_count" type="text" id="manual_reset_count" value="<?php echo $reset_count; ?>" size="6" />
                            <br/>The number of times this license has been manually reset by the admin (use it if you want to keep track of it). It can be helpful for the admin to keep track of manual reset counts.</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Date Created</th>
                        <td><input name="date_created" type="text" id="date_created" class="wplm_pick_date" value="<?php echo $created_date; ?>" size="10" />
                            <br/>Creation date of license.</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Date Renewed</th>
                        <td><input name="date_renewed" type="text" id="date_renewed" class="wplm_pick_date" value="<?php echo $renewed_date; ?>" size="10" />
                            <br/>Renewal date of license.</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Date of Expiry</th>
                        <td><input name="date_expiry" type="text" id="date_expiry" class="wplm_pick_date" value="<?php echo $expiry_date; ?>" size="10" />
                            <br/>Expiry date of license.</td>
                    </tr>

                </table>

                <div class="submit">
                    <input type="submit" class="button-primary" name="save_record" value="Save Record" />
                </div>
            </form>
        </div></div>
    <a href="admin.php?page=<?php echo SLM_MAIN_MENU_SLUG; ?>" class="button">Manage Licenses</a><br /><br />
    </div></div>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function() {
            jQuery('.del').click(function() {
                var $this = this;
                jQuery('#reg_del_msg').html('Loading ...');
                jQuery.get('<?php echo get_bloginfo('wpurl') ?>' + '/wp-admin/admin-ajax.php?action=del_reistered_domain&id=' + jQuery(this).attr('id'), function(data) {
                    if (data == 'success') {
                        jQuery('#reg_del_msg').html('Deleted');
                        jQuery($this).parent().parent().remove();
                    }
                    else {
                        jQuery('#reg_del_msg').html('Failed');
                    }
                });
            });
        });
    </script>
<?php
}
