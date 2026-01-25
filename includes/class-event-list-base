<?php
/**
 * Abstract Event List.
 *
 * Base class for all event-based lists (bridal, baby, generic events).
 *
 * @package WC_Customer_Lists
 */

declared( 'ABSPATH' ) || exit;

namespace WC_Customer_Lists\Lists;

use WC_Customer_Lists\Core\List_Engine;

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
	 * Set event type identifier.
	 */
	protected function set_event_type( string $type ): void {
		update_post_meta( $this->post_id, self::META_EVENT_TYPE, sanitize_key( $type ) );
	}

	/**
	 * Validate shared event constraints.
	 */
	public function validate(): void {
		// One event list per user per event type can be enforced here later.
	}
}
