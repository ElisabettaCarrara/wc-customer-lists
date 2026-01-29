<?php
/**
 * Abstract Wishlist.
 *
 * Base class for all wishlist types.
 * Handles core wishlist behavior.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

namespace WC_Customer_Lists\Lists;

use WC_Customer_Lists\Core\List_Engine;
use WC_Customer_Lists\Core\List_Registry;

abstract class Wishlist_Base extends List_Engine {

    /**
     * Meta key for optional description or notes.
     */
    protected const META_DESCRIPTION = '_wishlist_description';

    /**
     * Get wishlist description.
     */
    public function get_description(): string {
        return (string) get_post_meta( $this->post_id, self::META_DESCRIPTION, true );
    }

    /**
     * Set wishlist description.
     */
    public function set_description( string $description ): void {
        update_post_meta( $this->post_id, self::META_DESCRIPTION, wp_kses_post( $description ) );
    }

    /**
     * Validate wishlist constraints.
     *
     * E.g., enforce max per user.
     */
    public function validate(): void {
        $config = List_Registry::get_list_config( $this->get_post_type() );
        $max_per_user = $config['max_per_user'] ?? 0;

        if ( $max_per_user > 0 ) {
            $user_lists = get_posts( [
                'author'      => $this->get_owner_id(),
                'post_type'   => $this->get_post_type(),
                'post_status' => 'any',
                'exclude'     => [ $this->get_id() ],
            ] );

            if ( count( $user_lists ) >= $max_per_user ) {
                throw new \InvalidArgumentException(
                    sprintf( 'Maximum of %d wishlist(s) allowed per user.', $max_per_user )
                );
            }
        }
    }

    /**
     * Get all items in the wishlist.
     *
     * This reuses List_Engine logic.
     *
     * @return array<int,int> product_id => quantity
     */
    public function get_items(): array {
        return parent::get_items();
    }

    /**
     * Add or update a product in the wishlist.
     *
     * @param int $product_id
     * @param int $quantity
     */
    public function set_item( int $product_id, int $quantity = 1 ): void {
        parent::set_item( $product_id, $quantity );
    }

    /**
     * Remove a product from the wishlist.
     *
     * @param int $product_id
     */
    public function remove_item( int $product_id ): void {
        parent::remove_item( $product_id );
    }

    /**
     * Check if current user can manage this wishlist.
     *
     * @return bool
     */
    public function current_user_can_manage(): bool {
        return parent::current_user_can_manage();
    }
}
