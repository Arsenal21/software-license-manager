<?php

function wp_lic_mgr_client_applications_product_license_menu() {
    global $wp_lic_mgr_client_config;

    echo '<div class="wrap">';
    echo '<h2>Product License</h2>';
    echo '<div id="poststuff"><div id="post-body">';

    $message = "";
    if (isset($_POST['activate_license'])) {
        $returnData = wp_lic_mgr_client_verify_and_activate_license($_POST['lic_key']);
        if ($returnData == 'Success') {
            $message .= "License key is valid! Product activated.";
        } else {
            $message .= "License key is invalid!";
            $message .= "<br />" . $retData['msg'];
        }
    }
    if (isset($_POST['deactivate_license'])) {
        $returnData = wp_lic_mgr_client_deactivate_license_and_update_key($_POST['lic_key']);
        if ($returnData == 'Success') {
            $message .= "License key deactivated!";
        } else {
            $message .= "License key deactivation failed!";
            $message .= "<br />" . $retData['msg'];
        }
    }
    if (!empty($message)) {
        echo '<div id="message" class="updated fade"><p><strong>';
        echo $message;
        echo '</strong></p></div>';
    }
    $license_key = $wp_lic_mgr_client_config->getValue(WP_LICENSE_MGR_CLIENT_LICENSE_KEY_VAR_NAME);
    ?>

    <div class="postbox">
        <h3><label for="title">License Details </label></h3>
        <div class="inside">

            <p><strong>Please enter the license key for this product to activate it</strong> 
            <form action="" method="post">
                <table class="form-table">
                    <tr>
                        <th style="width:100px;"><label for="lic_key">License Key</label></th>
                        <td ><input class="regular-text" type="text" id="lic_key" name="lic_key"  value="<?php echo $license_key; ?>" ></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="activate_license" value="Activate" class="button-primary" />
                    <input type="submit" name="deactivate_license" value="Deactivate" />
                </p>
            </form>	
            </p>
        </div></div>	
    <?php
    echo '</div></div>';
    echo '</div>';
}

function wp_lic_mgr_client_verify_and_activate_license($lic_key) {
    global $wp_lic_mgr_client_config;
    $retData = wp_lic_mgr_client_license_verify($lic_key);
    if ($retData['result'] == 'Success') {
        // something else is to be done to store the license key.
        $wp_lic_mgr_client_config->setValue(WP_LICENSE_MGR_CLIENT_LICENSE_KEY_VAR_NAME, $lic_key);
        $wp_lic_mgr_client_config->saveConfig();
        return 'Success';
    } else {
        return $retData['msg'];
    }
}

function wp_lic_mgr_client_deactivate_license_and_update_key($lic_key) {
    global $wp_lic_mgr_client_config;
    $retData = wp_lic_mgr_client_deactivate_license($lic_key);
    if ($retData['result'] == 'Success') {
        // something else is to be done to store the license key.
        $wp_lic_mgr_client_config->setValue(WP_LICENSE_MGR_CLIENT_LICENSE_KEY_VAR_NAME, ""); //Reset the license key
        $wp_lic_mgr_client_config->saveConfig();
        return 'Success';
    } else {
        return $retData['msg'];
    }
}

function wp_lic_mgr_client_deactivate_license($lic_key) {
    // Post URL
    $postURL = WP_LICENSE_MGR_LIC_DEACTIVATION_POST_URL;
    // The Secret key
    $secretKey = WP_LICENSE_MGR_LIC_SECRET_KEY;
    // The License key
    $licenseKey = $lic_key; //take this input from the user
    $data = array();
    $data['secret_key'] = $secretKey;
    $data['license_key'] = $licenseKey;
    $data['registered_domain'] = $_SERVER['SERVER_NAME'];

    // send data to post URL
    $ch = curl_init($postURL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $returnValue = curl_exec($ch);
    //print_r($returnValue);
    list ($result, $msg, $additionalMsg) = explode("\n", $returnValue);
    $retData = array();
    $retData['result'] = $result;
    $retData['msg'] = $msg;
    $retData['additional_msg'] = $additionalMsg;
    return $retData;
}

function wp_lic_mgr_client_license_verify($lic_key) {
    // Post URL
    $postURL = WP_LICENSE_MGR_LIC_ACTIVATION_POST_URL;
    // The Secret key
    $secretKey = WP_LICENSE_MGR_LIC_SECRET_KEY;
    // The License key
    $licenseKey = $lic_key; //take this input from the user
    $data = array();
    $data['secret_key'] = $secretKey;
    $data['license_key'] = $licenseKey;
    $data['registered_domain'] = $_SERVER['SERVER_NAME'];

    // send data to post URL
    $ch = curl_init($postURL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $returnValue = curl_exec($ch);
    //print_r($returnValue);
    list ($result, $msg, $additionalMsg) = explode("\n", $returnValue);
    $retData = array();
    $retData['result'] = $result;
    $retData['msg'] = $msg;
    $retData['additional_msg'] = $additionalMsg;
    return $retData;
}

function wp_lic_mgr_client_is_license_valid() {
    global $wp_lic_mgr_client_config;
    $is_valid = false;
    $license_key = $wp_lic_mgr_client_config->getValue(WP_LICENSE_MGR_CLIENT_LICENSE_KEY_VAR_NAME);
    if (!empty($license_key)) {
        $is_valid = true;
    }
    return $is_valid;
}

function wp_lic_mgr_client_lic_activation_warning() {
    if (!wp_lic_mgr_client_is_license_valid()) {
        //TODO do not show this notice in the product license activation menu page
        echo '<div class="updated fade">The plugin is almost ready. You must provide a valid License key <a href="admin.php?page=wp_lic_mgr_product_license">here</a> to make it work.</div>';
    }
}

//add_action('admin_notices', 'wp_lic_mgr_client_lic_activation_warning');
