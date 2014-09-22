<?php
//TODO Enter your secret API Key (you can get this from your license manager plugin's settins menu)
define('WP_LICENSE_MGR_LIC_SECRET_KEY', '4c132dar5odafv31.645829762');
//TODO Enter your license activation/verification POST URL (you can get this from your license manager plugin's Integration Menu)
define('WP_LICENSE_MGR_LIC_ACTIVATION_POST_URL', 'http://example.com/path-to-the-license-manger-verification-api-script');
//TODO Enter your license deactivation POST URL (you can get this from your license manager plugin's Integration Menu)
define('WP_LICENSE_MGR_LIC_DEACTIVATION_POST_URL', 'http://example.com/path-to-the-license-manger-deactivation-api-script');
//TODO Enter a variable name that will be used to save the verified license key in the database (you can give it a unique name)
define('WP_LICENSE_MGR_CLIENT_LICENSE_KEY_VAR_NAME', 'wp_lic_mgr_client_product_key');

include_once('wp-license-mgr-config.php');
$wp_lic_mgr_client_config = WP_Lic_Mgr_Client_Config::getInstance();
include_once('wp-license-mgr-functions.php');
