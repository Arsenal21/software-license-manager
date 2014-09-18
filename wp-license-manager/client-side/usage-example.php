<?php
/*** 
 * Simply add the "client-side" package/folder to your product then do the 
 * following 4 todo items in your product to complete the integration
***/

//TODO - 1.Open the "client-side/client-custom-configs.php" file and fill the appropriate information (check the comments in that file for help) 

//TODO - 2.Add the following line of code to your main plugin file to load the license manager client configs and functions when the plugin loads 
include_once('client-side/client-custom-configs.php');

//TODO - 3.Call the following function from your product license menu/tab that you created in your product which gives the user ability to manage his license key 
wp_lic_mgr_client_applications_product_license_menu();

//TODO - 4.Call the following function before you show the other menues of your product (it returns true if the license is active on this instlla otherwise it returns false) 
//wp_lic_mgr_client_is_license_valid()




/***** Some example code with comments that might come in handy *****
// 1. Example Code To Verify a license from your remote license manager and activate it
//First include the 'client-custom-configs.php' file wich resides in the "client-side" folder (you can add this whole folder to your plugin or theme)
include_once('client-custom-configs.php');

//When a user enters the license key use the following example to verify and activate the license
$returnData = wp_lic_mgr_client_verify_and_activate_license($lic_key);
if($returnData == 'Success')
{
	//All good! Tell the user that the license checks out and it has been activated
}
else
{
	//License key activation failed! More details in the following variable (you can show the error message to the user)
	echo "<br />Error: ".$returnData;
}

// 2. Example Code To Deactivate a license in your remote license manager
//When a user decides to deactivate the license key use the following example to remotely deactivate the license and save the key in the local insall
$returnData = wp_lic_mgr_client_deactivate_license_and_update_key($lic_key);
if($returnData == 'Success')
{
	//All good! Tell the user that the license key deactivated and can be used in a different site
}
else
{
	//License key deactivation failed! More details in the following variable (you can show the error message to the user)
	echo "<br />Error: ".$returnData;
}
***** END of example code *****/
?>