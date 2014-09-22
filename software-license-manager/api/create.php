<?php

if (isset($_REQUEST['secret_key'])) {
    include_once('../../../../wp-load.php');
    include_once(WP_LICENSE_MANAGER_PATH . 'includes/slm_db_access.php');

    $options = get_option('slm_plugin_options');
    $right_secret_key = $options['lic_creation_secret'];
    $lic_key_prefix = $options['lic_prefix'];

    $received_secret_key = $_REQUEST['secret_key'];
    if ($received_secret_key != $right_secret_key) {
        echo "Error\n";
        echo "Secret key is invalid\n";
        exit(0);
    }
    $fields = array();
    $fields['license_key'] = uniqid($lic_key_prefix);
    $fields['lic_status'] = 'active';
    $fields['first_name'] = $_REQUEST['first_name'];
    $fields['last_name'] = $_REQUEST['last_name'];
    $fields['email'] = $_REQUEST['email'];
    $fields['company_name'] = $_REQUEST['company_name'];
    $fields['txn_id'] = $_REQUEST['txn_id'];
    if (empty($_REQUEST['max_allowed_domains'])) {
        $fields['max_allowed_domains'] = $options['default_max_domains'];
    }

    $updateDb = LicMgrDbAccess::insert(WP_LICENSE_MANAGER_LICENSE_TABLE_NAME, $fields);
    echo "Success\n";
    echo "License key\n";
    echo $fields['license_key'];
}
