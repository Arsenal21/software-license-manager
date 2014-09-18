<?php

function wp_lic_mgr_manage_licenses_menu()
{
	if(!wp_lic_mgr_is_license_valid())
	{		
		return;	//Do not display the page if licese key is invalid	
	}
		
    echo '<div class="wrap">';
    echo '<h2>Manage Licenses</h2>';
    echo '<div id="poststuff"><div id="post-body">';

    if (isset($_POST['limit_update']))
    {
        update_option('lic_mgr_manage_products_limit', (string)$_POST["lic_mgr_manage_products_limit"]);
    }
    $limit = get_option('lic_mgr_manage_products_limit');
    if(empty($limit))
    {
        update_option('lic_mgr_manage_products_limit', 50);
        $limit = 50;
    }

    if(isset($_POST['Delete']))
    {
        $cond = ' id = '.$_POST['lic_id'];
        $result = LicMgrDbAccess::delete(WP_LICENSE_MANAGER_LICENSE_TABLE_NAME,$cond);
        if($result)
        {
            $message = "License key successfully deleted";
        }
        else
        {
            $message = "An error occurded while trying to delete the entry";
        }
        echo '<div id="message" class="updated fade"><p><strong>';
        echo $message;
        echo '</strong></p></div>';
    }

    if (isset($_GET['entry_page']))
    {
        $page = $_GET['entry_page'];
    }
    else
    {
        $page = 1;
    }
    $start = ($page - 1) * $limit;

    if (isset($_POST['search_license']))
    {
        $search_term = (string)$_POST["lic_mgr_search_key"];
        $condition = "license_key like '%".$search_term."%' OR email like '%".$search_term."%'";
        $resultset = LicMgrDbAccess::findAll(WP_LICENSE_MANAGER_LICENSE_TABLE_NAME,$condition," id DESC ");
        $output = lic_mgr_display_lic_table($resultset);
    }
    else
    {
        //$wp_eStore_db = $wpdb->get_results("SELECT * FROM $products_table_name ORDER BY id DESC LIMIT $start, $limit", OBJECT);
        $orderby = " id DESC LIMIT ".$start.",". $limit;
        $resultset = LicMgrDbAccess::findAll(WP_LICENSE_MANAGER_LICENSE_TABLE_NAME,'',$orderby);
        $resultset2 = LicMgrDbAccess::findCount(WP_LICENSE_MANAGER_LICENSE_TABLE_NAME);
        $totalrows = $resultset2[0]->count;
        $output = lic_mgr_paginated_display($resultset,$limit,$totalrows);
    }

    ?>
    <br />
    <div class="postbox">
    <h3><label for="title">Search for a License</label></h3>
    <div class="inside">
    <br /><strong>Search for a License Key by entering the key or customer email address</strong>
    <br /><br />
    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">

    <input name="lic_mgr_search_key" type="text" size="30" value=""/>
    <div class="submit">
        <input type="submit" name="search_license" value="Search &raquo;" />
    </div>
    </form>
    </div></div>

    <?php echo $output; ?>

    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
    <input type="hidden" name="limit_update" id="limit_update" value="true" />
    <br />
    <strong>License Display Limit Per Page : </strong>
    <input name="lic_mgr_manage_products_limit" type="text" size="10" value="<?php echo get_option('lic_mgr_manage_products_limit'); ?>"/>

        <input type="submit" name="limit_update" value="Update &raquo;" />

    </form>
    <?php
    echo '<br /><a href="admin.php?page=wp_lic_mgr_addedit" class="button rbutton">Add License</a><br /><br />';
    echo '</div></div>';
    echo '</div>';
}

function lic_mgr_paginated_display($resultset,$limit,$totalrows)
{
    $output = lic_mgr_display_lic_table($resultset);

    //Number of pages setup
    $pages = ceil($totalrows / $limit)+1;
    if ($pages < 3)
    {
        return $output;
    }

    for($r = 1;$r<$pages;$r++)
    {
        $output .= "<a href='?page=wp-license-manager/wp_license_manager1.php&entry_page=".$r."' class=\"button rbutton\">".$r."</a>&nbsp;";
        if ($r%15==0)
           $output .= '<br /><br />';
    }
    return $output;
}

function lic_mgr_display_lic_table($resultset)
{
    $output = '
    <table class="widefat">
    <thead><tr>
    <th scope="col">ID</th>
    <th scope="col">License Key</th>
    <th scope="col">Maximum Domains allowed</th>
    <th scope="col">License Status</th>
    <th scope="col">Registered Email</th>
    <th scope="col"></th>
    </tr></thead>
    <tbody>';

    $i = 0;
    if ($resultset)
    {
        foreach ($resultset as $result)
        {
            if($i%2 == 0)
            {
                $output .= "<tr style='background-color: #fff;'>";
                $i++;
            }
            else
            {
                $output .= "<tr style='background-color: #E9EDF5;'>";
                $i++;
            }
            $output .= '<td>'.$result->id.'</td>';
            $output .= '<td><strong>'.$result->license_key.'</strong></td>';
            $output .= '<td><strong>'.$result->max_allowed_domains.'</strong></td>';
            $output .= '<td><strong>'.$result->lic_status.'</strong></td>';
            $output .= '<td><strong>'.$result->email.'</strong></td>';


            $output .= '<td style="text-align: center;"><a href="admin.php?page=wp_lic_mgr_addedit&edit_record='.$result->id.'">Edit</a>';

            $output .= "<form method=\"post\" action=\"\" onSubmit=\"return confirm('Are you sure you want to delete this entry?');\">";
            $output .= "<input type=\"hidden\" name=\"lic_id\" value=".$result->id." />";
            $output .= '<input style="border: none; background-color: transparent; padding: 0; cursor:pointer;" type="submit" name="Delete" value="Delete">';
            $output .= "</form>";
            $output .= "</td>";

            $output .= '</tr>';
        }
    }
    else
    {
        $output .= '<tr> <td colspan="6">No Licenses found</td> </tr>';
    }

    $output .= '</tbody></table>';
    return $output;
}
?>
