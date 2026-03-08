<?php
/**
 * My Account "My Lists" tab.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

class WC_Customer_Lists_My_Account {

	/**
	 * Constructor.
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'add_endpoint' ] );

		add_filter(
			'woocommerce_account_menu_items',
			[ $this, 'add_my_lists_tab' ],
			10
		);

		add_action(
			'woocommerce_account_my-lists_endpoint',
			[ $this, 'render_lists_page' ]
		);

		add_filter(
			'query_vars',
			[ $this, 'add_query_vars' ]
		);

		/* AJAX */
		add_action(
			'wp_ajax_wccl_delete_list',
			[ $this, 'ajax_delete_list' ]
		);

		add_action(
			'wp_ajax_wccl_toggle_product',
			[ $this, 'ajax_toggle_product' ]
		);

	}

	/**
	 * Register endpoint.
	 */
	public function add_endpoint(): void {

		add_rewrite_endpoint(
			'my-lists',
			EP_ROOT | EP_PAGES
		);

	}

	/**
	 * Add endpoint query var.
	 */
	public function add_query_vars( $vars ): array {

		$vars[] = 'my-lists';

		return $vars;

	}

	/**
	 * Flush rules (call on plugin activation).
	 */
	public static function flush_rules(): void {

		add_rewrite_endpoint(
			'my-lists',
			EP_ROOT | EP_PAGES
		);

		flush_rewrite_rules();

	}

	/**
	 * Add My Lists tab.
	 */
	public function add_my_lists_tab( $items ): array {

		$new_items = [];

		foreach ( $items as $key => $label ) {

			$new_items[ $key ] = $label;

			if ( 'orders' === $key ) {

				$new_items['my-lists'] = __(
					'My Lists',
					'wc-customer-lists'
				);

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

			echo '<p>' .
				esc_html__(
					'Please log in to manage your lists.',
					'wc-customer-lists'
				) .
			'</p>';

			return;

		}

		$lists = $this->get_user_lists( $user_id );

		echo '<h2>' .
			esc_html__( 'My Lists', 'wc-customer-lists' ) .
		'</h2>';

		?>

		<script>
		window.WCCL_MyAccount = {
			ajax_url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
			nonce: '<?php echo esc_js( wp_create_nonce( 'wc_customer_lists_nonce' ) ); ?>'
		};
		</script>

		<?php

		if ( empty( $lists ) ) {

			echo '<p>' .
				esc_html__(
					'You do not have any lists yet.',
					'wc-customer-lists'
				) .
			'</p>';

			return;

		}

		foreach ( $lists as $list_data ) {

			$this->render_list_card( $list_data );

		}

	}

	/**
	 * Retrieve user lists.
	 */
	private function get_user_lists( int $user_id ): array {

		$lists = [];

		$post_types = array_keys(
			WC_Customer_Lists_List_Registry::get_all_list_types()
		);

		foreach ( $post_types as $post_type ) {

			$posts = get_posts(
				[
					'author'      => $user_id,
					'post_type'   => $post_type,
					'post_status' => [ 'private', 'publish' ],
					'numberposts' => -1,
				]
			);

			foreach ( $posts as $post ) {

				try {

					$list = WC_Customer_Lists_List_Engine::get(
						$post->ID
					);

					if ( ! $list ) {
						continue;
					}

					if ( ! $list->current_user_can_manage() ) {
						continue;
					}

					$lists[] = [
						'id'    => $post->ID,
						'title' => get_the_title( $post->ID ),
						'items' => $list->get_items(),
					];

				} catch ( Throwable $e ) {

					continue;

				}

			}

		}

		return $lists;

	}

	/**
	 * Render single list card.
	 */
	private function render_list_card( array $list ): void {

		$list_id = (int) $list['id'];

		?>

		<div class="wc-customer-lists-card" data-list-id="<?php echo esc_attr( $list_id ); ?>">

			<h3>

				<?php echo esc_html( $list['title'] ); ?>

				<button
					class="button delete-list"
					data-list-id="<?php echo esc_attr( $list_id ); ?>"
				>

					<?php esc_html_e(
						'Delete List',
						'wc-customer-lists'
					); ?>

				</button>

			</h3>

			<?php

			$this->render_products_table(
				$list_id,
				$list['items']
			);

			?>

		</div>

		<?php

	}

	/**
	 * Render products table.
	 */
	private function render_products_table(
		int $list_id,
		array $items
	): void {

		if ( empty( $items ) ) {

			echo '<p>' .
				esc_html__(
					'No products yet.',
					'wc-customer-lists'
				) .
			'</p>';

			return;

		}

		echo '<table class="woocommerce-table">';

		foreach ( $items as $product_id => $qty ) {

			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			?>

			<tr
				data-product-id="<?php echo esc_attr( $product_id ); ?>"
			>

				<td>

					<?php echo $product->get_image( 'thumbnail' ); ?>

					<?php echo esc_html( $product->get_name() ); ?>

				</td>

				<td>

					<?php echo esc_html( $qty ); ?>

				</td>

				<td>

					<button
						class="button remove-item"
						data-list-id="<?php echo esc_attr( $list_id ); ?>"
						data-product-id="<?php echo esc_attr( $product_id ); ?>"
					>

						<?php esc_html_e(
							'Remove',
							'wc-customer-lists'
						); ?>

					</button>

				</td>

			</tr>

			<?php

		}

		echo '</table>';

	}

	/**
	 * AJAX: Delete list.
	 */
	public function ajax_delete_list(): void {

		check_ajax_referer(
			'wc_customer_lists_nonce',
			'nonce'
		);

		$list_id = absint( $_POST['list_id'] ?? 0 );
		$user_id = get_current_user_id();

		if ( ! $list_id || ! $user_id ) {

			wp_send_json_error(
				[
					'message' =>
					__( 'Invalid request.', 'wc-customer-lists' ),
				]
			);

		}

		try {

			$list = WC_Customer_Lists_List_Engine::get(
				$list_id
			);

			if ( ! $list ) {

				wp_send_json_error(
					[
						'message' =>
						__( 'List not found.', 'wc-customer-lists' ),
					]
				);

			}

			if ( $list->get_owner_id() !== $user_id ) {

				wp_send_json_error(
					[
						'message' =>
						__( 'Permission denied.', 'wc-customer-lists' ),
					]
				);

			}

			wp_delete_post( $list_id, true );

			wp_send_json_success(
				[
					'message' =>
					__( 'List deleted.', 'wc-customer-lists' ),
					'list_id' => $list_id,
				]
			);

		} catch ( Throwable $e ) {

			wp_send_json_error(
				[
					'message' =>
					__( 'Server error.', 'wc-customer-lists' ),
				]
			);

		}

	}

	/**
	 * AJAX: Remove product from list.
	 */
	public function ajax_toggle_product(): void {

		check_ajax_referer(
			'wc_customer_lists_nonce',
			'nonce'
		);

		$list_id    = absint( $_POST['list_id'] ?? 0 );
		$product_id = absint( $_POST['product_id'] ?? 0 );
		$user_id    = get_current_user_id();

		if ( ! $list_id || ! $product_id ) {

			wp_send_json_error(
				[
					'message' =>
					__( 'Invalid request.', 'wc-customer-lists' ),
				]
			);

		}

		try {

			$list = WC_Customer_Lists_List_Engine::get(
				$list_id
			);

			if ( ! $list ) {

				wp_send_json_error(
					[
						'message' =>
						__( 'List not found.', 'wc-customer-lists' ),
					]
				);

			}

			if ( $list->get_owner_id() !== $user_id ) {

				wp_send_json_error(
					[
						'message' =>
						__( 'Permission denied.', 'wc-customer-lists' ),
					]
				);

			}

			$list->remove_item( $product_id );

			wp_send_json_success(
				[
					'message' =>
					__( 'Product removed.', 'wc-customer-lists' ),
					'product_id' => $product_id,
					'list_id'    => $list_id,
					'item_count' => count(
						$list->get_items()
					),
				]
			);

		} catch ( Throwable $e ) {

			wp_send_json_error(
				[
					'message' =>
					__( 'Server error.', 'wc-customer-lists' ),
				]
			);

		}

	}

}
