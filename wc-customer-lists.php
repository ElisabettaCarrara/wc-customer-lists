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
 * @package    wc-customer-lists
 * @author     Elisabetta Carrara
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abort early if WooCommerce is not active.
 */
if ( ! in_array(
	'woocommerce/woocommerce.php',
	apply_filters( 'active_plugins', get_option( 'active_plugins', [] ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.array_casting_array_cast -- Strict needle.
	true
) ) {
	add_action(
		'admin_notices',
		static function () {
			/* translators: %s: Plugin name. */
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'WC Customer Lists requires WooCommerce to be active.', 'wc-customer-lists' )
			);
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
 * IMPORTANT: This file MUST exist and MUST NOT fatal.
 */
require_once WC_CUSTOMER_LISTS_PLUGIN_DIR . 'includes/class-wc-customer-lists.php';

/**
 * Initialize plugin.
 */
add_action(
	'plugins_loaded',
	static function () {
		load_plugin_textdomain( // Merged for efficiency.
			'wc-customer-lists',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		WC_Customer_Lists::get_instance();
	}
);

/**
 * Plugin activation hook.
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
 * Plugin uninstall hook (optional cleanup).
 */
function wc_customer_lists_uninstall(): void {
	// Delete options, transients, etc. here if needed.
	// e.g., delete_option( 'wc_customer_lists_settings' );
}
register_uninstall_hook( __FILE__, 'wc_customer_lists_uninstall' );
