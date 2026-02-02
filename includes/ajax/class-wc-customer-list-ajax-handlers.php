<?php
/**
 * AJAX Handlers for WC Customer Lists.
 *
 * Product → list add UI + API layer.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

final class WC_Customer_List_Ajax_Handlers {

	private array $settings = [];

	public function __construct() {
		$this->settings = get_option( 'wc_customer_lists_settings', [] );

		add_action( 'wp_ajax_wccl_get_user_lists', [ $this, 'get_user_lists' ] );
		add_action( 'wp_ajax_wccl_add_product_to_list', [ $this, 'add_product_to_list' ] );
	}

	/**
	 * AJAX: Get dropdown of user's enabled lists.
	 */
	public function get_user_lists(): void {
		check_ajax_referer( 'wc_customer_lists_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( [ 'message' => __( 'Not logged in.', 'wc-customer-lists' ) ] );
		}

		$enabled_lists = $this->settings['enabled_lists'] ?? [];
		if ( empty( $enabled_lists ) ) {
			wp_send_json_success( [ 'html' => '<p>' . esc_html__( 'No list types enabled.', 'wc-customer-lists' ) . '</p>' ] );
		}

		$lists = [];
		foreach ( $enabled_lists as $post_type ) {
			if ( ! WC_Customer_Lists_List_Registry::get_list_config( $post_type ) ) {
				continue;
			}

			$config = WC_Customer_Lists_List_Registry::$list_types[ $post_type ] ?? [];
			$posts  = get_posts( [
				'author'      => $user_id,
				'post_type'   => $post_type,
				'post_status' => 'private',
				'numberposts' => -1,
			] );

			foreach ( $posts as $post ) {
				$lists[] = [
					'id'              => $post->ID,
					'title'           => get_the_title( $post->ID ),
					'post_type'       => $post_type,
					'supports_events' => ! empty( $config['supports_events'] ),
				];
			}
		}

		if ( empty( $lists ) ) {
			wp_send_json_success( [
				'html' => '<p>' . esc_html__( 'No lists found. Create one first!', 'wc-customer-lists' ) . '</p>',
			] );
		}

		ob_start();
		?>
		<label for="wc_list_id"><?php esc_html_e( 'Select a list:', 'wc-customer-lists' ); ?></label>
		<select name="wc_list_id" id="wc_list_id">
			<?php foreach ( $lists as $list ) : ?>
				<option value="<?php echo esc_attr( $list['id'] ); ?>"
						data-supports-events="<?php echo esc_attr( $list['supports_events'] ? '1' : '0' ); ?>">
					<?php echo esc_html( $list['title'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<div id="wc_event_fields_container"></div>
		<?php
		wp_send_json_success( [ 'html' => ob_get_clean() ] );
	}

	/**
	 * AJAX: Add product to list (create OR append).
	 */
	public function add_product_to_list(): void {
		check_ajax_referer( 'wc_customer_lists_nonce', 'nonce' );

		$user_id    = get_current_user_id();
		$product_id = absint( $_POST['product_id'] ?? 0 );
		$list_id    = absint( $_POST['list_id'] ?? 0 );
		$event_data = $_POST['event_data'] ?? [];

		if ( ! $user_id || ! $product_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid request.', 'wc-customer-lists' ) ] );
		}

		try {
			if ( $list_id ) {
				// Add to existing list.
				$list = WC_Customer_Lists_List_Engine::get( $list_id );

				// Enabled list type check.
				$enabled_lists = $this->settings['enabled_lists'] ?? [];
				if ( ! in_array( $list->get_post_type(), $enabled_lists, true ) ) {
					wp_send_json_error( [ 'message' => __( 'List type disabled.', 'wc-customer-lists' ) ] );
				}

				// Ownership.
				if ( $list->get_owner_id() !== $user_id ) {
					wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wc-customer-lists' ) ] );
				}
			} else {
				// CREATE new list.
				$post_type  = sanitize_key( $_POST['post_type'] ?? '' );
				$list_title = sanitize_text_field( $_POST['list_title'] ?? '' );

				if ( ! $post_type || ! $list_title ) {
					throw new InvalidArgumentException( __( 'Missing list details.', 'wc-customer-lists' ) );
				}

				$list = WC_Customer_Lists_List_Engine::create( $post_type, $user_id, $list_title );
			}

			// Event fields (if Event_List_Base).
			if ( $list instanceof WC_Customer_Lists_Event_List_Base ) {
				$allowed_fields = [
					'event_name',
					'event_date', 
					'closing_date',
					'delivery_deadline',
				];

				foreach ( $allowed_fields as $field ) {
					if ( ! empty( $event_data[ $field ] ) ) {
						$list->{"set_{$field}"}( sanitize_text_field( $event_data[ $field ] ) );
					}
				}
			}

			// Core operation - throws if limits exceeded.
			$list->set_item( $product_id );

			wp_send_json_success( [
				'message' => __( 'Product added successfully!', 'wc-customer-lists' ),
				'list_id' => $list->get_id(),
			] );

		} catch ( InvalidArgumentException $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		} catch ( Throwable $e ) {
			wp_send_json_error( [ 'message' => __( 'Server error occurred.', 'wc-customer-lists' ) ] );
		}
	}
}
