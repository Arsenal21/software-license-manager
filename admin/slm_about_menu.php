<?php

if (!defined('WPINC')) {
    die;
}


function slm_about_menu()
{

    echo '<div class="wrap">';
    echo '<h2 class="imgh2"><img src="' . SLM_ASSETS_URL . 'images/slm_logo.svg" alt="slm logo">' . __('SLM - About', 'softwarelicensemanager') . '</h2>';
    echo '<div id="poststuff"><div id="post-body">';

    $slm_options = get_option('slm_plugin_options');

    ?>
    <br/>

    <div class="postbox">
        <h3 class="hndle"><label for="title"><?php _e('Credits and authors', 'softwarelicensemanager'); ?></label></h3>
        <div class="inside">
            <br>
            <p><?php _e('Software license management solution for your web applications (WordPress plugins, Themes, Applications, PHP based membership script etc.). Supports WooCommerce.', 'softwarelicensemanager'); ?></p>
            <br>

            <table>
                <thead>
                <tr>
                    <td></td>
                </tr>
                </thead>
                <tr>
                    <td><?php _e('Authors', 'softwarelicensemanager'); ?></td>
                    <td><a href="https://github.com/michelve/software-license-manager">Michel Velis</a> and <a
                                href="https://github.com/Arsenal21/software-license-manager">tipsandtricks</a></td>
                </tr>
                <tr>
                    <td><?php _e('Help and Support', 'softwarelicensemanager'); ?></td>
                    <td><a href="https://github.com/michelve/software-license-manager/issues"><?php _e('Submmit a request', 'softwarelicensemanager'); ?></a></td>
                </tr>

                <tr>
                    <td><?php _e('API demos', 'softwarelicensemanager'); ?></td>
                    <td>Postman <a
                                href="https://documenter.getpostman.com/view/307939/6tjU1FL?version=latest"><?php _e('demos', 'softwarelicensemanager'); ?></a>
                    </td>
                </tr>
            </table>

        </div>
    </div>

    <?php
    echo '</div></div>';
    echo '</div>';
}
