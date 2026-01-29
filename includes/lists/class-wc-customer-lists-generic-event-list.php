<?php
/**
 * Generic Event List.
 *
 * Concrete class for user-configurable event-based lists.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

namespace WC_Customer_Lists\Lists;

use WC_Customer_Lists\Core\List_Registry;

class Generic_Event_List extends Event_List {

    /**
     * Constructor.
     *
     * Sets the event type to 'generic' automatically.
     *
     * @param int $post_id List post ID
     */
    public function __construct( int $post_id ) {
        parent::__construct( $post_id );

        // Set event type if not already set
        if ( ! $this->get_event_type() ) {
            $this->set_event_type( 'generic' );
        }
    }

    /**
     * Provide CPT registration args.
     *
     * @return array CPT args
     */
    public static function get_post_type_args(): array {
        return [
            'label'               => __( 'Event Lists', 'wc-customer-lists' ),
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
     * Validate generic event constraints.
     *
     * For example, max per user if needed can be enforced here.
     */
    public function validate(): void {
        parent::validate();

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
                    sprintf( 'Maximum of %d generic event list(s) allowed per user.', $max_per_user )
                );
            }
        }
    }
}
