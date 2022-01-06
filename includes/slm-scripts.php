<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

// add admin styles and scipts
function slm_admin_assets()
{
    wp_enqueue_style('softwarelicensemanager', SLM_ASSETS_URL . 'css/slm.css');
    wp_enqueue_script('slm_validate', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js', array('jquery'), '1.19.0', true);
    wp_enqueue_script('slm_validate_js', SLM_ASSETS_URL . 'js/slm.js', array('jquery'), '1.0.1', true);
}

// load frontend styles
function slm_js_license(){
    //wp_enqueue_script('slm_js', SLM_ASSETS_URL . 'js/slm-js.js', array('jquery'), '1.0.1', true);
}

function slm_frontend_assets(){
    /**
     * Check if WooCommerce is activated
     */
    if (is_plugin_active('woocommerce/woocommerce.php')) {

        if (is_account_page() && SLM_Helper_Class::slm_get_option('slm_front_conflictmode') == 1) {
            wp_enqueue_style('bootstrapcdn-slm', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css');
            wp_enqueue_script('bootstrapcdn-slm-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js', array('jquery'), '4.1.3', true);
        }
    }
    // custom css
    wp_enqueue_style('softwarelicensemanager', SLM_ASSETS_URL . 'css/slm-front-end.css');
}

add_action('wp_enqueue_scripts', 'slm_frontend_assets');
add_action('admin_enqueue_scripts', 'slm_admin_assets');


/**
 * Check if WooCommerce is activated
 */
if (is_plugin_active('woocommerce/woocommerce.php')) {
    add_action('template_redirect', 'slm_get_page');

    function slm_get_page()
    {
        if (is_page('my-account')) {
            //add_action('wp_enqueue_scripts', 'slm_js_license');
        }
    }
}
