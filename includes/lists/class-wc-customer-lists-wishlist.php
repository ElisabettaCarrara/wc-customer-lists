<?php
/**
 * Concrete Wishlist class.
 *
 * Handles the WooCommerce wishlist CPT and logic.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

namespace WC_Customer_Lists\Lists;

use WC_Customer_Lists\Core\List_Registry;

class Wishlist extends Wishlist_Base {

    /**
     * Provide CPT registration args.
     *
     * @return array
     */
    public static function get_post_type_args(): array {
        return [
            'label'               => __( 'Wishlists', 'wc-customer-lists' ),
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'capability_type'     => 'post',
            'supports'            => [ 'title', 'editor', 'author' ],
            'has_archive'         => false,
            'show_in_rest'        => true,
        ];
    }

    /**
     * Constructor.
     *
     * @param int $post_id
     */
    public function __construct( int $post_id ) {
        parent::__construct( $post_id );
    }

    /**
     * Validate wishlist constraints.
     *
     * Uses base logic but can extend for custom rules.
     */
    public function validate(): void {
        parent::validate();

        // Example: enforce a minimum title length
        if ( strlen( $this->get_title() ?? '' ) < 3 ) {
            throw new \InvalidArgumentException( __( 'Wishlist title must be at least 3 characters.', 'wc-customer-lists' ) );
        }
    }

    /**
     * Get wishlist title.
     */
    public function get_title(): string {
        return (string) get_post_field( 'post_title', $this->post_id );
    }

    /**
     * Set wishlist title.
     */
    public function set_title( string $title ): void {
        wp_update_post( [
            'ID'         => $this->post_id,
            'post_title' => sanitize_text_field( $title ),
        ] );
    }
}
