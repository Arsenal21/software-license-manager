<?php

require_once 'LicenseAPI.php';

class CheckLicense
{
    private $licenseAPI;

    public function __construct()
    {
        $this->licenseAPI = new LicenseAPI();
    }

    /**
     * Check the status of a license.
     *
     * @param string $licenseKey The license key to check.
     * @return void Outputs the result of the license status check.
     */
    public function check($licenseKey)
    {
        $data = [
            'license_key' => $licenseKey,
        ];

        $response = $this->licenseAPI->checkLicenseStatus($data);

        // Interpret the response based on license status and other indicators
        if ($response['result'] === 'success') {
            $status = $response['data']['status'];
            switch ($status) {
                case 'active':
                    echo "License is active and valid.";
                    break;
                case 'expired':
                    echo "License has expired. Please renew to continue using the product.";
                    break;
                case 'blocked':
                    echo "License is blocked. Contact support for further assistance.";
                    break;
                default:
                    echo "License status: " . ucfirst($status) . ".";
                    break;
            }
        } elseif (isset($response['error_code'])) {
            // Handle specific error codes for license check failure
            switch ($response['error_code']) {
                case SLM_Error_Codes::LICENSE_INVALID:
                    echo "Error: Invalid license key provided.";
                    break;
                default:
                    echo "Error checking license status: " . ($response['message'] ?? 'Unknown error.');
                    break;
            }
        } else {
            // Fallback for unexpected issues
            echo "Error: Unable to check license status. " . ($response['message'] ?? 'Please try again later.');
        }
    }
}

// Usage example
$checkLicense = new CheckLicense();

// License key to check
$licenseKey = 'YOUR_LICENSE_KEY';
$checkLicense->check($licenseKey);

