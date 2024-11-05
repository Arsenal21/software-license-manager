<?php

require_once 'LicenseAPI.php';

class DeactivateLicense
{
    private $licenseAPI;

    public function __construct()
    {
        $this->licenseAPI = new LicenseAPI();
    }

    /**
     * Deactivate a license on a specific domain or device.
     *
     * @param array $deactivationData Deactivation data, including license key, domain, and/or device.
     * @return void Outputs the result based on the deactivation response.
     */
    public function deactivate($deactivationData)
    {
        $response = $this->licenseAPI->deactivateLicense($deactivationData);

        // Handle response scenarios
        if ($response['result'] === 'success') {
            echo "License deactivated successfully for domain: " . ($deactivationData['registered_domain'] ?? 'N/A');
            if (!empty($deactivationData['registered_devices'])) {
                echo " and device: " . $deactivationData['registered_devices'];
            }
        } elseif (isset($response['error_code'])) {
            // Handle specific deactivation error codes
            switch ($response['error_code']) {
                case SLM_Error_Codes::DOMAIN_ALREADY_INACTIVE:
                    echo "Error: The license is already inactive on the specified domain or device.";
                    break;
                case SLM_Error_Codes::DOMAIN_MISSING:
                    echo "Error: The specified domain or device was not found.";
                    break;
                default:
                    echo "Error: Deactivation failed. " . ($response['message'] ?? 'Unknown error.');
                    break;
            }
        } else {
            // Fallback for unexpected issues
            echo "Error: Unable to deactivate license. " . ($response['message'] ?? 'Please try again later.');
        }
    }
}

// Usage example
$deactivateLicense = new DeactivateLicense();

$deactivationData = [
    'license_key' => 'YOUR_LICENSE_KEY',
    'registered_domain' => 'example.com',      // Optional: Domain to deactivate the license on
    'registered_devices' => 'Device12345'      // Optional: Device to deactivate (if applicable)
];

// If both domain and device are provided, only one needs to match for deactivation.
$deactivateLicense->deactivate($deactivationData);

