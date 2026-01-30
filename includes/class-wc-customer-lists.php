<?php
/**
 * WC Customer Lists Main Plugin Class.
 *
 * Orchestrates includes, core hooks, cron.
 *
 * @package    wc-customer-lists
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Core namespace imports.
use WC_Customer_Lists\Core\List_Registry;
use WC_Customer_Lists\Lists\Event_List;

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

		// Core.
		require_once $core_dir . 'class-wc-customer-lists-list-engine.php';
		require_once $core_dir . 'class-wc-customer-lists-list-registry.php';

		// Lists.
		require_once $lists_dir . 'class-wc-customer-lists-event-list-base.php';
		require_once $lists_dir . 'class-wc-customer-lists-generic-event-list.php';
		require_once $lists_dir . 'class-wc-customer-lists-bridal-list.php';
		require_once $lists_dir . 'class-wc-customer-lists-baby-list.php';
		require_once $lists_dir . 'class-wc-customer-lists-wishlist-base.php';
		require_once $lists_dir . 'class-wc-customer-lists-wishlist.php';

		// AJAX/UI.
		require_once $ajax_dir . 'class-wc-customer-lists-ajax-handlers.php';
		require_once $ui_dir . 'class-wc-customer-lists-my-account.php';
		require_once $ui_dir . 'class-wc-customer-lists-product-modal.php';

		// Admin.
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

		// CRON: Auto-cart (critical!).
		add_action( 'wc_customer_list_auto_cart', [ Event_List::class, 'handle_auto_cart' ] );
	}

	/**
	 * Register post types.
	 *
	 * @since 1.0.0
	 */
	public function register_post_types(): void {
		List_Registry::register_post_types();
	}

	/**
 * Enqueue frontend assets (unminified).
 *
 * @since 1.0.0
 */
public function enqueue_scripts(): void {
	wp_enqueue_style(
		'wc-customer-lists',
		WC_CUSTOMER_LISTS_PLUGIN_URL . 'includes/assets/css/wc-customer-lists.css',
		[],
		WC_CUSTOMER_LISTS_VERSION
	);

	wp_enqueue_script(
		'wc-customer-lists',
		WC_CUSTOMER_LISTS_PLUGIN_URL . 'includes/assets/js/wc-customer-lists.js',
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
}
