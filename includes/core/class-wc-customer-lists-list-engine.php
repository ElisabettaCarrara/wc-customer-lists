<?php
/**
 * Core List Engine abstraction.
 *
 * Handles CRUD operations, validation, and permissions for all list types.
 *
 * @package WC_Customer_Lists
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract list engine.
 */
abstract class WC_Customer_Lists_List_Engine {

	/**
	 * List post ID.
	 *
	 * @var int
	 */
	protected $post_id = 0;

	/**
	 * List owner user ID.
	 *
	 * @var int
	 */
	protected $owner_id = 0;

	/**
	 * Constructor.
	 *
	 * @param int $post_id List post ID.
	 *
	 * @throws InvalidArgumentException Invalid post.
	 */
	public function __construct( $post_id ) {

		$this->post_id = (int) $post_id;

		$post = get_post( $this->post_id );

		if ( ! $post instanceof WP_Post ) {
			throw new InvalidArgumentException( esc_html__( 'Invalid list post ID.', 'wc-customer-lists' ) );
		}

		if ( static::get_post_type() !== $post->post_type ) {
			throw new InvalidArgumentException( esc_html__( 'Invalid list type.', 'wc-customer-lists' ) );
		}

		$this->owner_id = (int) $post->post_author;
	}

	/**
	 * Returns the list type slug.
	 *
	 * @return string
	 */
	abstract public static function get_type();

	/**
	 * Returns the CPT slug used by this list type.
	 *
	 * @return string
	 */
	abstract public static function get_post_type();

	/**
	 * Validate list constraints.
	 *
	 * @throws InvalidArgumentException Validation error.
	 */
	public function validate() {

		$items  = $this->get_items();
		$limits = $this->get_limits();

		$max_items = (int) $limits['max_items'];

		if ( $max_items > 0 && count( $items ) > $max_items ) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: %d: Maximum number of items allowed. */
					esc_html__( 'This list cannot have more than %d items.', 'wc-customer-lists' ),
					$max_items
				)
			);
		}
	}

	/**
	 * Get list owner ID.
	 *
	 * @return int
	 */
	public function get_owner_id() {
		return (int) $this->owner_id;
	}

	/**
	 * Get list ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return (int) $this->post_id;
	}

	/**
	 * Get limits from settings for this list type.
	 *
	 * @return array
	 */
	protected function get_limits() {

		$settings  = get_option( 'wc_customer_lists_settings', array() );
		$post_type = static::get_post_type();

		$limits = array();

		if ( isset( $settings['list_limits'][ $post_type ] ) ) {
			$limits = $settings['list_limits'][ $post_type ];
		}

		return array(
			'max_items'            => isset( $limits['max_items'] ) ? (int) $limits['max_items'] : 0,
			'max_lists'            => isset( $limits['max_lists'] ) ? (int) $limits['max_lists'] : 0,
			'not_purchased_action' => isset( $limits['not_purchased_action'] ) ? $limits['not_purchased_action'] : 'keep',
		);
	}

	/**
	 * Add or update product in list.
	 *
	 * @param int $product_id Product ID.
	 * @param int $quantity   Quantity.
	 *
	 * @throws InvalidArgumentException Invalid data.
	 */
	public function set_item( $product_id, $quantity = 1 ) {

		$product_id = (int) $product_id;
		$quantity   = (int) $quantity;

		if ( $product_id <= 0 ) {
			throw new InvalidArgumentException( esc_html__( 'Invalid product ID.', 'wc-customer-lists' ) );
		}

		if ( $quantity <= 0 ) {
			$this->remove_item( $product_id );
			return;
		}

		$limits    = $this->get_limits();
		$max_items = (int) $limits['max_items'];
		$items     = $this->get_items();

		if (
			$max_items > 0 &&
			! isset( $items[ $product_id ] ) &&
			count( $items ) >= $max_items
		) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: %d: Maximum items allowed. */
					esc_html__( 'This list already has the maximum allowed items (%d).', 'wc-customer-lists' ),
					$max_items
				)
			);
		}

		update_post_meta(
			$this->post_id,
			'_item_' . $product_id,
			$quantity
		);
	}

	/**
	 * Remove product from list.
	 *
	 * @param int $product_id Product ID.
	 */
	public function remove_item( $product_id ) {
		delete_post_meta( $this->post_id, '_item_' . (int) $product_id );
	}

	/**
	 * Get all items in list.
	 *
	 * @return array
	 */
	public function get_items() {

		$meta  = get_post_meta( $this->post_id );
		$items = array();

		foreach ( $meta as $key => $values ) {

			if ( 0 === strpos( $key, '_item_' ) ) {

				$product_id = (int) str_replace( '_item_', '', $key );

				if ( isset( $values[0] ) ) {
					$items[ $product_id ] = (int) $values[0];
				}
			}
		}

		return $items;
	}

	/**
	 * Check if current user can manage this list.
	 *
	 * @return bool
	 */
	public function current_user_can_manage() {

		$user_id = get_current_user_id();

		if ( user_can( $user_id, 'manage_woocommerce' ) ) {
			return true;
		}

		return (int) $user_id === (int) $this->owner_id;
	}

	/**
	 * Get configured action for not-purchased items.
	 *
	 * @return string
	 */
	public function get_not_purchased_action() {

		$limits = $this->get_limits();

		return isset( $limits['not_purchased_action'] )
			? $limits['not_purchased_action']
			: 'keep';
	}

	/**
	 * Create new list.
	 *
	 * @param string $post_type Post type.
	 * @param int    $owner_id  Owner ID.
	 * @param string $title     List title.
	 *
	 * @return static
	 *
	 * @throws InvalidArgumentException Error creating list.
	 */
	public static function create( $post_type, $owner_id, $title ) {

		$config    = WC_Customer_Lists_List_Registry::get_list_config( $post_type );
		$max_lists = isset( $config['max_lists'] ) ? (int) $config['max_lists'] : 0;

		if ( $max_lists > 0 ) {

			$existing = get_posts(
				array(
					'author'        => $owner_id,
					'post_type'     => $post_type,
					'post_status'   => array( 'publish', 'private' ),
					'posts_per_page'=> $max_lists + 1,
					'fields'        => 'ids',
					'no_found_rows' => true,
				)
			);

			if ( count( $existing ) >= $max_lists ) {
				throw new InvalidArgumentException(
					sprintf(
						/* translators: 1: max lists 2: list type */
						esc_html__( 'You can only create %1$d list(s) of type "%2$s".', 'wc-customer-lists' ),
						$max_lists,
						$post_type
					)
				);
			}
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => $post_type,
				'post_status' => 'private',
				'post_title'  => $title,
				'post_author' => $owner_id,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			throw new InvalidArgumentException( $post_id->get_error_message() );
		}

		$list = new static( (int) $post_id );
		$list->validate();

		return $list;
	}

	/**
	 * Get existing list.
	 *
	 * @param int $post_id List ID.
	 *
	 * @return static
	 */
	public static function get( $post_id ) {

		return new static( (int) $post_id );
	}
}
