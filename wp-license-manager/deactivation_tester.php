<?php
    //Post URL
    $postURL = 'http://www.scam-wiki.com/wp-content/plugins/wp-pdf-stamper/api/dc.php';
    // the Secret Key
    $secretKey = '4bc4101d10d424.12023041';
    // prepare the data
    $data = array ();
    $data['secret_key'] = $secretKey;

    // send data to post URL
    $ch = curl_init ($postURL);
    curl_setopt ($ch, CURLOPT_POST, true);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
    $returnValue = curl_exec ($ch);
	print_r($returnValue);
?>