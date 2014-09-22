<?php

function wp_lic_mgr_admin_fnc_menu() {

    echo '<div class="wrap">';
    echo '<h2>License Manager Admin Functions</h2>';
    echo '<div id="poststuff"><div id="post-body">';

    $slm_options = get_option('slm_plugin_options');
    
    if (isset($_POST['send_deactivation_request'])) {
        $postURL = $_POST['lic_mgr_deactivation_req_url'];
        $secretKeyForVerification = $slm_options['lic_verification_secret'];
        $data = array();
        $data['secret_key'] = $secretKeyForVerification;

        $ch = curl_init($postURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $returnValue = curl_exec($ch);

        $msg = "";
        if ($returnValue == "Success") {
            $msg .= "Success message returned from the remote host.";
        }
        echo '<div id="message" class="updated fade"><p>';
        echo 'Request sent to the specified URL!';
        echo '<br />' . $msg;
        echo '</p></div>';
    }
    ?>
    <br />
    <div class="postbox">
        <h3><label for="title">Send Deactivation Message for a License</label></h3>
        <div class="inside">
            <br /><strong>Enter the URL where the license deactivation message will be sent to</strong>
            <br /><br />
            <form method="post" action="">

                <input name="lic_mgr_deactivation_req_url" type="text" size="100" value="<?php echo $_POST['lic_mgr_deactivation_req_url']; ?>"/>
                <div class="submit">
                    <input type="submit" name="send_deactivation_request" value="Send Request" />
                </div>
            </form>
        </div></div>    
    <?php
    echo '</div></div>';
    echo '</div>';
}
