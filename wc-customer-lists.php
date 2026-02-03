<?php
/**
 * Plugin Name: WC Customer Lists
 * Plugin URI: https://elica-webservices.it/
 * Description: Customer wishlists + event registries with auto-cart.
 * Version: 1.0.0
 * Author: Elisabetta Carrara
 * Author URI: https://elica-webservices.it/
 * Text Domain: wc-customer-lists
 * Domain Path: /languages
 * WC requires at least: 8.0
 * WC tested up to: 8.7
 * Requires at least: 6.3
 * Tested up to: 6.6
 * Requires PHP: 8.1
 */

defined( 'ABSPATH' ) || exit;

// Constants
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

// WooCommerce HPOS compatibility
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});

// Bootstrap
add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>' . 
                 esc_html__( 'WC Customer Lists requires WooCommerce to be active.', 'wc-customer-lists' ) . 
                 '</p></div>';
        });
        return;
    }

    load_plugin_textdomain( 
        'wc-customer-lists', 
        false, 
        dirname( plugin_basename( WC_CUSTOMER_LISTS_PLUGIN_FILE ) ) . '/languages' 
    );

    $main_file = WC_CUSTOMER_LISTS_PLUGIN_DIR . 'includes/class-wc-customer-lists.php';
    if ( file_exists( $main_file ) ) {
        require_once $main_file;
        
        if ( class_exists( 'WC_Customer_Lists' ) ) {
            WC_Customer_Lists::get_instance();
        }
    }
}, 10 );

// Activation
register_activation_hook( __FILE__, function() {
    $main_file = WC_CUSTOMER_LISTS_PLUGIN_DIR . 'includes/class-wc-customer-lists.php';
    
    if ( file_exists( $main_file ) ) {
        require_once $main_file;
        
        if ( class_exists( 'WC_Customer_Lists' ) ) {
            WC_Customer_Lists::activate();
        }
    }
    
});
