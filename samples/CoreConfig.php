<?php

class CoreConfig
{
    // Define constants for API URL and Secret Key
    const API_URL = 'https://yourwebsite.com';
    const SECRET_KEY = 'YOUR_SECRET_KEY';

    /**
     * Get the full API URL with action as a query parameter.
     *
     * @param string $action The specific action for the API call.
     * @return string Full API endpoint for the specific action.
     */
    public static function getApiUrl($action)
    {
        return rtrim(self::API_URL, '/') . '/?slm_action=' . urlencode($action);
    }

    /**
     * Process the API response to ensure correct format and handle errors.
     *
     * @param mixed $response Raw JSON response from the API.
     * @return array Processed response with success/error information.
     */
    public static function processResponse($response)
    {
        if (!is_array($response)) {
            return [
                'result' => 'error',
                'message' => 'Invalid response format from the API.',
            ];
        }

        // Handle success and error cases
        if (isset($response['result']) && $response['result'] === 'success') {
            return ['result' => 'success', 'data' => $response];
        } elseif (isset($response['result']) && $response['result'] === 'error') {
            self::logError($response['message'] ?? 'Unknown error');
            return ['result' => 'error', 'message' => $response['message'] ?? 'An error occurred.'];
        }

        // Fallback for unexpected response structures
        return ['result' => 'error', 'message' => 'Unexpected response structure.'];
    }

    /**
     * Log errors for troubleshooting.
     *
     * @param string $message The error message to log.
     */
    public static function logError($message)
    {
        error_log("[API ERROR] " . $message);
    }

    /**
     * Sanitize and validate input fields for security.
     *
     * @param array $fields Fields to be sanitized.
     * @return array Sanitized fields.
     */
    public static function sanitizeFields($fields)
    {
        foreach ($fields as $key => $value) {
            switch ($key) {
                case 'email':
                    $fields[$key] = filter_var($value, FILTER_SANITIZE_EMAIL);
                    break;
                case 'max_allowed_domains':
                case 'max_allowed_devices':
                    $fields[$key] = intval($value);
                    break;
                default:
                    $fields[$key] = htmlspecialchars(strip_tags($value));
            }
        }
        return $fields;
    }

    /**
     * Send a secure API request with cURL.
     *
     * @param string $action API action name.
     * @param array $data Data to send in the request.
     * @return array Processed API response.
     */
    public static function apiRequest($action, $data)
    {
        $data['secret_key'] = self::SECRET_KEY;
        $data = self::sanitizeFields($data);

        $ch = curl_init(self::getApiUrl($action));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            self::logError('cURL Error: ' . curl_error($ch));
            curl_close($ch);
            return [
                'result' => 'error',
                'message' => 'Network error during API request.',
            ];
        }

        curl_close($ch);

        return self::processResponse(json_decode($response, true));
    }
}

