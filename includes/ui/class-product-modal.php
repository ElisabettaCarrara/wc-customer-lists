<?php
/**
 * Product Modal UI.
 *
 * Handles the "Add to List" button on products,
 * displays the modal to select a list, and enqueues scripts/styles.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

namespace WC_Customer_Lists\UI\Product_Modal;

class Product_Modal {

    public function __construct() {
        // Only hook if WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        // Enqueue front-end assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // Add button to single product page
        add_action( 'woocommerce_single_product_summary', array( $this, 'render_add_to_list_button' ), 35 );

        // Add button to product archives
        add_action( 'woocommerce_after_shop_loop_item', array( $this, 'render_add_to_list_button' ), 15 );

        // Modal HTML
        add_action( 'wp_footer', array( $this, 'render_modal' ) );
    }

    /**
     * Enqueue front-end JS & CSS
     */
    public function enqueue_assets(): void {
        $plugin_url = plugin_dir_url( __DIR__ ) . '../../assets/';

        wp_enqueue_style(
            'wc-customer-lists',
            $plugin_url . 'css/wc-customer-lists.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'wc-customer-lists',
            $plugin_url . 'js/wc-customer-lists.js',
            array(),
            '1.0.0',
            true
        );

        // Localize AJAX URL and nonce
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
     * Render "Add to List" button
     *
     * @param int|null $product_id Optional, if not passed, get global.
     */
    public function render_add_to_list_button( ?int $product_id = null ): void {
        global $product;

        if ( ! $product_id ) {
            $product_id = $product ? $product->get_id() : 0;
        }

        if ( ! $product_id ) {
            return;
        }

        echo '<button class="wc-customer-lists-add-btn button alt" data-product-id="' . esc_attr( $product_id ) . '">';
        echo esc_html__( 'Add to List', 'wc-customer-lists' );
        echo '</button>';
    }

    /**
 * Render modal container (HTML)
 */
public function render_modal(): void {
    ?>
    <dialog id="wc-customer-lists-modal" 
            class="wc-customer-lists-modal" 
            role="dialog" 
            aria-modal="true" 
            aria-labelledby="wc-customer-lists-modal-title">
        
        <div class="wc-customer-lists-modal-wrapper">

            <form method="dialog" class="wc-customer-lists-modal-form">
                
                <!-- Close button -->
                <button type="button" class="modal-close-btn" 
                        aria-label="<?php esc_attr_e( 'Close', 'wc-customer-lists' ); ?>">×</button>

                <!-- Modal title -->
                <h2 id="wc-customer-lists-modal-title">
                    <?php esc_html_e( 'Add Product to List', 'wc-customer-lists' ); ?>
                </h2>

                <!-- Content container (dropdown + event fields injected by JS) -->
                <div class="wc-customer-lists-modal-content">
                    <p class="wc-customer-lists-loading">
                        <?php esc_html_e( 'Loading your lists...', 'wc-customer-lists' ); ?>
                    </p>
                    <div id="wc_event_fields_container"></div>
                </div>

                <!-- Actions -->
                <div class="wc-customer-lists-modal-actions">
                    <button type="submit" class="modal-submit-btn button alt">
                        <?php esc_html_e( 'Add', 'wc-customer-lists' ); ?>
                    </button>
                </div>

            </form>
        </div>

        <!-- Overlay to allow closing by clicking outside -->
        <div class="wc-customer-lists-modal-overlay"></div>
    </dialog>

    <script>
    // Optional: allow clicking overlay to close modal
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('wc-customer-lists-modal');
        const overlay = modal.querySelector('.wc-customer-lists-modal-overlay');

        overlay.addEventListener('click', function() {
            if (modal.open) modal.close();
        });
    });
    </script>
    <?php
}
