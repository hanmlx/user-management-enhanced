# User Management Enhanced Plugin

A WordPress plugin that provides enhanced user management features, including role conversion, KBIS document management, and user editing capabilities.

## Features

- **User Management Panel**: Access all subscriber and customer users in one place
- **Role Conversion**: Easily convert subscribers to customers with one click
- **Automatic Welcome Emails**: WooCommerce welcome emails are automatically sent when converting users to customers
- **User Editing**: Edit user information including personal details and billing addresses
- **KBIS Document Management**: View and manage KBIS (commercial registration) documents
- **Role Filtering**: Filter users by role (Subscriber, Customer)
- **Pagination**: Navigate through large user lists efficiently
- **Manual Email Sending**: Manually send welcome emails to customers

## Requirements

- WordPress 5.0 or higher
- WooCommerce plugin (required)
- Advanced Custom Fields (ACF) plugin (required)
- PHP 7.0 or higher

## Installation

1. Download the plugin files
2. Upload `user-management-enhanced` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Ensure WooCommerce and ACF plugins are installed and activated

## Usage

### Accessing the User Management Panel

After activation, a new menu item "User Management" (用户管理) will appear in your WordPress admin panel. The plugin creates two main sections:

1. **User Management** - Main user list and management
2. **KBIS Management** - KBIS document management (admin only)

### User Management Panel

The main user management panel includes:

- **User List**: View all subscribers and customers with their information
- **Convert to Customer**: Convert subscribers to customer role
- **Edit User**: Click to expand and edit user details
- **View KBIS**: View the user's KBIS document if available
- **Filter by Role**: Filter users by their role
- **Pagination**: Navigate through pages of users

#### Role Conversion

When you convert a user from subscriber to customer:
1. The user's role is changed to "customer"
2. A WooCommerce welcome email is automatically sent
3. A success message is displayed

#### Editing Users

Click the "Edit User" button to:
- Update user's first name, last name, and email
- Update billing address fields:
  - First Name / Last Name
  - Company
  - Address Line 1 / Address Line 2
  - City
  - Postcode
  - Country
  - State/Province
  - Phone
  - Billing Email

#### Manual Email Sending

For customer accounts, you can manually send a WooCommerce welcome email using the "Send Welcome Email Manually" button in the edit form.

### KBIS File Management

Only administrators with `manage_options` capability can access the KBIS Management section:

- **View All KBIS Files**: See all users with uploaded KBIS documents
- **File Information**: View file name, size, and upload date
- **Delete KBIS**: Remove KBIS documents from users
- **Replace KBIS**: Upload new KBIS documents to replace existing ones

#### Supported File Formats

- PDF (.pdf)
- Images (.jpg, .jpeg, .png)
- Documents (.doc, .docx)
- Spreadsheets (.xls, .xlsx)

## Permissions

### User Management Panel
- **Shop Managers**: Can access and manage users
- **Administrators**: Full access

### KBIS Management
- **Administrators Only**: Only users with `manage_options` capability can access

## Text Domain

The plugin uses the text domain `user-management-enhanced` for internationalization. Translation files should be placed in the `/languages/` directory.

## Changelog

### Version 2.0
- Initial release with user management features
- KBIS document management
- Role conversion functionality
- WooCommerce email integration

## Support

For support, please contact the plugin author or post on the WordPress support forums.

## License

This plugin is licensed under GPL v2 or later.

## Credits

Developed for WordPress with WooCommerce and ACF integration.
