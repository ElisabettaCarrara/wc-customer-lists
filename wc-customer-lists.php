<?php
/**
 * Plugin Name: WC Customer Lists
 * Plugin URI:  https://elica-webservices.it/
 * Description: Allow customers to create and manage lists (wishlists, event lists, etc.) and add products to them.
 * Version:     1.0.0
 * Author:      Elisabetta Carrara
 * Author URI:  https://elica-webservices.it/
 * Text Domain: wc-customer-lists
 * Domain Path: /languages
 *
 * Requires at least: 6.0
 * Tested up to: 6.6
 * Requires PHP: 8.2
 *
 * WC requires at least: 7.0
 * WC tested up to: 8.3
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abort early if WooCommerce is not active.
 */
if ( ! in_array(
	'woocommerce/woocommerce.php',
	(array) apply_filters( 'active_plugins', get_option( 'active_plugins', [] ) ),
	true
) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'WC Customer Lists requires WooCommerce to be active.', 'wc-customer-lists' );
			echo '</p></div>';
		}
	);
	return;
}

/**
 * Plugin constants.
 */
define( 'WC_CUSTOMER_LISTS_VERSION', '1.0.0' );
define( 'WC_CUSTOMER_LISTS_PLUGIN_FILE', __FILE__ );
define( 'WC_CUSTOMER_LISTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WC_CUSTOMER_LISTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load main plugin class.
 *
 * IMPORTANT:
 * This file MUST exist and MUST NOT fatal.
 */
require_once WC_CUSTOMER_LISTS_PLUGIN_DIR . 'includes/class-wc-customer-lists.php';

/**
 * Initialize plugin.
 *
 * We do this immediately so CPTs and hooks are registered.
 */
add_action(
	'plugins_loaded',
	static function () {
		WC_Customer_Lists::get_instance();
	}
);

/**
 * Plugin activation hook.
 *
 * - Registers CPTs
 * - Flushes rewrite rules
 */
function wc_customer_lists_activate(): void {
	$plugin = WC_Customer_Lists::get_instance();
	$plugin->register_post_types();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wc_customer_lists_activate' );

/**
 * Plugin deactivation hook.
 */
function wc_customer_lists_deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wc_customer_lists_deactivate' );

/**
 * Load translations.
 */
add_action(
	'plugins_loaded',
	static function () {
		load_plugin_textdomain(
			'wc-customer-lists',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}
);
