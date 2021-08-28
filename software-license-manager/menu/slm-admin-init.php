<?php

/*
 * This file only gets included if "is_admin()" check is true.
 * Admin menu rendering code goes in this file.
 */

add_action( 'admin_menu', 'wp_lic_mgr_add_admin_menu' );

//Include menu handling files
require_once WP_LICENSE_MANAGER_PATH . '/menu/slm-manage-licenses.php';
require_once WP_LICENSE_MANAGER_PATH . '/menu/slm-add-licenses.php';
require_once WP_LICENSE_MANAGER_PATH . '/menu/slm-lic-settings.php';
require_once WP_LICENSE_MANAGER_PATH . '/menu/slm-admin-functions.php';
require_once WP_LICENSE_MANAGER_PATH . '/menu/slm-integration-help-page.php';

function wp_lic_mgr_add_admin_menu() {
	add_menu_page( 'License Manager', 'License Manager', SLM_MANAGEMENT_PERMISSION, SLM_MAIN_MENU_SLUG, 'wp_lic_mgr_manage_licenses_menu', SLM_MENU_ICON );
	add_submenu_page( SLM_MAIN_MENU_SLUG, 'Manage Licenses', 'Manage Licenses', SLM_MANAGEMENT_PERMISSION, SLM_MAIN_MENU_SLUG, 'wp_lic_mgr_manage_licenses_menu' );
	add_submenu_page( SLM_MAIN_MENU_SLUG, 'Add/Edit Licenses', 'Add/Edit Licenses', SLM_MANAGEMENT_PERMISSION, 'wp_lic_mgr_addedit', 'wp_lic_mgr_add_licenses_menu' );
	add_submenu_page( SLM_MAIN_MENU_SLUG, 'Settings', 'Settings', SLM_MANAGEMENT_PERMISSION, 'wp_lic_mgr_settings', 'wp_lic_mgr_settings_menu' );
	add_submenu_page( SLM_MAIN_MENU_SLUG, 'Admin Functions', 'Admin Functions', SLM_MANAGEMENT_PERMISSION, 'wp_lic_mgr_admin_fnc', 'wp_lic_mgr_admin_fnc_menu' );
	add_submenu_page( SLM_MAIN_MENU_SLUG, 'Integration Help', 'Integration Help', SLM_MANAGEMENT_PERMISSION, 'lic_mgr_integration_help_page', 'lic_mgr_integration_help_menu' );
}

