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
	private static ?self $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
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
	private function load_files(): void {
		$base_dir = __DIR__ . '/';

		$files = array(
			// Core.
			'core/class-wc-customer-lists-list-engine.php',
			'core/class-wc-customer-lists-list-registry.php',

			// Lists.
			'lists/class-wc-customer-lists-event-list-base.php',
			'lists/class-wc-customer-lists-generic-event-list.php',
			'lists/class-wc-customer-lists-bridal-list.php',
			'lists/class-wc-customer-lists-baby-list.php',
			'lists/class-wc-customer-lists-wishlist-base.php',
			'lists/class-wc-customer-lists-wishlist.php',

			// AJAX / UI.
			'ajax/class-wc-customer-lists-ajax-handlers.php',
			'ui/class-wc-customer-lists-my-account.php',
			'ui/class-wc-customer-lists-product-modal.php',
		);

		foreach ( $files as $relative_path ) {
			$file = $base_dir . $relative_path;

			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}

		// Admin-only.
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
	private function register_hooks(): void {
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Cron: auto-cart.
		add_action(
			'wc_customer_list_auto_cart',
			array( 'WC_Customer_Lists_Event_List', 'handle_auto_cart' )
		);
	}

	/**
	 * Register post types.
	 *
	 * @since 1.0.0
	 */
	public function register_post_types(): void {
		if ( class_exists( 'WC_Customer_Lists_List_Registry' ) ) {
			WC_Customer_Lists_List_Registry::register_post_types();
		}
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts(): void {
		wp_enqueue_style(
			'wc-customer-lists',
			WC_CUSTOMER_LISTS_PLUGIN_URL . 'assets/css/wc-customer-lists.css',
			array(),
			WC_CUSTOMER_LISTS_VERSION
		);

		wp_enqueue_script(
			'wc-customer-lists',
			WC_CUSTOMER_LISTS_PLUGIN_URL . 'assets/js/wc-customer-lists.js',
			array( 'jquery' ),
			WC_CUSTOMER_LISTS_VERSION,
			true
		);

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
	 * Plugin activation callback.
	 *
	 * @since 1.0.0
	 */
	public static function activate(): void {
		if ( class_exists( 'WC_Customer_Lists_List_Registry' ) ) {
			WC_Customer_Lists_List_Registry::register_post_types();
		}
	}
}
