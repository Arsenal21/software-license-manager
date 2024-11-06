<?php

require_once 'LicenseAPI.php';

class ActivateLicense
{
    private $licenseAPI;

    public function __construct()
    {
        $this->licenseAPI = new LicenseAPI();
    }

    /**
     * Activate a license on a specific domain or device.
     *
     * @param array $activationData Activation data, including license key, domain, and optionally device.
     * @return void Outputs the result based on the activation response.
     */
    public function activate($activationData)
    {
        $response = $this->licenseAPI->activateLicense($activationData);

        // Handle different scenarios based on the API response
        if ($response['result'] === 'success') {
            echo "License activated successfully for domain: " . $activationData['registered_domain'];
            if (!empty($activationData['registered_devices'])) {
                echo " and device: " . $activationData['registered_devices'];
            }
        } elseif (isset($response['error_code'])) {
            // Specific error handling based on the API's error code
            switch ($response['error_code']) {
                case SLM_Error_Codes::LICENSE_EXPIRED:
                    echo "Error: The license has expired. Please renew your license.";
                    break;
                case SLM_Error_Codes::LICENSE_BLOCKED:
                    echo "Error: The license is blocked. Contact support for assistance.";
                    break;
                case SLM_Error_Codes::LICENSE_IN_USE:
                    echo "Error: This license is already in use on the specified domain or device.";
                    break;
                case SLM_Error_Codes::REACHED_MAX_DOMAINS:
                    echo "Error: Maximum allowed domains reached. Upgrade your license for additional domains.";
                    break;
                case SLM_Error_Codes::REACHED_MAX_DEVICES:
                    echo "Error: Maximum allowed devices reached. Upgrade your license for additional devices.";
                    break;
                default:
                    echo "Error: Activation failed. " . ($response['message'] ?? 'Unknown error.');
                    break;
            }
        } else {
            // Generic error message for unexpected issues
            echo "Error: Unable to activate license. " . ($response['message'] ?? 'Please try again later.');
        }
    }
}

// Usage example
$activateLicense = new ActivateLicense();

$activationData = [
    'license_key' => 'YOUR_LICENSE_KEY',
    'registered_domain' => 'example.com',      // Required: Domain for license activation
    'registered_devices' => 'Device12345'      // Optional: Device identifier (if applicable)
];

$activateLicense->activate($activationData);

