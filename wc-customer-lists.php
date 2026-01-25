<?php
/**
 * Plugin Name: WC Customer Lists
 * Description: Allow customers to create lists and add products to them.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: wc-customer-lists
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check if WooCommerce is active
 */
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="error"><p>';
        esc_html_e( 'WC Customer Lists requires WooCommerce to be active.', 'wc-customer-lists' );
        echo '</p></div>';
    } );
    return; // Stop execution if Woo is not active
}

/**
 * Include main plugin class
 */
require_once __DIR__ . '/includes/class-wc-customer-lists.php';

/**
 * Initialize main plugin class
 */
WC_Customer_Lists::get_instance();

/**
 * Activation hook
 *
 * - Registers CPTs before flushing rewrite rules
 * - Flushes rewrite rules
 */
function wc_customer_lists_activate() {
    WC_Customer_Lists::get_instance()->register_post_types();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wc_customer_lists_activate' );

/**
 * Deactivation hook
 *
 * - Flushes rewrite rules on deactivation
 */
function wc_customer_lists_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wc_customer_lists_deactivate' );
