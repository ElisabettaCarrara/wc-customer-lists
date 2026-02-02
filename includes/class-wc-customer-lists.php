<?php
/**
 * WC Customer Lists main plugin class.
 *
 * Orchestrates includes, core hooks, and cron.
 *
 * @package WC_Customer_Lists
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

final class WC_Customer_Lists {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->load_files();
		$this->register_hooks();
	}

	/**
	 * Load required plugin files in a deterministic order.
	 *
	 * @since 1.0.0
	 */
	private function load_files() {
		$base_dir = WC_CUSTOMER_LISTS_PLUGIN_DIR . 'includes/';

		$files = array(
			// Core (must load first).
			'core/class-wc-customer-lists-list-registry.php',
			'core/class-wc-customer-lists-list-engine.php',

			// List bases.
			'lists/class-wc-customer-lists-event-list-base.php',
			'lists/class-wc-customer-lists-wishlist-base.php',

			// Concrete lists.
			'lists/class-wc-customer-lists-baby-list.php',
			'lists/class-wc-customer-lists-bridal-list.php',
			'lists/class-wc-customer-lists-generic-event-list.php',
			'lists/class-wc-customer-lists-wishlist.php',

			// AJAX / UI.
			'ajax/class-wc-customer-list-ajax-handlers.php',
			'ui/class-wc-customer-lists-product-modal.php',
			'ui/class-wc-customer-lists-my-account.php',
		);

		foreach ( $files as $relative_path ) {
			$file = $base_dir . $relative_path;

			if ( file_exists( $file ) ) {
				require_once $file;
			} else {
				// Log missing for debug.
				error_log( "WC Customer Lists: Missing file {$file}" );
			}
		}

		// Admin-only (lazy load).
		if ( is_admin() ) {
			$admin_file = $base_dir . 'admin/class-wc-customer-lists-admin.php';
			if ( file_exists( $admin_file ) ) {
				require_once $admin_file;
			}
		}
	}

	/**
	 * Register global hooks.
	 *
	 * @since 1.0.0
	 */
	private function register_hooks() {
		add_action( 'init', array( $this, 'register_post_types' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 5 );

		// Instantiate components.
		$this->init_components();

		// Cron: auto-cart for ALL event lists.
		add_action( 'wc_customer_list_auto_cart', array( 'WC_Customer_Lists_Event_List_Base', 'handle_auto_cart' ) );
	}

	/**
	 * Initialize plugin components.
	 *
	 * @since 1.0.0
	 */
	private function init_components() {
		// AJAX handlers (always).
		if ( class_exists( 'WC_Customer_List_Ajax_Handlers' ) ) {
			new WC_Customer_List_Ajax_Handlers();
		}

		// Admin (settings).
		if ( is_admin() && class_exists( 'WC_Customer_Lists_Admin' ) ) {
			new WC_Customer_Lists_Admin();
		}

		// My Account (always load to register endpoint).
		if ( class_exists( 'WC_Customer_Lists_My_Account' ) ) {
			new WC_Customer_Lists_My_Account();
		}

		// Product Modal (logged-in only).
		if ( ! is_admin() && is_user_logged_in() ) {
			if ( class_exists( 'WC_Customer_Lists_Product_Modal' ) ) {
				new WC_Customer_Lists_Product_Modal();
			}
		}
	}

	/**
	 * Register post types via Registry.
	 *
	 * @since 1.0.0
	 */
	public function register_post_types() {
		if ( class_exists( 'WC_Customer_Lists_List_Registry' ) ) {
			WC_Customer_Lists_List_Registry::register_post_types();
		}
	}

	/**
	 * Enqueue frontend assets + localize data (FIXED).
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
    // Load ONLY where needed.
    if ( ! ( is_product() || is_account_page() || is_shop() || is_product_category() || is_product_tag() ) ) {
        return;
    }

    // Load Dashicons on frontend
    wp_enqueue_style( 'dashicons' );

		// CSS.
		$css_file = WC_CUSTOMER_LISTS_PLUGIN_URL . 'assets/css/wc-customer-lists.css';
		wp_enqueue_style(
			'wc-customer-lists',
			$css_file,
			array(),
			WC_CUSTOMER_LISTS_VERSION
		);

		// JS.
		$js_file = WC_CUSTOMER_LISTS_PLUGIN_URL . 'assets/js/wc-customer-lists.js';
		wp_enqueue_script(
			'wc-customer-lists',
			$js_file,
			array( 'jquery' ),
			WC_CUSTOMER_LISTS_VERSION,
			true
		);

		// Nonce + AJAX data.
		wp_localize_script(
			'wc-customer-lists',
			'WCCL_Ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wc_customer_lists_nonce' ),
			)
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
		
		// Flush rewrite rules for My Account endpoint.
		flush_rewrite_rules();
	}
}
