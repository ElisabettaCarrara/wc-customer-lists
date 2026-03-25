<?php
/**
 * Product Modal UI.
 *
 * Renders the "Add to List" button and modal on product pages.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles rendering of the "Add to List" button and modal overlay.
 */
class WC_Customer_Lists_Product_Modal {

	/**
	 * Constructor.
	 *
	 * Registers all front-end hooks if WooCommerce is active.
	 */
	public function __construct() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Button on the single product page.
		add_action(
			'woocommerce_single_product_summary',
			array( $this, 'render_single_product_button' ),
			29
		);

		// Button in the shop/archive loop.
		add_action(
			'woocommerce_after_shop_loop_item',
			array( $this, 'render_loop_button' ),
			20
		);

		// Global modal injected once in the footer.
		add_action(
			'wp_footer',
			array( $this, 'render_modal' )
		);
	}

	/**
	 * Render the "Add to List" button on the single product page.
	 *
	 * Hooked into `woocommerce_single_product_summary` at priority 29,
	 * placing it just after the add-to-cart button.
	 */
	public function render_single_product_button() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$product_id = $product->get_id();
		?>
		<div class="wc-customer-lists-single-wrapper">
			<button
				type="button"
				class="wc-customer-lists-add-btn button"
				data-product-id="<?php echo esc_attr( $product_id ); ?>"
				aria-label="<?php echo esc_attr( sprintf( __( 'Add %s to list', 'wc-customer-lists' ), $product->get_name() ) ); ?>"
			>
				<span class="dashicons dashicons-heart" aria-hidden="true"></span>
				<span class="btn-text">
					<?php esc_html_e( 'Add to List', 'wc-customer-lists' ); ?>
				</span>
			</button>
		</div>
		<?php
	}

	/**
	 * Render the "Add to List" icon button in the product loop.
	 *
	 * Hooked into `woocommerce_after_shop_loop_item` at priority 20.
	 * Displays an icon-only button; the full label is provided via aria-label.
	 */
	public function render_loop_button() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$product_id = $product->get_id();
		?>
		<button
			type="button"
			class="wc-customer-lists-add-btn wc-customer-lists-loop"
			data-product-id="<?php echo esc_attr( $product_id ); ?>"
			aria-label="<?php echo esc_attr( sprintf( __( 'Add %s to list', 'wc-customer-lists' ), $product->get_name() ) ); ?>"
			title="<?php esc_attr_e( 'Add to List', 'wc-customer-lists' ); ?>"
		>
			<span class="dashicons dashicons-heart" aria-hidden="true"></span>
		</button>
		<?php
	}

	/**
	 * Render the global "Add to List" modal.
	 *
	 * Injected once into the footer via `wp_footer`. The modal body is
	 * populated dynamically via AJAX after a button is clicked.
	 */
	public function render_modal() {
		if ( ! is_user_logged_in() ) return;
    
    // 🔥 PRELOAD LISTS CACHE
    $user_id = get_current_user_id();
    $cache_key = 'wccl_lists_html_' . $user_id;
    $cached_html = get_transient( $cache_key );
    
    if ( false === $cached_html ) {
        ob_start();
        // Same logic as getuserlists() but no nonce
        $enabled_lists = get_option( 'wc_customer_lists_settings', [] )['enabled_lists'] ?? [];
        $posts = get_posts( [ /* single query */ ] );
        // Build <select> HTML...
        $cached_html = ob_get_clean();
        set_transient( $cache_key, $cached_html, 5 * MINUTE_IN_SECONDS );
    }
    ?>
    
    <!-- Cached lists (invisible) -->
    <div id="wccl-lists-cache" style="display:none;"><?php echo $cached_html; ?></div>
		<div id="wc-customer-lists-modal" class="wccl-modal" aria-hidden="true">
			<div class="wccl-modal-overlay"></div>
			<div class="wccl-modal-container">
				<button type="button" class="wccl-modal-close">&times;</button>
				<h3 class="wccl-modal-title">
					<?php esc_html_e( 'Add to List', 'wc-customer-lists' ); ?>
				</h3>
				<div class="wccl-modal-body wc-customer-lists-modal-body">
					<p class="wc-customer-lists-loading">
						<?php esc_html_e( 'Loading lists...', 'wc-customer-lists' ); ?>
					</p>
				</div>
				<div class="wccl-modal-footer">
					<button type="button" class="wccl-submit-btn modal-submit-btn">
						<?php esc_html_e( 'Add to List', 'wc-customer-lists' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}
}
