<?php
/**
 * WC Customer Lists Main Plugin Class.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

final class WC_Customer_Lists {

    private static ?WC_Customer_Lists $instance = null;

    public static function get_instance(): WC_Customer_Lists {
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
        require_once __DIR__ . '/core/class-list-engine.php';
        require_once __DIR__ . '/core/class-list-registry.php';

        // Lists
        require_once __DIR__ . '/lists/class-event-list.php';
        require_once __DIR__ . '/lists/class-bridal-list.php';
        require_once __DIR__ . '/lists/class-baby-list.php';
        require_once __DIR__ . '/lists/class-generic-event-list.php';
        require_once __DIR__ . '/lists/class-wishlist-base.php';
        require_once __DIR__ . '/lists/class-wishlist.php';

        // Admin
        if ( is_admin() ) {
            require_once __DIR__ . '/admin/class-admin.php';
        }

        // AJAX
        require_once __DIR__ . '/ajax/class-ajax.php';
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
            plugins_url( 'assets/css/frontend.css', WC_CUSTOMER_LISTS_FILE ),
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'wc-customer-lists',
            plugins_url( 'assets/js/frontend.js', WC_CUSTOMER_LISTS_FILE ),
            [],
            '1.0.0',
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
