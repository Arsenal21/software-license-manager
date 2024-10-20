=== Software License Manager ===
Contributors: Tips and Tricks HQ, Ruhul Amin
Donate link: https://www.tipsandtricks-hq.com/software-license-manager-plugin-for-wordpress
Tags: license key, serial key, manager, license, serial, key, selling, sell, license activation, manage license, software license, software license manager
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 4.5.8
Requires PHP: 7.4.0
License: GPLv2 or later

Create and manage license keys for your software applications easily

== Description ==

Software license management solution for your web applications (WordPress plugins, Themes, PHP based membership script etc.)

This plugin is very useful for creating a license server and doing the following via API:

- Create license keys in your system (license server)
- Check the status of a license key from from your application (remotely)
- Activate a license key from your application (remotely)
- Deactivate a license key (remotely)
- Check a license key (remotely)
- Track where the license key is being used.

You can also create license keys manually from the admin dashboard of this plugin.

= Please note that this plugin is ONLY for developers =

Check [license manager documentation](https://www.tipsandtricks-hq.com/software-license-manager-plugin-for-wordpress) to learn more.

= Integration with WP Express Checkout Plugin =
Check [WP Express Checkout integration documentation](https://wp-express-checkout.com/integrate-software-license-manager-plugin-with-wp-express-checkout/)

= Integration with WP eStore plugin =
Check [WP eStore integration documentation](https://www.tipsandtricks-hq.com/ecommerce/integrate-wp-estore-with-software-license-manager-plugin-3731)

= Github repository =

https://github.com/Arsenal21/software-license-manager

If you need some extra action hooks or filters for this plugin then let us know.

== Installation ==

1. Go to the Add New plugins screen in your WordPress admin area
1. Click the upload tab
1. Browse for the plugin file (software-license-manager.zip)
1. Click Install Now and then activate the plugin

== Frequently Asked Questions ==
None

== Screenshots ==
See the following page:
https://www.tipsandtricks-hq.com/software-license-manager-plugin-for-wordpress

== Changelog ==

= 4.5.8 =
- Added minimum PHP version requirement (PHP 7.4.0).
- Added a condition to suppress the debug warning when the 'item_reference' parameter is not included in the API call.

= 4.5.7 =
- PHP 8.3 compatibility related updates.
- Using Use COUNT(DISTINCT) to ensure that it is counting the number of distinct license keys.

= 4.5.6 =
- PHP 8.2 compatibility related update.

= 4.5.5 =
- Added the current date to the slm_check API response.

= 4.5.4 =
- Added a new action hook for when it receives an activation request for an expired license key (slm_api_listener_slm_activate_key_expired).
- Added new hook in the add license interface.
- Added hooks to the delete license domain interface.
- WP eStore Integration: it will use the default 1 year expiry date if a product specific expiry configuration is not set.

= 4.5.3 =
- Added a new database column named "user_ref" for allowing a user reference to be saved in the database with a license key (if applicable).

= 4.5.2 =
- PHP 7.2.x compatibility.

= 4.5.1 =
- Added nonce check to the 'slm_delete_domain' action. Thanks to Jetpack Scan team at Automattic.

= 4.5.0 =
- More variable escaping and sanitization.
- Replaced CURL with wp_remote_get() function.
- Removed example/sample plugin code file from the plugin.
- Added the sample plugin download option on our website.
- Removed the unused list table class.

= 4.4.9 =
- Added nonce check for debug log reset
- Log file name is automatically generated. 
- Added confirmation for log file reset operation.
- Added more sanitization to various request parameters.

= 4.4.8 =
- Sanitize the "edit_record" parameter in the "Edit License" menu. Thanks to WPScan team for pointing it out.
- Sanitize the prefix parameter in the settings menu. Thanks to WPScan team for pointing it out.
- Integration with the WP Express Checkout plugin.

= 4.4.7 =
- It is recommended that you backup your license database before upgrading this version (just to be sure).
- Improved the database query of the manage license page to be more efficient so it loads faster.
- Improved the database query of the manage license page's search function to make it more efficient.
- Added nonce check to the bulk delete action.

= 4.4.6 =
- Added sanitization and nonce check for the settings interface to prevent any potential CSRF attack issue. Thanks to Koken for pointing it out.

= 4.4.5 =
- The IP address is logged in the debug log file for an API request (if debug option is enabled in settings).

= 4.4.4 =
- Added "Add New License" button in the Manage licenses menu.
- PHP Notice in the manage licenses menu fixed.

= 4.4.3 =
- Fixed an issue with the sorting option in the Manage Licenses interface.

= 4.4.2 =
- Added a new filter for the Management Permission constant (so it can be customized by an addon).
- Fixed a wpdb::prepare query with the search feature. Thanks to @Nauriskolats for pointing it out.

= 4.4.1 =
- Fixed a product editing glitch with the WP eStore plugin integration.

= 4.4 = 
- The following UI improvements were submitted by Brian DiChiara. A big Thank You to @solepixel
- Adds domain to license search.
- Retain search term value in search field.
- Better UI for deleting domains:
    Wider domain table.
    Allow for more domains visible in table.
    Easier to click "delete" button.
    Prompt before deleting asking "Are you sure you want to remove this domain?".
    Intuitive post-delete to remove section if no more domains are active.
- Displays total activated domains in Manage Licenses table.

= 4.3 =
- The product quantity of WP eStore product is taken into account when creating a new license key.
- Added a new action hook in the listener API (can be used to override the API query).

= 4.2 =
- Added a new optional column "subscr_id" to the license keys table. This can be used to store the subsriber ID value (if any) for recurring payment plans.
- The "subscr_id" will also be present in the license query API output.

= 4.1 =
- Added a new action hook for estore recurring payments.

= 4.0 =
- Fixed a typo with the slm_api_response_args filter
- The license key is also included in the license check API query's JSON output.

= 3.9 =
- The license status parameter can now be passed when executing the license create API query.

= 3.8 =
- The manage licenses admin interface improvements for mobile devices.
- The product reference (if any) is shown in the manage licenses interface also.

= 3.7 =
- Added couple of filters to the API response args.

= 3.6 =
- The check license query now outputs all the db column values.
- It now captures the WP eStore product ID in the "Product Reference" column of the license manager (if the license is created by eStore).

= 3.5 =
- Updated slm-api-utility.php to add Content-Type header to the API response.

= 3.4 =
- The slm_create_new api call will no longer show an error code incorrectly.

= 3.3 =
- Check for existence of company_name query value before using to fix undefined index error when it doesn't exist.
- Add ability to specify product_ref when creating license via API. Thanks to @maddisondesigns for the update.

= 3.2 =
- Fixed undefined index warning "enable_auto_key_expiry"
- The product_ref field is now available in the add/edit license menu. Also, this value is output with the "slm_check" API call. Thanks to Tobias Hildebrandt for implementing this.

= 3.1 =
- Added a new error code for the following condition:
If maximum activation has reached and the license key is used on the domain (where the request came from) then it will return a new error: LICENSE_IN_USE_ON_DOMAIN_AND_MAX_REACHED

= 3.0 =
- The integration with WP eStore cart will create multiple licenses when a customer purchases more than 1 quantity of a product.

= 2.9 =
- The API response will now include a numeric error code (in the event of an error). Thanks to Steve Gehrman.

= 2.8 =
- The registered domains (if any) of a license key will get deleted when that key is deleted from the manage licenses menu.
- Added wp_unslash() for firstname, lastname, registered domain and company name. Thanks to @sgehrman.
- Added a new action hook (slm_license_key_expired) that gets triggered when a license key expires.

= 2.7 =
- eStore integration update: changed expiry date field to accept number of days so the plugin can dynamically calculate the expiry date for the key.

= 2.6 = 
- Updated the eStore integration so a custom "Expiry Date" value can be set in the product configuration.

= 2.5 =
- Updated the eStore plugin integration so a custom "Maximum Allowed Domains" value can be specified in the eStore product configuration.

= 2.4 =
- Added new action and filter hooks in the add/edit interface so an addon can extend the functionality of that interface.
- Added nonce check in the add/edit license interface.

= 2.3 = 
- Added a new feature to enable auto expiry of the license keys. You can enable this option from the settings.
- If you don't specify a expiry date, when adding a manual license key, it will use the current date plus 1 year as the expiry date.
- Increased the width and height of the "Registered Domains" box in the edit license interface.
- Added a new table column product_ref in the license keys table.
- Added couple of new hooks in the plugin.

= 2.2 =
- Added integration with the squeeze form submission of eStore plugin.

= 2.1 =
- The license check query now outputs the date values also.
- Improvement for the WP eStore integration.

= 2.0 =
- Added a filter to remove any null values from the DB insert query parameter of the API Utility class.

= 1.9 =
- Replaced "esc_url()" with "esc_url_raw()" in the sample plugin. 
- Updated some CSS in the admin interface for WordPress 4.4

= 1.8 =
- Added new hooks before the API query is executed. This allows a developer to override the API query and do custom stuff.
- Added a new API to check the details of an existing license key.

= 1.7 =
* The license key is also included with the response sent to the new license creation request. Below is an example response:
{"result":"success","message":"License successfully created","key":"5580effe188d3"}

* You can now pass a pre-generated license key to the license creation API using the "license_key" parameter in the request.

= 1.6 =
* Updated the sample plugin code so the query works better.
* Added the ability to reset the debug log file from the plugin settings interface.
* The item_reference value will be stored in the database (if sent via the activation API query).

= 1.5 =
* Added the option to search a license key from the manage licenses interface.

= 1.4 =
* Updated the license key creation API check to use the value from "Secret Key for License Creation" field.

= 1.3 =
* Added more sanitization.

= 1.2 =
* Fixed a bug with the bulk delete license operation.

= 1.1 =
* First commit to wordpress repository.

== Upgrade Notice ==
None

== Arbitrary section ==
See the following sample/example for multi-site environment/setup:
https://github.com/paratheme/Software-License-Manager-Multisite-licensed