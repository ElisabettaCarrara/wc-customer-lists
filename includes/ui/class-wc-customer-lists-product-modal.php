<?php
/**
 * Product Modal UI.
 *
 * "Add to List" button + modal on product pages.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product modal renderer.
 */
class WC_Customer_Lists_Product_Modal {

	/**
	 * Constructor.
	 */
	public function __construct() {

		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Single product page.
		add_action(
			'woocommerce_single_product_summary',
			array( $this, 'render_single_product_button' ),
			29
		);

		// Archive / loop button.
		add_action(
			'woocommerce_after_shop_loop_item',
			array( $this, 'render_loop_button' ),
			20
		);

		// Global modal.
		add_action(
			'wp_footer',
			array( $this, 'render_modal' )
		);
	}

	/**
	 * Render button on single product page.
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
	 * Render button in product loop.
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
	 * Render modal.
	 */
	public function render_modal() {

		if ( ! is_user_logged_in() ) {
			return;
		}
		?>

		<div
			id="wc-customer-lists-modal"
			class="wc-customer-lists-modal"
			role="dialog"
			aria-modal="true"
			aria-hidden="true"
		>

			<div class="wc-customer-lists-modal-overlay"></div>

			<div class="wc-customer-lists-modal-content">

				<button
					type="button"
					class="modal-close-btn"
					aria-label="<?php esc_attr_e( 'Close modal', 'wc-customer-lists' ); ?>"
				>
					×
				</button>

				<h2 id="wc-customer-lists-title">
					<?php esc_html_e( 'Add to List', 'wc-customer-lists' ); ?>
				</h2>

				<div class="wc-customer-lists-modal-body">

					<p class="wc-customer-lists-loading">
						<?php esc_html_e( 'Loading lists...', 'wc-customer-lists' ); ?>
					</p>

				</div>

				<div class="wc-customer-lists-modal-actions">

					<button
						type="button"
						class="modal-submit-btn button alt"
					>
						<?php esc_html_e( 'Add Product', 'wc-customer-lists' ); ?>
					</button>

				</div>

			</div>

		</div>

		<?php
	}
}
