<?php
include_once('lic_db_access.php');

define('WP_LICENSE_MANAGER_FOLDER', dirname(plugin_basename(__FILE__)));
define('WP_LICENSE_MANAGER_URL', plugins_url('',__FILE__));

/*** Start! Everything to do with License verification, activation, deactivation ***/
define('WP_LICENSE_MGR_LIC_SECRET_KEY', '4c132da1f24a41.63429762');
define('WP_LICENSE_MGR_LIC_DEACTIVATION_POST_URL', 'http://license-manager.tipsandtricks-hq.com/wp-content/plugins/wp-license-manager/api/deactivate.php');
define('WP_LICENSE_MGR_LIC_ACTIVATION_POST_URL', 'http://license-manager.tipsandtricks-hq.com/wp-content/plugins/wp-license-manager/api/verify.php');

function wp_lic_mgr_deactivate_lic($lic)
{
    // Post URL
    $postURL = WP_LICENSE_MGR_LIC_DEACTIVATION_POST_URL;
    // The Secret key
    $secretKey = WP_LICENSE_MGR_LIC_SECRET_KEY;
    // The License key
    $licenseKey = $lic;//take this input from the user
    $data = array ();
    $data['secret_key'] = $secretKey;
    $data['license_key'] = $licenseKey;
    $data['registered_domain'] = $_SERVER['SERVER_NAME'];

    // send data to post URL
    $ch = curl_init ($postURL);
    curl_setopt ($ch, CURLOPT_POST, true);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
    $returnValue = curl_exec ($ch);
    //print_r($returnValue);
    list ($result, $msg, $additionalMsg) = explode ("\n", $returnValue);
    $retData = array();
    $retData['result'] = $result;
    $retData['msg'] = $msg;
    $retData['additional_msg'] = $additionalMsg;
    return $retData;
}

function wp_lic_mgr_lic_verify($lic)
{
    // Post URL
    $postURL = WP_LICENSE_MGR_LIC_ACTIVATION_POST_URL;
    // The Secret key
    $secretKey = WP_LICENSE_MGR_LIC_SECRET_KEY;
    // The License key
    $licenseKey = $lic; //take this input from the user
    $data = array ();
    $data['secret_key'] = $secretKey;
    $data['license_key'] = $licenseKey;
    $data['registered_domain'] = $_SERVER['SERVER_NAME'];

    // send data to post URL
    $ch = curl_init ($postURL);
    curl_setopt ($ch, CURLOPT_POST, true);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
    $returnValue = curl_exec ($ch);
    //print_r($returnValue);
    list ($result, $msg, $additionalMsg) = explode ("\n", $returnValue);
    $retData = array();
    $retData['result'] = $result;
    $retData['msg'] = $msg;
    $retData['additional_msg'] = $additionalMsg;
    return $retData;
}
function wp_lic_mgr_is_license_valid()
{
    $is_valid = false;
    $license_key = get_option('wp_lic_mgr_lic_key');    
    if(!empty($license_key))
    {
    	$is_valid = true;
    }    
	return $is_valid;
}

function wp_lic_mgr_lic_warning()
{
	if(!wp_lic_mgr_is_license_valid())
	{
		echo '<div class="updated fade">License Manager is almost ready. You must provide a valid License key <a href="admin.php?page=wp_lic_mgr_product_license">here</a> to make it work.</div>';			
	}	
}
add_action('admin_notices', 'wp_lic_mgr_lic_warning');
/*** End! Everything to do with License verification, activation, deactivation ***/


add_action('wp_ajax_del_dom', 'del_reg_dom');
function del_reg_dom(){
	$reg_table = WP_LICENSE_MANAGER_REG_DOMAIN_TABLE_NAME;
	global $wpdb;
	$ret = $wpdb->query("DELETE FROM $reg_table WHERE id =" . $_GET['id']);
	echo ($ret)? 'success' :'failed';	
	exit(0);
}

//Add the Admin Menus
define("LIC_MGR_MANAGEMENT_PERMISSION", "edit_themes");
if (is_admin())
{
    function wp_lic_mgr_add_admin_menu()
    {
        add_menu_page("License Mgr", "License Mgr", LIC_MGR_MANAGEMENT_PERMISSION, __FILE__, "wp_lic_mgr_manage_licenses_menu");
        add_submenu_page(__FILE__, "Manage Licenses", "Manage Licenses", LIC_MGR_MANAGEMENT_PERMISSION, __FILE__, "wp_lic_mgr_manage_licenses_menu");
        add_submenu_page(__FILE__, "Add/Edit Licenses", "Add/Edit Licenses", LIC_MGR_MANAGEMENT_PERMISSION, 'wp_lic_mgr_addedit', "wp_lic_mgr_add_licenses_menu");
        add_submenu_page(__FILE__, "Settings", "Settings", LIC_MGR_MANAGEMENT_PERMISSION, 'wp_lic_mgr_settings', "wp_lic_mgr_settings_menu");
        add_submenu_page(__FILE__, "Admin Functions", "Admin Functions", LIC_MGR_MANAGEMENT_PERMISSION, 'wp_lic_mgr_admin_fnc', "wp_lic_mgr_admin_fnc_menu");
        add_submenu_page(__FILE__, "Product License", "Product License", LIC_MGR_MANAGEMENT_PERMISSION, 'wp_lic_mgr_product_license', "wp_lic_mgr_product_license_menu");
        add_submenu_page(__FILE__, "Integration Help", "Integration Help", LIC_MGR_MANAGEMENT_PERMISSION, 'lic_mgr_integration_help_page', "lic_mgr_integration_help_menu");
    }
    //Include menus
    require_once(dirname(__FILE__).'/menu/lic_manage_licenses.php');
    require_once(dirname(__FILE__).'/menu/lic_add_licenses.php');
    require_once(dirname(__FILE__).'/menu/lic_settings.php');
    require_once(dirname(__FILE__).'/menu/wp_lic_mgr_admin_fnc.php');    
    require_once(dirname(__FILE__).'/menu/product_license.php');
    require_once(dirname(__FILE__).'/menu/lic_mgr_integration_help_page.php');
}

// Insert the options page to the admin menu
if (is_admin())
{
    add_action('admin_menu','wp_lic_mgr_add_admin_menu');
}

?>
