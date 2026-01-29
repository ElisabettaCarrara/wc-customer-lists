<?php
/**
 * Core List Engine abstraction.
 *
 * @package WC_Customer_Lists
 */

namespace WC_Customer_Lists\Core;

defined( 'ABSPATH' ) || exit;

use WP_Post;

abstract class List_Engine {

	protected int $post_id;
	protected int $owner_id;

	public function __construct( int $post_id ) {
		$this->post_id = $post_id;

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			throw new \InvalidArgumentException( 'Invalid list post ID.' );
		}

		$this->owner_id = (int) $post->post_author;
	}

	/**
	 * Returns the list type slug.
	 */
	abstract public static function get_type(): string;

	/**
	 * Returns the CPT slug used by this list type.
	 */
	abstract public static function get_post_type(): string;

	/**
	 * Validate list-specific constraints.
	 *
	 * Called on creation/update.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function validate(): void {
		$items  = $this->get_items();
		$limits = $this->get_limits();

		$max_items = (int) ( $limits['max_items'] ?? 0 );

		if ( $max_items > 0 && count( $items ) > $max_items ) {
			throw new \InvalidArgumentException(
				sprintf(
					__( 'This list cannot have more than %d items.', 'wc-customer-lists' ),
					$max_items
				)
			);
		}
	}

	/**
	 * Get the list owner ID.
	 */
	public function get_owner_id(): int {
		return $this->owner_id;
	}

	/**
	 * Get the list ID.
	 */
	public function get_id(): int {
		return $this->post_id;
	}

	/**
	 * Get plugin settings-based limits for this list type.
	 *
	 * @return array{
	 *     max_items:int,
	 *     not_purchased_action:string
	 * }
	 */
	protected function get_limits(): array {
		$settings  = get_option( 'wc_customer_lists_settings', [] );
		$post_type = static::get_post_type();
		$limits    = $settings['list_limits'][ $post_type ] ?? [];

		return [
			'max_items'            => isset( $limits['max_items'] ) ? (int) $limits['max_items'] : 0,
			'not_purchased_action' => $limits['not_purchased_action'] ?? 'keep',
		];
	}

	/**
	 * Add or update a product in the list.
	 *
	 * @param int $product_id
	 * @param int $quantity
	 *
	 * @throws \InvalidArgumentException
	 */
	public function set_item( int $product_id, int $quantity = 1 ): void {
		if ( $quantity <= 0 ) {
			$this->remove_item( $product_id );
			return;
		}

		$limits    = $this->get_limits();
		$max_items = (int) ( $limits['max_items'] ?? 0 );
		$items     = $this->get_items();

		// Prevent adding new items if max_items reached
		if (
			$max_items > 0 &&
			! isset( $items[ $product_id ] ) &&
			count( $items ) >= $max_items
		) {
			throw new \InvalidArgumentException(
				sprintf(
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
	 * Remove a product from the list.
	 */
	public function remove_item( int $product_id ): void {
		delete_post_meta( $this->post_id, '_item_' . $product_id );
	}

	/**
	 * Get all items in the list.
	 *
	 * @return array<int,int> product_id => quantity
	 */
	public function get_items(): array {
		$meta  = get_post_meta( $this->post_id );
		$items = [];

		foreach ( $meta as $key => $values ) {
			if ( str_starts_with( $key, '_item_' ) ) {
				$product_id          = (int) str_replace( '_item_', '', $key );
				$items[ $product_id ] = (int) $values[0];
			}
		}

		return $items;
	}

	/**
	 * Check if the current user can manage this list.
	 */
	public function current_user_can_manage(): bool {
		$user_id = get_current_user_id();

		if ( user_can( $user_id, 'manage_woocommerce' ) ) {
			return true;
		}

		return $user_id === $this->owner_id;
	}

	/**
	 * Get the configured action for not-purchased items.
	 */
	public function get_not_purchased_action(): string {
		$limits = $this->get_limits();
		return $limits['not_purchased_action'] ?? 'keep';
	}
}
