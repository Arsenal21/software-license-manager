<?php

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
    <div class="wrap">
        <h1>Overview - Manage licenses</h1>
        <br>
        <a href="admin.php?page=slm_manage_license" class="page-title-action aria-button-if-js" role="button" aria-expanded="false">Add New</a>
        <hr class="wp-header-end">


        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-1">
                <div id="post-body-content">
                    <div class="meta-box-sortables ui-sortable">
                        <form method="post">

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
