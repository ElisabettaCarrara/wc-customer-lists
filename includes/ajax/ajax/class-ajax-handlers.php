<?php
/**
 * AJAX Handlers for WC Customer Lists.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

namespace WC_Customer_Lists\Ajax;

use WC_Customer_Lists\Core\List_Registry;
use WC_Customer_Lists\Core\List_Engine;
use WC_Customer_Lists\Lists\Event_List;

class Ajax_Handlers {

    public function __construct() {
        add_action( 'wp_ajax_wccl_get_user_lists', array( $this, 'get_user_lists' ) );
        add_action( 'wp_ajax_wccl_add_product_to_list', array( $this, 'add_product_to_list' ) );
    }

    /**
     * Fetch all lists for the current user and build HTML dropdown
     */
    public function get_user_lists(): void {
        check_ajax_referer( 'wc_customer_lists_nonce', 'nonce' );

        $user_id = get_current_user_id();
        $html    = '';

        $lists = [];

        foreach ( List_Registry::get_registered_types() as $post_type => $config ) {

            $args = [
                'author'      => $user_id,
                'post_type'   => $post_type,
                'post_status' => 'private',
                'numberposts' => -1,
            ];

            $user_posts = get_posts( $args );

            // Respect max_per_user
            $max_per_user = $config['max_per_user'] ?? 0;
            if ( $max_per_user > 0 && count( $user_posts ) >= $max_per_user ) {
                continue; // Skip, user reached max
            }

            foreach ( $user_posts as $post ) {
                $lists[ $post->ID ] = [
                    'title' => get_the_title( $post ),
                    'post_type' => $post_type,
                    'supports_events' => $config['supports_events'] ?? false,
                ];
            }
        }

        if ( empty( $lists ) ) {
            $html .= '<p>' . esc_html__( 'No lists available. Create one first!', 'wc-customer-lists' ) . '</p>';
        } else {
            $html .= '<label for="wc_list_id">' . esc_html__( 'Select a list', 'wc-customer-lists' ) . '</label>';
            $html .= '<select name="wc_list_id" id="wc_list_id">';
            foreach ( $lists as $id => $list ) {
                $html .= sprintf(
                    '<option value="%1$d" data-type="%2$s" data-supports-events="%3$s">%4$s</option>',
                    esc_attr( $id ),
                    esc_attr( $list['post_type'] ),
                    esc_attr( $list['supports_events'] ? '1' : '0' ),
                    esc_html( $list['title'] )
                );
            }
            $html .= '</select>';

            // Container for dynamic event fields
            $html .= '<div id="wc_event_fields_container"></div>';
        }

        wp_send_json_success( [ 'html' => $html ] );
    }

    /**
     * Add product to selected list
     */
    public function add_product_to_list(): void {
        check_ajax_referer( 'wc_customer_lists_nonce', 'nonce' );

        $user_id    = get_current_user_id();
        $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        $list_id    = isset( $_POST['list_id'] ) ? intval( $_POST['list_id'] ) : 0;
        $event_data = $_POST['event_data'] ?? []; // optional data for event lists

        if ( ! $product_id || ! $list_id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid product or list.', 'wc-customer-lists' ) ] );
        }

        try {
            $list = List_Registry::get( $list_id );

            // Ownership check
            if ( $list->get_owner_id() !== $user_id ) {
                wp_send_json_error( [ 'message' => __( 'You do not own this list.', 'wc-customer-lists' ) ] );
            }

            // Max-per-user check for event lists
            $config = List_Registry::get_list_config( $list->get_post_type() );
            $max_per_user = $config['max_per_user'] ?? 0;
            if ( $max_per_user > 0 ) {
                $args = [
                    'author'      => $user_id,
                    'post_type'   => $list->get_post_type(),
                    'post_status' => 'private',
                    'numberposts' => -1,
                ];
                $existing_posts = get_posts( $args );
                if ( count( $existing_posts ) > $max_per_user ) {
                    wp_send_json_error( [ 'message' => __( 'You reached the maximum number of this type of list.', 'wc-customer-lists' ) ] );
                }
            }

            // If this is an Event List, validate required event fields
            if ( $list instanceof Event_List && $config['supports_events'] ?? false ) {
                foreach ( $event_data as $key => $value ) {
                    if ( empty( $value ) ) {
                        wp_send_json_error( [ 'message' => sprintf( __( 'Please fill in the %s field.', 'wc-customer-lists' ), esc_html( $key ) ) ] );
                    }
                    // Save event meta
                    update_post_meta( $list_id, '_'.$key, sanitize_text_field( $value ) );
                }
            }

            // Add product
            $list->set_item( $product_id );

            wp_send_json_success( [ 'message' => __( 'Product added to list!', 'wc-customer-lists' ) ] );

        } catch ( \Exception $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ] );
        }
    }
}
