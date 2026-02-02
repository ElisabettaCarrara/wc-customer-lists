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

		// Buttons only (main class handles enqueue).
		add_action( 'woocommerce_single_product_summary', [ $this, 'render_add_to_list_button' ], 35 );
		add_action( 'woocommerce_after_shop_loop_item', [ $this, 'render_add_to_list_button' ], 15 );

		// Global modal.
		add_action( 'wp_footer', [ $this, 'render_modal' ] );
	}

	/**
	 * Render "Add to List" button.
	 */
	public function render_add_to_list_button(): void {
		global $product;

		if ( ! $product || ! is_user_logged_in() ) {
			return;
		}

		$product_id = $product->get_id();
		?>
		<button class="wc-customer-lists-add-btn button alt" 
		        data-product-id="<?php echo esc_attr( $product_id ); ?>">
			<?php esc_html_e( 'Add to List', 'wc-customer-lists' ); ?>
		</button>
		<?php
	}

	/**
	 * Render modal HTML (one per page).
	 */
	public function render_modal(): void {
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
