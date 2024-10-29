<?php

if (!defined('WPINC')) {
    die; // Security measure to prevent direct access
}

function slm_integration_help_menu()
{
    ?>

    <h2><?php echo esc_html__('License Manager Integration Help', 'slmplus'); ?> v <?php echo esc_html(SLM_VERSION); ?></h2>

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
            <h3><?php echo esc_html__('API Settings', 'slmplus'); ?></h3>
            <div class="inside">
                <?php
                $slm_options                = get_option('slm_plugin_options');
                $slm_creation_secret_key    = esc_attr($slm_options['lic_creation_secret']);
                $slm_secret_verification_key = esc_attr($slm_options['lic_verification_secret']);
                $slm_api_query_post_url     = esc_url(SLM_SITE_HOME_URL);

                echo "<br><strong>" . esc_html__('The License API Query POST URL For Your Installation', 'slmplus') . "</strong>";
                echo '<br><div class="slm_code"> <input style="width: 500px" type="text" value="' . $slm_api_query_post_url . '" readonly /></div>';

                echo "<br><strong>" . esc_html__('The License Activation or Deactivation API Secret Key', 'slmplus') . "</strong>";
                echo '<br><div class="slm_code"><input style="width: 500px" type="text" value="' . $slm_secret_verification_key . '" readonly /></div>';

                echo "<br><strong>" . esc_html__('The License Creation API Secret Key', 'slmplus') . "</strong>";
                echo '<br><div class="slm_code"><input style="width: 500px" type="text" value="' . $slm_creation_secret_key . '" readonly /></div>';
                ?>
            </div>

            <div>
                <p><?php echo esc_html__('Documentation and guides:', 'slmplus'); ?> 
                    <a href="https://documenter.getpostman.com/view/307939/6tjU1FL?version=latest" target="_blank"><?php echo esc_html__('Check out Postman demos', 'slmplus'); ?></a>
                </p>
            </div>

            <div class="error_codes">
                <h3><?php echo esc_html__('Error Codes and Descriptions', 'slmplus'); ?></h3>

                <table class="slm-lic-error-code">
                    <thead>
                        <tr>
                            <td><strong><?php echo esc_html__('Constant', 'slmplus'); ?></strong></td>
                            <td><strong><?php echo esc_html__('Error Code', 'slmplus'); ?></strong></td>
                            <td><strong><?php echo esc_html__('Description', 'slmplus'); ?></strong></td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>CREATE_FAILED</td><td>10</td>
                            <td><?php echo esc_html__('The license creation failed due to an unknown error.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>CREATE_KEY_INVALID</td><td>100</td>
                            <td><?php echo esc_html__('The license key provided during creation is invalid.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>DOMAIN_ALREADY_INACTIVE</td><td>80</td>
                            <td><?php echo esc_html__('The domain associated with this license is already inactive.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>DOMAIN_MISSING</td><td>70</td>
                            <td><?php echo esc_html__('The domain information is missing in the request.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>KEY_CANCELED</td><td>130</td>
                            <td><?php echo esc_html__('The license key has been canceled.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>KEY_CANCELED_FAILED</td><td>140</td>
                            <td><?php echo esc_html__('Failed to cancel the license key.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>KEY_DEACTIVATE_DOMAIN_SUCCESS</td><td>360</td>
                            <td><?php echo esc_html__('Successfully deactivated the license key for the specified domain.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>KEY_DEACTIVATE_SUCCESS</td><td>340</td>
                            <td><?php echo esc_html__('The license key was successfully deactivated.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>KEY_DELETE_FAILED</td><td>300</td>
                            <td><?php echo esc_html__('Failed to delete the license key.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>KEY_DELETE_SUCCESS</td><td>320</td>
                            <td><?php echo esc_html__('The license key was successfully deleted.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>KEY_DELETED</td><td>130</td>
                            <td><?php echo esc_html__('The license key has been deleted.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>KEY_UPDATE_FAILED</td><td>220</td>
                            <td><?php echo esc_html__('Failed to update the license key details.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>KEY_UPDATE_SUCCESS</td><td>240</td>
                            <td><?php echo esc_html__('The license key was successfully updated.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>LICENSE_ACTIVATED</td><td>380</td>
                            <td><?php echo esc_html__('The license key was successfully activated.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>LICENSE_BLOCKED</td><td>20</td>
                            <td><?php echo esc_html__('The license key has been blocked from further use.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>LICENSE_CREATED</td><td>400</td>
                            <td><?php echo esc_html__('The license key was successfully created.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>LICENSE_EXIST</td><td>200</td>
                            <td><?php echo esc_html__('The license key already exists in the system.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>LICENSE_EXPIRED</td><td>30</td>
                            <td><?php echo esc_html__('The license key has expired.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>LICENSE_IN_USE</td><td>40</td>
                            <td><?php echo esc_html__('The license key is already in use on another domain or device.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>LICENSE_INVALID</td><td>60</td>
                            <td><?php echo esc_html__('The license key is invalid.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>MISSING_KEY_DELETE_FAILED</td><td>280</td>
                            <td><?php echo esc_html__('Failed to delete the license key because it was not found.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>MISSING_KEY_UPDATE_FAILED</td><td>260</td>
                            <td><?php echo esc_html__('Failed to update the license key because it was not found.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>REACHED_MAX_DEVICES</td><td>120</td>
                            <td><?php echo esc_html__('The license key has reached its maximum allowable devices.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>REACHED_MAX_DOMAINS</td><td>50</td>
                            <td><?php echo esc_html__('The license key has reached its maximum allowable domains.', 'slmplus'); ?></td>
                        </tr>
                        <tr>
                            <td>VERIFY_KEY_INVALID</td><td>90</td>
                            <td><?php echo esc_html__('The key verification failed due to an invalid key.', 'slmplus'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php
}
