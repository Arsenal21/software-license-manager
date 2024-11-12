# Welcome to SLM Plus 👋

![Version](https://img.shields.io/github/v/release/michelve/software-license-manager?color=blue)
![Build Status](https://img.shields.io/github/workflow/status/michelve/software-license-manager/Build%20and%20Upload%20Release%20Asset)
[![Documentation](https://img.shields.io/badge/documentation-yes-brightgreen.svg)](https://documenter.getpostman.com/view/307939/6tjU1FL?version=latest)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/michelve/software-license-manager/blob/master/LICENSE.md)


🔐 SLM Plus - Enhanced SLM Plus for WordPress

SLM Plus is a robust and customizable license management plugin for WordPress, built to integrate seamlessly with WooCommerce and WP eStore. Designed to provide comprehensive license generation, validation, and tracking capabilities, SLM Plus simplifies software licensing workflows, ensuring secure, efficient distribution and control of your digital products.

## Key Features
- WooCommerce & WP eStore Compatibility: Fully integrates with both platforms, enabling automated license key generation and management upon product purchase.
- Flexible License Types: Supports varied license models, including subscription-based and lifetime licenses, with adjustable terms and expiration settings.
- Secure API: Offers a secure API for license creation and validation, providing reliable protection for digital goods and software products.
- Advanced Configuration Options: Customize license settings, including device limits, domain constraints, and renewal reminders, all from a centralized admin interface.
- Bulk License Generation: Efficiently issue licenses for past WooCommerce orders with the "Generate Licenses" tool, ensuring complete licensing coverage across all sales.

SLM Plus is the ideal solution for developers, digital product vendors, and businesses seeking a powerful, easy-to-manage license manager that scales with growth.

### 🏠 [Homepage](https://github.com/michelve/software-license-manager#readme)

## 🔧 Install

```text
1. Go to the Add New Plugins screen in your WordPress admin area
2. Click the upload tab
3. Browse for the plugin file (slm-plus.zip)
4. Click Install Now and then activate the plugin
```

### Sample Files Overview
- **CoreConfig.php**: Sets up global constants and utility methods for API requests and responses.
- **LicenseAPI.php**: Provides core methods for each license management action, using `CoreConfig.php` for secure requests.
- **Action Files**:
  - `CreateLicense.php`: Handles license creation.
  - `ActivateLicense.php`: Activates a license for a specific domain or device.
  - `DeactivateLicense.php`: Manages deactivation of a license.
  - `CheckLicense.php`: Checks the current status of a license.
  - `GetLicenseInfo.php`: Retrieves detailed information about a license.

Refer to each [wiki page](https://github.com/michelve/software-license-manager/wiki) for in-depth guides on using these files.


## Author

👤 **Michel Velis and Tips and Tricks HQ**

-   Github: [@michelve](https://github.com/michelve)

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!

Feel free to check [issues page](https://github.com/michelve/software-license-manager/issues).

## Show your support

Give a ⭐️ if this project helped you!

## 📓 Postman samples:

[API Demo and Samples:](https://documenter.getpostman.com/view/307939/6tjU1FL?version=latest)

## 📦SLM Plus Features

- **Create License Keys**: Easily generate unique license keys for applications.
- **Remote License Management**:
  - Remotely **check**, **activate**, **deactivate**, **update**, and **delete** license keys from within your application.
  - **Track status**, **activation dates**, and **usage locations** for each license key.
- **License Activity Monitoring**:
  - View detailed **usage logs** and **activation history** for each license key.
  - Monitor **requests** and **activities** associated with each license.
- **Manual and Bulk License Creation**:
  - Manually create licenses from the admin dashboard.
  - **Bulk license generation** for WooCommerce orders, including orders placed before plugin activation.
- **WooCommerce Integration**:
  - **Attach license data** directly to WooCommerce orders and display details within each order.
  - Support for **custom WooCommerce product types** related to license management.
- **User and Admin Features**:
  - **Admin widgets** for license stats and key metrics.
  - **Export licenses** for both admins and users.
  - Allow users to **view**, **activate**, and **manage licenses** from their WooCommerce “My Account” page.
- **Enhanced License Management**:
  - **Bulk actions** support for efficient license handling.
  - **View licenses by subscriber** and access detailed activity logs per license.
- **Notification and Expiration Management**:
  - **Email notifications** for expiration, activation, and renewal reminders.
  - Configure **custom expiration terms** and automate reminders for users.
- **Multilingual Support**: Available in **English** and **Spanish** with additional language support planned.
- **Admin Tools and Security**:
  - Flexible **API endpoints** for integration.
  - Enhanced **security measures** and **data verification** on each action for safe data handling.
  
This feature set offers complete license management for WordPress and WooCommerce environments, providing enhanced control, security, and visibility for admins and end-users.


## ✅ Compatibility

-   [-] Woocommerce
-   [-] WP eStore
-   [-] WP Download Manager

## 🕘 Changelog and history

Changelog: [View changelog](https://github.com/michelve/software-license-manager/blob/master/CHANGELOG.md)

## 📄 Documentation and Wiki

For a detailed guide on each action, refer to the new wiki pages.

## 🎑 Screenshots

<img src="https://raw.githubusercontent.com/michelve/software-license-manager/master/public/assets/images/previews/1.png?raw=true" width="800" alt="SLM Plus" />

<img src="https://raw.githubusercontent.com/michelve/software-license-manager/master/public/assets/images/previews/2.png?raw=true" width="800" alt="SLM Plus" />

<img src="https://raw.githubusercontent.com/michelve/software-license-manager/master/public/assets/images/previews/3.png?raw=true" width="800" alt="SLM Plus" />

<img src="https://raw.githubusercontent.com/michelve/software-license-manager/master/public/assets/images/previews/4.png?raw=true" width="800" alt="SLM Plus" />

<img src="https://raw.githubusercontent.com/michelve/software-license-manager/master/public/assets/images/previews/5.png?raw=true" width="800" alt="SLM Plus" />

<img src="https://raw.githubusercontent.com/michelve/software-license-manager/master/public/assets/images/previews/6.png?raw=true"  width="800" alt="SLM Plus" />

<img src="https://raw.githubusercontent.com/michelve/software-license-manager/master/public/assets/images/previews/7.png?raw=true" width="800" alt="SLM Plus" />

<img src="https://raw.githubusercontent.com/michelve/software-license-manager/master/public/assets/images/previews/8.png?raw=true" width="800" alt="SLM Plus" />

<img src="https://raw.githubusercontent.com/michelve/software-license-manager/master/public/assets/images/previews/9.png?raw=true" width="800" alt="SLM Plus" />

<img src="https://raw.githubusercontent.com/michelve/software-license-manager/master/public/assets/images/previews/10.png?raw=true" width="800" alt="SLM Plus" />

<img src="https://raw.githubusercontent.com/michelve/software-license-manager/master/public/assets/images/previews/11.png?raw=true" width="800" alt="SLM Plus" />

<img src="https://raw.githubusercontent.com/michelve/software-license-manager/master/public/assets/images/previews/12.png?raw=true" width="800" alt="SLM Plus" />

<img src="https://raw.githubusercontent.com/michelve/software-license-manager/master/public/assets/images/previews/13.png?raw=true" width="800" alt="SLM Plus" />

<img src="https://raw.githubusercontent.com/michelve/software-license-manager/master/public/assets/images/previews/14.png?raw=true" width="800" alt="SLM Plus" />

## 📝 License

Copyright © 2024 [Michel Velis and Tips and Tricks HQ](https://github.com/michelve).

This project is [MIT](https://github.com/michelve/software-license-manager/blob/master/LICENSE.md) licensed.