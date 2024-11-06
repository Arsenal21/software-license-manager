<?php

require_once 'LicenseAPI.php';

class CreateLicense
{
    private $licenseAPI;

    public function __construct()
    {
        $this->licenseAPI = new LicenseAPI();
    }

    /**
     * Create a new license using provided data.
     *
     * @param array $licenseData Data for license creation.
     * @return void Displays success or error message based on response.
     */
    public function create($licenseData)
    {
        $response = $this->licenseAPI->createLicense($licenseData);

        if ($response['result'] === 'success') {
            echo "License created successfully. Key: " . $response['data']['key'];
        } else {
            echo "Error creating license: " . $response['message'];
        }
    }
}

// Usage example
$createLicense = new CreateLicense();

$licenseData = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'johndoe@example.com',
    'purchase_id_' => '12345',
    'max_allowed_domains' => 2,
    'max_allowed_devices' => 1,
    'date_created' => date('Y-m-d'),
    'product_ref' => 'ThemePro'
];

$createLicense->create($licenseData);

