<?php

require_once 'LicenseAPI.php';

class GetLicenseInfo
{
    private $licenseAPI;

    public function __construct()
    {
        $this->licenseAPI = new LicenseAPI();
    }

    /**
     * Retrieve and display detailed information about a license.
     *
     * @param string $licenseKey The license key to retrieve information for.
     * @return void Outputs detailed license information based on the response.
     */
    public function retrieve($licenseKey)
    {
        $data = [
            'license_key' => $licenseKey,
        ];

        $response = $this->licenseAPI->getLicenseInfo($data);

        // Interpret and display license information based on the API response
        if ($response['result'] === 'success') {
            $info = $response['data'];

            echo "License Information:\n";
            echo "-----------------------\n";
            echo "License Key: " . $info['license_key'] . "\n";
            echo "Status: " . ucfirst($info['status']) . "\n";
            echo "Registered User: " . $info['first_name'] . " " . $info['last_name'] . "\n";
            echo "Email: " . $info['email'] . "\n";
            echo "Company: " . ($info['company_name'] ?? 'N/A') . "\n";
            echo "Product: " . $info['product_ref'] . "\n";
            echo "Created Date: " . $info['date_created'] . "\n";
            echo "Expiry Date: " . ($info['date_expiry'] ?? 'N/A') . "\n";
            echo "Max Domains: " . $info['max_allowed_domains'] . "\n";
            echo "Max Devices: " . $info['max_allowed_devices'] . "\n";
            
            if (!empty($info['registered_domains'])) {
                echo "Registered Domains:\n";
                foreach ($info['registered_domains'] as $domain) {
                    echo " - " . $domain->registered_domain . "\n";
                }
            }

            if (!empty($info['registered_devices'])) {
                echo "Registered Devices:\n";
                foreach ($info['registered_devices'] as $device) {
                    echo " - " . $device->registered_devices . "\n";
                }
            }
            echo "-----------------------\n";

        } elseif (isset($response['error_code'])) {
            // Handle specific error codes for information retrieval
            switch ($response['error_code']) {
                case SLM_Error_Codes::LICENSE_INVALID:
                    echo "Error: Invalid license key provided.";
                    break;
                default:
                    echo "Error retrieving license information: " . ($response['message'] ?? 'Unknown error.');
                    break;
            }
        } else {
            // Fallback for unexpected issues
            echo "Error: Unable to retrieve license information. " . ($response['message'] ?? 'Please try again later.');
        }
    }
}

// Usage example
$getLicenseInfo = new GetLicenseInfo();

// License key to retrieve information for
$licenseKey = 'YOUR_LICENSE_KEY';
$getLicenseInfo->retrieve($licenseKey);

