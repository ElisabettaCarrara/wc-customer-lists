<?php
/**
 * Admin functionality for WC Customer Lists.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

final class WC_Customer_Lists_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function register_menu(): void {
        add_menu_page(
            __( 'Customer Lists', 'wc-customer-lists' ),
            __( 'Customer Lists', 'wc-customer-lists' ),
            'manage_options',
            'wc-customer-lists',
            [ $this, 'render_settings_page' ],
            'dashicons-heart',
            56
        );
    }

    public function enqueue_assets(): void {
        wp_enqueue_style(
            'wc-customer-lists-admin',
            plugins_url( 'assets/css/admin.css', WC_CUSTOMER_LISTS_FILE ),
            [],
            '1.0.0'
        );
    }

    public function render_settings_page(): void {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Customer Lists Settings', 'wc-customer-lists' ); ?></h1>
            <p><?php esc_html_e( 'Settings will go here.', 'wc-customer-lists' ); ?></p>
        </div>
        <?php
    }
}

// Bootstrap admin
new WC_Customer_Lists_Admin();
