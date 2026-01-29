<?php
/**
 * Baby / Baptism Event List.
 *
 * Concrete class for baby event-based lists.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

namespace WC_Customer_Lists\Lists;

use WC_Customer_Lists\Core\List_Registry;

class Baby_List extends Event_List {

    protected const META_BABY_NAME      = '_baby_name';
    protected const META_CEREMONY_TYPE  = '_ceremony_type';

    /**
     * Constructor.
     *
     * Sets the event type to 'baby' automatically.
     *
     * @param int $post_id List post ID
     */
    public function __construct( int $post_id ) {
        parent::__construct( $post_id );

        // Set event type if not already set
        if ( ! $this->get_event_type() ) {
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
     * Get ceremony type.
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
     * Provide CPT registration args.
     *
     * @return array CPT args
     */
    public static function get_post_type_args(): array {
        return [
            'label'               => __( 'Baby Lists', 'wc-customer-lists' ),
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'capability_type'     => 'post',
            'supports'            => [ 'title', 'editor', 'author' ],
            'has_archive'         => false,
            'show_in_rest'        => true,
        ];
    }
}
