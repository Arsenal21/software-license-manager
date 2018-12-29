<?php

// add admin styles and scipts
function slm_admin_assets() {
   wp_enqueue_style('slm', SLM_ASSETS_URL .'css/slm.css');
   wp_enqueue_script('slm_validate', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js', array('jquery'), '1.19.0', true);
   wp_enqueue_script('slm_validate_js', SLM_ASSETS_URL .'js/slm.js', array('jquery'), '1.0.1', true );
}
add_action('admin_enqueue_scripts', 'slm_admin_assets');


// load frontend styles
function slm_frontend_assets() {
    // cdn
    if ( class_exists( 'WooCommerce' ) ) {
        wp_enqueue_style('bootstrapcdn-slm', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css');
        wp_enqueue_script( 'bootstrapcdn-slm-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js', array('jquery'), '4.1.3', true );
    }
    // custom css
    wp_enqueue_style('slm', SLM_ASSETS_URL .'css/slm-front-end.css');
}
add_action('wp_enqueue_scripts', 'slm_frontend_assets');



