# CHANGELOG
All notable changes to this project will be documented in this file.

#### 4.7 - WIP
- The product quantity of WP eStore product is taken into account when creating a new license key.
- Added a new action hook in the listener API (can be used to override the API query).



#### 4.6
- added: new error codes to api listener
- added: export functionality for license
- added: form validation when creating a new license in wp admin
- fixes: cleaned code and improved ui
- added: license key is saved inside wc order as a custom field value
- added: license key added as a click-able note
- fixed: email notification when order is completed
- added: my subscriptions table to thank you order page
- fixed: license generation for wp-stores


#### 4.5
- added: plugin updater helper

#### 4.4
- improved: simple product meta boxes
- added license manager as product type
- added support for "subscr_id" from version 4.3
- added support for version number
- added license type (lifetime or subscription) :: "lic_type"
- improved license key generator
- fixed minor bug and performance issues
- update wp icon and menu name
- updated meta boxes (simple products)
- [] WIP: support for variations

#### 4.3
- Added a new action hook for estore recurring payments.
- BUG FIX: Sanitize DB query value before using (injection) thanks to @eighty20results
- ENHANCEMENT: Added slm_update action handler - update_api_listener() thanks to @eighty20results
- Added a new optional column "subscr_id" to the license keys table. This can be used to store the subsriber ID value (if any) for recurring payment plans.
- The "subscr_id" will also be present in the license query API output.
- The product quantity of WP eStore product is taken into account when creating a new license key.

#### 4.2
- Added: Support for license removal using api (slm_action=slm_remove)

#### 4.1
- Added: New action hook added.

#### 4.0
- Fixed a typo with the slm_api_response_args filter

#### 3.9
- The license status parameter can now be passed when executing the license create API query.

####  3.8
- The manage licenses admin interface improvements for mobile devices.
- The product reference (if any) is shown in the manage licenses interface also.

####  3.7
- Added couple of filters to the API response args.

####  3.6
- The check license query now outputs all the db column values.
- It now captures the WP eStore product ID in the \"Product Reference\" column of the license manager (if the license is created by eStore).

####  3.5
- Updated slm-api-utility.php to add Content-Type header to the API response.

####  3.4
- The slm_create_new api call will no longer show an error code incorrectly.

####  3.3
- Check for existence of company_name query value before using to fix undefined index error when it doesn\'t exist.
- Add ability to specify product_ref when creating license via API. Thanks to @maddisondesigns for the update.

####  3.2
- Fixed undefined index warning \"enable_auto_key_expiry\"
- The product_ref field is now available in the add/edit license menu. Also, this value is output with the \"slm_check\" API call. Thanks to Tobias Hildebrandt for implementing this.

####  3.1
- Added a new error code for the following condition:
If maximum activation has reached and the license key is used on the domain (where the request came from) then it will return a new error: LICENSE_IN_USE_ON_DOMAIN_AND_MAX_REACHED

####  3.0
- The integration with WP eStore cart will create multiple licenses when a customer purchases more than 1 quantity of a product.

####  2.9
- The API response will now include a numeric error code (in the event of an error). Thanks to Steve Gehrman.

####  2.8
- The registered domains (if any) of a license key will get deleted when that key is deleted from the manage licenses menu.
- Added wp_unslash() for firstname, lastname, registered domain and company name. Thanks to @sgehrman.
- Added a new action hook (slm_license_key_expired) that gets triggered when a license key expires.

####  2.7
- eStore integration update: changed expiry date field to accept number of days so the plugin can dynamically calculate the expiry date for the key.

####  2.6
- Updated the eStore integration so a custom \"Expiry Date\" value can be set in the product configuration.

####  2.5
- Updated the eStore plugin integration so a custom \"Maximum Allowed Domains\" value can be specified in the eStore product configuration.

####  2.4
- Added new action and filter hooks in the add/edit interface so an addon can extend the functionality of that interface.
- Added nonce check in the add/edit license interface.

####  2.3
- Added a new feature to enable auto expiry of the license keys. You can enable this option from the settings.
- If you don\'t specify a expiry date, when adding a manual license key, it will use the current date plus 1 year as the expiry date.
- Increased the width and height of the \"Registered Domains\" box in the edit license interface.
- Added a new table column product_ref in the license keys table.
- Added couple of new hooks in the plugin.

#### 2.2
- Added integration with the squeeze form submission of eStore plugin.

####  2.1
- The license check query now outputs the date values also.
- Improvement for the WP eStore integration.

####  2.0
- Added a filter to remove any null values from the DB insert query parameter of the API Utility class.

####  1.9
- Replaced \"esc_url()\" with \"esc_url_raw()\" in the sample plugin.
- Updated some CSS in the admin interface for WordPress 4.4

####  1.8
- Added new hooks before the API query is executed. This allows a developer to override the API query and do custom stuff.
- Added a new API to check the details of an existing license key.

####  1.7
* The license key is also included with the response sent to the new license creation request. Below is an example response:
{\"result\":\"success\",\"message\":\"License successfully created\",\"key\":\"5580effe188d3\"}

* You can now pass a pre-generated license key to the license creation API using the \"license_key\" parameter in the request.

#### 1.6
* Updated the sample plugin code so the query works better.
* Added the ability to reset the debug log file from the plugin settings interface.
* The item_reference value will be stored in the database (if sent via the activation API query).

####  1.5
* Added the option to search a license key from the manage licenses interface.

####  1.4
* Updated the license key creation API check to use the value from \"Secret Key for License Creation\" field.

####  1.3
* Added more sanitization.

####  1.2
* Fixed a bug with the bulk delete license operation.

####  1.1
* First commit to wordpress repository.
