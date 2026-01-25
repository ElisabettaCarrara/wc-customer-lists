<?php
/**
 * AJAX Handlers for WC Customer Lists.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

namespace WC_Customer_Lists\Ajax;

use WC_Customer_Lists\Core\List_Registry;
use WC_Customer_Lists\Lists\Event_List;

final class Ajax_Handlers {

    private array $settings = [];

    public function __construct() {
        $this->settings = get_option( 'wc_customer_lists_settings', [] );

        add_action( 'wp_ajax_wccl_get_user_lists', [ $this, 'get_user_lists' ] );
        add_action( 'wp_ajax_wccl_add_product_to_list', [ $this, 'add_product_to_list' ] );
    }

    /**
     * Fetch lists for the current user, only enabled ones.
     */
    public function get_user_lists(): void {
        check_ajax_referer( 'wc_customer_lists_nonce', 'nonce' );

        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            wp_send_json_error( [ 'message' => __( 'Not logged in.', 'wc-customer-lists' ) ] );
        }

        $enabled_lists = $this->settings['enabled_lists'] ?? [];
        if ( empty( $enabled_lists ) ) {
            wp_send_json_success( [ 'html' => '<p>' . esc_html__( 'No list types are enabled.', 'wc-customer-lists' ) . '</p>' ] );
        }

        $lists = [];

        foreach ( $enabled_lists as $post_type ) {
            if ( ! isset( List_Registry::$list_types[ $post_type ] ) ) {
                continue; // just in case
            }

            $config = List_Registry::$list_types[ $post_type ];

            $posts = get_posts( [
                'author'      => $user_id,
                'post_type'   => $post_type,
                'post_status' => 'private',
                'numberposts' => -1,
            ] );

            foreach ( $posts as $post ) {
                $lists[] = [
                    'id'              => $post->ID,
                    'title'           => get_the_title( $post ),
                    'post_type'       => $post_type,
                    'supports_events' => ! empty( $config['supports_events'] ),
                ];
            }
        }

        if ( empty( $lists ) ) {
            wp_send_json_success( [
                'html' => '<p>' . esc_html__( 'No lists found.', 'wc-customer-lists' ) . '</p>',
            ] );
        }

        ob_start();
        ?>
        <label for="wc_list_id"><?php esc_html_e( 'Select a list', 'wc-customer-lists' ); ?></label>
        <select name="wc_list_id" id="wc_list_id">
            <?php foreach ( $lists as $list ) : ?>
                <option
                    value="<?php echo esc_attr( $list['id'] ); ?>"
                    data-supports-events="<?php echo esc_attr( $list['supports_events'] ? '1' : '0' ); ?>">
                    <?php echo esc_html( $list['title'] ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div id="wc_event_fields_container"></div>
        <?php

        wp_send_json_success( [ 'html' => ob_get_clean() ] );
    }

    /**
     * Add product to a list.
     */
    public function add_product_to_list(): void {
        check_ajax_referer( 'wc_customer_lists_nonce', 'nonce' );

        $user_id    = get_current_user_id();
        $product_id = absint( $_POST['product_id'] ?? 0 );
        $list_id    = absint( $_POST['list_id'] ?? 0 );
        $event_data = $_POST['event_data'] ?? [];

        if ( ! $user_id || ! $product_id || ! $list_id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid request.', 'wc-customer-lists' ) ] );
        }

        try {
            $list = List_Registry::get( $list_id );

            // Only allow enabled lists
            $enabled_lists = $this->settings['enabled_lists'] ?? [];
            if ( ! in_array( $list->get_post_type(), $enabled_lists, true ) ) {
                wp_send_json_error( [ 'message' => __( 'This list type is disabled.', 'wc-customer-lists' ) ] );
            }

            if ( $list->get_owner_id() !== $user_id ) {
                wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wc-customer-lists' ) ] );
            }

            // Validate event data (if applicable)
            if ( $list instanceof Event_List ) {
                $allowed_fields = [
                    'event_name',
                    'event_date',
                    'closing_date',
                    'delivery_deadline',
                ];

                foreach ( $allowed_fields as $field ) {
                    if ( empty( $event_data[ $field ] ) ) {
                        wp_send_json_error( [
                            'message' => sprintf(
                                __( 'Missing required field: %s', 'wc-customer-lists' ),
                                esc_html( $field )
                            ),
                        ] );
                    }

                    update_post_meta(
                        $list_id,
                        '_' . $field,
                        sanitize_text_field( $event_data[ $field ] )
                    );
                }
            }

            $list->set_item( $product_id );

            wp_send_json_success( [
                'message' => __( 'Product added to list.', 'wc-customer-lists' ),
            ] );

        } catch ( \Throwable $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ] );
        }
    }
}

// Bootstrap
new Ajax_Handlers();
