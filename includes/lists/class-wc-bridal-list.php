<?php
/**
 * Bridal List.
 *
 * Concrete class for bridal event-based lists.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

namespace WC_Customer_Lists\Lists;

use WC_Customer_Lists\Core\List_Registry;

class Bridal_List extends Event_List {

    protected const META_SPOUSE_1 = '_spouse_1';
    protected const META_SPOUSE_2 = '_spouse_2';

    /**
     * Constructor.
     *
     * Sets the event type to 'bridal' automatically.
     *
     * @param int $post_id List post ID
     */
    public function __construct( int $post_id ) {
        parent::__construct( $post_id );

        // Set event type if not already set
        if ( ! $this->get_event_type() ) {
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
     * Provide CPT registration args.
     *
     * @return array CPT args
     */
    public static function get_post_type_args(): array {
        return [
            'label'               => __( 'Bridal Lists', 'wc-customer-lists' ),
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
