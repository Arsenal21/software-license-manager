<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

// Register block editor assets.
add_action('enqueue_block_editor_assets', function () {
    wp_enqueue_script(
        'slm-blocks-js',
        SLM_ASSETS_URL . 'js/slm-blocks.js',
        ['wp-blocks', 'wp-editor', 'wp-element', 'wp-i18n'], // Dependencies.
        SLM_VERSION,
        true
    );

    wp_enqueue_style(
        'slm-blocks-style',
        SLM_ASSETS_URL . 'css/slm-blocks.css',
        [],
        SLM_VERSION
    );
});

