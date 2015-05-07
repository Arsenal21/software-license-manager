<?php

function wp_lic_mgr_manage_licenses_menu() {
    echo '<div class="wrap">';
    echo '<h2>Manage Licenses</h2>';
    echo '<div id="poststuff"><div id="post-body">';
    ?>

    <div class="postbox">
        <h3><label for="title">License Search</label></h3>
        <div class="inside">
            Search for a license by using email, name, key or transaction ID
            <br /><br />
            <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                <input name="slm_search" type="text" size="40" value=""/>
                <input type="submit" name="slm_search_btn" class="button" value="Search" />
            </form>
        </div></div>


    <div class="postbox">
        <h3><label for="title">Software Licenses</label></h3>
        <div class="inside">
            <?php
            include_once( 'wp-lic-mgr-list-licenses.php' ); //For rendering the license List Table
            $license_list = new WPLM_List_Licenses();
            if (isset($_REQUEST['action'])) { //Do list table form row action tasks
                if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete_license') { //Delete link was clicked for a row in list table
                    $license_list->delete_licenses(strip_tags($_REQUEST['id']));
                }
            }
            //Fetch, prepare, sort, and filter our data...
            $license_list->prepare_items();
            //echo "put table of locked entries here"; 
            ?>
            <form id="tables-filter" method="get" onSubmit="return confirm('Are you sure you want to perform this bulk operation on the selected entries?');">
                <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
                <!-- Now we can render the completed list table -->
                <?php $license_list->display(); ?>
            </form>

        </div></div>

    <?php
    echo '</div></div>';
    echo '</div>';
}

