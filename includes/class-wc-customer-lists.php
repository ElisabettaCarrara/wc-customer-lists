<?php
/**
 * WC Customer Lists — main plugin class.
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

	// ── Singleton ────────────────────────────────────────────────────────────

	/**
	 * Get singleton instance.
	 *
	 * @since  1.0.0
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor — load files, then register hooks.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->load_files();
		$this->register_hooks();
	}

	// ── File loading ─────────────────────────────────────────────────────────

	/**
	 * Load required plugin files in a deterministic order.
	 *
	 * @since 1.0.0
	 */
	private function load_files(): void {
		$base_dir = WC_CUSTOMER_LISTS_PLUGIN_DIR . 'includes/';

		$files = [
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
		];

		foreach ( $files as $relative_path ) {
			$file = $base_dir . $relative_path;

			if ( file_exists( $file ) ) {
				require_once $file;
			} else {
				error_log( "WC Customer Lists: Missing file {$file}" );
			}
		}

		// Admin-only files (lazy load).
		if ( is_admin() ) {
			$admin_file = $base_dir . 'admin/class-wc-customer-lists-admin.php';
			if ( file_exists( $admin_file ) ) {
				require_once $admin_file;
			}
		}
	}

	// ── Hook registration ─────────────────────────────────────────────────────

	/**
	 * Register all global hooks.
	 *
	 * @since 1.0.0
	 */
	private function register_hooks(): void {
		add_action( 'init',               [ $this, 'register_post_types' ], 5 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ],     20 );

		// Instantiate UI/AJAX components.
		$this->init_components();

		// Cron: auto-cart for all event lists.
		add_action( 'wc_customer_list_auto_cart', [ 'WC_Customer_Lists_Event_List_Base', 'handle_auto_cart' ] );
	}

	/**
	 * Instantiate plugin components.
	 *
	 * Called once during construction, after all files are loaded.
	 *
	 * @since 1.0.0
	 */
	private function init_components(): void {
		// AJAX handlers — always needed (handles both logged-in and guest requests).
		if ( class_exists( 'WC_Customer_List_Ajax_Handlers' ) ) {
			new WC_Customer_List_Ajax_Handlers();
		}

		// Admin panel.
		if ( is_admin() && class_exists( 'WC_Customer_Lists_Admin' ) ) {
			new WC_Customer_Lists_Admin();
		}

		// My Account — always instantiated to register the endpoint.
		if ( class_exists( 'WC_Customer_Lists_My_Account' ) ) {
			new WC_Customer_Lists_My_Account();
		}

		// Product modal — frontend only, logged-in users only.
		if ( ! is_admin() && is_user_logged_in() && class_exists( 'WC_Customer_Lists_Product_Modal' ) ) {
			new WC_Customer_Lists_Product_Modal();
		}
	}

	// ── Public action callbacks ───────────────────────────────────────────────

	/**
	 * Register custom post types via the List Registry.
	 *
	 * Hooked to `init` at priority 5.
	 *
	 * @since 1.0.0
	 */
	public function register_post_types(): void {
		if ( class_exists( 'WC_Customer_Lists_List_Registry' ) ) {
			WC_Customer_Lists_List_Registry::register_post_types();
		}
	}

	/**
	 * Enqueue frontend CSS, JS and AJAX localisation data.
	 *
	 * Loads only on pages where the plugin UI is actually needed.
	 * Hooked to `wp_enqueue_scripts` at priority 20.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts(): void {
		if ( ! ( is_product() || is_account_page() || is_shop() || is_product_category() || is_product_tag() ) ) {
			return;
		}

		// Dashicons (used by plugin UI on the frontend).
		wp_enqueue_style( 'dashicons' );

		// CSS.
		wp_enqueue_style(
			'wc-customer-lists',
			WC_CUSTOMER_LISTS_PLUGIN_URL . 'assets/css/wc-customer-lists.css',
			[],
			WC_CUSTOMER_LISTS_VERSION
		);

		// JS.
		wp_enqueue_script(
			'wc-customer-lists',
			WC_CUSTOMER_LISTS_PLUGIN_URL . 'assets/js/wc-customer-lists.js',
			[ 'jquery' ],
			WC_CUSTOMER_LISTS_VERSION,
			true  // Load in footer.
		);

		// Pass AJAX URL + nonce to JS.
		wp_localize_script(
			'wc-customer-lists',
			'WCCL_Ajax',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wc_customer_lists_nonce' ),
			]
		);
	}

	// ── Lifecycle hooks ───────────────────────────────────────────────────────

	/**
	 * Plugin activation.
	 *
	 * Registers CPTs and flushes rewrite rules so the My Account
	 * endpoint is immediately available.
	 * Called from `register_activation_hook` in the root file.
	 *
	 * @since 1.0.0
	 */
	public static function activate(): void {
		// Register CPTs so their rewrite slugs exist before flushing.
		if ( class_exists( 'WC_Customer_Lists_List_Registry' ) ) {
			WC_Customer_Lists_List_Registry::register_post_types();
		}

		// Instantiate My Account to trigger add_rewrite_endpoint(), then flush.
		if ( class_exists( 'WC_Customer_Lists_My_Account' ) ) {
			new WC_Customer_Lists_My_Account();
			WC_Customer_Lists_My_Account::flush_rules();
		}

		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 *
	 * Flushes rewrite rules so the My Account endpoint is removed cleanly.
	 * Called from `register_deactivation_hook` in the root file.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	/**
	 * Plugin uninstallation.
	 *
	 * Deletes all CPT posts, plugin options, and scheduled cron events.
	 * Called from `register_uninstall_hook` in the root file.
	 *
	 * @since 1.0.0
	 */
	public static function uninstall(): void {
		if ( ! defined( 'ABSPATH' ) ) {
			return;
		}

		// Delete all posts belonging to plugin CPTs.
		$post_types = [ 'wc_baby_list', 'wc_bridal_list', 'wc_event_list', 'wc_wishlist' ];

		foreach ( $post_types as $post_type ) {
			$posts = get_posts( [
				'post_type'   => $post_type,
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',  // IDs only for performance.
			] );

			foreach ( $posts as $post_id ) {
				wp_delete_post( $post_id, true );  // Force-delete, bypass trash.
			}
		}

		// Remove plugin options.
		delete_option( 'wc_customer_lists_settings' );

		// Clear all scheduled cron events.
		wp_clear_scheduled_hook( 'wc_customer_list_auto_cart' );
	}
}
