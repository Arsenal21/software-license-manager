<?php
include_once('admin_includes1.php');

function lic_mgr_integration_help_menu()
{
	if(!wp_lic_mgr_is_license_valid())
	{		
		return;	//Do not display the page if licese key is invalid	
	}
		
    echo '<div class="wrap">';
    echo '<div id="poststuff"><div id="post-body">';
	lic_mgr_admin_general_css();
    echo '<h2>WP License Manager Integration Help v'.WP_LICENSE_MANAGER_VERSION.'</h2>';
	
	$LicenseCreationPostURL = WP_LICENSE_MANAGER_URL.'/api/create.php';	
	echo "<strong>The License Creation POST URL For Your Installation</strong>";
	echo '<div class="lic_mgr_code">'.$LicenseCreationPostURL.'</div>';

	$LicenseVerificationPostURL = WP_LICENSE_MANAGER_URL.'/api/verify.php';	
	echo "<strong>The License Verification POST URL For Your Installation</strong>";
	echo '<div class="lic_mgr_code">'.$LicenseVerificationPostURL.'</div>';
	
	$LicenseDeactivationPostURL = WP_LICENSE_MANAGER_URL.'/api/deactivate.php';	
	echo "<strong>The License Deactivation POST URL For Your Installation</strong>";
	echo '<div class="lic_mgr_code">'.$LicenseDeactivationPostURL.'</div>';	
	?>
<h2>3rd Party Integration</h2>

Integrating a 3rd party payment system or shopping cart with WP License Manager is possible.
<br /><br />
The integration process can be accomplished in three steps, namely:
<br />
<br />1. Generate POST data
<br />2. Send POST data to the POST URL
<br />3. Process the returned data
<br /><br />
<strong>POST Values</strong>
<br />
WP License Manager expects a certain set of variables to be sent to it via HTTP POST. These variables are:
<br /><br />
Mandatory Variables
<br />
----------------
<br />a. Secret Key: A Secret API key (you can find this value in the settings menu of this plugin)
<br /><br />
Optional Variables
<br />
---------------
<br />b. Customer First Name: The first name of the customer
<br />c. Customer Last Name: The last name of the customer
<br />d. Customer Email: The email address of the customer
<br />e. Company Name: The customer's company name
<br />f. Maximum Domains Allowed: The number of domains this license key can be used on
<br />g. Transaction ID: A unique transaction ID to reference the transaction
<br /><br />
<strong>Return Value</strong>
<br />
Upon successful processing, WP License Manager will return a plain text message that will have two or three lines similar to the following:
<br />
<div class="lic_mgr_code">
Success 
<br />License key
<br />WPLICMGR4bc29fd61e471
</div>
or
<div class="lic_mgr_code">
Error
<br />Secret key is invalid
</div>

1. The first line is an indication of success or error
<br />2. The second line is the result.
<br />3. The third line is additional message that resulted from the request.
<br /><br />
<strong>Sample PHP Code</strong>
<br />
Below is a sample PHP code that shows how easy it is to integrate with WP License Manager
<br />

<div class="lic_mgr_code">
/*** Mandatory data ***/
<br />// Post URL
<br />$postURL = "<?php echo $LicenseCreationPostURL; ?>";
<br />// The Secret key
<br />$secretKey = "<?php echo get_option('wp_lic_mgr_reg_secret_key'); ?>";
<br /> 
<br />/*** Optional Data ***/
<br />$firstname = "John";
<br />$lastname = "Doe";
<br />$email = "john.doe@gmail.com";
<br />
<br />// prepare the data
<br />$data = array ();
<br />$data['secret_key'] = $secretKey;
<br />$data['source_file'] = $fileURL;
<br />$data['first_name'] = $firstname;
<br />$data['last_name'] = $lastname;
<br />$data['email'] = $email;
<br />
<br />// send data to post URL
<br />$ch = curl_init ($postURL);
<br />curl_setopt ($ch, CURLOPT_POST, true);
<br />curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
<br />curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
<br />$returnValue = curl_exec ($ch);
<br />
<br />// Process the return values
<br />list ($status, $msg, $additionalMsg) = explode ("\n", $returnValue);
<br />if(strpos($status,"Success") !== false)
<br />{
<br />    $license_key = trim($additionalMsg);
<br />    echo "The generated license key is: ".$license_key;
<br />}
<br />else
<br />{
<br />    echo "An error occured while trying to create license! Error details: ".$msg;
<br />}
</div>
	
	<?php     
    echo '</div></div>';
    echo '</div>';	
}
?>