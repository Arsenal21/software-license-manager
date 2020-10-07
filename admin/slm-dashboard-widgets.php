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
        'Software license manager',     // Title.
        'slm_dashboard_widget_function' // Display function.
    );
}

function add_toolbar_items($admin_bar){
    $admin_bar->add_menu(array(
        'id'    => 'slm-menu',
        'title' => '<span class="ab-icon"></span>' . __('SLM', 'softwarelicensemanager'),
        'href'  => admin_url('admin.php?page=slm_overview'),
        'meta'  => array(
            'title' => __('SLM'),
        ),
    ));
    $admin_bar->add_menu(array(
        'id'    => 'slm-manage-licenses-overview',
        'parent' => 'slm-menu',
        'title' => 'Overview',
        'href'  => admin_url('admin.php?page=slm_overview'),
        'meta'  => array(
            'title' => __('Overview'),
            'class' => 'slm_overview_menu'
        ),
    ));
    $admin_bar->add_menu(array(
        'id'    => 'slm-manage-licenses-addnew',
        'parent' => 'slm-menu',
        'title' => 'Add new license',
        'href'  => admin_url('admin.php?page=slm_manage_license'),
        'meta'  => array(
            'title' => __( 'Add new license'),
            'class' => 'slm_addlicense_menu'
        ),
    ));
    $admin_bar->add_menu(array(
        'id'    => 'slm-manage-licenses-settings',
        'parent' => 'slm-menu',
        'title' => 'Settings',
        'href'  => admin_url( 'admin.php?page=slm_settings'),
        'meta'  => array(
            'title' => __('Settings'),
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
            <a href="<?php echo admin_url('admin.php?page=slm_overview'); ?>">
                <div class="icon"> <span class="dashicons dashicons-admin-network"></span> </div>
                <strong>Manage licenses</strong> Total active licenses <span class="badge"> <?php echo SLM_Utility::get_total_licenses(); ?> </span>
            </a> </li>
        <li class="active-licenses">
            <a href="<?php echo admin_url('admin.php?page=slm_overview&s=active&view=active'); ?>">
                <div class="icon"><span class="dashicons dashicons-yes-alt"></span></div>
                <strong> <?php echo SLM_Utility::count_licenses('active'); ?> </strong> Active licenses
            </a>
        </li>
        <li class="pending-licenses">
            <a href="<?php echo admin_url('admin.php?page=slm_overview&s=pending&view=pending'); ?>">
                <div class="icon"> <span class="dashicons dashicons-warning"></span> </div>
                <strong><?php echo SLM_Utility::count_licenses('pending '); ?></strong> Pending licenses
            </a>
        </li>


        <li class=" blocked-licenses">
            <a href="<?php echo admin_url('admin.php?page=slm_overview&s=blocked&view=blocked'); ?>">
                <div class="icon"> <span class="dashicons dashicons-dismiss"></span> </div>
                <strong><?php echo SLM_Utility::count_licenses('blocked'); ?></strong> Blocked licenses
            </a>
        </li>

        <li class="expired-licenses">
            <a href="<?php echo admin_url('admin.php?page=slm_overview&s=expired&view=expired'); ?>">
                <div class="icon"> <span class="dashicons dashicons-calendar-alt"></span> </div>
                <strong><?php echo SLM_Utility::count_licenses('expired'); ?></strong> Expired licenses
            </a>
        </li>
    </ul>

    <div class="table recent_licenses">
        <hr>
        <table>
            <thead>
                <tr>
                    <th scope="col">
                        Recent Licenses <a href="<?php echo admin_url('admin.php?page=slm_overview'); ?>">&nbsp;–&nbsp;View All</a>
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
?>