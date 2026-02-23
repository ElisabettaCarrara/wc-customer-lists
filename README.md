# WC Customer Lists

**Plugin Name:** WC Customer Lists
**Plugin URI:** https://elica-webservices.it/
**Description:** Customer wishlists + event registries with auto-cart for WooCommerce. This plugin allows your customers to create various types of lists, such as wishlists, bridal registries, baby registries, and generic event lists, and manage them directly from their My Account page.

**Version:** 1.0.0
**Author:** Elisabetta Carrara
**Author URI:** https://elica-webservices.it/
**Requires at least:** 6.3
**Tested up to:** 6.6
**Requires PHP:** 8.1
**WC requires at least:** 8.0
**WC tested up to:** 8.7

## Description

WC Customer Lists is a powerful WooCommerce extension that provides flexible list management functionalities for your customers. Whether it's a personal wishlist, a bridal registry for a wedding, a baby registry for a new arrival, or a generic event list, this plugin handles it all.

**Key Features:**

*   **Multiple List Types:** Supports Bridal Lists, Baby Lists, Generic Event Lists, and standard Wishlists.
*   **My Account Integration:** Customers can create, view, edit, and delete their lists and list items directly from their WooCommerce My Account page.
*   **Product Modal:** A convenient "Add to List" button and modal on product pages allow customers to easily add products to their existing lists or create new ones.
*   **Event-Specific Fields:** Event-based lists (Bridal, Baby, Generic Event) include custom fields for event name, date, closing date, and delivery deadline.
*   **Auto-Cart Functionality:** For event lists, products can be automatically added to the customer's cart on a specified closing date.
*   **Admin Settings:** Control which list types are enabled, set limits for the number of lists per user and items per list, and configure "not purchased" item behavior.
*   **Secure & Robust:** Built with WordPress best practices, including nonce verification, input sanitization, and proper escaping.
*   **WooCommerce HPOS Compatible:** Fully compatible with WooCommerce High-Performance Order Storage.

## Installation

1.  **Upload:** Upload the `wc-customer-lists` folder to the `/wp-content/plugins/` directory.
2.  **Activate:** Activate the plugin through the 'Plugins' menu in WordPress.
3.  **Configure:** Navigate to `WooCommerce > Customer Lists` in your WordPress admin dashboard to configure enabled list types and their settings.

## Usage

### For Customers

*   **Adding Products to a List:** On any product page, click the "Add to List" button. A modal will appear, allowing you to select an existing list or create a new one.
*   **Managing Lists:** Log in to your account and go to the "My Account" page. You will find a new "My Lists" tab where you can view, edit, and delete your lists and the products within them.

### For Administrators

*   **Enable/Disable List Types:** Go to `WooCommerce > Customer Lists` to enable or disable specific list types (e.g., Bridal, Baby, Wishlist).
*   **Set Limits:** For each enabled list type, you can set a maximum number of lists a user can create and a maximum number of items per list.
*   **Configure Auto-Cart:** For event-based lists, you can configure how "not purchased" items are handled when the list's closing date is reached (e.g., keep all, remove purchased, purchased only).

## Screenshots

*(Placeholder for future screenshots)*

## Changelog

### 1.0.0 - 2026-02-23
*   Initial Release.
*   Implemented multiple customer list types: Wishlist, Bridal List, Baby List, Generic Event List.
*   Integrated list management into WooCommerce My Account page.
*   Added "Add to List" product modal for easy product addition.
*   Developed auto-cart functionality for event-based lists.
*   Introduced comprehensive admin settings for list configuration and limits.
*   Ensured WooCommerce HPOS compatibility.
*   Refactored uninstall process for robustness and WPCS compliance.
*   Improved WPCS compliance for short array syntax.

## Credits

*   **Author:** Elisabetta Carrara
*   **Website:** https://elica-webservices.it/

## License

This plugin is released under the GPLv2 or later.
