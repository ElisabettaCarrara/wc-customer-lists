<?php
/**
 * Plugin Name: WC Customer Lists
 * Plugin URI:  https://elica-webservices.it/
 * Description: Allow customers to create and manage lists, and add products to them. Works with WooCommerce.
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
 * Check if WooCommerce is active.
 */
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins', [] ) ), true ) ) {
    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        esc_html_e( 'WC Customer Lists requires WooCommerce to be active.', 'wc-customer-lists' );
        echo '</p></div>';
    } );
    return; // Stop execution if WooCommerce is not active.
}

/**
 * Define plugin constants.
 */
define( 'WC_CUSTOMER_LISTS_VERSION', '1.0.0' );
define( 'WC_CUSTOMER_LISTS_PLUGIN_FILE', __FILE__ );
define( 'WC_CUSTOMER_LISTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WC_CUSTOMER_LISTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Include main plugin class.
 */
require_once WC_CUSTOMER_LISTS_PLUGIN_DIR . 'includes/class-wc-customer-lists.php';

/**
 * Initialize plugin.
 */
WC_Customer_Lists::get_instance();

/**
 * Activation hook: register CPTs and flush rewrite rules.
 */
function wc_customer_lists_activate(): void {
    // Ensure post types are registered for flush
    WC_Customer_Lists::get_instance()->register_post_types();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wc_customer_lists_activate' );

/**
 * Deactivation hook: flush rewrite rules.
 */
function wc_customer_lists_deactivate(): void {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wc_customer_lists_deactivate' );

/**
 * Load plugin textdomain for translations.
 */
function wc_customer_lists_load_textdomain(): void {
    load_plugin_textdomain(
        'wc-customer-lists',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
}
add_action( 'plugins_loaded', 'wc_customer_lists_load_textdomain' );
