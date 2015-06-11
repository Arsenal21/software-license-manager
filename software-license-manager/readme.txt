=== Software License Manager ===
Contributors: Tips and Tricks HQ, Peter Petreski, Ruhul Amin
Donate link: https://www.tipsandtricks-hq.com/software-license-manager-plugin-for-wordpress
Tags: license key, serial key, manager, license, serial, key, selling, sell, license activation, manage license, software license, software license manager
Requires at least: 3.0
Tested up to: 4.2
Stable tag: 1.6
License: GPLv2 or later

Create and manage license keys for your software applications easily

== Description ==

Software license management solution for your web applications (WordPress plugins, Themes, PHP based membership script etc.)

This plugin is very useful for creating a license server and doing the following via API:

- Create license keys in your system (license server)
- Check the status of a license key from from your application (remotely)
- Activate a license key from your application (remotely)
- Deactivate a license key (remotely)
- Track where the license key is being used.

You can also create license keys manually from the admin dashboard of this plugin.

= Please note that this plugin is ONLY for developers =

Check [license manager documentation](https://www.tipsandtricks-hq.com/software-license-manager-plugin-for-wordpress) to learn more.

= Integration with WP eStore =
Check [WP eStore integration documentation](https://www.tipsandtricks-hq.com/ecommerce/integrate-wp-estore-with-software-license-manager-plugin-3731)

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