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

                <table class="slm-lic-erro-code">
                    <thead>
                    <td><strong>Constant</strong></td>
                    <td><strong>Error code</strong></td>
                    </thead>

                <tr>
                    <td>CREATE_FAILED</td>
                    <td>10</td>
                </tr>
                <tr>
                    <td>CREATE_KEY_INVALID</td>
                    <td>100</td>
                </tr>
                <tr>
                    <td>DOMAIN_ALREADY_INACTIVE </td>
                    <td>80</td>
                </tr>
                <tr>
                    <td>DOMAIN_MISSING </td>
                    <td>70</td>
                </tr>
                <tr>
                    <td>KEY_CANCELED </td>
                    <td>130</td>
                </tr>
                <tr>
                    <td>KEY_CANCELED_FAILED  </td>
                    <td>140</td>
                </tr>
                <tr>
                    <td>KEY_DEACTIVATE_DOMAIN_SUCCESS </td>
                    <td>360</td>
                </tr>
                <tr>
                    <td>KEY_DEACTIVATE_SUCCESS  </td>
                    <td>340</td>
                </tr>
                <tr>
                    <td>KEY_DELETE_FAILED </td>
                    <td>300</td>
                </tr>
                <tr>
                    <td>KEY_DELETE_SUCCESS</td>
                    <td>320</td>
                </tr>
                <tr>
                    <td>KEY_DELETED  </td>
                    <td>130</td>
                </tr>
                <tr>
                    <td>KEY_UPDATE_FAILED </td>
                    <td>220</td>
                </tr>
                <tr>
                    <td>KEY_UPDATE_SUCCESS</td>
                    <td>240</td>
                </tr>
                <tr>
                    <td>LICENSE_ACTIVATED </td>
                    <td>380</td>
                </tr>
                <tr>
                    <td>LICENSE_BLOCKED</td>
                    <td>20</td>
                </tr>
                <tr>
                    <td>LICENSE_CREATED</td>
                    <td>400</td>
                </tr>
                <tr>
                    <td>LICENSE_EXIST</td>
                    <td>200</td>
                </tr>
                <tr>
                    <td>LICENSE_EXPIRED</td>
                    <td>30</td>
                </tr>
                <tr>
                    <td>LICENSE_IN_USE </td>
                    <td>40</td>
                </tr>
                <tr>
                    <td>LICENSE_INVALID</td>
                    <td>60</td>
                </tr>
                <tr>
                    <td>MISSING_KEY_DELETE_FAILED  </td>
                    <td>280</td>
                </tr>
                <tr>
                    <td>MISSING_KEY_UPDATE_FAILED  </td>
                    <td>260</td>
                </tr>
                <tr>
                    <td>REACHED_MAX_DEVICES  </td>
                    <td>120</td>
                </tr>
                <tr>
                    <td>REACHED_MAX_DOMAINS  </td>
                    <td>50</td>
                </tr>
                <tr>
                    <td>VERIFY_KEY_INVALID</td>
                    <td>90</td>
                </tr>

                </table>
            </div>
        </div>
    </div>

<?php

}
