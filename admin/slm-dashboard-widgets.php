<?php

if (!defined('WPINC')) {
    die;
}

add_action('wp_dashboard_setup', 'slm_add_dashboard_widgets');

if (null !== SLM_Helper_Class::slm_get_option('slm_adminbar') && SLM_Helper_Class::slm_get_option('slm_adminbar') == 1) {
    add_action('admin_bar_menu', 'add_toolbar_items', 100);
}
/**
 * Add a widget to the dashboard.
 *
 * This function is hooked into the 'wp_dashboard_setup' action below.
 */
function slm_add_dashboard_widgets()
{

    wp_add_dashboard_widget(
        'slm_dashboard_widget',         // Widget slug.
        'SLM Plus',     // Title.
        'slm_dashboard_widget_function' // Display function.
    );
}

function add_toolbar_items($admin_bar){
    $admin_bar->add_menu(array(
        'id'    => 'slm-menu',
        'title' => '<span class="ab-icon"></span>' . __('SLM Plus', 'slm-plus'),  // Added text domain
        'href'  => admin_url('admin.php?page=slm_overview'),
        'meta'  => array(
            'title' => __('slm-plus', 'slm-plus'),  // Added text domain
        ),
    ));
    $admin_bar->add_menu(array(
        'id'    => 'slm-manage-licenses-overview',
        'parent' => 'slm-menu',
        'title' => __('Overview', 'slm-plus'),  // Added text domain
        'href'  => admin_url('admin.php?page=slm_overview'),
        'meta'  => array(
            'title' => __('Overview', 'slm-plus'),  // Added text domain
            'class' => 'slm_overview_menu'
        ),
    ));
    $admin_bar->add_menu(array(
        'id'    => 'slm-manage-licenses-addnew',
        'parent' => 'slm-menu',
        'title' => __('Add new license', 'slm-plus'),  // Added text domain
        'href'  => admin_url('admin.php?page=slm_manage_license'),
        'meta'  => array(
            'title' => __('Add new license', 'slm-plus'),  // Added text domain
            'class' => 'slm_addlicense_menu'
        ),
    ));
    $admin_bar->add_menu(array(
        'id'    => 'slm-manage-licenses-settings',
        'parent' => 'slm-menu',
        'title' => __('Settings', 'slm-plus'),  // Added text domain
        'href'  => admin_url( 'admin.php?page=slm_settings'),
        'meta'  => array(
            'title' => __('Settings', 'slm-plus'),  // Added text domain
            'class' => 'slm_settings_menu'
        ),
    ));
}


/**
 * Create the function to output the contents of our Dashboard Widget.
 */
function slm_dashboard_widget_function()
{ ?>

<ul class="slm_status_list">
    <li class="total-licenses">
        <a href="<?php echo esc_url(admin_url('admin.php?page=slm_overview')); ?>">
            <div class="icon"> <span class="dashicons dashicons-admin-network"></span> </div>
            <strong><?php esc_html_e('Manage licenses', 'slm-plus'); ?></strong> 
            <?php esc_html_e('Total active licenses', 'slm-plus'); ?> 
            <span class="badge"><?php echo esc_html(SLM_Utility::get_total_licenses()); ?></span>
        </a> 
    </li>
    <li class="active-licenses">
        <a href="<?php echo esc_url(admin_url('admin.php?page=slm_overview&s=active&view=active')); ?>">
            <div class="icon"><span class="dashicons dashicons-yes-alt"></span></div>
            <strong><?php echo esc_html(SLM_Utility::count_licenses('active')); ?></strong> 
            <?php esc_html_e('Active licenses', 'slm-plus'); ?>
        </a>
    </li>
    <li class="pending-licenses">
        <a href="<?php echo esc_url(admin_url('admin.php?page=slm_overview&s=pending&view=pending')); ?>">
            <div class="icon"> <span class="dashicons dashicons-warning"></span> </div>
            <strong><?php echo esc_html(SLM_Utility::count_licenses('pending')); ?></strong> 
            <?php esc_html_e('Pending licenses', 'slm-plus'); ?>
        </a>
    </li>
    <li class="blocked-licenses">
        <a href="<?php echo esc_url(admin_url('admin.php?page=slm_overview&s=blocked&view=blocked')); ?>">
            <div class="icon"> <span class="dashicons dashicons-dismiss"></span> </div>
            <strong><?php echo esc_html(SLM_Utility::count_licenses('blocked')); ?></strong> 
            <?php esc_html_e('Blocked licenses', 'slm-plus'); ?>
        </a>
    </li>
    <li class="expired-licenses">
        <a href="<?php echo esc_url(admin_url('admin.php?page=slm_overview&s=expired&view=expired')); ?>">
            <div class="icon"> <span class="dashicons dashicons-calendar-alt"></span> </div>
            <strong><?php echo esc_html(SLM_Utility::count_licenses('expired')); ?></strong> 
            <?php esc_html_e('Expired licenses', 'slm-plus'); ?>
        </a>
    </li>
</ul>

<div class="table recent_licenses">
    <hr>
    <table>
        <thead>
            <tr>
                <th scope="col">
                    <?php esc_html_e('Recent Licenses', 'slm-plus'); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=slm_overview')); ?>">&nbsp;–&nbsp;<?php esc_html_e('View All', 'slm-plus'); ?></a>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php SLM_Utility::slm_wp_dashboards_stats('5'); ?>
        </tbody>
    </table>
</div>


<?php
}

