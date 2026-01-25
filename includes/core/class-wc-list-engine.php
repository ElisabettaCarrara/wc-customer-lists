<?php
/**
 * Core List Engine abstraction.
 *
 * @package WC_Customer_Lists
 */

declared( 'ABSPATH' ) || exit;

namespace WC_Customer_Lists\Core;

use WP_Post;

abstract class List_Engine {

	protected int $post_id;
	protected int $owner_id;

	public function __construct( int $post_id ) {
		$this->post_id = $post_id;
		$post          = get_post( $post_id );

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
	 * Called on creation/update.
	 */
	public function validate(): void {
		// Default: no constraints.
	}

	/**
	 * Get list owner ID.
	 */
	public function get_owner_id(): int {
		return $this->owner_id;
	}

	/**
	 * Get list ID.
	 */
	public function get_id(): int {
		return $this->post_id;
	}

	/**
 * Add or update a product in the list.
 */
public function set_item( int $product_id, int $quantity = 1 ): void {
    if ( $quantity <= 0 ) {
        $this->remove_item( $product_id );
        return;
    }

    // 1️⃣ Get plugin settings
    $settings = get_option( 'wc_customer_lists_settings', [] );
    $list_type = static::get_post_type();
    $list_limits = $settings['list_limits'][$list_type] ?? [];

    // 2️⃣ Check max items per list
    $max_items = intval( $list_limits['max_items'] ?? 0 ); // 0 = unlimited
    $current_count = count( $this->get_items() );

    if ( $max_items > 0 && ! isset( $this->get_items()[$product_id] ) && $current_count >= $max_items ) {
        throw new \InvalidArgumentException(
            sprintf(
                __( 'This list already has the maximum allowed items (%d).', 'wc-customer-lists' ),
                $max_items
            )
        );
    }

    // 3️⃣ Add/update item
    update_post_meta( $this->post_id, '_item_' . $product_id, $quantity );
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
		$items = array();

		foreach ( $meta as $key => $values ) {
			if ( str_starts_with( $key, '_item_' ) ) {
				$product_id          = (int) str_replace( '_item_', '', $key );
				$items[ $product_id ] = (int) $values[0];
			}
		}

		return $items;
	}

	/**
	 * Check if current user can manage this list.
	 */
	public function current_user_can_manage(): bool {
		$user_id = get_current_user_id();

		if ( user_can( $user_id, 'manage_woocommerce' ) ) {
			return true;
		}

		return $user_id === $this->owner_id;
	}
}
