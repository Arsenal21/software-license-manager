<?php

function wp_lic_mgr_settings_menu() {

    echo '<div class="wrap">';
    echo '<h2>WP License Manager Settings v' . WP_LICENSE_MANAGER_VERSION . '</h2>';
    echo '<div id="poststuff"><div id="post-body">';

    wp_lic_mgr_general_settings();

    echo '</div></div>';
    echo '</div>';
}

function wp_lic_mgr_general_settings() {
    if (isset($_POST['info_update'])) {
        update_option('wp_lic_mgr_reg_secret_key', (string) $_POST["wp_lic_mgr_reg_secret_key"]);
        update_option('wp_lic_mgr_key_prefix', (string) $_POST["wp_lic_mgr_key_prefix"]);
        if (is_numeric($_POST["wp_lic_mgr_max_num_domain"]))
            update_option('wp_lic_mgr_max_num_domain', (string) $_POST["wp_lic_mgr_max_num_domain"]);
        update_option('wp_lic_mgr_verification_secret_key', (string) $_POST["wp_lic_mgr_verification_secret_key"]);
        //update_option('eStore_enable_wishlist_int', ($_POST['eStore_enable_wishlist_int']=='1') ? '1':'' );
    }
    $secret_key = get_option('wp_lic_mgr_reg_secret_key');
    if (empty($secret_key)) {
        $secret_key = uniqid('', true);
    }
    $secret_verification_key = get_option('wp_lic_mgr_verification_secret_key');
    if (empty($secret_verification_key)) {
        $secret_verification_key = uniqid('', true);
    }
    ?>
    <p>For information, updates and detailed documentation, please visit the <a href="http://www.tipsandtricks-hq.com" target="_blank">WP License Manager Documentation Site</a> or
        The main plugin page <a href="http://www.tipsandtricks-hq.com/" target="_blank">WP License Manager</a></p>

    <div class="postbox">
        <h3><label for="title">Quick Usage Guide</label></h3>
        <div class="inside">

            <p>1. First register a key at purchase time.</p>
            <p>2. Add the code so at activation time it asks for the key.</p>
            <p>3. Integrate the real time online key verification part.</p>
        </div></div>

    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">

        <div class="postbox">
            <h3><label for="title">General License Manager Settings</label></h3>
            <div class="inside">
                <table class="form-table">

                    <tr valign="top">
                        <th scope="row">Secret Key for License Creation</th>
                        <td><input type="text" name="wp_lic_mgr_reg_secret_key" value="<?php echo $secret_key; ?>" size="30" />
                            <br />This secret key will be used to authenticate any license creation request. You can change it with something random.</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">License Key Prefix</th>
                        <td><input type="text" name="wp_lic_mgr_key_prefix" value="<?php echo get_option('wp_lic_mgr_key_prefix'); ?>" size="30" />
                            <br />You can optionaly specify a prefix for the license keys. This prefix will be added to the uniquely generated license keys.</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Secret Key for License Verification Requests</th>
                        <td><input type="text" name="wp_lic_mgr_verification_secret_key" value="<?php echo $secret_verification_key; ?>" size="30" />
                            <br />This secret key will be used to authenticate any license verification request from customer's site. Important! Do not change this value once your customer's start to use your product(s)!</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Maximum Allowed Domains</th>
                        <td><input type="text" name="wp_lic_mgr_max_num_domain" value="<?php echo get_option('wp_lic_mgr_max_num_domain'); ?>" size="30" />
                            <br />Maximum number of domains which each license is valid for.</td>
                    </tr>

                </table>
            </div></div>

        <div class="submit">
            <input type="submit" name="info_update" value="Update Options &raquo;" />
        </div>
    </form>
    <?php
}
