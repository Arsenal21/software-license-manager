<?php

function slm_stripe_checkout_register_order_type() {
    $labels = array(
        'name' => __('Orders', 'wp-stripe-checkout'),
        'singular_name' => __('Order', 'wp-stripe-checkout'),
        'menu_name' => __('Stripe Checkout', 'wp-stripe-checkout'),
        'name_admin_bar' => __('Order', 'wp-stripe-checkout'),
        'add_new' => __('Add New', 'wp-stripe-checkout'),
        'add_new_item' => __('Add New Order', 'wp-stripe-checkout'),
        'new_item' => __('New Order', 'wp-stripe-checkout'),
        'edit_item' => __('Edit Order', 'wp-stripe-checkout'),
        'view_item' => __('View Order', 'wp-stripe-checkout'),
        'all_items' => __('All Orders', 'wp-stripe-checkout'),
        'search_items' => __('Search Orders', 'wp-stripe-checkout'),
        'parent_item_colon' => __('Parent Orders:', 'wp-stripe-checkout'),
        'not_found' => __('No Orders found.', 'wp-stripe-checkout'),
        'not_found_in_trash' => __('No orders found in Trash.', 'wp-stripe-checkout')
    );

    $capability = 'manage_options';
    $capabilities = array(
        'edit_post' => $capability,
        'read_post' => $capability,
        'delete_post' => $capability,
        'create_posts' => $capability,
        'edit_posts' => $capability,
        'edit_others_posts' => $capability,
        'publish_posts' => $capability,
        'read_private_posts' => $capability,
        'read' => $capability,
        'delete_posts' => $capability,
        'delete_private_posts' => $capability,
        'delete_published_posts' => $capability,
        'delete_others_posts' => $capability,
        'edit_private_posts' => $capability,
        'edit_published_posts' => $capability
    );

    $args = array(
        'labels' => $labels,
        'public' => false,
        'exclude_from_search' => true,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_nav_menus' => false,
        'show_in_menu' => current_user_can('manage_options') ? true : false,
        'query_var' => false,
        'rewrite' => false,
        'capabilities' => $capabilities,
        'has_archive' => false,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('editor')
    );

    register_post_type('slmstripeco_order', $args);
}

function slm_stripe_checkout_order_columns($columns) {
    unset($columns['title']);
    unset($columns['date']);
    $edited_columns = array(
        'title' => __('Order', 'wp-stripe-checkout'),
        'txn_id' => __('Transaction ID', 'wp-stripe-checkout'),
        'name' => __('Name', 'wp-stripe-checkout'),
        'email' => __('Email', 'wp-stripe-checkout'),
        'amount' => __('Total', 'wp-stripe-checkout'),
        'date' => __('Date', 'wp-stripe-checkout')
    );
    return array_merge($columns, $edited_columns);
}

function slm_stripe_checkout_custom_column($column, $post_id) {
    switch ($column) {
        case 'title' :
            echo $post_id;
            break;
        case 'txn_id' :
            echo get_post_meta($post_id, '_txn_id', true);
            break;
        case 'name' :
            echo get_post_meta($post_id, '_name', true);
            break;
        case 'email' :
            echo get_post_meta($post_id, '_email', true);
            break;
        case 'amount' :
            echo get_post_meta($post_id, '_amount', true);
            break;
    }
}

function slm_stripe_checkout_save_meta_box_data($post_id) {
    /*
     * We need to verify this came from our screen and with proper authorization,
     * because the save_post action can be triggered at other times.
     */
    // Check if our nonce is set.
    if (!isset($_POST['slmstripecheckout_meta_box_nonce'])) {
        return;
    }
    // Verify that the nonce is valid.
    if (!wp_verify_nonce($_POST['slmstripecheckout_meta_box_nonce'], 'slmstripecheckout_meta_box')) {
        return;
    }
    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    // Check the user's permissions.
    if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }
}

add_action('save_post', 'slm_stripe_checkout_save_meta_box_data');