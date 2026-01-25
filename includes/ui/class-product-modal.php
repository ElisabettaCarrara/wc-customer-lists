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
        // Enqueue front-end assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // Add button to single product page
        add_action( 'woocommerce_single_product_summary', array( $this, 'render_add_to_list_button' ), 35 );

        // Add button to product archives
        add_action( 'woocommerce_after_shop_loop_item', array( $this, 'render_add_to_list_button' ), 15 );

        // Modal HTML (can also be hooked to wp_footer)
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
            array( 'jquery' ),
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

        echo '<button class="wc-customer-lists-add-btn" data-product-id="' . esc_attr( $product_id ) . '">';
        echo esc_html__( 'Add to List', 'wc-customer-lists' );
        echo '</button>';
    }

    /**
     * Render modal container (HTML)
     */
    public function render_modal(): void {
        ?>
        <dialog id="wc-customer-lists-modal" class="wc-customer-lists-modal">
            <form method="dialog">
                <button class="modal-close-btn" aria-label="<?php esc_attr_e( 'Close', 'wc-customer-lists' ); ?>">×</button>
                <h2><?php esc_html_e( 'Add Product to List', 'wc-customer-lists' ); ?></h2>

                <div class="wc-customer-lists-modal-content">
                    <!-- Dropdown and form fields will be populated dynamically via JS -->
                </div>

                <button class="modal-submit-btn"><?php esc_html_e( 'Add', 'wc-customer-lists' ); ?></button>
            </form>
        </dialog>
        <?php
    }
}
