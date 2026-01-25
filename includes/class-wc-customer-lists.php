<?php
/**
 * WC Customer Lists Main Plugin Class.
 *
 * Orchestrates loading core logic, CPT registration,
 * admin pages, and front-end hooks.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

final class WC_Customer_Lists {

    /**
     * Singleton instance.
     *
     * @var WC_Customer_Lists|null
     */
    private static ?WC_Customer_Lists $instance = null;

    /**
     * Get singleton instance.
     */
    public static function get_instance(): WC_Customer_Lists {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->includes();
        $this->register_hooks();
    }

    /**
     * Include all necessary files.
     */
    private function includes(): void {
        // Core
        require_once __DIR__ . '/includes/core/class-list-engine.php';
        require_once __DIR__ . '/includes/core/class-list-registry.php';

        // Lists
        require_once __DIR__ . '/includes/lists/class-event-list.php';
        require_once __DIR__ . '/includes/lists/class-bridal-list.php';
        require_once __DIR__ . '/includes/lists/class-baby-list.php';
        require_once __DIR__ . '/includes/lists/class-generic-event-list.php';
        require_once __DIR__ . '/includes/lists/class-wishlist-base.php';
        require_once __DIR__ . '/includes/lists/class-wishlist.php';
    }

    /**
     * Register all hooks.
     */
    private function register_hooks(): void {
        // CPTs
        add_action( 'init', [ $this, 'register_post_types' ] );

        // Admin menu
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );

        // Front-end WooCommerce hooks
        add_action( 'woocommerce_after_shop_loop_item', [ $this, 'display_add_to_list_button' ], 10 );
        add_action( 'woocommerce_single_product_summary', [ $this, 'display_add_to_list_button' ], 35 );

        // AJAX handlers
        add_action( 'wp_ajax_wc_customer_list_add_item', [ $this, 'ajax_add_item' ] );
        add_action( 'wp_ajax_nopriv_wc_customer_list_add_item', [ $this, 'ajax_add_item' ] );

        // Enqueue scripts/styles
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
    }

    /**
     * Register all list CPTs.
     */
    public function register_post_types(): void {
        \WC_Customer_Lists\Core\List_Registry::register_post_types();
    }

    /**
     * Add plugin settings page.
     */
    public function register_admin_menu(): void {
        add_menu_page(
            __( 'Customer Lists', 'wc-customer-lists' ),
            __( 'Customer Lists', 'wc-customer-lists' ),
            'manage_options',
            'wc-customer-lists',
            [ $this, 'render_admin_page' ],
            'dashicons-heart',
            56
        );
    }

    /**
     * Render admin page content.
     */
    public function render_admin_page(): void {
        echo '<div class="wrap"><h1>' . esc_html__( 'Customer Lists Settings', 'wc-customer-lists' ) . '</h1>';
        echo '<p>' . esc_html__( 'Settings will go here.', 'wc-customer-lists' ) . '</p>';
        echo '</div>';
    }

    /**
     * Enqueue front-end scripts and styles.
     */
    public function enqueue_scripts(): void {
        wp_enqueue_style( 'wc-customer-lists', plugins_url( 'assets/css/frontend.css', __FILE__ ), [], '1.0.0' );
        wp_enqueue_script( 'wc-customer-lists', plugins_url( 'assets/js/frontend.js', __FILE__ ), [ 'jquery' ], '1.0.0', true );

        // Localize for AJAX
        wp_localize_script( 'wc-customer-lists', 'WC_Customer_Lists', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wc_customer_lists_nonce' ),
        ] );
    }

    /**
     * Enqueue admin scripts/styles.
     */
    public function enqueue_admin_scripts(): void {
        wp_enqueue_style( 'wc-customer-lists-admin', plugins_url( 'assets/css/admin.css', __FILE__ ), [], '1.0.0' );
        wp_enqueue_script( 'wc-customer-lists-admin', plugins_url( 'assets/js/admin.js', __FILE__ ), [ 'jquery' ], '1.0.0', true );
    }

    /**
     * Display "Add to List" button on products.
     */
    public function display_add_to_list_button(): void {
        global $product;

        if ( ! is_user_logged_in() || ! $product ) {
            return;
        }

        $user_id = get_current_user_id();
        $lists   = $this->get_user_lists( $user_id );

        if ( empty( $lists ) ) {
            return;
        }

        // Simple dropdown for now
        echo '<div class="wc-customer-lists-dropdown">';
        echo '<select class="wc-customer-list-select">';
        echo '<option value="">' . esc_html__( 'Add to list...', 'wc-customer-lists' ) . '</option>';
        foreach ( $lists as $list ) {
            echo '<option value="' . esc_attr( $list->get_id() ) . '">' . esc_html( $list->get_title() ) . '</option>';
        }
        echo '</select>';
        echo '<button class="button wc-customer-list-add-item" data-product-id="' . esc_attr( $product->get_id() ) . '">' . esc_html__( 'Add', 'wc-customer-lists' ) . '</button>';
        echo '</div>';
    }

    /**
     * Helper: get all lists of a user.
     *
     * @param int $user_id
     * @return array
     */
    private function get_user_lists( int $user_id ): array {
        $all_lists = [];
        foreach ( \WC_Customer_Lists\Core\List_Registry::get_all_registered_types() as $post_type => $config ) {
            $posts = get_posts( [
                'author'      => $user_id,
                'post_type'   => $post_type,
                'post_status' => 'any',
                'numberposts' => -1,
            ] );

            foreach ( $posts as $post ) {
                $class = $config['class'];
                $all_lists[] = new $class( $post->ID );
            }
        }
        return $all_lists;
    }

    /**
     * AJAX handler to add item to list.
     */
    public function ajax_add_item(): void {
        check_ajax_referer( 'wc_customer_lists_nonce', 'nonce' );

        $list_id    = intval( $_POST['list_id'] ?? 0 );
        $product_id = intval( $_POST['product_id'] ?? 0 );

        if ( ! $list_id || ! $product_id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid request.', 'wc-customer-lists' ) ] );
        }

        try {
            $list = \WC_Customer_Lists\Core\List_Registry::get( $list_id );
            $list->set_item( $product_id );

            wp_send_json_success( [
                'message' => __( 'Product added to list.', 'wc-customer-lists' ),
            ] );
        } catch ( \Exception $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ] );
        }
    }
}

// Initialize plugin
WC_Customer_Lists::get_instance();
