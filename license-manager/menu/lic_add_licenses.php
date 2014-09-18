<?php

function wp_lic_mgr_add_licenses_menu()
{
	if(!wp_lic_mgr_is_license_valid())
	{		
		return;	//Do not display the page if licese key is invalid	
	}
	
    echo '<div class="wrap">';
    echo '<h2>Add/Edit Licenses</h2>';
    echo '<div id="poststuff"><div id="post-body">';

    //If product is being edited, grab current product info
    if ($_GET['edit_record']!='')
    {
        $id = $_GET['edit_record'];
        $editing_record = LicMgrDbAccess::find(WP_LICENSE_MANAGER_LICENSE_TABLE_NAME," id = ".$id);
    }
    if (isset($_POST['save_record']))
    {
    	global $wpdb;
        //Save the entry to the database
        $fields = array();
        $fields['license_key'] = $_POST['license_key'];
        $fields['max_allowed_domains'] = $_POST['max_allowed_domains'];
        $fields['lic_status'] = $_POST['lic_status'];
        $fields['first_name'] = $_POST['first_name'];
        $fields['last_name'] = $_POST['last_name'];
        $fields['email'] = $_POST['email'];
        $fields['company_name'] = $_POST['company_name'];
        $fields['txn_id'] = $_POST['txn_id'];
        $fields['manual_reset_count'] = $_POST['manual_reset_count'];

        $id = $_POST['edit_record'];
        if(empty($id))//Insert into database
        {
            $updated = LicMgrDbAccess::insert(WP_LICENSE_MANAGER_LICENSE_TABLE_NAME, $fields);            
            //Retrieve the added record
            $id = mysql_insert_id();
            $cond = " id = ".$id;
            $editing_record = LicMgrDbAccess::find(WP_LICENSE_MANAGER_LICENSE_TABLE_NAME,$cond);
        }
        else //Update recored
        {
            $cond = " id = ". $id;
            $updated = LicMgrDbAccess::update(WP_LICENSE_MANAGER_LICENSE_TABLE_NAME, $cond, $fields);
            //Retrieve the updated record
            $editing_record = LicMgrDbAccess::find(WP_LICENSE_MANAGER_LICENSE_TABLE_NAME,$cond);
        }

        $message = "Record successfully saved!";
        echo '<div id="message" class="updated fade"><p>';
        echo $message;
        echo '</p></div>';

    }

    lic_mgr_add_lic_view($editing_record,$id);

    echo '<a href="admin.php?page=wp-license-manager/wp_license_manager1.php" class="button rbutton">Manage Licenses</a><br /><br />';
    echo '</div></div>';
    echo '</div>';
}

function lic_mgr_add_lic_view($editing_record,$id='')
{
    ?>
    <style type="text/css">
       .del{
          cursor: pointer;
          color:red;	
	   }
    </style>
    You can add a new license or edit an existing one from this interface.
    <br /><br />

    <div class="postbox">
    <h3><label for="title">License Details </label></h3>
    <div class="inside">

    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
    <table class="form-table">

    <?php
    if ($id!='')
    {
        echo '<input name="edit_record" type="hidden" value="'.$id.'" />';
    }
    else
    {
        //Auto generate unique key
        $lic_key_prefix = get_option('wp_lic_mgr_key_prefix');
        $editing_record->license_key = uniqid($lic_key_prefix);//uniqid('', true);
    }
    ?>

    <tr valign="top">
    <th scope="row">License Key</th>
    <td><input name="license_key" type="text" id="license_key" value="<?php echo $editing_record->license_key; ?>" size="30" />
    <br/>The unique license key. When adding a new record it automatically generates a unique key in this field for you. You can change this value to customize the key if you like.</td>
    </tr>

    <tr valign="top">
    <th scope="row">Maximum Allowed Domains</th>
    <td><input name="max_allowed_domains" type="text" id="max_allowed_domains" value="<?php echo $editing_record->max_allowed_domains; ?>" size="5" /><br/>Number of domains in which this license can be used.</td>
    </tr>
   
    <tr valign="top">
    <th scope="row">License Status</th>
    <td>
    <select name="lic_status">    
    	<option value="active" <?php if($editing_record->lic_status=='active') echo 'selected="selected"'; ?> >Active</option>	
    	<option value="blocked" <?php if($editing_record->lic_status=='blocked') echo 'selected="selected"'; ?> >Blocked</option>
    	<option value="expired" <?php if($editing_record->lic_status=='expired') echo 'selected="selected"'; ?> >Expired</option>
    </select>
    </td></tr>
    
    <?php if($id!=''){
    	global $wpdb;
    	$reg_table = WP_LICENSE_MANAGER_REG_DOMAIN_TABLE_NAME;
    	$reg_domains = $wpdb->get_results(" SELECT * FROM $reg_table WHERE lic_key_id= '$id'", OBJECT );    
    ?>
    <tr valign="top">
    <th scope="row">Registered Domains</th>
    <td><?php
    if(count($reg_domains )>0){ 
    ?>
    <div style="background: red;width: 100px;color:white; font-weight: bold;padding-left: 10px;" id="reg_del_msg"></div>
    <div style="overflow:auto; height:157px;width:250px;border:1px solid #ccc;">
    	<table cellpadding="0" cellspacing="0">
    		<?php 
    		$count = 0;    
    		foreach($reg_domains as $reg_domain){?>
    		<tr <?php echo ($count%2)? 'class="alternate"':'';?>>
    			<td height="5"><?php echo $reg_domain->registered_domain;?></td> 
    			<td height="5"><span class="del" id=<?php echo $reg_domain->id ?>>X</span></td>
    		</tr>
    		<?php 
    		$count++;}    		
    		?>
    	</table>         
    	</div>
    	<?php 
        }else{
        	echo "Not Registered Yet.";
        }    	
    	?>
    </td>
    </tr>
    <?php }?>
        
    <tr valign="top">
    <th scope="row">First Name</th>
    <td><input name="first_name" type="text" id="first_name" value="<?php echo $editing_record->first_name; ?>" size="20" /><br/>License user's first name</td>
    </tr>

    <tr valign="top">
    <th scope="row">Last Name</th>
    <td><input name="last_name" type="text" id="last_name" value="<?php echo $editing_record->last_name; ?>" size="20" /><br/>License user's last name</td>
    </tr>

    <tr valign="top">
    <th scope="row">Email Address</th>
    <td><input name="email" type="text" id="email" value="<?php echo $editing_record->email; ?>" size="30" /><br/>License user's email address</td>
    </tr>

    <tr valign="top">
    <th scope="row">Company Name</th>
    <td><input name="company_name" type="text" id="company_name" value="<?php echo $editing_record->company_name; ?>" size="30" /><br/>License user's company name</td>
    </tr>

    <tr valign="top">
    <th scope="row">Unique Transaction ID</th>
    <td><input name="txn_id" type="text" id="txn_id" value="<?php echo $editing_record->txn_id; ?>" size="30" /><br/>The unique transaction ID associated with this license key</td>
    </tr>

    <tr valign="top">
    <th scope="row">Manual Reset Count</th>
    <td><input name="manual_reset_count" type="text" id="manual_reset_count" value="<?php echo $editing_record->manual_reset_count; ?>" size="6" />
    <br/>The number of times this license has been manually reset by the admin (use it if you want to keep track of it). It can be helpful for the admin to keep track of manual reset counts.</td>
    </tr>

    <tr valign="top">
    <th scope="row">Date Created</th>
    <td><input name="date_created" type="text" id="date_created" value="<?php echo $editing_record->date_created; ?>" size="6" />
    <br/>Creation date of license.</td>
    </tr>

    <tr valign="top">
    <th scope="row">Date Renewed</th>
    <td><input name="date_renewed" type="text" id="date_renewed" value="<?php echo $editing_record->date_renewed; ?>" size="6" />
    <br/>Renewal date of license.</td>
    </tr>

    <tr valign="top">
    <th scope="row">Date of Expiry</th>
    <td><input name="date_expiry" type="text" id="date_expiry" value="<?php echo $editing_record->date_expiry; ?>" size="6" />
    <br/>Expiry date of license.</td>
    </tr>

    </table>

    <div class="submit">
        <input type="submit" name="save_record" value="Save &raquo;" />
    </div>
    </form>
    </div></div>
    <script type="text/javascript">
    jQuery(document).ready(function(){
       jQuery('.del').click(function(){
           var $this = this;
           jQuery('#reg_del_msg').html('Loading ...');
    	   jQuery.get('<?php echo get_bloginfo('wpurl')?>'+'/wp-admin/admin-ajax.php?action=del_dom&id='+jQuery(this).attr('id'), function(data) {    		  
        	   if(data=='success'){
    		   		jQuery('#reg_del_msg').html('Deleted');    		   
               		jQuery($this).parent().parent().remove();
        	   }
        	   else{
        		   jQuery('#reg_del_msg').html('Failed');
        	   }
    		 });           
       }); 
    });
    </script>
    <?php
}
?>
