<?php
/**
 * My Account UI for WC Customer Lists
 *
 * Adds a separate "My Lists" tab in WooCommerce My Account.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

namespace WC_Customer_Lists\UI\MyAccount;

use WC_Customer_Lists\Lists\List_Base;

class MyAccount {

    public function __construct() {
        // Add new My Lists tab
        add_filter( 'woocommerce_account_menu_items', array( $this, 'add_my_lists_tab' ), 10, 1 );
        add_action( 'woocommerce_account_my-lists_endpoint', array( $this, 'render_lists_section' ) );
        add_action( 'init', array( $this, 'add_endpoint' ) );
    }

    /**
     * Register new endpoint for My Lists tab
     */
    public function add_endpoint(): void {
        add_rewrite_endpoint( 'my-lists', EP_PAGES );
    }

    /**
     * Add "My Lists" to My Account menu
     */
    public function add_my_lists_tab( $items ): array {
        $new_items = array();
        foreach ( $items as $key => $label ) {
            $new_items[ $key ] = $label;
            // Insert after orders tab
            if ( 'orders' === $key ) {
                $new_items['my-lists'] = __( 'My Lists', 'wc-customer-lists' );
            }
        }
        return $new_items;
    }

    /**
     * Render the Lists section for the tab
     */
    public function render_lists_section(): void {
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            echo '<p>' . esc_html__( 'You must be logged in to view your lists.', 'wc-customer-lists' ) . '</p>';
            return;
        }

        $user_lists = $this->get_user_lists( $user_id );

        if ( empty( $user_lists ) ) {
            echo '<p>' . esc_html__( 'You have no lists yet.', 'wc-customer-lists' ) . '</p>';
            return;
        }

        echo '<h2>' . esc_html__( 'My Lists', 'wc-customer-lists' ) . '</h2>';

        foreach ( $user_lists as $list ) {
            $this->render_single_list_table( $list );
        }
    }

    /**
     * Fetch all lists for a user
     */
    private function get_user_lists( int $user_id ): array {
        $args = array(
            'post_type'      => 'wc_customer_list',
            'author'         => $user_id,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $posts = get_posts( $args );
        $lists = array();

        foreach ( $posts as $post ) {
            $list_type = get_post_meta( $post->ID, '_wc_list_type', true );
            $lists[] = List_Base::get_list_instance_by_post( $post, $list_type );
        }

        return $lists;
    }

    /**
     * Render a single list table
     */
    private function render_single_list_table( List_Base $list ): void {
        $list_name     = esc_html( $list->get_name() );
        $list_type     = esc_html( $list->get_type_label() );
        $list_products = $list->get_products();

        echo '<h3>' . $list_name . ' (' . $list_type . ')</h3>';

        if ( empty( $list_products ) ) {
            echo '<p>' . esc_html__( 'No products in this list yet.', 'wc-customer-lists' ) . '</p>';
            return;
        }

        echo '<table class="wc-customer-lists-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__( 'Product', 'wc-customer-lists' ) . '</th>';

        if ( $list->supports_events() ) {
            echo '<th>' . esc_html__( 'Event Name', 'wc-customer-lists' ) . '</th>';
            echo '<th>' . esc_html__( 'Event Date', 'wc-customer-lists' ) . '</th>';
            echo '<th>' . esc_html__( 'Closing Date', 'wc-customer-lists' ) . '</th>';
            echo '<th>' . esc_html__( 'Delivery Deadline', 'wc-customer-lists' ) . '</th>';
        }

        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ( $list_products as $product_item ) {
            $product_id = is_object( $product_item ) ? $product_item->get_id() : $product_item;
            $product    = wc_get_product( $product_id );

            echo '<tr>';
            echo '<td>' . ( $product ? esc_html( $product->get_name() ) : esc_html__( 'Unknown Product', 'wc-customer-lists' ) ) . '</td>';

            if ( $list->supports_events() ) {
                $event_data = $list->get_event_data_for_product( $product_id );
                echo '<td>' . esc_html( $event_data['event_name'] ?? '' ) . '</td>';
                echo '<td>' . esc_html( $event_data['event_date'] ?? '' ) . '</td>';
                echo '<td>' . esc_html( $event_data['closing_date'] ?? '' ) . '</td>';
                echo '<td>' . esc_html( $event_data['delivery_deadline'] ?? '' ) . '</td>';
            }

            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }
}
