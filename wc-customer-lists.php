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

/*
|--------------------------------------------------------------------------
| Dependency Check
|--------------------------------------------------------------------------
*/
if ( ! class_exists( 'WooCommerce' ) ) {
	add_action( 'admin_notices', static function(): void {
		?>
		<div class="notice notice-error">
			<p><?php 
			esc_html_e( 'WC Customer Lists requires WooCommerce to be installed and active.', 'wc-customer-lists' );
			?></p>
		</div>
		<?php
	} );
	return;
}

/*
|--------------------------------------------------------------------------
| Plugin Constants
|--------------------------------------------------------------------------
*/
if ( ! defined( 'WC_CUSTOMER_LISTS_VERSION' ) ) {
	define( 'WC_CUSTOMER_LISTS_VERSION', '1.0.0' );
}
if ( ! defined( 'WC_CUSTOMER_LISTS_PLUGIN_FILE' ) ) {
	define( 'WC_CUSTOMER_LISTS_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'WC_CUSTOMER_LISTS_PLUGIN_DIR' ) ) {
	define( 'WC_CUSTOMER_LISTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WC_CUSTOMER_LISTS_PLUGIN_URL' ) ) {
	define( 'WC_CUSTOMER_LISTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/*
|--------------------------------------------------------------------------
| Load Main Class
|--------------------------------------------------------------------------
*/
$main_class_file = WC_CUSTOMER_LISTS_PLUGIN_DIR . 'includes/class-wc-customer-lists.php';
if ( ! file_exists( $main_class_file ) ) {
	return;
}
require_once $main_class_file;

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/
add_action( 'plugins_loaded', static function(): void {
	load_plugin_textdomain(
		'wc-customer-lists',
		false,
		dirname( plugin_basename( WC_CUSTOMER_LISTS_PLUGIN_FILE ) ) . '/languages'
	);

	WC_Customer_Lists::get_instance();
} );

/*
|--------------------------------------------------------------------------
| Activation / Deactivation
|--------------------------------------------------------------------------
*/
register_activation_hook( __FILE__, static function(): void {
	WC_Customer_Lists::activate();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, static function(): void {
	flush_rewrite_rules();
} );
