<?php
/**
 * WC Customer Lists Main Plugin Class.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

final class WC_Customer_Lists {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->register_hooks();
    }

    /**
     * Load required files.
     */
    private function includes(): void {

        // Core
        require_once __DIR__ . '/core/class-wc-customer-lists-list-engine.php';
        require_once __DIR__ . '/core/class-wc-customer-lists-list-registry.php';

        // Lists
        require_once __DIR__ . '/lists/class-wc-customer-lists-event-list-base.php';
        require_once __DIR__ . '/lists/class-wc-customer-lists-generic-event-list.php';
        require_once __DIR__ . '/lists/class-wc-customer-lists-bridal-list.php';
        require_once __DIR__ . '/lists/class-wc-customer-lists-baby-list.php';
        require_once __DIR__ . '/lists/class-wc-customer-lists-wishlist-base.php';
        require_once __DIR__ . '/lists/class-wc-customer-lists-wishlist.php';

        // Admin
        if ( is_admin() ) {
            require_once __DIR__ . '/admin/class-wc-customer-lists-admin.php';
        }

        // AJAX
        require_once __DIR__ . '/ajax/class-wc-customer-lists-ajax-handlers.php';

        // UI
        require_once __DIR__ . '/ui/class-wc-customer-lists-my-account.php';
        require_once __DIR__ . '/ui/class-wc-customer-lists-product-modal.php';
    }

    /**
     * Register global hooks.
     */
    private function register_hooks(): void {
        add_action( 'init', [ $this, 'register_post_types' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    public function register_post_types(): void {
        \WC_Customer_Lists\Core\List_Registry::register_post_types();
    }

    public function enqueue_scripts(): void {

        wp_enqueue_style(
            'wc-customer-lists',
            plugins_url(
                'includes/assets/css/wc-customer-lists.css',
                WC_CUSTOMER_LISTS_PLUGIN_FILE
            ),
            [],
            WC_CUSTOMER_LISTS_VERSION
        );

        wp_enqueue_script(
            'wc-customer-lists',
            plugins_url(
                'includes/assets/js/wc-customer-lists.js',
                WC_CUSTOMER_LISTS_PLUGIN_FILE
            ),
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
