<?php
/**
 * Wishlist Concrete.
 *
 * Simple product wishlist functionality.
 *
 * @package WC_Customer_Lists
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Customer_Lists_Wishlist extends WC_Customer_Lists_Wishlist_Base {

	/**
	 * Constructor.
	 *
	 * @param int $post_id List post ID.
	 */
	public function __construct( int $post_id ) {
		parent::__construct( $post_id );
	}

	/**
	 * List type identifier.
	 */
	public static function get_type(): string {
		return 'wishlist';
	}

	/**
	 * Post type slug.
	 */
	public static function get_post_type(): string {
		return 'wc_wishlist';
	}

	/**
	 * CPT registration args.
	 */
	public static function get_post_type_args(): array {
		return [
			'label'                 => __( 'Wishlists', 'wc-customer-lists' ),
			'labels'                => [
				'name'          => __( 'Wishlists', 'wc-customer-lists' ),
				'singular_name' => __( 'Wishlist', 'wc-customer-lists' ),
				'menu_name'     => __( 'Wishlists', 'wc-customer-lists' ),
				'add_new'       => __( 'Add Wishlist', 'wc-customer-lists' ),
				'add_new_item'  => __( 'Add New Wishlist', 'wc-customer-lists' ),
			],
			'description'           => __( 'Customer wishlists.', 'wc-customer-lists' ),
			'public'                => false,
			'publicly_queryable'    => false,
			'show_ui'               => true,
			'show_in_menu'          => 'woocommerce',
			'query_var'             => false,
			'rewrite'               => false,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'has_archive'           => false,
			'hierarchical'          => false,
			'menu_position'         => null,
			'menu_icon'             => 'dashicons-star-filled',
			'supports'              => [ 'title' ],
			'show_in_rest'          => false,
			'delete_with_user'      => false,
		];
	}

	/**
	 * Validate wishlist constraints.
	 *
	 * @throws InvalidArgumentException
	 */
	public function validate(): void {
		parent::validate();

		// Title length minimum.
		$post = get_post( $this->post_id );
		if ( $post && strlen( $post->post_title ) < 3 ) {
			throw new InvalidArgumentException(
				__( 'Wishlist title must be at least 3 characters.', 'wc-customer-lists' )
			);
		}
	}
}
