<?php
/**
 * List Registry & Factory.
 *
 * Responsible for registering list types, CPTs,
 * providing capability flags, and instantiating the correct list objects,
 * now fully respecting admin settings for enabled lists and per-type limits.
 *
 * @package WC_Customer_Lists
 */

namespace WC_Customer_Lists\Core;

use InvalidArgumentException;
use WP_Post;

defined( 'ABSPATH' ) || exit;

class List_Registry {

	/**
	 * Registered list types.
	 *
	 * CPT slug => configuration array:
	 * - class (required)
	 * - supports_events (bool)
	 * - supports_auto_cart (bool)
	 *
	 * @var array<string,array>
	 */
	protected static array $list_types = [
		'wc_bridal_list' => [
			'class'              => \WC_Customer_Lists\Lists\Bridal_List::class,
			'supports_events'    => true,
			'supports_auto_cart' => true,
		],
		'wc_baby_list' => [
			'class'              => \WC_Customer_Lists\Lists\Baby_List::class,
			'supports_events'    => true,
			'supports_auto_cart' => true,
		],
		'wc_event_list' => [
			'class'              => \WC_Customer_Lists\Lists\Generic_Event_List::class,
			'supports_events'    => true,
			'supports_auto_cart' => true,
		],
		'wc_wishlist' => [
			'class'              => \WC_Customer_Lists\Lists\Wishlist::class,
			'supports_events'    => false,
			'supports_auto_cart' => false,
		],
	];

	/**
	 * Return all list types that are enabled in settings.
	 *
	 * @return array<string,array>
	 */
	public static function get_enabled_list_types(): array {
		$settings = get_option( 'wc_customer_lists_settings', [] );
		$enabled  = $settings['enabled_lists'] ?? [];

		$enabled_types = [];
		foreach ( $enabled as $post_type ) {
			if ( isset( self::$list_types[ $post_type ] ) ) {
				$enabled_types[ $post_type ] = self::$list_types[ $post_type ];
			}
		}

		return $enabled_types;
	}

	/**
	 * Get configuration for a list type (settings + registry defaults)
	 *
	 * @param string $post_type
	 * @return array
	 */
	public static function get_list_config( string $post_type ): array {
		if ( ! isset( self::$list_types[ $post_type ] ) ) {
			throw new InvalidArgumentException( sprintf( 'Unknown list type "%s".', $post_type ) );
		}

		$config = self::$list_types[ $post_type ];

		$settings = get_option( 'wc_customer_lists_settings', [] );
		$limits   = $settings['list_limits'][ $post_type ] ?? [];

		// Apply settings-based limits
		$config['max_lists']   = isset( $limits['max_lists'] ) ? (int) $limits['max_lists'] : 0;   // 0 = unlimited
		$config['max_items']   = isset( $limits['max_items'] ) ? (int) $limits['max_items'] : 0;
		$config['not_purchased_action'] = $limits['not_purchased_action'] ?? 'keep';

		return $config;
	}

	/**
	 * Check if a list type supports events.
	 */
	public static function supports_events( string $post_type ): bool {
		$config = self::get_list_config( $post_type );
		return ! empty( $config['supports_events'] );
	}

	/**
	 * Check if a list type supports auto-cart.
	 */
	public static function supports_auto_cart( string $post_type ): bool {
		$config = self::get_list_config( $post_type );
		return ! empty( $config['supports_auto_cart'] );
	}

	/**
	 * Get maximum number of lists per user for this type (from settings)
	 */
	public static function get_max_per_user( string $post_type ): int {
		$config = self::get_list_config( $post_type );
		return $config['max_lists'] ?? 0;
	}

	/**
	 * Register all list CPTs.
	 *
	 * Each list class is responsible for defining its own CPT args.
	 */
	public static function register_post_types(): void {
		foreach ( self::$list_types as $post_type => $config ) {
			$class_name = $config['class'];

			if ( ! method_exists( $class_name, 'get_post_type_args' ) ) {
				throw new InvalidArgumentException( sprintf( 'List class %s must implement get_post_type_args().', $class_name ) );
			}

			register_post_type(
				$post_type,
				$class_name::get_post_type_args()
			);
		}
	}

	/**
	 * Create a new list.
	 *
	 * Respects max_lists from settings.
	 *
	 * @param string $post_type List CPT slug.
	 * @param int    $owner_id  Owner user ID.
	 * @param string $title     List title.
	 * @return List_Engine
	 */
	public static function create( string $post_type, int $owner_id, string $title ): List_Engine {
		$config = self::get_list_config( $post_type );
		$max_per_user = $config['max_lists'] ?? 0;

		if ( $max_per_user > 0 ) {
			$existing = get_posts( [
				'author'      => $owner_id,
				'post_type'   => $post_type,
				'post_status' => [ 'publish', 'private' ],
				'numberposts' => -1,
				'fields'      => 'ids',
			] );

			if ( count( $existing ) >= $max_per_user ) {
				throw new InvalidArgumentException( sprintf(
					'You can only create %d list(s) of type "%s".',
					$max_per_user,
					$post_type
				) );
			}
		}

		$post_id = wp_insert_post( [
			'post_type'   => $post_type,
			'post_status' => 'private',
			'post_title'  => $title,
			'post_author' => $owner_id,
		], true );

		if ( is_wp_error( $post_id ) ) {
			throw new InvalidArgumentException( $post_id->get_error_message() );
		}

		$class = self::$list_types[ $post_type ]['class'];
		$list  = new $class( (int) $post_id );

		$list->validate();

		return $list;
	}

	/**
	 * Get a list object from a post ID.
	 */
	public static function get( int $post_id ): List_Engine {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			throw new InvalidArgumentException( 'Invalid list post.' );
		}

		$post_type = $post->post_type;

		if ( ! isset( self::$list_types[ $post_type ] ) ) {
			throw new InvalidArgumentException( 'Post type is not a registered list.' );
		}

		$class = self::$list_types[ $post_type ]['class'];

		return new $class( (int) $post_id );
	}
}
