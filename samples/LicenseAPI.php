<?php

require_once 'CoreConfig.php';

class LicenseAPI
{
    /**
     * Create a new license.
     *
     * @param array $data License data to send with the request.
     * @return array Response from the API.
     */
    public function createLicense($data)
    {
        return CoreConfig::apiRequest('slm_create_new', $data);
    }

    /**
     * Update an existing license.
     *
     * @param array $data License data to send with the request.
     * @return array Response from the API.
     */
    public function updateLicense($data)
    {
        return CoreConfig::apiRequest('slm_update', $data);
    }

    /**
     * Activate a license for a specific domain or device.
     *
     * @param array $data License activation data.
     * @return array Response from the API.
     */
    public function activateLicense($data)
    {
        return CoreConfig::apiRequest('slm_activate', $data);
    }

    /**
     * Deactivate a license from a specific domain or device.
     *
     * @param array $data License deactivation data.
     * @return array Response from the API.
     */
    public function deactivateLicense($data)
    {
        return CoreConfig::apiRequest('slm_deactivate', $data);
    }

    /**
     * Check the status of a license.
     *
     * @param array $data License data for checking status.
     * @return array Response from the API.
     */
    public function checkLicenseStatus($data)
    {
        return CoreConfig::apiRequest('slm_check', $data);
    }

    /**
     * Retrieve detailed information about a license.
     *
     * @param array $data License data for retrieving information.
     * @return array Response from the API.
     */
    public function getLicenseInfo($data)
    {
        return CoreConfig::apiRequest('slm_info', $data);
    }
}

