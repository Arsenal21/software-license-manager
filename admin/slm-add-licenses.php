<?php
function slm_add_licenses_menu()
{
    global $wpdb;
    $slm_options    = get_option('slm_plugin_options');
    //initialise some variables
    $id             = '';
    $item_reference = '';
    $license_key    = '';
    $max_domains    = SLM_Helper_Class::slm_get_option('default_max_domains');
    $max_devices    = SLM_Helper_Class::slm_get_option('default_max_devices');
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
    $current_ver    = '';
    $product_ref    = '';
    $subscr_id      = '';
    $lic_type       = '';
    $reg_domains    = '';
    $reg_devices    = '';
    $class_hide     = '';
    $date_activated = '';
    $lic_item_ref   = '';
    $slm_billing_length   = '';
    $slm_billing_interval   = '';
    //$current_date   = (date("Y-m-d"));
    $current_date   = wp_date("Y-m-d");
    $current_time = wp_date("H:i:s");
    $current_date_plus_1year = wp_date('Y-m-d', strtotime('+1 year'));

    echo '<div class="wrap">';
    // echo '<h2>Add/Edit Licenses</h2>';
    echo '<div id="poststuff"><div id="post-body">';

    //If product is being edited, grab current product info
    if (isset($_GET['edit_record'])) {
        $errors         = '';
        $id             = $_GET['edit_record'];
        $lk_table       = SLM_TBL_LICENSE_KEYS;
        $sql_prep       = $wpdb->prepare("SELECT * FROM $lk_table WHERE id = %s", $id);
        $record         = $wpdb->get_row($sql_prep, OBJECT);
        $license_key    = $record->license_key;
        $max_domains    = $record->max_allowed_domains;
        $max_devices    = $record->max_allowed_devices;
        $license_status = $record->lic_status;
        $first_name     = $record->first_name;
        $last_name      = $record->last_name;
        $email          = $record->email;
        $company_name   = $record->company_name;
        $txn_id         = $record->txn_id;
        $reset_count    = $record->manual_reset_count;
        $purchase_id_   = $record->purchase_id_;
        $created_date   = $record->date_created;
        $renewed_date   = $record->date_renewed;
        $date_activated = $record->date_activated;
        $product_ref    = $record->product_ref;
        $until          = $record->until;
        $current_ver    = $record->current_ver;
        $subscr_id      = $record->subscr_id;
        $lic_type       = $record->lic_type;
        $expiry_date    = $record->date_expiry;
        $lic_item_ref   = $record->item_reference;
        $slm_billing_length  = $record->slm_billing_length;
        $slm_billing_interval   = $record->slm_billing_interval;
    }
    if (isset($_POST['save_record'])) {

        //Check nonce
        if (!isset($_POST['slm_add_edit_nonce_val']) || !wp_verify_nonce($_POST['slm_add_edit_nonce_val'], 'slm_add_edit_nonce_action')) {
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
        $date_activated = $_POST['date_activated'];
        // $expiry_date    = $_POST['date_expiry'];
        $product_ref    = $_POST['product_ref'];
        $until          = $_POST['until'];
        $current_ver    = $_POST['current_ver'];
        $subscr_id      = $_POST['subscr_id'];
        $lic_type       = $_POST['lic_type'];

        if ("" == trim($_POST['item_reference'])) {
            $lic_item_ref   = 'default';
        } else {
            $lic_item_ref   = trim($_POST['item_reference']);
        }


        $slm_billing_length = trim($_POST['slm_billing_length']);
        $slm_billing_interval = trim($_POST['slm_billing_interval']);

        $expiry_date    = '';
        if ($_POST['lic_type'] == 'lifetime') {
            $expiry_date       = '0000-00-00';
        } else {
            $expiry_date    = $_POST['date_expiry'];
        }

        if (empty($created_date)) {
            $created_date = $current_date;
        }
        if (empty($renewed_date)) {
            $renewed_date = $current_date;
        }
        if (empty($expiry_date) && $lic_type !== 'lifetime') {
            $expiry_date = $current_date_plus_1year;
        }

        //Save the entry to the database
        $fields                         = array();
        $fields['license_key']          = $license_key;
        $fields['max_allowed_domains']  = $max_domains;
        $fields['max_allowed_devices']  = $max_devices;
        $fields['lic_status']           = $license_status;
        $fields['first_name']           = $first_name;
        $fields['last_name']            = $last_name;
        $fields['email']                = $email;
        $fields['company_name']         = $company_name;
        $fields['txn_id']               = $txn_id;
        $fields['manual_reset_count']   = $reset_count;
        $fields['purchase_id_']         = $purchase_id_;
        $fields['date_created']         = $created_date;
        $fields['date_renewed']         = $renewed_date;
        $fields['date_activated']       = $date_activated;
        $fields['date_expiry']          = $expiry_date;
        $fields['product_ref']          = $product_ref;
        $fields['until']                = $until;
        $fields['current_ver']          = $current_ver;
        $fields['subscr_id']            = $subscr_id;
        $fields['lic_type']             = $lic_type;
        $fields['item_reference']       = $lic_item_ref;
        $fields['slm_billing_length']   = $slm_billing_length;
        $fields['slm_billing_interval'] = $slm_billing_interval;
        $id                             = isset($_POST['edit_record']) ? $_POST['edit_record'] : '';
        $lk_table                       = SLM_TBL_LICENSE_KEYS;

        if (empty($id)) {
            //Insert into database
            $result = $wpdb->insert($lk_table, $fields);
            $id = $wpdb->insert_id;
            if ($result === false) {
                $errors .= __('Record could not be inserted into the database!', 'softwarelicensemanager');
            }
        } else {
            //Update record
            $where = array('id' => $id);
            $updated = $wpdb->update($lk_table, $fields, $where);
            if ($updated === false) {
                //TODO - log error
                $errors .= __('Update of the license key table failed!', 'softwarelicensemanager');
            }
        }
        $data = array('row_id' => $id, 'key' => $license_key);
        do_action('slm_add_edit_interface_save_record_processed', $data);
    }
?>
    <?php
    if (SLM_Helper_Class::slm_get_option('slm_conflictmode') == 1) {
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" integrity="sha384-gXt9imSW0VcJVHezoNQsP+TNrjYXoGcrqBZJpry9zJt8PCQjobwmhMGaDHTASo9N" crossorigin="anonymous">
            <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>';
    }
    ?>
    <style>
        .wp-admin select {
            height: calc(2.25rem + 2px);
        }
    </style>

    <div id="container-2" class="container slm-container">
        <div class="mx-auto" style="">
            <div class="row pb-4">
                <div class="slm-logo col-md-1">
                    <img src="<?php echo SLM_Utility::slm_get_icon_url('logo', 'slm-large.svg'); ?>" alt="">
                </div>
                <div class="heading col-md-10">
                    <h1 class="woocommerce-order-data__heading">
                        <?php _e('Software License Manager', 'softwarelicensemanager'); ?>
                    </h1>
                    <p class="lead">
                        <?php _e('You can add a new license or edit an existing one from this interface.', 'softwarelicensemanager'); ?>
                    </p>
                </div>
            </div>
        </div>

        <?php
        //save_record - messages
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            echo '<div class="alert alert-primary" role="alert"> <strong>'.__('Done!', 'softwarelicensemanager').'</strong>' .__('License was successfully generated', 'softwarelicensemanager'). '<button type="button" class="close" data-dismiss="alert" aria-label="Close"> <span aria-hidden="true">&times;</span> </button></div>';
        }
        //edit
        elseif (isset($_GET['edit_record'])) {
            echo '<div class="alert alert-warning" role="alert"> '. __('Edit the information below to update your license key','softwarelicensemanager').'</div>';
        }
        // new
        else {
            echo '<div class="alert alert-info" role="alert"> '. __('Fill the information below to generate your license key','softwarelicensemanager').' </div>';
        }
        ?>
        <div id="normal-sortables" class="meta-box-sortables ui-sortable">
            <div id="woocommerce-order-data">
                <div id="woocommerce-order-data">
                    <div class="inside">
                        <div class="panel-wrap woocommerce">
                            <div id="order_data" class="panel woocommerce-order-data">

                                <div class="clear"></div>
                                <div id="error_box">
                                    <div id="summary">
                                        <div class="error_slm alert alert-info" style="display:none">
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="clear"></div>


                                <div class="order_data_column_container">
                                    <div class="order_data_column row">

                                        <div class="col-3 sml-col-right">

                                            <ul class="nav flex-column nav-pills" aria-orientation="vertical" id="slm_manage_license" role="tablist">

                                                <li class="nav-item">
                                                    <a class="nav-link active" id="license-tab" data-toggle="tab" href="#license" role="tab" aria-controls="license" aria-selected="false"><span class="dashicons dashicons-lock"></span> <?php _e('License key and status', 'softwarelicensemanager'); ?></a>
                                                </li>

                                                <li class="nav-item">
                                                    <a class="nav-link" id="userinfo-tab" data-toggle="tab" href="#userinfo" role="tab" aria-controls="userinfo" aria-selected="false"><span class="dashicons dashicons-admin-users"></span> <?php _e('User information', 'softwarelicensemanager'); ?></a>
                                                </li>

                                                <?php
                                                if (isset($_GET['edit_record'])) : ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" id="devicesinfo-tab" data-toggle="tab" href="#devicesinfo" role="tab" aria-controls="devicesinfo" aria-selected="false"><span class="dashicons dashicons-admin-site-alt2"></span> <?php _e('Devices & Domains', 'softwarelicensemanager'); ?></a>
                                                    </li>
                                                <?php endif; ?>

                                                <li class="nav-item">
                                                    <a class="nav-link" id="transaction-tab" data-toggle="tab" href="#transaction" role="tab" aria-controls="transaction" aria-selected="false"><span class="dashicons dashicons-media-text"></span> <?php _e('Subscription and Renewal', 'softwarelicensemanager'); ?></a>
                                                </li>

                                                <li class="nav-item">
                                                    <a class="nav-link" id="productinfo-tab" data-toggle="tab" href="#productinfo" role="tab" aria-controls="productinfo" aria-selected="false"><span class="dashicons dashicons-store"></span> <?php _e('Product', 'softwarelicensemanager'); ?></a>
                                                </li>

                                                <?php
                                                if (isset($_GET['edit_record'])) : ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" id="activity-log-tab" data-toggle="tab" href="#activity-log" role="tab" aria-controls="activity-log" aria-selected="false"><span class="dashicons dashicons-media-text"></span> <?php _e('Activity log ', 'softwarelicensemanager'); ?></a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php
                                                if (isset($_GET['edit_record'])) : ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" id="export-license-tab" data-toggle="tab" href="#export-license" role="tab" aria-controls="export-license" aria-selected="false"><span class="dashicons dashicons-external"></span> <?php _e('Export ', 'softwarelicensemanager'); ?></a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                        <div class="col-9 sml-col-left">
                                            <form method="post" class="slm_license_form row" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                                                <?php
                                                wp_nonce_field('slm_add_edit_nonce_action', 'slm_add_edit_nonce_val');

                                                if ($id != '') {
                                                    echo '<input name="edit_record" type="hidden" value="' . $id . '" />';
                                                } else {
                                                    if (!isset($editing_record)) {
                                                        $editing_record = new stdClass();
                                                    }
                                                    $lic_key_prefix = $slm_options['lic_prefix'];

                                                    if (!empty($lic_key_prefix)) {
                                                        $license_key = slm_get_license($lic_key_prefix);
                                                    } else {
                                                        $license_key =  slm_get_license($lic_key_prefix);
                                                    }
                                                }
                                                ?>
                                                <div class="tab-content col-md-12" id="slm_manage_licenseContent">
                                                    <div class="tab-pane fade show active" id="license" role="tabpanel" aria-labelledby="license-tab">
                                                        <div class="license col-full">

                                                            <div class="slm-img-ico">
                                                                <img src="<?php echo SLM_Utility::slm_get_icon_url('1x', 'locked.png'); ?>" alt="">
                                                            </div>
                                                            <h3 class="slm-tab-title"><?php _e('License key and status','softwarelicensemanager'); ?></h3>
                                                            <div class="clear clear-fix"></div>
                                                            <div class="sml-sep"></div>

                                                            <div class="form-group">
                                                                <label for="license_key"><?php _e('License Key','softwarelicensemanager'); ?></label>
                                                                <input name="license_key" class="form-control" aria-describedby="licInfo" type="text" id="license_key" value="<?php echo $license_key; ?>" readonly />
                                                                <small id="licInfo" class="form-text text-muted"><?php _e('The unique license key.','softwarelicensemanager'); ?></small>
                                                            </div>

                                                            <div class="row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="lic_status"><?php _e('License Status','softwarelicensemanager'); ?></label>
                                                                    <select name="lic_status" class="form-control">
                                                                        <option value="pending" <?php if ($license_status == 'pending') {
                                                                                                    echo 'selected="selected"';
                                                                                                } ?>><?php _e('Pending','softwarelicensemanager'); ?></option>
                                                                        <option value="active" <?php if ($license_status == 'active') {
                                                                                                    echo 'selected="selected"';
                                                                                                } ?>><?php _e('Active','softwarelicensemanager'); ?></option>
                                                                        <?php
                                                                        if (isset($_GET['edit_record'])) : ?>
                                                                            <option value="blocked" <?php if ($license_status == 'blocked') {
                                                                                                        echo 'selected="selected"';
                                                                                                    } ?>><?php _e('Blocked','softwarelicensemanager'); ?></option>
                                                                            <option value="expired" <?php if ($license_status == 'expired') {
                                                                                                        echo 'selected="selected"';
                                                                                                    } ?>><?php _e('Expired','softwarelicensemanager'); ?></option>
                                                                        <?php endif; ?>

                                                                    </select>
                                                                </div>

                                                                <div class="form-group col-md-6">
                                                                    <label for="lic_type"><?php _e('License type','softwarelicensemanager')?></label>
                                                                    <select name="lic_type" class="form-control">
                                                                        <option value="subscription" <?php if ($lic_type == 'subscription') {
                                                                                                            echo 'selected="selected"';
                                                                                                        } ?>> <?php _e('Subscription','softwarelicensemanager'); ?> </option>
                                                                        <option value="lifetime" <?php if ($lic_type == 'lifetime') {
                                                                                                        echo 'selected="selected"';
                                                                                                    } ?>> <?php _e('Life-time','softwarelicensemanager'); ?></option>
                                                                    </select>
                                                                    <small class="form-text text-muted"><?php _e('type of license: subscription base or lifetime','softwarelicensemanager'); ?></small>
                                                                </div>
                                                                <div class="clear"></div>
                                                            </div>
                                                            <div class="clear"></div>
                                                        </div>
                                                    </div>

                                                    <div class="tab-pane fade show" id="userinfo" role="tabpanel" aria-labelledby="userinfo-tab">
                                                        <div class="col-full">

                                                            <div class="slm-img-ico">
                                                                <img src="<?php echo SLM_Utility::slm_get_icon_url('1x', 'circle-09.png'); ?>" alt="">
                                                            </div>
                                                            <h3 class="slm-tab-title"><?php _e('User Information','softwarelicensemanager'); ?></h3>
                                                            <div class="clear clear-fix"></div>
                                                            <div class="sml-sep"></div>

                                                            <div class="row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="first_name"><?php _e('First Name','softwarelicensemanager'); ?></label>
                                                                    <input name="first_name" type="text" id="first_name" value="<?php echo $first_name; ?>" class="form-control required" required />
                                                                    <small class="form-text text-muted"><?php _e('License user\'s first name','softwarelicensemanager'); ?> </small>
                                                                </div>

                                                                <div class="form-group col-md-6">
                                                                    <label for="last_name"><?php _e(' Last Name','softwarelicensemanager'); ?></label>
                                                                    <input name="last_name" type="text" id="last_name" value="<?php echo $last_name; ?>" class="form-control required" required />
                                                                    <small class="form-text text-muted"><?php _e('License user\'s last name','softwarelicensemanager'); ?> </small>
                                                                </div>
                                                            </div>
                                                            <div class="clear"></div>

                                                            <div class="row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="email"><?php _e('Subscriber ID','softwarelicensemanager'); ?></label>
                                                                    <input name="subscr_id" class="form-control" type="text" id="subscr_id" value="<?php echo $subscr_id; ?>" />
                                                                    <small class="form-text text-muted"><?php _e('The Subscriber ID (if any). Can be useful if you are using the license key with a recurring payment plan.','softwarelicensemanager'); ?></small>
                                                                </div>


                                                                <div class="form-group col-md-6">
                                                                    <label for="email"><?php _e('Email Address','softwarelicensemanager'); ?></label>
                                                                    <input name="email" type="email" class="form-control required" id="email" value="<?php echo $email; ?>" required />
                                                                    <?php
                                                                    if (isset($_GET['edit_record'])) : ?>
                                                                        <small class="form-text text-muted"><?php _e('License user\'s email address.','softwarelicensemanager'); ?> <a href="<?php echo admin_url('admin.php?page=slm_subscribers&slm_subscriber_edit=true&manage_subscriber=' . $subscr_id . '&email=' . $email . '') ?>"><?php _e('View all licenses','softwarelicensemanager'); ?></a> <?php _e('registered to this email address.','softwarelicensemanager'); ?></small>
                                                                    <?php else : ?>
                                                                        <small class="form-text text-muted"><?php _e('License user\'s email address','softwarelicensemanager'); ?></small>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="clear"></div>

                                                                <div class="form-group col-md-12">
                                                                    <label for="company_name"><?php _e('Company Name','softwarelicensemanager'); ?></label>
                                                                    <input name="company_name" class="form-control" type="text" id="company_name" value="<?php echo $company_name; ?>" />
                                                                    <small class="form-text text-muted"><?php _e('License user\'s company name','softwarelicensemanager'); ?></small>
                                                                </div>
                                                            </div>
                                                            <div class="clear"></div>

                                                        </div>
                                                    </div>

                                                    <div class="tab-pane fade show " id="devicesinfo" role="tabpanel" aria-labelledby="devicesinfo-tab">
                                                        <div class="devicesinfo col-full">
                                                            <div class="slm-img-ico">
                                                                <img src="<?php echo SLM_Utility::slm_get_icon_url('1x', 'l-system-update.png'); ?>" alt="">
                                                            </div>
                                                            <h3 class="slm-tab-title"><?php _e('Allowed Activations','softwarelicensemanager'); ?></h3>
                                                            <div class="clear clear-fix"></div>
                                                            <div class="sml-sep"></div>
                                                            <div class="slm_ajax_msg"></div>
                                                            <div class="row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="max_allowed_domains"><?php _e('Maximum Allowed Domains','softwarelicensemanager'); ?></label>
                                                                    <input name="max_allowed_domains" class="form-control" type=" text" id="max_allowed_domains" value="<?php echo $max_domains; ?>" />
                                                                    <small class="form-text text-muted"><?php _e('Number of domains/installs in which this license can be used','softwarelicensemanager'); ?></small>
                                                                    <?php SLM_Utility::get_license_activation($license_key, SLM_TBL_LIC_DOMAIN, 'Domains', 'Domains'); ?>
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label for="max_allowed_devices"><?php _e('Maximum Allowed Devices','softwarelicensemanager'); ?></label>
                                                                    <input name="max_allowed_devices" class="form-control" type="text" id="max_allowed_devices" value="<?php echo $max_devices; ?>" />
                                                                    <small class="form-text text-muted"><?php _e('Number of domains/installs in which this license can be used','softwarelicensemanager'); ?></small>
                                                                    <?php SLM_Utility::get_license_activation($license_key, SLM_TBL_LIC_DEVICES, 'Devices', 'Devices'); ?>
                                                                </div>
                                                            </div>
                                                            <div class="clear"></div>
                                                        </div>
                                                        <div class="clear"></div>
                                                    </div>

                                                    <div class="tab-pane fade show " id="transaction" role="tabpanel" aria-labelledby="transaction-tab">

                                                        <div class="col-full">
                                                            <div class="slm-img-ico">
                                                                <img src="<?php echo SLM_Utility::slm_get_icon_url('1x', 'detail.png'); ?>" alt="">
                                                            </div>
                                                            <h3 class="slm-tab-title"><?php _e('Advanced Details','softwarelicensemanager'); ?></h3>
                                                            <div class="clear clear-fix"></div>
                                                            <div class="sml-sep"></div>

                                                            <div class="form-group">
                                                                <label for="order_date"><?php _e('Manual Reset Count','softwarelicensemanager'); ?></label>
                                                                <input name="manual_reset_count" class="form-control" type="text" id="manual_reset_count" value="<?php echo $reset_count; ?>" />
                                                                <small class="form-text text-muted"><?php _e('The number of times this license has been manually reset by the admin (use it if you want to keep track of it). It can be helpful for the admin to keep track of manual reset counts','softwarelicensemanager'); ?></small>

                                                            </div>
                                                            <div class="clear"></div>
                                                            <hr>
                                                            <div class="clear"></div>

                                                            <div class="row">
                                                                <div class="col-md-12">
                                                                    <h5><?php _e('Billing period','softwarelicensemanager'); ?></h5>
                                                                </div>
                                                                <div class="clear"></div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label for="order_date"><?php _e('Billing length','softwarelicensemanager'); ?></label>
                                                                        <input name="slm_billing_length" class="form-control" type="text" id="slm_billing_length" value="<?php echo $slm_billing_length; ?>" />
                                                                        <small class="form-text text-muted"><?php _e('Amount in days or months or years','softwarelicensemanager'); ?></small>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label for="order_date"><?php _e('Billing Interval','softwarelicensemanager'); ?></label>
                                                                        <select name="slm_billing_interval" class="form-control">
                                                                            <option value="days" <?php
                                                                                                    if ($slm_billing_interval == 'days') {
                                                                                                        echo 'selected="selected"';
                                                                                                    }
                                                                                                    ?>>
                                                                                <?php _e('Days','softwarelicensemanager'); ?>
                                                                            </option>
                                                                            <option value="months" <?php
                                                                                                    if ($slm_billing_interval == 'months') {
                                                                                                        echo 'selected="selected"';
                                                                                                    }
                                                                                                    ?>>
                                                                                <?php _e('Months','softwarelicensemanager'); ?>
                                                                            </option>
                                                                            <option value="years" <?php
                                                                                                    if ($slm_billing_interval == 'years') {
                                                                                                        echo 'selected="selected"';
                                                                                                    }
                                                                                                    ?>>
                                                                                <?php _e('Years','softwarelicensemanager'); ?>
                                                                            </option>
                                                                        </select>
                                                                        <small class="form-text text-muted"><?php _e('Frequency period: in days, months, years','softwarelicensemanager'); ?></small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="clear"></div>
                                                            <hr>
                                                            <div class="row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="order_date"><?php _e('Date Created','softwarelicensemanager'); ?></label>
                                                                    <input type="date" name="date_created" id="date_created" class="form-control wplm_pick_date" value="<?php echo $created_date; ?>">

                                                                    <small class="form-text text-muted"><?php _e('Creation date of license','softwarelicensemanager'); ?></small>
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label for="date_expiry"><?php _e('Expiration Date','softwarelicensemanager'); ?></label>
                                                                    <?php
                                                                    if ($lic_type == 'lifetime') : ?>

                                                                        <input name="date_expiry" type="date" id="date_expiry" class="form-control wplm_pick_date" value="<?php echo $expiry_date; ?>" disabled />

                                                                    <?php else : ?>

                                                                        <input name="date_expiry" type="date" id="date_expiry" class="form-control wplm_pick_date" value="<?php echo $expiry_date; ?>" />

                                                                    <?php endif;
                                                                    ?>
                                                                    <small class="form-text text-muted"><?php _e('Expiry date of license','softwarelicensemanager'); ?></small>
                                                                </div>

                                                                <div class="form-group col-md-6">
                                                                    <label for="date_renewed"><?php _e('Date Renewed','softwarelicensemanager'); ?></label>
                                                                    <input name="date_renewed" type="date" id="date_renewed" class="form-control wplm_pick_date" value="<?php echo $renewed_date; ?>" />
                                                                    <small class="form-text text-muted"><?php _e('Renewal date of license','softwarelicensemanager'); ?></small>
                                                                </div>

                                                                <div class="form-group col-md-6">
                                                                    <label for="date_activated"><?php _e('Date activated','softwarelicensemanager'); ?></label>
                                                                    <input name="date_activated" type="date" id="date_activated" class="form-control wplm_pick_date" value="<?php echo $date_activated; ?>" />
                                                                    <small class="form-text text-muted"><?php _e('Activation date','softwarelicensemanager'); ?></small>
                                                                </div>
                                                                <div class="clear"></div>
                                                            </div>
                                                        </div>
                                                        <div class="clear"></div>
                                                    </div>

                                                    <div class="tab-pane fade show " id="productinfo" role="tabpanel" aria-labelledby="productinfo-tab">

                                                        <div class="col-full">
                                                            <div class="slm-img-ico">
                                                                <img src="<?php echo SLM_Utility::slm_get_icon_url('1x', 'box-2.png'); ?>" alt="">
                                                            </div>
                                                            <h3 class="slm-tab-title"><?php _e('Product Informations','softwarelicensemanager'); ?></h3>
                                                            <div class="clear clear-fix"></div>
                                                            <div class="sml-sep"></div>

                                                            <div class="form-group">
                                                                <label for="product_ref"><?php _e('Product','softwarelicensemanager'); ?></label>
                                                                <input name="product_ref" class="form-control" type="text" id="product_ref" value="<?php echo $product_ref; ?>" />
                                                                <small class="form-text text-muted"><?php _e('The product that this license gives access to','softwarelicensemanager'); ?></small>
                                                            </div>

                                                            <div class="row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="txn_id"><?php _e('Unique Transaction ID','softwarelicensemanager'); ?></label>
                                                                    <input name="txn_id" type="text" class="form-control" id="txn_id" value="<?php echo $txn_id; ?>" />
                                                                    <small class="form-text text-muted"><?php _e('The unique transaction ID associated with this license key','softwarelicensemanager'); ?></small>
                                                                </div>

                                                                <div class="form-group col-md-6">
                                                                    <label for="purchase_id_"><?php _e('Purchase Order ID #','softwarelicensemanager'); ?></label>
                                                                    <input name="purchase_id_" class="form-control" type="text" id="purchase_id_" value="<?php echo $purchase_id_; ?>" size="8" />
                                                                    <?php
                                                                    if (!empty($purchase_id_)) : ?>
                                                                        <small class="form-text text-muted"><?php _e('This is associated with the purchase ID woocommerce support.','softwarelicensemanager'); ?> <a href="<?php echo admin_url() . 'post.php?post=' . $purchase_id_; ?>&action=edit"><?php _e('View Order','softwarelicensemanager'); ?> </a></small>
                                                                    <?php else : ?>
                                                                        <small class="form-text text-muted"> <?php _e('No order found yet','softwarelicensemanager'); ?></small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="clear"></div>
                                                            <div class="row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="until"><?php _e('Supported Until','softwarelicensemanager'); ?></label>
                                                                    <input name="until" type="text" class="form-control" id="until" value="<?php echo $until; ?>" />
                                                                    <small class="form-text text-muted"><?php _e('Until what version this product is supported','softwarelicensemanager'); ?></small>
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label for="current_ver"><?php _e('Current Version','softwarelicensemanager'); ?></label>
                                                                    <input name="current_ver" type="text" class="form-control" id="current_ver" value="<?php echo $current_ver; ?>" />
                                                                    <small class="form-text text-muted"><?php _e('What is the current version of this product','softwarelicensemanager'); ?></small>
                                                                </div>
                                                                <div class="clear"></div>
                                                            </div>
                                                            <?php
                                                            if ($slm_options['slm_multiple_items'] == 1) :
                                                                global $wpdb;
                                                                $post_meta_tbl      = $wpdb->prefix . 'postmeta';
                                                                $item_ref_meta      = '_license_item_reference';
                                                                $sql_prep           = $wpdb->prepare("SELECT DISTINCT(meta_value) FROM $post_meta_tbl WHERE meta_key = %s", $item_ref_meta);
                                                                $values_item_refs   = $wpdb->get_results($sql_prep, OBJECT);
                                                            ?>
                                                                <div class="row">
                                                                    <div class="form-group col-md-12">
                                                                        <label for="item_reference"><?php _e('Item reference','softwarelicensemanager'); ?></label>
                                                                        <select name="item_reference" class="form-control">
                                                                            <?php
                                                                            $was_selected = false;
                                                                            foreach ($values_item_refs as $item_reference) {
                                                                                $sel_val        = esc_attr(trim($item_reference->meta_value));
                                                                                $is_selected    = $lic_item_ref == $sel_val;
                                                                                //remember is it was selected during the process
                                                                                $was_selected = $was_selected == false ? $is_selected : $was_selected;
                                                                                // filter out empty values
                                                                                if (!empty($sel_val)) {
                                                                                    echo '<option value="' . $sel_val . '"' . ($is_selected == true ? ' selected' : '') . '>' . $sel_val . '</option>';
                                                                                }
                                                                            }
                                                                            ?>
                                                                            <option value="select one" <?php echo ($was_selected == false ? ' selected' : '') ?>><?php _e(' Select one ...','softwarelicensemanager'); ?></option>
                                                                        </select>
                                                                        <small class="form-text text-muted"><?php _e('Item reference of your software','softwarelicensemanager'); ?></small>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="clear"></div>
                                                        </div>
                                                        <div class="clear"></div>
                                                    </div>
                                                    <?php
                                                    if (isset($_GET['edit_record']) && !empty($_GET['edit_record'])) : ?>
                                                        <div class="tab-pane fade show " id="export-license" role="tabpanel" aria-labelledby="export-license-tab">

                                                            <div class="slm-img-ico">
                                                                <img src="<?php echo SLM_Utility::slm_get_icon_url('1x', 'share-right.png'); ?>" alt="">
                                                            </div>
                                                            <h3 class="slm-tab-title"><?php _e('Export License','softwarelicensemanager'); ?></h3>
                                                            <div class="clear clear-fix"></div>
                                                            <div class="sml-sep"></div>

                                                            <div class="export-license col-full">
                                                                <div class="license_export_info" style="min-width: 100%; max-width: 900px">
                                                                    <?php
                                                                    $lic_info = SLM_Utility::slm_get_licinfo('slm_info', $license_key);

                                                                    echo '<figure class="highlight"><pre><code id="lic-json-data" data-lickey="' . $license_key . '">' . json_encode($lic_info, JSON_PRETTY_PRINT) . '</code></pre></figure>';
                                                                    ?>
                                                                    <a href="#" class="button-secondary" onclick="slm_exportlicense()"><?php _e('Export License','softwarelicensemanager'); ?></a>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="tab-pane fade show " id="activity-log" role="tabpanel" aria-labelledby="activity-log-tab">
                                                            <div class="slm-img-ico">
                                                                <img src="<?php echo SLM_Utility::slm_get_icon_url('1x', 'server-rack.png'); ?>" alt="">
                                                            </div>
                                                            <h3 class="slm-tab-title"><?php _e('Activity Log','softwarelicensemanager'); ?></h3>
                                                            <div class="clear clear-fix"></div>

                                                            <div class="sml-sep"></div>

                                                            <div class="lic-activity-log" style="min-height: 325px; min-width: 100%; max-width: 900px">
                                                                <?php SLM_Utility::get_lic_activity($license_key); ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="output-msg">
                                                        <?php
                                                        $data = array('row_id' => $id, 'key' => $license_key);
                                                        $extra_output = apply_filters('slm_add_edit_interface_above_submit', '', $data);
                                                        if (!empty($extra_output)) {
                                                            echo $extra_output;
                                                        }
                                                        ?>
                                                    </div>
                                                    <div class="submit form_actions">
                                                        <?php
                                                        $save_label = '';
                                                        if (isset($_GET['edit_record']) && !empty($_GET['edit_record'])) {
                                                            $save_label =  __('Save changes','softwarelicensemanager');
                                                        } else {
                                                            $save_label = __('Create license','softwarelicensemanager');
                                                        }
                                                        ?>
                                                        <input type="submit" class="button button-primary save_lic" name="save_record" value="<?php echo $save_label; ?>" />
                                                        <a href="admin.php?page=<?php echo SLM_MAIN_MENU_SLUG; ?>" class="button media-button select-mode-toggle-button"><?php _e('Manage Licenses','softwarelicensemanager'); ?></a>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <!-- end of form -->
                                        <div class="clear"></div>
                                    </div>
                                </div>
                                <!-- end of tabbed form -->
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
            jQuery(".save_lic").click(function(event) {
                // Fetch form to apply custom Bootstrap validation
                var form = jQuery(".slm_license_form")
                if (form[0].checkValidity() === false) {
                    jQuery('#userinfo-tab').css("color", "red");
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.addClass('was-validated');
            });
            jQuery(document).ready(function() {
                jQuery('.deactivate_lic_key').click(function(event) {
                    var id = jQuery(this).attr("id");
                    var activation_type = jQuery(this).attr('data-activation_type');
                    var class_name = '.lic-entry-' + id;

                    jQuery(this).text('Removing');
                    jQuery.get('<?php echo esc_url(home_url('/')); ?>' + 'wp-admin/admin-ajax.php?action=del_activation&id=' + id + '&activation_type=' + activation_type, function(data) {
                        if (data == 'success') {
                            jQuery(class_name).remove();
                            jQuery('.slm_ajax_msg').html('<div class="alert alert-primary" role="alert"> License key was deactivated! </div>');
                        } else {
                            jQuery('.slm_ajax_msg').html('<div class="alert alert-danger" role="alert"> License key was not deactivated! </div>');
                        }
                    });
                });
            });
        });
    </script>
<?php
}
