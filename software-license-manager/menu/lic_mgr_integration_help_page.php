<?php

function lic_mgr_integration_help_menu() {
    ?>
    <style type="text/css">
        .lic_mgr_code{border:1px solid #C2D7EF; background-color:#E2EDFF; margin:10px 0; padding:10px; width:800px; font-family:"Consolas","Bitstream Vera Sans Mono","Courier New",Courier,monospace !important; font-size:13px;}
    </style>
    <?php
    
    $options = get_option('slm_plugin_options');
    $creation_secret_key = $options['lic_creation_secret'];
    $secret_verification_key = $options['lic_verification_secret'];
    
    echo '<div class="wrap">';
    echo '<div id="poststuff"><div id="post-body">';
    echo '<h2>License Manager Integration Help v' . WP_LICENSE_MANAGER_VERSION . '</h2>';

    $api_query_post_url = SLM_SITE_HOME_URL;
    echo "<strong>The License API Query POST URL For Your Installation</strong>";
    echo '<div class="lic_mgr_code">' . $api_query_post_url . '</div>';

    echo "<strong>The License Activation or Deactivation API secret key</strong>";
    echo '<div class="lic_mgr_code">' . $secret_verification_key . '</div>';
    
    echo "<strong>The License Creation API secret key</strong>";
    echo '<div class="lic_mgr_code">' . $creation_secret_key . '</div>';
    ?>
    <h2>3rd Party Integration</h2>

    Integrating a 3rd party payment system or shopping cart with License Manager is easy.
    <br /><br />
    The integration process can be accomplished in three steps, namely:
    <br />
    <br />1. Generate POST data
    <br />2. Send POST data to the API POST URL
    <br />3. Process the returned data
    <br /><br />
    <strong>POST Values</strong>
    <br />
    License Manager expects a certain set of variables to be sent to it via HTTP POST or GET. These variables are:
    <br /><br />
    Mandatory Variables
    <br />
    ----------------
    <br />a. secret_key - A Secret API key for authentication (you can find the secret key value in the settings menu of this plugin)
    <br />b. slm_action - The action being performed. The values can be slm_create_new or slm_activate or slm_deactivate
    <br /><br />
    Optional Variables
    <br />
    ---------------
    <br />c. Customer First Name: The first name of the customer
    <br />d. Customer Last Name: The last name of the customer
    <br />e. Customer Email: The email address of the customer
    <br />f. Company Name: The customer's company name
    <br />g. Maximum Domains Allowed: The number of domains this license key can be used on
    <br />h. Transaction ID: A unique transaction ID to reference the transaction
    <br /><br />
    <strong>Return Value</strong>
    <br />
    Upon successful processing, License Manager will return a plain text message that will have two or three lines similar to the following:
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
    Below is a sample PHP code that shows how you can create a license via the API
    <br />

    <div class="lic_mgr_code">
        /*** Mandatory data ***/
        <br />// Post URL
        <br />$postURL = "<?php echo $LicenseCreationPostURL; ?>";
        <br />// The Secret key
        <br />$secretKey = "<?php echo $creation_secret_key; ?>";
        <br /> 
        <br />/*** Optional Data ***/
        <br />$firstname = "John";
        <br />$lastname = "Doe";
        <br />$email = "john.doe@gmail.com";
        <br />
        <br />// prepare the data
        <br />$data = array ();
        <br />$data['secret_key'] = $secretKey;
        <br />$data['slm_action'] = 'slm_create_new';
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
        <br />//var_dump($returnValue);
    </div>

    <?php
    echo '</div></div>';
    echo '</div>';
}
