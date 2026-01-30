<?php
/**
 * Baby/Baptism List Concrete.
 *
 * Event-based baby registry list.
 *
 * @package    wc-customer-lists
 * @since      1.0.0
 */

namespace WC_Customer_Lists\Lists;

use WC_Customer_Lists\Core\List_Registry;

defined( 'ABSPATH' ) || exit;

class Baby_List extends Event_List {

	/**
	 * Baby-specific meta.
	 */
	protected const META_BABY_NAME     = '_baby_name';
	protected const META_CEREMONY_TYPE = '_ceremony_type';

	/**
	 * Constructor—auto-sets 'baby' type.
	 *
	 * @since 1.0.0
	 * @param int $post_id List post ID.
	 */
	public function __construct( int $post_id ) {
		parent::__construct( $post_id );

		if ( ! $this->get_event_type() ) {
			$this->set_event_type( 'baby' );
		}
	}

	/**
	 * Get baby name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_baby_name(): string {
		return (string) get_post_meta( $this->post_id, self::META_BABY_NAME, true );
	}

	/**
	 * Set baby name.
	 *
	 * @since 1.0.0
	 * @param string $name Name.
	 */
	public function set_baby_name( string $name ): void {
		update_post_meta( $this->post_id, self::META_BABY_NAME, sanitize_text_field( $name ) );
	}

	/**
	 * Get ceremony type (baptism, naming, etc.).
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_ceremony_type(): string {
		return (string) get_post_meta( $this->post_id, self::META_CEREMONY_TYPE, true );
	}

	/**
	 * Set ceremony type.
	 *
	 * @since 1.0.0
	 * @param string $type Type slug.
	 */
	public function set_ceremony_type( string $type ): void {
		update_post_meta( $this->post_id, self::META_CEREMONY_TYPE, sanitize_text_field( $type ) );
	}

	/**
	 * List type slug.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_type(): string {
		return 'baby';
	}

	/**
	 * CPT slug.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_post_type(): string {
		return 'wc_baby_list';
	}

	/**
	 * CPT args for registry.
	 *
	 * @since 1.0.0
	 * @return array<string,mixed>
	 */
	public static function get_post_type_args(): array {
		return [
			'label'               => _x( 'Baby Lists', 'post type general name', 'wc-customer-lists' ),
			'labels'              => [
				'name'          => _x( 'Baby Lists', 'post type general name', 'wc-customer-lists' ),
				'singular_name' => _x( 'Baby List', 'post type singular name', 'wc-customer-lists' ),
				'menu_name'     => _x( 'Baby Lists', 'admin menu', 'wc-customer-lists' ),
				'add_new'       => __( 'Add Baby List', 'wc-customer-lists' ),
				'add_new_item'  => __( 'Add New Baby List', 'wc-customer-lists' ),
			],
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'woocommerce',
			'menu_icon'           => 'dashicons-businessman', // Baby icon.
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'supports'            => [ 'title', 'author' ],
			'has_archive'         => false,
			'show_in_rest'        => true,
			'rewrite'             => [ 'slug' => 'baby-list' ],
			'publicly_queryable'  => false,
		];
	}
}
