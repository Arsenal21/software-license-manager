<?php

if (!defined('WPINC')) {
    die;
}


function slm_integration_help_menu()
{
    ?>

    <h2>License Manager Integration Help v <?php SLM_VERSION; ?></h2>

    <style>
        pre {
            display: block;
            font-size: 87.5%;
            color: #212529;
            line-height: 12px;
            padding: 8px;
            background: #fff;
            margin: 16px 0;
            text-align: left;
        }

        pre {
            display: block;
            font-size: 87.5%;
            color: #212529;
        }
    </style>
    <div class="wrap">
        <div class="slm-postbox set-pd">
            <h3>API Settings</h3>
            <div class="inside">
                <?php
                $options                    = get_option('slm_plugin_options');
                $creation_secret_key        = $options['lic_creation_secret'];
                $secret_verification_key    = $options['lic_verification_secret'];
                $api_query_post_url = SLM_SITE_HOME_URL;
                echo "<br><strong>The License API Query POST URL For Your Installation</strong>";
                echo '<br><div class="slm_code"> <input style="width: 500px" type="text" value="' . $api_query_post_url . '"></div>';
                echo "<br><strong>The License Activation or Deactivation API secret key</strong>";
                echo '<br><div class="slm_code"><input style="width: 500px" type="text" value="' . $secret_verification_key . '"></div>';
                echo "<br><strong>The License Creation API secret key</strong>";
                echo '<br><div class="slm_code"><input style="width: 500px" type="text" value="' . $creation_secret_key . '"></div>';
                ?>

            </div>

            <div>
                <p>Documentation and guides: <a href="https://documenter.getpostman.com/view/307939/6tjU1FL?version=latest">check out postman demos</a></p>
            </div>

            <div class="error_codes">
                <h3>Error codes and constants</h3>
                <pre class="slm_code">
                    const CREATE_FAILED                 = 10;
                    const CREATE_KEY_INVALID            = 100;
                    const DOMAIN_ALREADY_INACTIVE       = 80;
                    const DOMAIN_MISSING                = 70;
                    const KEY_CANCELED                  = 130;
                    const KEY_CANCELED_FAILED           = 140;
                    const KEY_DEACTIVATE_DOMAIN_SUCCESS = 360;
                    const KEY_DEACTIVATE_SUCCESS        = 340;
                    const KEY_DELETE_FAILED             = 300;
                    const KEY_DELETE_SUCCESS            = 320;
                    const KEY_DELETED                   = 130;
                    const KEY_UPDATE_FAILED             = 220;
                    const KEY_UPDATE_SUCCESS            = 240;
                    const LICENSE_ACTIVATED             = 380;
                    const LICENSE_BLOCKED               = 20;
                    const LICENSE_CREATED               = 400;
                    const LICENSE_EXIST                 = 200;
                    const LICENSE_EXPIRED               = 30;
                    const LICENSE_IN_USE                = 40;
                    const LICENSE_INVALID               = 60;
                    const MISSING_KEY_DELETE_FAILED     = 280;
                    const MISSING_KEY_UPDATE_FAILED     = 260;
                    const REACHED_MAX_DEVICES           = 120;
                    const REACHED_MAX_DOMAINS           = 50;
                    const VERIFY_KEY_INVALID            = 90;
                                                                            </pre>
            </div>
        </div>
    </div>

<?php

}
