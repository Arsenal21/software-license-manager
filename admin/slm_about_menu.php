<?php

if (!defined('WPINC')) {
    die;
}


function slm_about_menu()
{

    echo '<div class="wrap">';
    echo '<h2 class="imgh2"><img src="' . SLM_ASSETS_URL . 'images/slm_logo.svg" alt="slm logo"> SLM - About</h2>';
    echo '<div id="poststuff"><div id="post-body">';

    $slm_options = get_option('slm_plugin_options');

    ?>
    <br />

     <div class="postbox">
        <h3 class="hndle"><label for="title"><?php _e('Credits and authors', 'softwarelicensemanager'); ?></label></h3>
        <div class="inside">
            <br>
            <p>Software license management solution for your web applications (WordPress plugins, Themes, Applications, PHP based membership script etc.). Supports WooCommerce.</p> <br>

            <table>
                <thead>
                    <tr>
                        <td></td>
                    </tr>
                </thead>
                <tr>
                    <td>Authors</td>
                    <td><a href="https://github.com/michelve/software-license-manager">Michel Velis</a> and <a href="https://github.com/Arsenal21/software-license-manager">tipsandtricks</a> </td>
                </tr>
                <tr>
                    <td>Help and Support</td>
                    <td><a href="https://github.com/michelve/software-license-manager/issues">Submmit a request</a></td>
                </tr>

                <tr>
                    <td>API demos</td>
                    <td>Postman <a href="https://documenter.getpostman.com/view/307939/6tjU1FL?version=latest">demos</a> </td>
                </tr>
            </table>


        </div>
    </div>

    <?php
    echo '</div></div>';
    echo '</div>';
}
