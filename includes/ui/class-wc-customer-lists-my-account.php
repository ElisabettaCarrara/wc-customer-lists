<?php
/**
 * My Account "My Lists" tab.
 *
 * Shows user's lists with full CRUD (view/edit/delete products).
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

class WC_Customer_Lists_My_Account {

	public function __construct() {
		add_filter( 'woocommerce_account_menu_items', [ $this, 'add_my_lists_tab' ], 10, 1 );
		add_action( 'woocommerce_account_my-lists_endpoint', [ $this, 'render_lists_page' ] );
		add_action( 'init', [ $this, 'add_endpoint' ] );

		// AJAX for inline edits.
		add_action( 'wp_ajax_wccl_delete_list', [ $this, 'ajax_delete_list' ] );
		add_action( 'wp_ajax_wccl_toggle_product', [ $this, 'ajax_toggle_product' ] );
	}

	/**
	 * Add rewrite endpoint (NO FLUSH!).
	 */
	public function add_endpoint(): void {
		add_rewrite_endpoint( 'my-lists', EP_PAGES );
		// flush_rewrite_rules() REMOVED - handled by activation
	}

	/**
	 * Add tab to My Account menu.
	 */
	public function add_my_lists_tab( array $items ): array {
		$new_items = [];
		foreach ( $items as $key => $label ) {
			$new_items[ $key ] = $label;
			if ( 'orders' === $key ) {
				$new_items['my-lists'] = __( 'My Lists', 'wc-customer-lists' );
			}
		}
		return $new_items;
	}

	/**
	 * Render My Lists page.
	 */
	public function render_lists_page(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			echo '<p>' . esc_html__( 'Please log in to manage your lists.', 'wc-customer-lists' ) . '</p>';
			return;
		}

		$lists = $this->get_user_lists( $user_id );

		if ( empty( $lists ) ) {
			echo '<div class="woocommerce-message woocommerce-info">';
			echo '<p>' . esc_html__( 'No lists yet. Create one from any product page!', 'wc-customer-lists' ) . '</p>';
			echo '</div>';
			return;
		}

		echo '<h2>' . esc_html__( 'My Lists', 'wc-customer-lists' ) . ' <small>(' . count( $lists ) . ')</small></h2>';

		// JS data for AJAX handlers.
		?>
		<script>
		window.WCCL_MyAccount = {
			ajax_url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
			nonce: '<?php echo esc_js( wp_create_nonce( 'wc_customer_lists_nonce' ) ); ?>'
		};
		</script>
		<?php

		foreach ( $lists as $list_data ) {
			$this->render_list_card( $list_data );
		}
	}

	/**
	 * Get all user's lists grouped by type.
	 */
	private function get_user_lists( int $user_id ): array {
		$lists = [];

		$post_types = array_keys( WC_Customer_Lists_List_Registry::get_all_list_types() );

		foreach ( $post_types as $post_type ) {
			$posts = get_posts( [
				'author'      => $user_id,
				'post_type'   => $post_type,
				'post_status' => [ 'private', 'publish' ],
				'numberposts' => -1,
				'orderby'     => 'date',
				'order'       => 'DESC',
			] );

			foreach ( $posts as $post ) {
				try {
					$list = WC_Customer_Lists_List_Engine::get( $post->ID );
					if ( ! $list->current_user_can_manage() ) {
						continue;
					}

					$lists[] = [
						'id'        => $post->ID,
						'title'     => get_the_title( $post->ID ),
						'type'      => $list::get_type(),
						'type_label'=> ucfirst( $list::get_type() ), // Fallback
						'post_type' => $post_type,
						'count'     => count( $list->get_items() ),
						'updated'   => get_the_modified_date( 'M j, Y', $post ),
						'items'     => $list->get_items(),
					];
				} catch ( Exception $e ) {
					// Skip invalid lists.
				}
			}
		}

		return $lists;
	}

	/**
	 * Render single list card with products table.
	 */
	private function render_list_card( array $list_data ): void {
		?>
		<div class="wc-customer-lists-card">
			<div class="wc-customer-lists-card-header">
				<h3>
					<a href="#list-<?php echo esc_attr( $list_data['id'] ); ?>" 
					   class="list-title"><?php echo esc_html( $list_data['title'] ); ?></a>
					<span class="list-meta">
						<?php echo esc_html( $list_data['type_label'] ?? ucfirst( $list_data['type'] ) ); ?> • 
						<span class="item-count"><?php echo (int) $list_data['count']; ?></span> items • 
						<?php echo esc_html( $list_data['updated'] ); ?>
					</span>
				</h3>
				<div class="list-actions">
					<button class="button toggle-list" 
					        data-list-id="<?php echo esc_attr( $list_data['id'] ); ?>">
						<?php esc_html_e( 'Show Products', 'wc-customer-lists' ); ?>
					</button>
					<button class="button delete-list" 
					        data-list-id="<?php echo esc_attr( $list_data['id'] ); ?>">
						<?php esc_html_e( 'Delete List', 'wc-customer-lists' ); ?>
					</button>
				</div>
			</div>

			<div id="list-<?php echo esc_attr( $list_data['id'] ); ?>" 
			     class="wc-customer-lists-products" style="display:none;">
				<?php $this->render_products_table( $list_data ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render products table for list.
	 */
	private function render_products_table( array $list_data ): void {
		if ( empty( $list_data['items'] ) ) {
			echo '<p>' . esc_html__( 'No products yet.', 'wc-customer-lists' ) . '</p>';
			return;
		}

		echo '<table class="woocommerce-table woocommerce-table--lists">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Product', 'wc-customer-lists' ) . '</th>';
		echo '<th>' . esc_html__( 'Quantity', 'wc-customer-lists' ) . '</th>';
		echo '<th>' . esc_html__( 'Actions', 'wc-customer-lists' ) . '</th>';
		echo '</tr></thead>';
		echo '<tbody>';

		foreach ( $list_data['items'] as $product_id => $qty ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			echo '<tr data-product-id="' . esc_attr( $product_id ) . '" data-list-id="' . esc_attr( $list_data['id'] ) . '">';
			echo '<td>';
			echo $product->get_image( 'thumbnail' );
			echo '<strong>' . esc_html( $product->get_name() ) . '</strong>';
			echo '<br><small>' . $product->get_price_html() . '</small>';
			echo '</td>';
			echo '<td class="qty">' . esc_html( $qty ) . '</td>';
			echo '<td>';
			echo '<button class="button remove-item" data-list-id="' . esc_attr( $list_data['id'] ) . '" data-product-id="' . esc_attr( $product_id ) . '">';
			echo esc_html__( 'Remove', 'wc-customer-lists' );
			echo '</button>';
			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * AJAX: Delete entire list.
	 */
	public function ajax_delete_list(): void {
		check_ajax_referer( 'wc_customer_lists_nonce', 'nonce' );

		$list_id = absint( $_POST['list_id'] ?? 0 );
		$user_id = get_current_user_id();

		if ( ! $list_id || ! $user_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid request.', 'wc-customer-lists' ) ] );
		}

		try {
			$list = WC_Customer_Lists_List_Engine::get( $list_id );
			if ( $list->get_owner_id() !== $user_id ) {
				wp_send_json_error( [ 'message' => __( 'Not your list.', 'wc-customer-lists' ) ] );
			}

			wp_delete_post( $list_id, true );

			wp_send_json_success( [ 
				'message' => __( 'List deleted.', 'wc-customer-lists' ),
				'list_id' => $list_id 
			] );
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}

	/**
	 * AJAX: Remove product from list.
	 */
	public function ajax_toggle_product(): void {
		check_ajax_referer( 'wc_customer_lists_nonce', 'nonce' );

		$list_id    = absint( $_POST['list_id'] ?? 0 );
		$product_id = absint( $_POST['product_id'] ?? 0 );
		$user_id    = get_current_user_id();

		if ( ! $list_id || ! $product_id || ! $user_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid request.', 'wc-customer-lists' ) ] );
		}

		try {
			$list = WC_Customer_Lists_List_Engine::get( $list_id );
			if ( $list->get_owner_id() !== $user_id ) {
				wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wc-customer-lists' ) ] );
			}

			$list->remove_item( $product_id );

			wp_send_json_success( [
				'message'    => __( 'Product removed.', 'wc-customer-lists' ),
				'list_id'    => $list_id,
				'product_id' => $product_id,
				'item_count' => count( $list->get_items() ),
			] );
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}
}
