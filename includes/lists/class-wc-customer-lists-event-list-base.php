<?php
/**
 * Abstract Event List Base.
 *
 * Base for event-based lists (bridal, baby, generic).
 *
 * @package    wc-customer-lists
 * @since      1.0.0
 */

namespace WC_Customer_Lists\Lists;

use WC_Customer_Lists\Core\{ List_Engine, List_Registry };
use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

abstract class Event_List extends List_Engine {

	/**
	 * Meta keys (consts for type safety).
	 */
	protected const META_EVENT_NAME        = '_event_name';
	protected const META_EVENT_DATE        = '_event_date';
	protected const META_CLOSING_DATE      = '_closing_date';
	protected const META_DELIVERY_DEADLINE = '_delivery_deadline';
	protected const META_SHIPPING_ADDRESS  = '_shipping_address';
	protected const META_EVENT_TYPE        = '_event_type';

	/**
	 * Get event name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_event_name(): string {
		return (string) get_post_meta( $this->post_id, self::META_EVENT_NAME, true );
	}

	/**
	 * Set event name.
	 *
	 * @since 1.0.0
	 * @param string $name Event name.
	 */
	public function set_event_name( string $name ): void {
		update_post_meta( $this->post_id, self::META_EVENT_NAME, sanitize_text_field( $name ) );
	}

	/**
	 * Get event date (Y-m-d).
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_event_date(): string {
		return (string) get_post_meta( $this->post_id, self::META_EVENT_DATE, true );
	}

	/**
	 * Set event date (Y-m-d).
	 *
	 * @since 1.0.0
	 * @param string $date Date.
	 */
	public function set_event_date( string $date ): void {
		update_post_meta( $this->post_id, self::META_EVENT_DATE, sanitize_text_field( $date ) );
	}

	/**
	 * Get closing date (Y-m-d).
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_closing_date(): string {
		return (string) get_post_meta( $this->post_id, self::META_CLOSING_DATE, true );
	}

	/**
	 * Set closing date (Y-m-d).
	 *
	 * @since 1.0.0
	 * @param string $date Date.
	 */
	public function set_closing_date( string $date ): void {
		update_post_meta( $this->post_id, self::META_CLOSING_DATE, sanitize_text_field( $date ) );
		$this->schedule_auto_cart(); // Auto-schedule.
	}

	/**
	 * Get delivery deadline (admin).
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_delivery_deadline(): string {
		return (string) get_post_meta( $this->post_id, self::META_DELIVERY_DEADLINE, true );
	}

	/**
	 * Set delivery deadline (admin).
	 *
	 * @since 1.0.0
	 * @param string $date Date.
	 */
	public function set_delivery_deadline( string $date ): void {
		update_post_meta( $this->post_id, self::META_DELIVERY_DEADLINE, sanitize_text_field( $date ) );
	}

	/**
	 * Get shipping address (admin).
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_shipping_address(): string {
		return (string) get_post_meta( $this->post_id, self::META_SHIPPING_ADDRESS, true );
	}

	/**
	 * Set shipping address (admin).
	 *
	 * @since 1.0.0
	 * @param string $address Address.
	 */
	public function set_shipping_address( string $address ): void {
		update_post_meta( $this->post_id, self::META_SHIPPING_ADDRESS, wp_kses_post( $address ) );
	}

	/**
	 * Get event type.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_event_type(): string {
		return (string) get_post_meta( $this->post_id, self::META_EVENT_TYPE, true );
	}

	/**
	 * Set event type (protected).
	 *
	 * @since 1.0.0
	 * @param string $type Type slug.
	 */
	protected function set_event_type( string $type ): void {
		update_post_meta( $this->post_id, self::META_EVENT_TYPE, sanitize_key( $type ) );
	}

	/**
	 * Validate event constraints.
	 *
	 * @since 1.0.0
	 * @throws InvalidArgumentException
	 */
	public function validate(): void {
		parent::validate();

		// Max lists per user per event type.
		$max_per_user = List_Registry::get_max_per_user( $this->get_post_type() );

		if ( $max_per_user > 0 ) {
			$existing = get_posts( [
				'author'        => $this->get_owner_id(),
				'post_type'     => $this->get_post_type(),
				'post_status'   => [ 'publish', 'private' ],
				'meta_key'      => self::META_EVENT_TYPE,
				'meta_value'    => $this->get_event_type(),
				'numberposts'   => $max_per_user + 1,
				'fields'        => 'ids',
				'no_found_rows' => true, // Perf.
			] );

			// Exclude self if updating.
			if ( $this->post_id && ( $key = array_search( $this->post_id, $existing, true ) ) !== false ) {
				unset( $existing[ $key ] );
			}

			if ( count( $existing ) >= $max_per_user ) {
				throw new InvalidArgumentException( sprintf(
					/* translators: 1: Max lists, 2: Event type. */
					__( 'You can only create %1$d list(s) for "%2$s".', 'wc-customer-lists' ),
					$max_per_user,
					$this->get_event_type()
				) );
			}
		}

		// Closing ≤ delivery.
		$closing  = strtotime( $this->get_closing_date() );
		$deadline = strtotime( $this->get_delivery_deadline() );

		if ( $closing && $deadline && $closing > $deadline ) {
			throw new InvalidArgumentException( 'Closing date cannot be after delivery deadline.' );
		}
	}

	/**
	 * Schedule auto-cart on closing.
	 *
	 * @since 1.0.0
	 */
	public function schedule_auto_cart(): void {
		if ( ! $this->supports_auto_cart() ) {
			return;
		}

		$closing_timestamp = strtotime( $this->get_closing_date() . ' 23:59:59' );

		if ( ! wp_next_scheduled( 'wc_customer_list_auto_cart', [ $this->post_id ] ) ) {
			wp_schedule_single_event( $closing_timestamp, 'wc_customer_list_auto_cart', [ $this->post_id ] );
		}
	}

	/**
	 * Supports auto-cart?
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	protected function supports_auto_cart(): bool {
		return List_Registry::supports_auto_cart( $this->get_post_type() );
	}

	/**
 * Auto-cart cron handler—moves items to owner's cart.
 *
 * 1. Fetch owner/cart.
 * 2. Add items per not_purchased_action ('keep', 'remove', 'purchased_only').
 * 3. Email notification.
 * 4. Clear schedule.
 *
 * @since 1.0.0
 * @param int $post_id List ID.
 */
public static function handle_auto_cart( int $post_id ): void {
	$list = List_Registry::get( $post_id );
	$action = $list->get_not_purchased_action();
	$owner_id = $list->get_owner_id();
	$items = $list->get_items();

	if ( empty( $items ) || ! $owner_id ) {
		return;
	}

	// Get/add owner cart.
	$user = get_user_by( 'id', $owner_id );
	if ( ! $user ) {
		return;
	}

	WC()->cart->empty_cart(); // Clear abandoned cart first? Or append.

	$purchased = wc_get_customer_order_ids( $owner_id ); // Recent orders.
	$order_items = [];
	foreach ( $purchased as $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			foreach ( $order->get_items() as $item ) {
				$order_items[ $item->get_product_id() ] = true;
			}
		}
	}

	foreach ( $items as $product_id => $qty ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_purchasable() ) {
			continue;
		}

		switch ( $action ) {
			case 'purchased_only':
				if ( isset( $order_items[ $product_id ] ) ) {
					WC()->cart->add_to_cart( $product_id, $qty );
				}
				break;
			case 'remove':
				// Skip non-purchased.
				if ( isset( $order_items[ $product_id ] ) ) {
					WC()->cart->add_to_cart( $product_id, $qty );
				}
				break;
			case 'keep':
			default:
				WC()->cart->add_to_cart( $product_id, $qty );
				break;
		}
	}

	// Email owner.
	$event_name = $list->get_event_name();
	$item_count = count( array_filter( $items ) );
	/* translators: 1: List name, 2: Item count. */
	$subject = sprintf( __( '%s items auto-added to cart!', 'wc-customer-lists' ), $event_name );
	$message = sprintf(
		/* translators: 1: Event name, 2: Item count. */
		__( "Hi,\n\n%1\$s has closed. %2\$d items auto-added to your cart.\n\nView cart: %3\$s", 'wc-customer-lists' ),
		$event_name,
		$item_count,
		wc_get_cart_url()
	);

	wp_mail( $user->user_email, $subject, $message );

	// Clear this schedule.
	wp_clear_scheduled_hook( 'wc_customer_list_auto_cart', [ $post_id ] );

	/**
	 * Auto-cart complete.
	 *
	 * @param int          $post_id List ID.
	 * @param List_Engine  $list    List object.
	 * @param array<int,int> $items Original items.
	 */
	do_action( 'wc_customer_lists_auto_cart_complete', $post_id, $list, $items );
}

}
