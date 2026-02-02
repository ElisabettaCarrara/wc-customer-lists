<?php
/**
 * Abstract Wishlist Base.
 *
 * Base for simple wishlist functionality (no events/auto-cart).
 *
 * @package WC_Customer_Lists
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

abstract class WC_Customer_Lists_Wishlist_Base extends WC_Customer_Lists_List_Engine {

	/**
	 * Meta key for optional description.
	 */
	protected const META_DESCRIPTION = '_wishlist_description';

	/**
	 * Get wishlist description.
	 */
	public function get_description(): string {
		return (string) get_post_meta( $this->post_id, self::META_DESCRIPTION, true );
	}

	/**
	 * Set wishlist description.
	 */
	public function set_description( string $description ): void {
		update_post_meta( $this->post_id, self::META_DESCRIPTION, wp_kses_post( $description ) );
	}

	/**
	 * Validate wishlist constraints.
	 *
	 * @throws InvalidArgumentException
	 */
	public function validate(): void {
		parent::validate();

		// Max lists per user.
		$limits = $this->get_limits();
		$max_lists = (int) ( $limits['max_lists'] ?? 0 );

		if ( $max_lists > 0 ) {
			$existing = get_posts( [
				'author'        => $this->get_owner_id(),
				'post_type'     => static::get_post_type(),
				'post_status'   => [ 'publish', 'private' ],
				'numberposts'   => $max_lists + 1,
				'fields'        => 'ids',
				'no_found_rows' => true,
				'exclude'       => $this->get_id(),
			] );

			if ( count( $existing ) >= $max_lists ) {
				throw new InvalidArgumentException(
					sprintf(
						/* translators: %d: Max wishlists allowed. */
						__( 'Maximum of %d wishlist(s) allowed per user.', 'wc-customer-lists' ),
						$max_lists
					)
				);
			}
		}
	}
}
