<?php
/**
 * Core List Engine abstraction.
 *
 * Handles CRUD operations, validation, permissions for all list types.
 *
 * @package WC_Customer_Lists
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

abstract class WC_Customer_Lists_List_Engine {

	/**
	 * List post ID.
	 *
	 * @var int
	 */
	protected int $post_id;

	/**
	 * List owner user ID.
	 *
	 * @var int
	 */
	protected int $owner_id;

	/**
	 * Constructor.
	 *
	 * @param int $post_id List post ID.
	 * @throws InvalidArgumentException Invalid post.
	 */
	public function __construct( int $post_id ) {
		$this->post_id = $post_id;

		$post = get_post( $post_id );
		if ( ! $post || 'wp_post' !== get_class( $post ) ) {
			throw new InvalidArgumentException( 'Invalid list post ID.' );
		}

		$this->owner_id = (int) $post->post_author;
	}

	/**
	 * Returns the list type slug.
	 *
	 * @return string
	 */
	abstract public static function get_type(): string;

	/**
	 * Returns the CPT slug used by this list type.
	 *
	 * @return string
	 */
	abstract public static function get_post_type(): string;

	/**
	 * Validate list constraints.
	 *
	 * @throws InvalidArgumentException
	 */
	public function validate(): void {
		$items  = $this->get_items();
		$limits = $this->get_limits();

		$max_items = (int) ( $limits['max_items'] ?? 0 );

		if ( $max_items > 0 && count( $items ) > $max_items ) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: %d: Maximum number of items allowed. */
					__( 'This list cannot have more than %d items.', 'wc-customer-lists' ),
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
	public function get_owner_id(): int {
		return $this->owner_id;
	}

	/**
	 * Get list ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->post_id;
	}

	/**
	 * Get limits from settings for this list type.
	 *
	 * @return array{
	 *     max_items: int,
	 *     max_lists: int,
	 *     not_purchased_action: string
	 * }
	 */
	protected function get_limits(): array {
		$settings  = get_option( 'wc_customer_lists_settings', [] );
		$post_type = static::get_post_type();
		$limits    = $settings['list_limits'][ $post_type ] ?? [];

		return [
			'max_items'             => (int) ( $limits['max_items'] ?? 0 ),
			'max_lists'             => (int) ( $limits['max_lists'] ?? 0 ),
			'not_purchased_action'  => $limits['not_purchased_action'] ?? 'keep',
		];
	}

	/**
	 * Add/update product in list.
	 *
	 * @param int $product_id Product ID.
	 * @param int $quantity   Quantity (default 1).
	 * @throws InvalidArgumentException
	 */
	public function set_item( int $product_id, int $quantity = 1 ): void {
		if ( $quantity <= 0 ) {
			$this->remove_item( $product_id );
			return;
		}

		$limits    = $this->get_limits();
		$max_items = (int) ( $limits['max_items'] ?? 0 );
		$items     = $this->get_items();

		if (
			$max_items > 0 &&
			! isset( $items[ $product_id ] ) &&
			count( $items ) >= $max_items
		) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: %d: Maximum number of items allowed. */
					__( 'This list already has the maximum allowed items (%d).', 'wc-customer-lists' ),
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
	public function remove_item( int $product_id ): void {
		delete_post_meta( $this->post_id, '_item_' . $product_id );
	}

	/**
	 * Get all items in list.
	 *
	 * @return array<int,int>
	 */
	public function get_items(): array {
		$meta  = get_post_meta( $this->post_id );
		$items = [];

		foreach ( $meta as $key => $values ) {
			if ( str_starts_with( $key, '_item_' ) ) {
				$product_id             = (int) str_replace( '_item_', '', $key );
				$items[ $product_id ]   = (int) $values[0];
			}
		}

		return $items;
	}

	/**
	 * Check if current user can manage this list.
	 *
	 * @return bool
	 */
	public function current_user_can_manage(): bool {
		$user_id = get_current_user_id();

		if ( user_can( $user_id, 'manage_woocommerce' ) ) {
			return true;
		}

		return $user_id === $this->owner_id;
	}

	/**
	 * Get configured action for not-purchased items.
	 *
	 * @return string
	 */
	public function get_not_purchased_action(): string {
		$limits = $this->get_limits();
		return $limits['not_purchased_action'] ?? 'keep';
	}

	/**
	 * Create new list (static factory).
	 *
	 * @param string $post_type List type.
	 * @param int    $owner_id  User ID.
	 * @param string $title     List title.
	 * @return static
	 * @throws InvalidArgumentException
	 */
	public static function create( string $post_type, int $owner_id, string $title ): static {
		$config     = WC_Customer_Lists_List_Registry::get_list_config( $post_type );
		$max_lists  = (int) ( $config['max_lists'] ?? 0 );

		if ( $max_lists > 0 ) {
			$existing = get_posts( [
				'author'        => $owner_id,
				'post_type'     => $post_type,
				'post_status'   => [ 'publish', 'private' ],
				'numberposts'   => $max_lists + 1,
				'fields'        => 'ids',
				'no_found_rows' => true,
			] );

			if ( count( $existing ) >= $max_lists ) {
				throw new InvalidArgumentException(
					sprintf(
						/* translators: 1: Max lists, 2: Post type. */
						__( 'You can only create %1$d list(s) of type "%2$s".', 'wc-customer-lists' ),
						$max_lists,
						$post_type
					)
				);
			}
		}

		$post_id = wp_insert_post( [
			'post_type'    => $post_type,
			'post_status'  => 'private',
			'post_title'   => $title,
			'post_author'  => $owner_id,
		], true );

		if ( is_wp_error( $post_id ) ) {
			throw new InvalidArgumentException( $post_id->get_error_message() );
		}

		/** @var static */
		$list = new static( (int) $post_id );
		$list->validate();

		return $list;
	}

	/**
	 * Get existing list.
	 *
	 * @param int $post_id List post ID.
	 * @return static
	 * @throws InvalidArgumentException
	 */
	public static function get( int $post_id ): static {
		/** @var static */
		$list = new static( $post_id );
		return $list;
	}
}
