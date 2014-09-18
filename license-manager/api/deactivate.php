<?php

if (isset($_REQUEST['secret_key'])) {
    include_once('../../../../wp-load.php');
    include_once(WP_LICENSE_MANAGER_PATH . 'includes/slm_db_access.php');

    $right_secret_key = get_option('wp_lic_mgr_verification_secret_key');

    $received_secret_key = $_REQUEST['secret_key'];
    if ($received_secret_key != $right_secret_key) {
        echo "Error\n";
        echo "Secret key is invalid\n";
        exit;
    }

    if (empty($_REQUEST['license_key'])) {
        echo "Error\n";
        echo "License key information is missing.\n";
        exit;
    }
    if (empty($_REQUEST['registered_domain'])) {
        echo "Error\n";
        echo "Registered domain information is missing.\n";
        exit;
    }
    $registered_domain = trim($_REQUEST['registered_domain']);

    $updateDb = LicMgrDbAccess::delete(WP_LICENSE_MANAGER_REG_DOMAIN_TABLE_NAME, 'lic_key=\'' . $_REQUEST['license_key'] . '\' AND registered_domain=\'' . $registered_domain . '\'');

    if ($updateDb) {
        echo "Success\n";
        echo "Following License Key Deactivated: ";
        echo $_REQUEST['license_key'];
        echo "\n";
    } else {
        echo "Error\n";
        echo "License key could not be deactivated.\n";
    }
}