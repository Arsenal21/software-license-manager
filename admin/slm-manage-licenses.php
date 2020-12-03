<?php

if (!defined('WPINC')) {
    die;
}

//add_action('plugins_loaded', 'get_user_info');
// TODO

function get_user_info()
{
    if (!current_user_can('manage_licenses')) {
        $response = array(
            'success' => false,
            'message' => _e('You do not have permission to manage this license.', 'softwarelicensemanager'),
        );
        echo json_encode($response);
        die();
    }
}

function slm_manage_licenses_menu()
{
    //include_once('slm-list-licenses-class.php');
    $license_list = new SLM_List_Licenses();

    if (isset($_REQUEST['action'])) { //Do list table form row action tasks
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete_license') { //Delete link was clicked for a row in list table
            $license_list->delete_license_key(sanitize_text_field($_REQUEST['id']));
        }
    }
?>
    <div class="stats">
    </div>
    <div class="wrap">
        <h1><?php _e('Overview - Manage licenses', 'softwarelicensemanager'); ?></h1>
        <br>
        <a href="<?php echo admin_url('admin.php?page=slm_manage_license') ?>" class="page-title-action aria-button-if-js" role="button" aria-expanded="false"><?php _e('Add New', 'softwarelicensemanager'); ?></a>

        <hr class="wp-header-end">

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-1">
                <div id="post-body-content">

                    <div class="overview">
                        <?php

                        $options    = get_option('slm_plugin_options');
                        $slm_stats = $options['slm_stats'];

                        if ($slm_stats == 1 && !empty($slm_stats)) {
                            include SLM_ADMIN_ADDONS . 'partials/stats.php';
                        }
                        ?>
                    </div>
                    <div class="meta-box-sortables ui-sortable">
                        <form id="licenses-filter" method="get">
                            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                            <?php
                            $license_list->prepare_items();
                            $license_list->search_box(__('Search'), 'search-box-id');
                            $license_list->views();
                            $license_list->display(); ?>
                        </form>
                    </div>
                </div>
            </div>
            <br class="clear">
            <div id="post-body" class="metabox-holder columns-1">

            </div>
        </div>
    </div>
<?php
}