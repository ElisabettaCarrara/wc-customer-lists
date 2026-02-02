<?php
/**
 * Baby List Concrete.
 *
 * Event-based baby registry list.
 *
 * @package WC_Customer_Lists
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Customer_Lists_Baby_List extends WC_Customer_Lists_Event_List_Base {

	/**
	 * Baby-specific meta keys.
	 */
	protected const META_BABY_NAME     = '_baby_name';
	protected const META_CEREMONY_TYPE = '_ceremony_type';

	/**
	 * Constructor - auto-sets event type.
	 *
	 * @param int $post_id List post ID.
	 */
	public function __construct( int $post_id ) {
		parent::__construct( $post_id );

		if ( empty( $this->get_event_type() ) ) {
			$this->set_event_type( 'baby' );
		}
	}

	/**
	 * Get baby name.
	 */
	public function get_baby_name(): string {
		return (string) get_post_meta( $this->post_id, self::META_BABY_NAME, true );
	}

	/**
	 * Set baby name.
	 */
	public function set_baby_name( string $name ): void {
		update_post_meta( $this->post_id, self::META_BABY_NAME, sanitize_text_field( $name ) );
	}

	/**
	 * Get ceremony type (baptism, naming, etc.).
	 */
	public function get_ceremony_type(): string {
		return (string) get_post_meta( $this->post_id, self::META_CEREMONY_TYPE, true );
	}

	/**
	 * Set ceremony type.
	 */
	public function set_ceremony_type( string $type ): void {
		update_post_meta( $this->post_id, self::META_CEREMONY_TYPE, sanitize_text_field( $type ) );
	}

	/**
	 * List type identifier.
	 */
	public static function get_type(): string {
		return 'baby';
	}

	/**
	 * Post type slug.
	 */
	public static function get_post_type(): string {
		return 'wc_baby_list';
	}

	/**
	 * CPT registration args.
	 */
	public static function get_post_type_args(): array {
		return [
			'label'                 => __( 'Baby Lists', 'wc-customer-lists' ),
			'labels'                => [
				'name'          => __( 'Baby Lists', 'wc-customer-lists' ),
				'singular_name' => __( 'Baby List', 'wc-customer-lists' ),
				'menu_name'     => __( 'Baby Lists', 'wc-customer-lists' ),
				'add_new'       => __( 'Add Baby List', 'wc-customer-lists' ),
				'add_new_item'  => __( 'Add New Baby List', 'wc-customer-lists' ),
				'edit_item'     => __( 'Edit Baby List', 'wc-customer-lists' ),
				'view_item'     => __( 'View Baby List', 'wc-customer-lists' ),
			],
			'description'           => __( 'Customer baby registry lists.', 'wc-customer-lists' ),
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
			'menu_icon'             => 'dashicons-groups',
			'supports'              => [ 'title' ],
			'show_in_rest'          => false, // Private lists.
			'delete_with_user'      => false,
		];
	}
}
