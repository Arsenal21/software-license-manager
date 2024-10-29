<?php

// Prevent direct access to the file
if (!defined('WPINC')) {
    die;
}

/**
 * Display the About menu for SLM
 */
function slm_about_menu()
{
    // Output the wrapper div and heading
    echo '<div class="wrap">';
    echo '<h2 class="imgh2"><img src="' . esc_url(SLM_ASSETS_URL . 'images/slm_logo.svg') . '" alt="' . esc_attr__('SLM Logo', 'slmplus') . '">' . esc_html__('SLM - About', 'slmplus') . '</h2>';
    echo '<div id="poststuff"><div id="post-body">';

    // Retrieve plugin options with caching for performance
    $slm_options = get_option('slm_plugin_options', []);

    // Display content inside a postbox
    ?>
    <br/>

    <div class="postbox">
        <h3 class="hndle"><label for="title"><?php esc_html_e('Credits and authors', 'slmplus'); ?></label></h3>
        <div class="inside">
            <p><?php esc_html_e('Software license management solution for your web applications (WordPress plugins, Themes, Applications, PHP based membership script, etc.). Supports WooCommerce.', 'slmplus'); ?></p>

            <table class="slm-about-table">
                <thead>
                <tr>
                    <th><?php esc_html_e('Information', 'slmplus'); ?></th>
                    <th><?php esc_html_e('Details', 'slmplus'); ?></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><?php esc_html_e('Authors', 'slmplus'); ?></td>
                    <td>
                        <a href="https://github.com/michelve/software-license-manager" target="_blank" rel="noopener noreferrer">Michel Velis</a> 
                        <?php esc_html_e('and', 'slmplus'); ?>
                        <a href="https://github.com/Arsenal21/software-license-manager" target="_blank" rel="noopener noreferrer">tipsandtricks</a>
                    </td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Help and Support', 'slmplus'); ?></td>
                    <td>
                        <a href="https://github.com/michelve/software-license-manager/issues" target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e('Submit a request', 'slmplus'); ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <td><?php esc_html_e('API Demos', 'slmplus'); ?></td>
                    <td>
                        <a href="https://documenter.getpostman.com/view/307939/6tjU1FL?version=latest" target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e('Postman Demos', 'slmplus'); ?>
                        </a>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php

    // Close wrapping divs
    echo '</div></div>';
    echo '</div>';
}
