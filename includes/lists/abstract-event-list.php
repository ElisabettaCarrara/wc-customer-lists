<?php
/**
 * Abstract Event List.
 *
 * Base class for all event-based lists (bridal, baby, generic events).
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

namespace WC_Customer_Lists\Lists;

use WC_Customer_Lists\Core\List_Engine;
use WC_Customer_Lists\Core\List_Registry;
use InvalidArgumentException;

abstract class Event_List extends List_Engine {

	/**
	 * Meta keys used by all event lists.
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
	 * Get list closing date (Y-m-d).
	 */
	public function get_closing_date(): string {
		return (string) get_post_meta( $this->post_id, self::META_CLOSING_DATE, true );
	}

	/**
	 * Set list closing date (Y-m-d).
	 */
	public function set_closing_date( string $date ): void {
		update_post_meta( $this->post_id, self::META_CLOSING_DATE, sanitize_text_field( $date ) );
	}

	/**
	 * Get delivery deadline (admin-only).
	 */
	public function get_delivery_deadline(): string {
		return (string) get_post_meta( $this->post_id, self::META_DELIVERY_DEADLINE, true );
	}

	/**
	 * Set delivery deadline (admin-only).
	 */
	public function set_delivery_deadline( string $date ): void {
		update_post_meta( $this->post_id, self::META_DELIVERY_DEADLINE, sanitize_text_field( $date ) );
	}

	/**
	 * Get shipping address (admin-only).
	 */
	public function get_shipping_address(): string {
		return (string) get_post_meta( $this->post_id, self::META_SHIPPING_ADDRESS, true );
	}

	/**
	 * Set shipping address (admin-only).
	 */
	public function set_shipping_address( string $address ): void {
		update_post_meta( $this->post_id, self::META_SHIPPING_ADDRESS, wp_kses_post( $address ) );
	}

	/**
	 * Get event type identifier.
	 */
	public function get_event_type(): string {
		return (string) get_post_meta( $this->post_id, self::META_EVENT_TYPE, true );
	}

	/**
	 * Set event type identifier (protected; used by concrete classes).
	 */
	protected function set_event_type( string $type ): void {
		update_post_meta( $this->post_id, self::META_EVENT_TYPE, sanitize_key( $type ) );
	}

	/**
	 * Validate shared event constraints.
	 *
	 * Enforces:
	 * - Max-per-user per event type
	 * - Closing date <= delivery deadline
	 */
	public function validate(): void {
		parent::validate();

		// Max per user per event type
		$max_per_user = List_Registry::get_max_per_user( $this->get_post_type() );

		if ( $max_per_user > 0 ) {
			$existing = get_posts( [
				'author'      => $this->get_owner_id(),
				'post_type'   => $this->get_post_type(),
				'post_status' => [ 'publish', 'private' ],
				'meta_key'    => self::META_EVENT_TYPE,
				'meta_value'  => $this->get_event_type(),
				'numberposts' => -1,
				'fields'      => 'ids',
			] );

			// Exclude current post from count if updating
			if ( $this->post_id && ( $key = array_search( $this->post_id, $existing, true ) ) !== false ) {
				unset( $existing[ $key ] );
			}

			if ( count( $existing ) >= $max_per_user ) {
				throw new InvalidArgumentException( sprintf(
					'You can only create %d list(s) of type "%s".',
					$max_per_user,
					$this->get_event_type()
				) );
			}
		}

		// Closing date <= delivery deadline
		$closing    = strtotime( $this->get_closing_date() );
		$deadline   = strtotime( $this->get_delivery_deadline() );

		if ( $closing && $deadline && $closing > $deadline ) {
			throw new InvalidArgumentException( 'Closing date cannot be later than delivery deadline.' );
		}
	}

	/**
	 * Schedule automatic cart addition on closing date.
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
	 * Returns whether this list type supports auto-cart.
	 */
	protected function supports_auto_cart(): bool {
		$config = List_Registry::get_list_config( $this->get_post_type() );
		return ! empty( $config['supports_auto_cart'] );
	}

	/**
	 * Placeholder to handle auto-cart when cron fires.
	 *
	 * Concrete classes or plugin bootstrap should implement the actual transfer
	 * of remaining items to the owner's cart, with optional badge/label.
	 *
	 * @param int $post_id List post ID
	 */
	public static function handle_auto_cart( int $post_id ): void {
		// TODO: implement adding remaining items to cart with badge
	}

}
