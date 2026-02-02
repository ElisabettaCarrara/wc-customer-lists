<?php
/**
 * Abstract Event List Base.
 *
 * Base for event-based lists (bridal, baby, generic).
 *
 * @package WC_Customer_Lists
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

abstract class WC_Customer_Lists_Event_List_Base extends WC_Customer_Lists_List_Engine {

	/**
	 * Meta keys.
	 */
	protected const META_EVENT_NAME        = '_event_name';
	protected const META_EVENT_DATE        = '_event_date';
	protected const META_CLOSING_DATE      = '_closing_date';
	protected const META_DELIVERY_DEADLINE = '_delivery_deadline';
	protected const META_SHIPPING_ADDRESS  = '_shipping_address';
	protected const META_EVENT_TYPE        = '_event_type';

	/**
	 * Get event name.
	 */
	public function get_event_name(): string {
		return (string) get_post_meta( $this->post_id, self::META_EVENT_NAME, true );
	}

	/**
	 * Set event name.
	 */
	public function set_event_name( string $name ): void {
		update_post_meta( $this->post_id, self::META_EVENT_NAME, sanitize_text_field( $name ) );
	}

	/**
	 * Get event date (Y-m-d).
	 */
	public function get_event_date(): string {
		return (string) get_post_meta( $this->post_id, self::META_EVENT_DATE, true );
	}

	/**
	 * Set event date (Y-m-d).
	 */
	public function set_event_date( string $date ): void {
		update_post_meta( $this->post_id, self::META_EVENT_DATE, sanitize_text_field( $date ) );
	}

	/**
	 * Get closing date (Y-m-d).
	 */
	public function get_closing_date(): string {
		return (string) get_post_meta( $this->post_id, self::META_CLOSING_DATE, true );
	}

	/**
	 * Set closing date (Y-m-d) + schedule cron.
	 */
	public function set_closing_date( string $date ): void {
		update_post_meta( $this->post_id, self::META_CLOSING_DATE, sanitize_text_field( $date ) );
		$this->schedule_auto_cart();
	}

	/**
	 * Get delivery deadline (Y-m-d).
	 */
	public function get_delivery_deadline(): string {
		return (string) get_post_meta( $this->post_id, self::META_DELIVERY_DEADLINE, true );
	}

	/**
	 * Set delivery deadline (Y-m-d).
	 */
	public function set_delivery_deadline( string $date ): void {
		update_post_meta( $this->post_id, self::META_DELIVERY_DEADLINE, sanitize_text_field( $date ) );
	}

	/**
	 * Get shipping address.
	 */
	public function get_shipping_address(): string {
		return (string) get_post_meta( $this->post_id, self::META_SHIPPING_ADDRESS, true );
	}

	/**
	 * Set shipping address.
	 */
	public function set_shipping_address( string $address ): void {
		update_post_meta( $this->post_id, self::META_SHIPPING_ADDRESS, wp_kses_post( $address ) );
	}

	/**
	 * Get event type.
	 */
	public function get_event_type(): string {
		return (string) get_post_meta( $this->post_id, self::META_EVENT_TYPE, true );
	}

	/**
	 * Set event type (protected).
	 */
	protected function set_event_type( string $type ): void {
		update_post_meta( $this->post_id, self::META_EVENT_TYPE, sanitize_key( $type ) );
	}

	/**
	 * Validate event constraints.
	 *
	 * @throws InvalidArgumentException
	 */
	public function validate(): void {
		parent::validate();

		// Closing ≤ delivery date check.
		$closing  = strtotime( $this->get_closing_date() );
		$delivery = strtotime( $this->get_delivery_deadline() );

		if ( $closing && $delivery && $closing > $delivery ) {
			throw new InvalidArgumentException( 'Closing date cannot be after delivery deadline.' );
		}
	}

	/**
	 * Schedule auto-cart cron on closing date.
	 */
	protected function schedule_auto_cart(): void {
		if ( ! WC_Customer_Lists_List_Registry::supports_auto_cart( static::get_post_type() ) ) {
			return;
		}

		$closing_timestamp = strtotime( $this->get_closing_date() . ' 23:59:59' );

		if ( ! $closing_timestamp ) {
			return;
		}

		$args = [ $this->post_id ];

		if ( ! wp_next_scheduled( 'wc_customer_list_auto_cart', $args ) ) {
			wp_schedule_single_event( $closing_timestamp, 'wc_customer_list_auto_cart', $args );
		}
	}

	/**
	 * Auto-cart cron handler - moves items to cart.
	 *
	 * @param int $post_id List post ID.
	 */
	public static function handle_auto_cart( int $post_id ): void {
		// WC context check.
		if ( ! function_exists( 'WC' ) || ! WC()->is_initialized() ) {
			return;
		}

		try {
			$list    = static::get( $post_id );
			$action  = $list->get_not_purchased_action();
			$owner_id = $list->get_owner_id();
			$items   = $list->get_items();

			if ( empty( $items ) || ! $owner_id ) {
				return;
			}

			$user = get_user_by( 'id', $owner_id );
			if ( ! $user ) {
				return;
			}

			// Get recent orders for purchased check.
			$orders = wc_get_orders( [
				'customer_id' => $owner_id,
				'limit'       => 5, // Recent orders.
				'status'      => [ 'wc-completed', 'wc-processing' ],
			] );

			$purchased_items = [];
			foreach ( $orders as $order ) {
				foreach ( $order->get_items() as $item ) {
					$purchased_items[ $item->get_product_id() ] = true;
				}
			}

			$added_count = 0;
			foreach ( $items as $product_id => $qty ) {
				$product = wc_get_product( $product_id );
				if ( ! $product || ! $product->is_purchasable() || $qty <= 0 ) {
					continue;
				}

				// Apply action logic.
				switch ( $action ) {
					case 'purchased_only':
						if ( isset( $purchased_items[ $product_id ] ) ) {
							WC()->cart->add_to_cart( $product_id, $qty );
							$added_count++;
						}
						break;

					case 'remove':
						// Add UNLESS already purchased.
						if ( ! isset( $purchased_items[ $product_id ] ) ) {
							WC()->cart->add_to_cart( $product_id, $qty );
							$added_count++;
						}
						break;

					case 'keep':
					default:
						WC()->cart->add_to_cart( $product_id, $qty );
						$added_count++;
						break;
				}
			}

			// Email notification.
			if ( $added_count > 0 ) {
				$event_name = $list->get_event_name();
				/* translators: 1: Event name, 2: Items added. */
				$subject = sprintf( __( '%1$s: %2$d items added to cart!', 'wc-customer-lists' ), $event_name, $added_count );
				$message = sprintf(
					/* translators: 1: Event name, 2: Cart URL. */
					__( "Hi,\n\nYour %1$s has closed. %2$d items were automatically added to your cart.\n\n%3$s\n\nThanks!", 'wc-customer-lists' ),
					$event_name,
					$added_count,
					wc_get_cart_url()
				);

				wp_mail( $user->user_email, $subject, $message );
			}

			// Clear schedule.
			wp_clear_scheduled_hook( 'wc_customer_list_auto_cart', [ $post_id ] );

			/**
			 * Auto-cart complete hook.
			 *
			 * @param int                           $post_id    List ID.
			 * @param WC_Customer_Lists_List_Engine $list       List.
			 * @param array<int,int>                $items      Original items.
			 * @param int                           $added_count Items added.
			 */
			do_action( 'wc_customer_lists_auto_cart_complete', $post_id, $list, $items, $added_count );

		} catch ( Exception $e ) {
			error_log( 'WC Customer Lists auto-cart error: ' . $e->getMessage() );
		}
	}
}
