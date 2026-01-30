<?php
/**
 * Bridal List Concrete.
 *
 * Event-based bridal registry list.
 *
 * @package    wc-customer-lists
 * @since      1.0.0
 */

namespace WC_Customer_Lists\Lists;

use WC_Customer_Lists\Core\List_Registry;

defined( 'ABSPATH' ) || exit;

class Bridal_List extends Event_List {

	/**
	 * Bridal-specific meta.
	 */
	protected const META_SPOUSE_1 = '_spouse_1';
	protected const META_SPOUSE_2 = '_spouse_2';

	/**
	 * Constructor—auto-sets 'bridal' type.
	 *
	 * @since 1.0.0
	 * @param int $post_id List post ID.
	 */
	public function __construct( int $post_id ) {
		parent::__construct( $post_id );

		if ( ! $this->get_event_type() ) {
			$this->set_event_type( 'bridal' );
		}
	}

	/**
	 * Get spouse 1 name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_spouse_1(): string {
		return (string) get_post_meta( $this->post_id, self::META_SPOUSE_1, true );
	}

	/**
	 * Set spouse 1 name.
	 *
	 * @since 1.0.0
	 * @param string $name Name.
	 */
	public function set_spouse_1( string $name ): void {
		update_post_meta( $this->post_id, self::META_SPOUSE_1, sanitize_text_field( $name ) );
	}

	/**
	 * Get spouse 2 name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_spouse_2(): string {
		return (string) get_post_meta( $this->post_id, self::META_SPOUSE_2, true );
	}

	/**
	 * Set spouse 2 name.
	 *
	 * @since 1.0.0
	 * @param string $name Name.
	 */
	public function set_spouse_2( string $name ): void {
		update_post_meta( $this->post_id, self::META_SPOUSE_2, sanitize_text_field( $name ) );
	}

	/**
	 * List type slug.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_type(): string {
		return 'bridal';
	}

	/**
	 * CPT slug.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_post_type(): string {
		return 'wc_bridal_list';
	}

	/**
	 * CPT args for registry.
	 *
	 * @since 1.0.0
	 * @return array<string,mixed>
	 */
	public static function get_post_type_args(): array {
		return [
			'label'               => _x( 'Bridal Lists', 'post type general name', 'wc-customer-lists' ),
			'labels'              => [
				'name'          => _x( 'Bridal Lists', 'post type general name', 'wc-customer-lists' ),
				'singular_name' => _x( 'Bridal List', 'post type singular name', 'wc-customer-lists' ),
				'menu_name'     => _x( 'Bridal Lists', 'admin menu', 'wc-customer-lists' ),
				'add_new'       => __( 'Add Bridal List', 'wc-customer-lists' ),
				'add_new_item'  => __( 'Add New Bridal List', 'wc-customer-lists' ),
			],
			'public'              => false, // Private by default.
			'show_ui'             => true,
			'show_in_menu'        => 'woocommerce',
			'menu_icon'           => 'dashicons-heart',
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'supports'            => [ 'title', 'author' ], // No editor.
			'has_archive'         => false,
			'show_in_rest'        => true,
			'rewrite'             => [ 'slug' => 'bridal-list' ],
			'publicly_queryable'  => false,
		];
	}
}
