<?php

/*
 * This file only gets included if "is_admin()" check is true.
 * Admin menu rendering code goes in this file.
 */

add_action('admin_menu', 'wp_lic_mgr_add_admin_menu');

//Include menu handling files
require_once(SLM_ADMIN . 'slm-manage-licenses.php');
require_once(SLM_ADMIN . 'slm-add-licenses.php');
require_once(SLM_ADMIN . 'slm-lic-settings.php');
require_once(SLM_ADMIN . 'slm-admin-functions.php');
require_once(SLM_ADMIN . 'slm-integration-help-page.php');


// Base 64 encoded SVG image.



function wp_lic_mgr_add_admin_menu() {
    $icon_svg = SLM_ASSETS_URL . 'images/slm_logo_small.svg';

    add_menu_page("License Manager", "License Manager", SLM_MANAGEMENT_PERMISSION, SLM_MAIN_MENU_SLUG, "wp_lic_mgr_manage_licenses_menu", $icon_svg);
    add_submenu_page(SLM_MAIN_MENU_SLUG, "All Licenses", "All Licenses", SLM_MANAGEMENT_PERMISSION, SLM_MAIN_MENU_SLUG, "wp_lic_mgr_manage_licenses_menu");
    add_submenu_page(SLM_MAIN_MENU_SLUG, "New License", "New Licenses", SLM_MANAGEMENT_PERMISSION, 'wp_lic_mgr_addedit', "wp_lic_mgr_add_licenses_menu");
    add_submenu_page(SLM_MAIN_MENU_SLUG, "Admin Tools", "Admin Tools", SLM_MANAGEMENT_PERMISSION, 'wp_lic_mgr_admin_fnc', "wp_lic_mgr_admin_fnc_menu");
    add_submenu_page(SLM_MAIN_MENU_SLUG, "Settings", "Settings", SLM_MANAGEMENT_PERMISSION, 'wp_lic_mgr_settings', "wp_lic_mgr_settings_menu");
    add_submenu_page(SLM_MAIN_MENU_SLUG, "Integration Help", "Integration Help", SLM_MANAGEMENT_PERMISSION, 'lic_mgr_integration_help_page', "lic_mgr_integration_help_menu");
}