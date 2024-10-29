<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

// Enqueue admin styles and scripts
function slm_admin_assets() {
    wp_enqueue_style('slmplus-admin', SLM_ASSETS_URL . 'css/slm.css');
    wp_enqueue_script('slm-validate', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.min.js', array('jquery'), '1.19.0', true);
    wp_enqueue_script('slm-admin-js', SLM_ASSETS_URL . 'js/slm.js', array('jquery'), '1.0.1', true);
}

// Enqueue frontend styles and scripts
function slm_frontend_assets() {
    if (is_plugin_active('woocommerce/woocommerce.php')) {
        if (is_account_page() && SLM_Helper_Class::slm_get_option('slm_front_conflictmode') == 1) {
            wp_enqueue_style('bootstrapcdn-slm', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css');
            wp_enqueue_script('bootstrapcdn-slm-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js', array('jquery'), '4.1.3', true);
        }
    }
    wp_enqueue_style('slmplus-frontend', SLM_ASSETS_URL . 'css/slm-front-end.css');
}

// Hook assets into the appropriate actions
add_action('wp_enqueue_scripts', 'slm_frontend_assets');
add_action('admin_enqueue_scripts', 'slm_admin_assets');

// If WooCommerce is active, add specific actions for handling scripts on certain pages
if (is_plugin_active('woocommerce/woocommerce.php')) {
    add_action('template_redirect', 'slm_handle_my_account_page');

    function slm_handle_my_account_page() {
        if (is_page('my-account')) {
            // If you need to enqueue additional scripts on the "my-account" page, uncomment the line below
            // wp_enqueue_script('slm-js', SLM_ASSETS_URL . 'js/slm-js.js', array('jquery'), '1.0.1', true);
        }
    }
}
