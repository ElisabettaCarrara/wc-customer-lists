<?php
/**
 * WC Customer Lists Main Plugin Class.
 *
 * @package    wc-customer-lists
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Namespaced imports (matches List_Engine).
use WC_Customer_Lists\Core\List_Registry;

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
		$this->includes();
		$this->register_hooks();
	}

	/**
	 * Load required files.
	 *
	 * @since 1.0.0
	 */
	private function includes(): void {
		$core_dir  = __DIR__ . '/core/';
		$lists_dir = __DIR__ . '/lists/';
		$admin_dir = __DIR__ . '/admin/';
		$ajax_dir  = __DIR__ . '/ajax/';
		$ui_dir    = __DIR__ . '/ui/';

		// Core (always).
		require_once $core_dir . 'class-wc-customer-lists-list-engine.php';
		require_once $core_dir . 'class-wc-customer-lists-list-registry.php';

		// Lists (always).
		require_once $lists_dir . 'class-wc-customer-lists-event-list-base.php';
		require_once $lists_dir . 'class-wc-customer-lists-generic-event-list.php';
		require_once $lists_dir . 'class-wc-customer-lists-bridal-list.php';
		require_once $lists_dir . 'class-wc-customer-lists-baby-list.php';
		require_once $lists_dir . 'class-wc-customer-lists-wishlist-base.php';
		require_once $lists_dir . 'class-wc-customer-lists-wishlist.php';

		// AJAX (frontend+admin).
		require_once $ajax_dir . 'class-wc-customer-lists-ajax-handlers.php';

		// UI (frontend).
		require_once $ui_dir . 'class-wc-customer-lists-my-account.php';
		require_once $ui_dir . 'class-wc-customer-lists-product-modal.php';

		// Admin (conditional).
		if ( is_admin() ) {
			require_once $admin_dir . 'class-wc-customer-lists-admin.php';
		}
	}

	/**
	 * Register global hooks.
	 *
	 * @since 1.0.0
	 */
	private function register_hooks(): void {
		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'after_setup_theme', [ $this, 'add_template_hooks' ] );
	}

	/**
	 * Register post types.
	 *
	 * @since 1.0.0
	 */
	public function register_post_types(): void {
		List_Registry::register_post_types(); // ✅ Short name via use import.
	}

	/**
	 * Enqueue frontend scripts/styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts(): void {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style(
			'wc-customer-lists',
			WC_CUSTOMER_LISTS_PLUGIN_URL . "assets/css/wc-customer-lists{$suffix}.css",
			[],
			WC_CUSTOMER_LISTS_VERSION
		);

		wp_enqueue_script(
			'wc-customer-lists',
			WC_CUSTOMER_LISTS_PLUGIN_URL . "assets/js/wc-customer-lists{$suffix}.js",
			[ 'jquery' ],
			WC_CUSTOMER_LISTS_VERSION,
			true
		);

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
	 * Add template/theme hooks.
	 *
	 * @since 1.0.0
	 */
	public function add_template_hooks(): void {
		add_filter( 'woocommerce_account_menu_items', [ $this, 'add_my_account_menu_item' ] );
		add_action( 'woocommerce_account_lists_endpoint', [ $this, 'my_account_lists_content' ] );
		// Add more as UI classes hook in.
	}

}
