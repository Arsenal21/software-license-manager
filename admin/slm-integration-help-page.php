<?php

if (!defined('WPINC')) {
    die; // Security measure to prevent direct access
}

/**
 * Display the Integration Help menu for SLM
 */
function slm_integration_help_menu()
{
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e('SLM Plus - Integration Help', 'slm-plus'); ?></h1>
        <span class="version"><?php echo esc_html__('Version:', 'slm-plus') . ' ' . esc_html(SLM_VERSION); ?></span>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e('API Settings', 'slm-plus'); ?></h2>
                        <div class="inside">
                            <?php
                            $slm_options = get_option('slm_plugin_options');
                            // Apply escaping to ensure output is safe
                            $slm_creation_secret_key = esc_attr($slm_options['lic_creation_secret']);
                            $slm_secret_verification_key = esc_attr($slm_options['lic_verification_secret']);
                            $slm_api_query_post_url = esc_url(SLM_SITE_HOME_URL);
                            ?>
                            <p><strong><?php esc_html_e('License API Query POST URL for Your Installation', 'slm-plus'); ?></strong></p>
                            <input class="widefat" type="text" value="<?php echo esc_url($slm_api_query_post_url); ?>" readonly />

                            <p><strong><?php esc_html_e('License Activation/Deactivation API Secret Key', 'slm-plus'); ?></strong></p>
                            <input class="widefat" type="text" value="<?php echo esc_attr($slm_secret_verification_key); ?>" readonly />

                            <p><strong><?php esc_html_e('License Creation API Secret Key', 'slm-plus'); ?></strong></p>
                            <input class="widefat" type="text" value="<?php echo esc_attr($slm_creation_secret_key); ?>" readonly />
                        </div>
                    </div>


                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e('Documentation and Guides', 'slm-plus'); ?></h2>
                        <div class="inside">
                            <p><?php esc_html_e('Need more help? Check out the documentation:', 'slm-plus'); ?>
                                <a href="https://documenter.getpostman.com/view/307939/6tjU1FL?version=latest" target="_blank" rel="noopener noreferrer">
                                    <?php esc_html_e('Postman API Demos', 'slm-plus'); ?>
                                </a>
                            </p>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e('Error Codes and Descriptions', 'slm-plus'); ?></h2>
                        <div class="inside">
                            <table class="widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Constant', 'slm-plus'); ?></th>
                                        <th><?php esc_html_e('Error Code', 'slm-plus'); ?></th>
                                        <th><?php esc_html_e('Description', 'slm-plus'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $error_codes = [
                                        ['CREATE_FAILED', '10', __('The license creation failed due to an unknown error.', 'slm-plus')],
                                        ['CREATE_KEY_INVALID', '100', __('The license key provided during creation is invalid.', 'slm-plus')],
                                        ['DOMAIN_ALREADY_INACTIVE', '80', __('The domain associated with this license is already inactive.', 'slm-plus')],
                                        ['DOMAIN_MISSING', '70', __('The domain information is missing in the request.', 'slm-plus')],
                                        ['KEY_CANCELED', '130', __('The license key has been canceled.', 'slm-plus')],
                                        ['KEY_CANCELED_FAILED', '140', __('Failed to cancel the license key.', 'slm-plus')],
                                        ['KEY_DEACTIVATE_DOMAIN_SUCCESS', '360', __('Successfully deactivated the license key for the specified domain.', 'slm-plus')],
                                        ['KEY_DEACTIVATE_SUCCESS', '340', __('The license key was successfully deactivated.', 'slm-plus')],
                                        ['KEY_DELETE_FAILED', '300', __('Failed to delete the license key.', 'slm-plus')],
                                        ['KEY_DELETE_SUCCESS', '320', __('The license key was successfully deleted.', 'slm-plus')],
                                        ['KEY_DELETED', '130', __('The license key has been deleted.', 'slm-plus')],
                                        ['KEY_UPDATE_FAILED', '220', __('Failed to update the license key details.', 'slm-plus')],
                                        ['KEY_UPDATE_SUCCESS', '240', __('The license key was successfully updated.', 'slm-plus')],
                                        ['LICENSE_ACTIVATED', '380', __('The license key was successfully activated.', 'slm-plus')],
                                        ['LICENSE_BLOCKED', '20', __('The license key has been blocked from further use.', 'slm-plus')],
                                        ['LICENSE_CREATED', '400', __('The license key was successfully created.', 'slm-plus')],
                                        ['LICENSE_EXIST', '200', __('The license key already exists in the system.', 'slm-plus')],
                                        ['LICENSE_EXPIRED', '30', __('The license key has expired.', 'slm-plus')],
                                        ['LICENSE_IN_USE', '40', __('The license key is already in use on another domain or device.', 'slm-plus')],
                                        ['LICENSE_INVALID', '60', __('The license key is invalid.', 'slm-plus')],
                                        ['MISSING_KEY_DELETE_FAILED', '280', __('Failed to delete the license key because it was not found.', 'slm-plus')],
                                        ['MISSING_KEY_UPDATE_FAILED', '260', __('Failed to update the license key because it was not found.', 'slm-plus')],
                                        ['REACHED_MAX_DEVICES', '120', __('The license key has reached its maximum allowable devices.', 'slm-plus')],
                                        ['REACHED_MAX_DOMAINS', '50', __('The license key has reached its maximum allowable domains.', 'slm-plus')],
                                        ['VERIFY_KEY_INVALID', '90', __('The key verification failed due to an invalid key.', 'slm-plus')],
                                    ];

                                    foreach ($error_codes as $code) {
                                        echo '<tr>';
                                        foreach ($code as $value) {
                                            echo '<td>' . esc_html($value) . '</td>';
                                        }
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div> <!-- end postbox -->
                </div> <!-- end post-body-content -->
            </div> <!-- end post-body -->
        </div> <!-- end poststuff -->
    </div> <!-- end wrap -->
    <?php
}
