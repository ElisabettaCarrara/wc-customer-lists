<?php
/**
 * Generic Event List Concrete.
 *
 * User-configurable generic event lists.
 *
 * @package WC_Customer_Lists
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Customer_Lists_Generic_Event_List extends WC_Customer_Lists_Event_List_Base {

	/**
	 * Constructor - auto-sets event type.
	 *
	 * @param int $post_id List post ID.
	 */
	public function __construct( int $post_id ) {
		parent::__construct( $post_id );

		if ( empty( $this->get_event_type() ) ) {
			$this->set_event_type( 'generic' );
		}
	}

	/**
	 * List type identifier.
	 */
	public static function get_type(): string {
		return 'generic';
	}

	/**
	 * Post type slug.
	 */
	public static function get_post_type(): string {
		return 'wc_event_list';
	}

	/**
	 * CPT registration args.
	 */
	public static function get_post_type_args(): array {
		return [
			'label'                 => __( 'Event Lists', 'wc-customer-lists' ),
			'labels'                => [
				'name'          => __( 'Event Lists', 'wc-customer-lists' ),
				'singular_name' => __( 'Event List', 'wc-customer-lists' ),
				'menu_name'     => __( 'Event Lists', 'wc-customer-lists' ),
				'add_new'       => __( 'Add Event List', 'wc-customer-lists' ),
				'add_new_item'  => __( 'Add New Event List', 'wc-customer-lists' ),
			],
			'description'           => __( 'Customer generic event registry lists.', 'wc-customer-lists' ),
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
			'menu_icon'             => 'dashicons-calendar-alt',
			'supports'              => [ 'title' ],
			'show_in_rest'          => false,
			'delete_with_user'      => false,
		];
	}

	/**
	 * Validate generic event constraints.
	 *
	 * @throws InvalidArgumentException
	 */
	public function validate(): void {
		parent::validate();

		// Max lists per user (inherits List_Engine logic).
		// No additional generic-specific validation needed.
	}
}
