<?php
/**
 * 
 * Handles Page Creation
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create License Cart page on plugin activation if it doesn't exist.
 */
function slm_create_license_cart_page() {
    // Check if the License Cart page already exists
    $query = new WP_Query(array(
        'post_type'      => 'page',
        'name'           => 'license-cart', // Check for the slug
        'post_status'    => 'publish',
        'posts_per_page' => 1,
    ));

    if (!$query->have_posts()) {
        // Create the License Cart page
        $page_id = wp_insert_post(array(
            'post_title'     => 'License Cart',
            'post_content'   => '', // Empty content for now
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'post_name'      => 'license-cart', // Set the slug
            'meta_input'     => array('_wp_page_template' => 'page-license-cart.php'), // Assign the custom template
        ));

        if ($page_id && !is_wp_error($page_id)) {
            // Optionally, hide the page from menus/navigation
            update_post_meta($page_id, '_menu_item_visibility', 'hidden');
            error_log(__('License Cart page created successfully with ID: ', 'slm-plus') . $page_id);
        } else {
            error_log(__('Failed to create License Cart page.', 'slm-plus'));
        }
    } else {
        error_log(__('License Cart page already exists.', 'slm-plus'));
    }
}

/**
 * Hook into plugin activation to create the License Cart page.
 */
register_activation_hook(__FILE__, 'slm_create_license_cart_page');
