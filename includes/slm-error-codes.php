<?php

/**
 * SLM Plus Error Codes
 *
 * This file contains the API error codes used throughout the SLM Plus plugin.
 * Each constant represents a specific error or success message to be returned in API responses.
 *
 * Usage:
 * - These codes are used to provide standardized error or success responses.
 * - API endpoints or internal logic should reference these constants to ensure consistency.
 *
 * @package SLM Plus
 */

class SLM_Error_Codes {
    
    // License creation errors
    const CREATE_FAILED                 = 10;   // License creation failed
    const CREATE_KEY_INVALID            = 100;  // License creation secret key is invalid

    // License key deactivation and cancellation
    const DOMAIN_ALREADY_INACTIVE       = 80;   // Domain is already inactive
    const DOMAIN_MISSING                = 70;   // Domain information is missing
    const KEY_CANCELED                  = 130;  // License key was successfully canceled
    const KEY_CANCELED_FAILED           = 140;  // Failed to cancel the license key
    const KEY_DEACTIVATE_DOMAIN_SUCCESS = 360;  // Domain successfully deactivated
    const KEY_DEACTIVATE_SUCCESS        = 340;  // License key successfully deactivated

    // License key deletion
    const KEY_DELETE_FAILED             = 300;  // Failed to delete the license key
    const KEY_DELETE_SUCCESS            = 320;  // License key successfully deleted
    const KEY_DELETED                   = 130;  // License key was deleted

    // License key updates
    const KEY_UPDATE_FAILED             = 220;  // Failed to update the license key
    const KEY_UPDATE_SUCCESS            = 240;  // License key successfully updated

    // License activation and validation
    const LICENSE_ACTIVATED             = 380;  // License key successfully activated
    const LICENSE_BLOCKED               = 20;   // License key is blocked
    const LICENSE_CREATED               = 400;  // License key was successfully created
    const LICENSE_EXIST                 = 200;  // License key already exists
    const LICENSE_EXPIRED               = 30;   // License key has expired
    const LICENSE_IN_USE                = 40;   // License key is currently in use
    const LICENSE_INVALID               = 60;   // License key is invalid
    const LICENSE_VALID                 = 65;   // License key is valid

    // Miscellaneous errors
    const MISSING_KEY_DELETE_FAILED     = 280;  // Missing license key for deletion failed
    const MISSING_KEY_UPDATE_FAILED     = 260;  // Missing license key for update failed
    const REACHED_MAX_DEVICES           = 120;  // Reached the maximum number of devices for the license
    const REACHED_MAX_DOMAINS           = 50;   // Reached the maximum number of domains for the license

    // Verification errors
    const VERIFY_KEY_INVALID            = 90;   // Verification secret key is invalid
}
