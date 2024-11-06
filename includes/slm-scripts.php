<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

// Enqueue admin styles and scripts
function slm_admin_assets() {
    wp_enqueue_style('slmplus-admin', SLM_ASSETS_URL . 'css/slm.css', array(), time(), 'all');
    wp_enqueue_script('slm-admin-js', SLM_ASSETS_URL . 'js/slm.js', array('jquery'), time(), true);
}
add_action('admin_enqueue_scripts', 'slm_admin_assets');