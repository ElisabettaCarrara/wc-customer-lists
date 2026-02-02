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

		// Single product page - below title
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_single_product_button' ), 6 );
		
		// Shop/archive pages - next to product title
		add_action( 'woocommerce_shop_loop_item_title', array( $this, 'render_loop_button_start' ), 5 );
		add_action( 'woocommerce_shop_loop_item_title', array( $this, 'render_loop_button_end' ), 15 );
		
		// Global modal.
		add_action( 'wp_footer', array( $this, 'render_modal' ) );
	}

	/**
	 * Render button on single product page (below title).
	 */
	public function render_single_product_button() {
		global $product;
		if ( ! $product || ! is_user_logged_in() ) {
			return;
		}

		$product_id = $product->get_id();
		?>
		<button class="wc-customer-lists-add-btn wc-customer-lists-single" 
		        data-product-id="<?php echo esc_attr( $product_id ); ?>"
		        aria-label="<?php esc_attr_e( 'Add to wishlist', 'wc-customer-lists' ); ?>">
			<span class="dashicons dashicons-heart"></span>
			<span class="btn-text"><?php esc_html_e( 'Add to List', 'wc-customer-lists' ); ?></span>
		</button>
		<?php
	}

	/**
	 * Start wrapper for product title + button on loop.
	 */
	public function render_loop_button_start() {
		if ( ! is_user_logged_in() ) {
			return;
		}
		echo '<div class="wc-customer-lists-title-wrapper">';
	}

	/**
	 * Render button after product title on loop.
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
		        aria-label="<?php esc_attr_e( 'Add to wishlist', 'wc-customer-lists' ); ?>">
			<span class="dashicons dashicons-heart"></span>
		</button>
		</div>
		<?php
	}

	/**
	 * Render modal HTML (one per page).
	 */
	public function render_modal() {
		if ( ! is_user_logged_in() ) {
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
