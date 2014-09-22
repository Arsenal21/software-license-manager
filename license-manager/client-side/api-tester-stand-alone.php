<?php

wp_lic_verify_license();

function wp_lic_create_license()
{
    //Post URL
    $postURL = 'http://localhost/wordpress/wp-content/plugins/wp-license-manager/api/create.php';
    // the Secret Key
    $secretKey = '4bc301bc4163f';
    // prepare the data
    $data = array ();
    $data['secret_key'] = $secretKey;
    $data['registered_domain'] = '';
    $data['lic_status'] = '';
    $data['first_name'] = 'Ruhul';
    $data['last_name'] = 'Amin';
    $data['email'] = 'amin@gmail.com';
    $data['company_name'] = '';
    $data['txn_id'] = '';
    $fields['max_allowed_domains'] = 1;

    // send data to post URL
    $ch = curl_init ($postURL);
    curl_setopt ($ch, CURLOPT_POST, true);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
    $returnValue = curl_exec ($ch);

    //print_r($returnValue);

    //list ($name, $value) = explode ("\n", $returnValue);
    list ($status, $msg, $additionalMsg) = explode ("\n", $returnValue);
    if(strpos($status,"Success") !== false)
    {
        $licenseKey = $additionalMsg;
    }
    else
    {
        echo "Error!";
    }
    echo "<br />License key: ".$licenseKey;
}

function wp_lic_verify_license()
{
    // Post URL
    $postURL = 'http://localhost/wordpress/wp-content/plugins/wp-license-manager/api/verify.php';
    // The Secret key
    $secretKey = '4bed4c0f228895.45965740';
    // The License key
    $licenseKey = '4bed7e6c7af17'; //take this input from the user

    // prepare the data
    $data = array ();
    $data['secret_key'] = $secretKey;
    $data['license_key'] = $licenseKey;
    // set migrate_from if you want to transfer license from "migrate_from" domain to current domain.
    //$data['migrate_from'] = 'domain_name_from_which_license_will_be_migrated';

    // send data to post URL
    $ch = curl_init ($postURL);
    curl_setopt ($ch, CURLOPT_POST, true);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
    $returnValue = curl_exec ($ch);

    //print_r($returnValue);

    list ($result, $msg, $additionalMsg) = explode ("\n", $returnValue);
    if ($result == 'Success')
    {
        //License key verified... go ahead with the activation.
        echo "<br />".$msg;
        echo "<br />".$additionalMsg;
    }
    else
    {
        //Verification failed.. do not activate.
        echo "<br />Error!";
        echo "<br />".$msg;
    }
}
