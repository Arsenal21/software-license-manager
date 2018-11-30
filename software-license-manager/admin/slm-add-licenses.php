<?php

function wp_lic_mgr_add_licenses_menu() {
    global $wpdb;
    //initialise some variables
    $id             = '';
    $license_key    = '';
    $max_domains    = 2;
    $max_devices    = 2;
    $license_status = '';
    $first_name     = '';
    $last_name      = '';
    $email          = '';
    $company_name   = '';
    $txn_id         = '';
    $reset_count    = '';
    $purchase_id_   = '';
    $created_date   = '';
    $renewed_date   = '';
    $expiry_date    = '';
    $until          = '';
    $product_ref    = '';
    $subscr_id      = '';
    $current_date   = (date ("Y-m-d"));
    $slm_options    = get_option('slm_plugin_options');
    $current_date_plus_1year = date('Y-m-d', strtotime('+1 year'));

    echo '<div class="wrap">';
    // echo '<h2>Add/Edit Licenses</h2>';
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
        $max_devices = $record->max_allowed_devices;
        $license_status = $record->lic_status;
        $first_name = $record->first_name;
        $last_name = $record->last_name;
        $email = $record->email;
        $company_name = $record->company_name;
        $txn_id = $record->txn_id;
        $reset_count = $record->manual_reset_count;
        $purchase_id_ = $record->purchase_id_;
        $created_date = $record->date_created;
        $renewed_date = $record->date_renewed;
        $expiry_date = $record->date_expiry;
        $product_ref = $record->product_ref;
        $until = $record->until;
        $subscr_id = $record->subscr_id;
    }
    if (isset($_POST['save_record'])) {

        //Check nonce
        if ( !isset($_POST['slm_add_edit_nonce_val']) || !wp_verify_nonce($_POST['slm_add_edit_nonce_val'], 'slm_add_edit_nonce_action' )){
            //Nonce check failed.
            wp_die("Error! Nonce verification failed for license save action.");
        }

        do_action('slm_add_edit_interface_save_submission');

        //TODO - do some validation
        $license_key    = $_POST['license_key'];
        $max_domains    = $_POST['max_allowed_domains'];
        $max_devices    = $_POST['max_allowed_devices'];
        $license_status = $_POST['lic_status'];
        $first_name     = $_POST['first_name'];
        $last_name      = $_POST['last_name'];
        $email          = $_POST['email'];
        $company_name   = $_POST['company_name'];
        $txn_id         = $_POST['txn_id'];
        $reset_count    = $_POST['manual_reset_count'];
        $purchase_id_   = $_POST['purchase_id_'];
        $created_date   = $_POST['date_created'];
        $renewed_date   = $_POST['date_renewed'];
        $expiry_date    = $_POST['date_expiry'];
        $product_ref    = $_POST['product_ref'];
        $until          = $_POST['until'];
        $subscr_id      = $_POST['subscr_id'];

        if(empty($created_date)){
            $created_date = $current_date;
        }
        if(empty($renewed_date)){
            $renewed_date = $current_date;
        }
        if(empty($expiry_date)){
            $expiry_date = $current_date_plus_1year;
        }

        //Save the entry to the database
        $fields = array();
        $fields['license_key']  = $license_key;
        $fields['max_allowed_domains'] = $max_domains;
        $fields['max_allowed_devices'] = $max_devices;
        $fields['lic_status']   = $license_status;
        $fields['first_name']   = $first_name;
        $fields['last_name']    = $last_name;
        $fields['email']        = $email;
        $fields['company_name'] = $company_name;
        $fields['txn_id']       = $txn_id;
        $fields['manual_reset_count'] = $reset_count;
        $fields['purchase_id_'] = $purchase_id_;
        $fields['date_created'] = $created_date;
        $fields['date_renewed'] = $renewed_date;
        $fields['date_expiry']  = $expiry_date;
        $fields['product_ref']  = $product_ref;
        $fields['until']        = $until;
        $subscr_id              = $_POST['subscr_id'];

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
            echo '</div></div>';
        }else{
            echo '<div id="message" class="error">' . $errors . '</div>';
        }

        $data = array('row_id' => $id, 'key' => $license_key);
        do_action('slm_add_edit_interface_save_record_processed',$data);

    }
?>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

<div id="postbox-container-2" class="postbox-container slm-container">
    <div class="logo">
        <img src="<?php echo SLM_ASSETS_URL; ?>images/slm_logo.svg" alt="slm logo">
    </div>
    <div id="normal-sortables" class="meta-box-sortables ui-sortable">
        <div id="woocommerce-order-data">
            <div id="woocommerce-order-data">
                <div class="inside">
                    <div class="panel-wrap woocommerce">
                        <div id="order_data" class="panel woocommerce-order-data">

                            <h1 class="woocommerce-order-data__heading center">
                               Software License Manager
                            </h1>
                            <p class="woocommerce-order-data__meta order_number center">
                                You can add a new license or edit an existing one from this interface.
                            </p>
                            <div class="order_data_column_container">
                                <div class="order_data_column">
                                    <ul class="nav nav-tabs">
                                      <li class="active"><a data-toggle="tab" href="#license"><i class="glyphicon glyphicon-lock"></i> License</a></li>
                                      <li><a data-toggle="tab" href="#user_info"><i class="glyphicon glyphicon-user"></i> User</a></li>
                                      <li><a data-toggle="tab" href="#devices_info"><i class="glyphicon glyphicon-modal-window"></i> Devices & Domains</a></li>
                                      <li><a data-toggle="tab" href="#company"><i class="glyphicon glyphicon-globe"></i> Company</a></li>
                                      <li><a data-toggle="tab" href="#transaction"><i class="glyphicon glyphicon-shopping-cart"></i> Transaction</a></li>
                                      <li><a data-toggle="tab" href="#product_info"><i class="glyphicon glyphicon-gift"></i> Product</a></li>
                                    </ul>

                                    <form method="post" class="form-inline" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">

                                        <?php
                                            function hyphenate($str) {
                                                return implode("-", str_split($str, 5));
                                            }
                                            wp_nonce_field('slm_add_edit_nonce_action', 'slm_add_edit_nonce_val' );

                                            if ($id != '') {
                                                echo '<input name="edit_record" type="hidden" value="' . $id . '" />';
                                            }
                                            else {
                                                if(!isset($editing_record)){
                                                    $editing_record = new stdClass();
                                                }

                                                $lic_key_prefix = $slm_options['lic_prefix'];
                                                if (!empty($lic_key_prefix)) {
                                                    // $license_key = uniqid($lic_key_prefix);
                                                    $license_key = strtoupper($lic_key_prefix . get_current_user_id() .'-' . hyphenate(md5(uniqid(rand(4,8), true) . time() )) . get_current_user_id());
                                                }
                                                else {
                                                    // $license_key = uniqid();
                                                    $license_key =  get_current_user_id() . strtoupper(hyphenate(md5(uniqid(rand(4,8), true) . time() )));
                                                }
                                            }
                                        ?>

                                        <div class="tab-content">
                                            <div id="license" class="tab-pane fade in active">
                                                <div class="postbox license col">
                                                    <h3> License Details for: <span><?php echo $license_key; ?></span></h3>
                                                    <div class="form-field form-field-wide">
                                                        <label for="license_key">License Key</label>
                                                        <input name="license_key" type="text" id="license_key" value="<?php echo $license_key; ?>" size="30" />
                                                        <br/>The unique license key.
                                                    </div>

                                                    <div class="form-field form-field-wide">
                                                        <label for="lic_status">License Status</label>
                                                        <select name="lic_status" class="form-control">
                                                            <option value="pending" <?php if ($license_status == 'pending') { echo 'selected="selected"';} ?> >Pending</option>
                                                            <option value="active" <?php if ($license_status == 'active') {  echo 'selected="selected"'; } ?> >Active</option>
                                                            <option value="blocked" <?php if ($license_status == 'blocked') {  echo 'selected="selected"'; } ?> >Blocked</option>
                                                            <option value="expired" <?php if ($license_status == 'expired') {  echo 'selected="selected"'; } ?> >Expired</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div id="user_info" class="tab-pane fade">
                                                <div class="postbox user_info col">
                                                    <h3>User Information</h3>
                                                    <div class="form-field form-field-wide col-half">
                                                        <label for="first_name">First Name</label>
                                                        <input name="first_name" type="text" id="first_name" value="<?php echo $first_name; ?>" size="20" required />
                                                        <br/>License user's first name
                                                    </div>

                                                   <div class="form-field form-field-wide col-half">
                                                        <label for="last_name"> Last Name</label>
                                                        <input name="last_name" type="text" id="last_name" value="<?php echo $last_name; ?>" size="20" required  />
                                                        <br/>License user's last name
                                                    </div>
                                                    <div class="clear"></div>

                                                    <div class="form-field form-field-wide">
                                                        <label for="email">Subscriber ID</label>
                                                        <input name="subscr_id" type="text" id="subscr_id" value="<?php echo $subscr_id; ?>" />
                                                        <br/>The Subscriber ID (if any). Can be useful if you are using the license key with a recurring payment plan.
                                                    </div>
                                                    <div class="clear"></div>

                                                    <div class="form-field form-field-wide">
                                                        <label for="email">Email Address</label>
                                                        <input name="email" type="text" id="email" value="<?php echo $email; ?>" size="30" required  />
                                                        <br/>License user's email address
                                                    </div>
                                                    <div class="clear"></div>
                                                </div>
                                            </div>

                                            <div id="devices_info" class="tab-pane fade">
                                                <div class="postbox devices_info col">
                                                    <h3>Allowed Activations</h3>
                                                    <div class="form-field form-field-wide col-half">
                                                        <label for="max_allowed_domains">Maximum Allowed Domains</label>
                                                        <input name="max_allowed_domains" type="text" id="max_allowed_domains" value="<?php echo $max_domains; ?>" size="5" /><br/>Number of domains/installs in which this license can be used

                                                        <div class="table">

                                                            <label class="form-field form-field-wide">Registered Domains</label>

                                                            <?php
                                                                if($id != '') {
                                                                    global $wpdb;
                                                                    $reg_table = SLM_TBL_LIC_DOMAIN;
                                                                    $sql_prep = $wpdb->prepare("SELECT * FROM $reg_table WHERE lic_key_id = %s", $id);
                                                                    $reg_domains = $wpdb->get_results($sql_prep, OBJECT);
                                                                }
                                                                if(count($reg_domains) > 0) : ?>
                                                                    <div style="background: red;width: 100px;color:white; font-weight: bold;padding-left: 10px;" id="reg_del_msg"></div>
                                                                    <div class="devices-info">
                                                                        <table cellpadding="0" cellspacing="0" class="table">
                                                                            <?php
                                                                                $count = 0;
                                                                                foreach ($reg_domains as $reg_domain) :?>
                                                                                    <tr <?php echo ($count % 2) ? 'class="alternate"' : ''; ?>>
                                                                                        <td height="5"><?php echo $reg_domain->registered_domain; ?></td>
                                                                                        <td height="5"><span class="del" id=<?php echo $reg_domain->id ?>>X</span></td>
                                                                                    </tr>
                                                                            <?php $count++; ?>
                                                                            <?php endforeach; ?>
                                                                        </table>
                                                                    </div>
                                                               <?php else: ?>
                                                                   <?php echo "Not Registered Yet.";?>
                                                                <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="form-field form-field-wide col-half">
                                                        <label for="max_allowed_devices">Maximum Allowed Devices</label>
                                                        <input name="max_allowed_devices" type="text" id="max_allowed_devices" value="<?php echo $max_devices; ?>" size="5" /><br/>Number of domains/installs in which this license can be used <br><br>
                                                            <label for="order_date">Registered Devices</label>
                                                            <?php
                                                                if ($id != '') {
                                                                    global $wpdb;
                                                                    $devices_table  = SLM_TBL_LIC_DEVICES;
                                                                    $sql_prep2      = $wpdb->prepare("SELECT * FROM `$devices_table` WHERE `lic_key_id` = '%s'", $id);
                                                                    $reg_devices    = $wpdb->get_results($sql_prep2, OBJECT);
                                                                }
                                                                if (count($reg_devices) > 0): ?>
                                                                    <div style="background: red;width: 100px;color:white; font-weight: bold;padding-left: 10px;" id="reg_del_msg"></div>
                                                                    <div class="devices-info">
                                                                        <table cellpadding="0" cellspacing="0" class="table">
                                                                            <?php
                                                                                $count_ = 0;
                                                                                foreach ($reg_devices as $reg_device): ?>
                                                                                    <tr <?php echo ($count_ % 2) ? 'class="alternate"' : ''; ?>>
                                                                                        <td height="5"><?php echo $reg_device->registered_devices; ?></td>
                                                                                        <td height="5"><span class="del_device" id=<?php echo $reg_device->id ?>>X</span></td>
                                                                                    </tr>
                                                                            <?php $count_++; ?>
                                                                            <?php endforeach; ?>
                                                                        </table>
                                                                    </div>
                                                                <?php else: ?>
                                                                   <?php echo "Not Registered Yet."; ?>
                                                                <?php endif; ?>
                                                    </div>
                                                    <div class="clear"></div>
                                                </div>
                                                <div class="clear"></div>
                                            </div>

                                            <div id="company" class="tab-pane fade">
                                                <div class="postbox company col">
                                                    <h3>Organization</h3>
                                                    <div class="form-field form-field-wide">
                                                        <label for="company_name">Company Name</label>
                                                        <input name="company_name" type="text" id="company_name" value="<?php echo $company_name; ?>" size="30" /><br/>License user's company name
                                                    </div>
                                                </div>
                                                <div class="clear"></div>
                                            </div>


                                            <div id="transaction" class="tab-pane fade">
                                                <div class="postbox transaction col">
                                                    <h3>Advanced Details</h3>
                                                    <div class="form-field form-field-wide">
                                                        <label for="order_date">Manual Reset Count</label>
                                                        <input name="manual_reset_count" type="text" id="manual_reset_count" value="<?php echo $reset_count; ?>" size="6" />
                                                            <br/>The number of times this license has been manually reset by the admin (use it if you want to keep track of it). It can be helpful for the admin to keep track of manual reset counts
                                                    </div>

                                                    <div class="form-field form-field-wide col-half">
                                                        <label for="order_date">Date Created</label>
                                                        <input name="date_created" type="text" id="date_created" class="wplm_pick_date" value="<?php echo $created_date; ?>" size="10" />
                                                            <br/>Creation date of license
                                                    </div>

                                                    <div class="form-field form-field-wide col-half">
                                                        <label for="date_expiry">Expiration Date</label>
                                                        <input name="date_expiry" type="text" id="date_expiry" class="wplm_pick_date" value="<?php echo $expiry_date; ?>" size="10" />
                                                        <br/>Expiry date of license
                                                    </div>
                                                    <div class="clear"></div>

                                                    <div class="form-field form-field-wide">
                                                        <label for="date_renewed">Date Renewed</label>
                                                        <input name="date_renewed" type="text" id="date_renewed" class="wplm_pick_date" value="<?php echo $renewed_date; ?>" size="10" />
                                                        <br/>Renewal date of license
                                                    </div>
                                                </div>
                                                <div class="clear"></div>
                                            </div>

                                            <div id="product_info" class="tab-pane fade">
                                                <div class="postbox product_info col">
                                                    <h3>Product Information</h3>
                                                    <div class="form-field form-field-wide">
                                                        <label for="product_ref">Product</label>
                                                        <input name="product_ref" type="text" id="product_ref" value="<?php echo $product_ref; ?>" size="30" />
                                                            <br/>The product that this license gives access to.
                                                    </div>

                                                    <div class="form-field form-field-wide col-half">
                                                        <label for="txn_id">Unique Transaction ID</label>
                                                        <input name="txn_id" type="text" id="txn_id" value="<?php echo $txn_id; ?>" size="30" /><br/>The unique transaction ID associated with this license key
                                                    </div>

                                                    <div class="form-field form-field-wide  col-half">
                                                        <label for="purchase_id_">Purchase Order ID #</label>
                                                        <input name="purchase_id_" type="text" id="purchase_id_" value="<?php echo $purchase_id_; ?>" size="8" />
                                                        <br/>This is associated with the purchase ID woocommerce support. <a href="<?php echo admin_url().'post.php?post='.$purchase_id_; ?>&action=edit">View Order #<?php echo $purchase_id_; ?></a>
                                                    </div>
                                                    <div class="clear"></div>

                                                    <div class="form-field form-field-wide">
                                                        <label for="until">Supported Until</label>
                                                        <input name="until" type="text" id="until" value="<?php echo $until; ?>" size="30" />
                                                            <br/>Until what version this product is supported
                                                    </div>
                                                    <div class="clear"></div>

                                                </div>
                                                <div class="clear"></div>
                                            </div>

                                            <div class="output-msg">
                                                <?php
                                                    $data = array('row_id' => $id, 'key' => $license_key);
                                                    $extra_output = apply_filters('slm_add_edit_interface_above_submit','', $data);
                                                    if(!empty($extra_output)){
                                                        echo $extra_output;
                                                    }
                                                ?>
                                            </div>

                                            <div class="submit form_actions">
                                                <input type="submit" class="button-primary" name="save_record" value="Save License" />
                                                <a href="admin.php?page=<?php echo SLM_MAIN_MENU_SLUG; ?>" class="button">Manage Licenses</a>
                                            </div>
                                        </div>
                                    </form>
                                    <div class="clear"></div>
                                </div>
                            </div>
                            <div class="clear"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="clear"></div>
    </div>
</div>
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

        jQuery('.del_device').click(function() {
            var $this = this;
            jQuery('#reg_device_del_msg').html('Loading ...');
            jQuery.get('<?php echo get_bloginfo('wpurl') ?>' + '/wp-admin/admin-ajax.php?action=del_reistered_devices&id=' + jQuery(this).attr('id'), function(data) {
                if (data == 'success') {
                    jQuery('#reg_device_del_msg').html('Deleted');
                    jQuery($this).parent().parent().remove();
                }
                else {
                    jQuery('#reg_device_del_msg').html('Failed');
                }
            });
        });
    });
</script>
<?php
}