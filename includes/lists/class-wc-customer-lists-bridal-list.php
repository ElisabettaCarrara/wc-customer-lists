<?php
/**
 * Bridal List Concrete.
 *
 * Event-based bridal/wedding registry list.
 *
 * @package WC_Customer_Lists
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Customer_Lists_Bridal_List extends WC_Customer_Lists_Event_List_Base {

	/**
	 * Bridal-specific meta keys.
	 */
	protected const META_SPOUSE_1 = '_spouse_1';
	protected const META_SPOUSE_2 = '_spouse_2';

	/**
	 * Constructor - auto-sets event type.
	 *
	 * @param int $post_id List post ID.
	 */
	public function __construct( int $post_id ) {
		parent::__construct( $post_id );

		if ( empty( $this->get_event_type() ) ) {
			$this->set_event_type( 'bridal' );
		}
	}

	/**
	 * Get spouse 1 name.
	 */
	public function get_spouse_1(): string {
		return (string) get_post_meta( $this->post_id, self::META_SPOUSE_1, true );
	}

	/**
	 * Set spouse 1 name.
	 */
	public function set_spouse_1( string $name ): void {
		update_post_meta( $this->post_id, self::META_SPOUSE_1, sanitize_text_field( $name ) );
	}

	/**
	 * Get spouse 2 name.
	 */
	public function get_spouse_2(): string {
		return (string) get_post_meta( $this->post_id, self::META_SPOUSE_2, true );
	}

	/**
	 * Set spouse 2 name.
	 */
	public function set_spouse_2( string $name ): void {
		update_post_meta( $this->post_id, self::META_SPOUSE_2, sanitize_text_field( $name ) );
	}

	/**
	 * List type identifier.
	 */
	public static function get_type(): string {
		return 'bridal';
	}

	/**
	 * Post type slug.
	 */
	public static function get_post_type(): string {
		return 'wc_bridal_list';
	}

	/**
	 * CPT registration args.
	 */
	public static function get_post_type_args(): array {
		return [
			'label'                 => __( 'Bridal Lists', 'wc-customer-lists' ),
			'labels'                => [
				'name'          => __( 'Bridal Lists', 'wc-customer-lists' ),
				'singular_name' => __( 'Bridal List', 'wc-customer-lists' ),
				'menu_name'     => __( 'Bridal Lists', 'wc-customer-lists' ),
				'add_new'       => __( 'Add Bridal List', 'wc-customer-lists' ),
				'add_new_item'  => __( 'Add New Bridal List', 'wc-customer-lists' ),
				'edit_item'     => __( 'Edit Bridal List', 'wc-customer-lists' ),
				'view_item'     => __( 'View Bridal List', 'wc-customer-lists' ),
			],
			'description'           => __( 'Customer bridal/wedding registry lists.', 'wc-customer-lists' ),
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
			'menu_icon'             => 'dashicons-heart',
			'supports'              => [ 'title' ],
			'show_in_rest'          => false,
			'delete_with_user'      => false,
		];
	}
}
