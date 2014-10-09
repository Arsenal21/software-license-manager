<?php

// Post URL
$postURL = "http://localhost:81/wp/wp7";
// The Secret key
$secretKey = "541fc9967d4b43.07908805";
$firstname = "John";
$lastname = "Doe";
$email = "john.doe@gmail.com";

// prepare the data
$data = array ();
$data['secret_key'] = $secretKey;
$data['slm_action'] = 'slm_create_new';
$data['first_name'] = $firstname;
$data['last_name'] = $lastname;
$data['email'] = $email;

// send data to API post URL
$ch = curl_init ($postURL);
curl_setopt ($ch, CURLOPT_POST, true);
curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
$returnValue = curl_exec ($ch);

// Process the return values
//var_dump($returnValue);
