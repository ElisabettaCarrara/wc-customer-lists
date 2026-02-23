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
		// CSS.
		$css_file = WC_CUSTOMER_LISTS_PLUGIN_URL . 'assets/css/wc-customer-lists.css';
		wp_enqueue_style(
			'wc-customer-lists',
			$css_file,
			[],
			WC_CUSTOMER_LISTS_VERSION
		);

		// JS.
		$js_file = WC_CUSTOMER_LISTS_PLUGIN_URL . 'assets/js/wc-customer-lists.js';
		wp_enqueue_script(
			'wc-customer-lists',
			$js_file,
			[ 'jquery' ],
			WC_CUSTOMER_LISTS_VERSION,
			true
		);

		// Nonce + AJAX data.
		wp_localize_script(
			'wc-customer-lists',
			'WCCL_Ajax',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wc_customer_lists_nonce' ),
			]
		);
	}

	/**
	 * Plugin activation: Register CPTs only.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		if ( class_exists( 'WC_Customer_Lists_List_Registry' ) ) {
			WC_Customer_Lists_List_Registry::register_post_types();
		}

// Instantiate My_Account to register endpoint, THEN flush
    if ( class_exists( 'WC_Customer_Lists_My_Account' ) ) {
        $my_account = new WC_Customer_Lists_My_Account();  // Triggers add_endpoint()
        WC_Customer_Lists_My_Account::flush_rules();
    }
		
		// Flush rewrite rules for My Account endpoint.
		flush_rewrite_rules();
	}
}


// Deactivation
register_deactivation_hook( __FILE__, function() {
    require_once WC_CUSTOMER_LISTS_PLUGIN_DIR . 'includes/class-wc-customer-lists.php';
    
    if ( class_exists( 'WC_Customer_Lists' ) ) {
        WC_Customer_Lists::deactivate();
    }
});
 
// Uninstall
register_uninstall_hook( __FILE__, function() {
    require_once WC_CUSTOMER_LISTS_PLUGIN_DIR . 'includes/class-wc-customer-lists.php';
    
    if ( class_exists( 'WC_Customer_Lists' ) ) {
        WC_Customer_Lists::uninstall();
    }
});
