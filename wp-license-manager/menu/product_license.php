<?php
function wp_lic_mgr_product_license_menu()
{
    echo '<div class="wrap">';
    echo '<h2>Product License</h2>';
    echo '<div id="poststuff"><div id="post-body">';

    $message = "";
	if(isset($_POST['activate_license']))
	{
		$retData = wp_lic_mgr_lic_verify($_POST['lic_key']);
	    if ($retData['result'] == 'Success')
	    {
	    	// something else is to be done to store the license key.
	    	update_option('wp_lic_mgr_lic_key',$_POST['lic_key']);
	    	$message .= "License key is valid! Product activated.";
	    }
	    else
	    {
	    	$message .= "License key is invalid!";
	    	$message .= "<br />".$retData['msg'];
	    }
	}   
	if(isset($_POST['deactivate_license']))
	{
		$retData = wp_lic_mgr_deactivate_lic($_POST['lic_key']);
	    if ($retData['result'] == 'Success')
	    {
	    	// Reset the license key
	    	update_option('wp_lic_mgr_lic_key','');
	    	$message .= "License key deactivated!";
	    }
	    else
	    {
	    	$message .= "License key deactivation failed!";
	    	$message .= "<br />".$retData['msg'];
	    }
	} 
	if(!empty($message))
	{
	    echo '<div id="message" class="updated fade"><p><strong>';
	    echo $message;
	    echo '</strong></p></div>';	
	}
        	
    ?>
    <div class="postbox">
    <h3><label for="title">License Details </label></h3>
    <div class="inside">
        
    <p><strong>Please enter the license key for this product to activate it</strong> 
    <form action="" method="post">
	<table class="form-table">
	    <tr>
	    	<th style="width:100px;"><label for="lic_key">License Key</label></th>
	        <td ><input class="regular-text" type="text" id="lic_key" name="lic_key"  value="<?php echo get_option('wp_lic_mgr_lic_key'); ?>" ></td>
	    </tr>
	</table>
	<p class="submit">
		<input type="submit" name="activate_license" value="Activate" class="button-primary" />
		<input type="submit" name="deactivate_license" value="Deactivate" />
	</p>
	</form>	
	</p>
	</div></div>	
	<?php
    
    echo '</div></div>';
    echo '</div>';
}
?>