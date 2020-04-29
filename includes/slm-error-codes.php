<?php

/*
 * Contains the API error codes
 */

class SLM_Error_Codes {
    const CREATE_FAILED                 = 10;
    const CREATE_KEY_INVALID            = 100;
    const DOMAIN_ALREADY_INACTIVE       = 80;
    const DOMAIN_MISSING                = 70;
    const KEY_CANCELED                  = 130;
    const KEY_CANCELED_FAILED           = 140;
    const KEY_DEACTIVATE_DOMAIN_SUCCESS = 360;
    const KEY_DEACTIVATE_SUCCESS        = 340;
    const KEY_DELETE_FAILED             = 300;
    const KEY_DELETE_SUCCESS            = 320;
    const KEY_DELETED                   = 130;
    const KEY_UPDATE_FAILED             = 220;
    const KEY_UPDATE_SUCCESS            = 240;
    const LICENSE_ACTIVATED             = 380;
    const LICENSE_BLOCKED               = 20;
    const LICENSE_CREATED               = 400;
    const LICENSE_EXIST                 = 200;
    const LICENSE_EXPIRED               = 30;
    const LICENSE_IN_USE                = 40;
    const LICENSE_INVALID               = 60;
    const LICENSE_VALID                 = 65;
    const MISSING_KEY_DELETE_FAILED     = 280;
    const MISSING_KEY_UPDATE_FAILED     = 260;
    const REACHED_MAX_DEVICES           = 120;
    const REACHED_MAX_DOMAINS           = 50;
    const VERIFY_KEY_INVALID            = 90;
}