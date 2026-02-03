<?php
/**
 * Product Modal UI.
 *
 * "Add to List" button + modal on product pages.
 *
 * @package WC_Customer_Lists
 */
defined( 'ABSPATH' ) || exit;

class WC_Customer_Lists_Product_Modal {

	public function __construct() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Single product page - below title, before add-to-cart
		add_action( 'woocommerce_single_product_summary', [ $this, 'render_single_product_button' ], 29 ); // Moved to 29 (after meta, before add-to-cart)
		
		// Shop/archive pages - wrap title + button
		add_action( 'woocommerce_shop_loop_item_title', [ $this, 'render_loop_button_start' ], 5 );
		add_action( 'woocommerce_shop_loop_item_title', [ $this, 'render_loop_button_end' ], 15 );
		
		// Global modal (only on shop/product pages for perf)
		add_action( 'wp_footer', [ $this, 'render_modal' ] );
	}

	/**
	 * Render button on single product page.
	 */
	public function render_single_product_button() {
		global $product;
		if ( ! $product || ! is_user_logged_in() ) {
			return;
		}

		$product_id = $product->get_id();
		?>
		<div class="wc-customer-lists-single-wrapper">
			<button class="wc-customer-lists-add-btn wc-customer-lists-single button" 
			        data-product-id="<?php echo esc_attr( $product_id ); ?>"
			        aria-label="<?php printf( esc_attr__( 'Add %s to list', 'wc-customer-lists' ), esc_attr( $product->get_name() ) ); ?>">
				<span class="dashicons dashicons-heart" aria-hidden="true"></span>
				<span class="btn-text"><?php esc_html_e( 'Add to List', 'wc-customer-lists' ); ?></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Start wrapper for loop title + button.
	 */
	public function render_loop_button_start() {
		if ( ! is_user_logged_in() ) {
			return;
		}
		echo '<div class="wc-customer-lists-title-wrapper">';
	}

	/**
	 * End wrapper + render icon-only button.
	 */
	public function render_loop_button_end() {
		global $product;
		if ( ! $product || ! is_user_logged_in() ) {
			echo '</div>';
			return;
		}

		$product_id = $product->get_id();
		?>
		<button class="wc-customer-lists-add-btn wc-customer-lists-loop" 
		        data-product-id="<?php echo esc_attr( $product_id ); ?>"
		        aria-label="<?php printf( esc_attr__( 'Add %s to list', 'wc-customer-lists' ), esc_attr( $product->get_name() ) ); ?>"
		        title="<?php esc_attr_e( 'Add to List', 'wc-customer-lists' ); ?>">
			<span class="dashicons dashicons-heart" aria-hidden="true"></span>
		</button>
		</div>
		<?php
	}

	/**
	 * Render modal (only if needed).
	 */
	public function render_modal() {
		if ( ! is_user_logged_in() || ! ( is_shop() || is_product_taxonomy() || is_product() ) ) {
			return;
		}
		?>
		<dialog id="wc-customer-lists-modal" 
		        class="wc-customer-lists-modal" 
		        role="dialog" 
		        aria-modal="true" 
		        aria-labelledby="wc-customer-lists-title">
			
			<div class="wc-customer-lists-modal-wrapper">
				<form method="dialog" class="wc-customer-lists-modal-form">
					
					<button type="button" class="modal-close-btn" 
					        aria-label="<?php esc_attr_e( 'Close modal', 'wc-customer-lists' ); ?>">×</button>
					
					<h2 id="wc-customer-lists-title">
						<?php esc_html_e( 'Add to List', 'wc-customer-lists' ); ?>
					</h2>
					
					<div class="wc-customer-lists-modal-content">
						<p class="wc-customer-lists-loading">
							<?php esc_html_e( 'Loading lists...', 'wc-customer-lists' ); ?>
						</p>
					</div>
					
					<div class="wc-customer-lists-modal-actions">
						<button type="submit" class="modal-submit-btn button alt">
							<?php esc_html_e( 'Add Product', 'wc-customer-lists' ); ?>
						</button>
					</div>
				</form>
			</div>
			
			<div class="wc-customer-lists-modal-overlay" aria-hidden="true"></div>
		</dialog>
		<?php
	}
}
