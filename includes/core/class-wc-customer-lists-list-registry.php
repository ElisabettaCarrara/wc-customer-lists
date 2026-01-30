<?php
/**
 * List Registry & Factory.
 *
 * Registers list types, CPTs, capability flags, instantiates list objects,
 * respects admin settings for enabled lists/limits.
 *
 * @package    wc-customer-lists
 * @since      1.0.0
 */

namespace WC_Customer_Lists\Core;

use InvalidArgumentException;
use WP_Post;

defined( 'ABSPATH' ) || exit;

class List_Registry {

	/**
	 * Registered list types.
	 *
	 * @var array<string,array{
	 *     class: string,
	 *     supports_events: bool,
	 *     supports_auto_cart: bool,
	 *     max_lists: int,
	 *     max_items: int,
	 *     not_purchased_action: string
	 * }>
	 */
	protected static array $list_types = [
		'wc_bridal_list' => [
			'class'              => Bridal_List::class,     // Short via namespace.
			'supports_events'    => true,
			'supports_auto_cart' => true,
		],
		'wc_baby_list' => [
			'class'              => Baby_List::class,
			'supports_events'    => true,
			'supports_auto_cart' => true,
		],
		'wc_event_list' => [
			'class'              => Generic_Event_List::class,
			'supports_events'    => true,
			'supports_auto_cart' => true,
		],
		'wc_wishlist' => [
			'class'              => Wishlist::class,
			'supports_events'    => false,
			'supports_auto_cart' => false,
		],
	];

	/**
	 * Get enabled list types from settings.
	 *
	 * @since 1.0.0
	 * @return array<string,array> Enabled types.
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
	 * Get config (settings + defaults).
	 *
	 * @since 1.0.0
	 * @param string $post_type List CPT.
	 * @return array<string,mixed> Config array.
	 * @throws InvalidArgumentException Unknown type.
	 */
	public static function get_list_config( string $post_type ): array {
		if ( ! isset( self::$list_types[ $post_type ] ) ) {
			throw new InvalidArgumentException( sprintf( 'Unknown list type "%s".', $post_type ) );
		}

		$config = self::$list_types[ $post_type ];

		$settings = get_option( 'wc_customer_lists_settings', [] );
		$limits   = $settings['list_limits'][ $post_type ] ?? [];

		$config['max_lists']   = (int) ( $limits['max_lists'] ?? 0 );
		$config['max_items']   = (int) ( $limits['max_items'] ?? 0 );
		$config['not_purchased_action'] = $limits['not_purchased_action'] ?? 'keep';

		return $config;
	}

	/**
	 * Supports events?
	 *
	 * @since 1.0.0
	 */
	public static function supports_events( string $post_type ): bool {
		$config = self::get_list_config( $post_type );
		return (bool) ( $config['supports_events'] ?? false );
	}

	/**
 * Check if list supports auto-cart (event-driven).
 *
 * When event expires (bridal/wedding date passes), items auto-move to cart via cron.
 * List engine respects this flag for cleanup logic.
 *
 * @since 1.0.0
 * @param string $post_type List CPT.
 * @return bool True if auto-cart enabled.
 */
public static function supports_auto_cart( string $post_type ): bool {
	$config = self::get_list_config( $post_type );
	return (bool) ( $config['supports_auto_cart'] ?? false );
}

	/**
	 * Max lists per user.
	 *
	 * @since 1.0.0
	 */
	public static function get_max_per_user( string $post_type ): int {
		$config = self::get_list_config( $post_type );
		return (int) ( $config['max_lists'] ?? 0 );
	}

	/**
	 * Register all CPTs.
	 *
	 * @since 1.0.0
	 * @throws InvalidArgumentException If class lacks `get_post_type_args()`.
	 */
	public static function register_post_types(): void {
		foreach ( self::$list_types as $post_type => $config ) {
			$class_name = $config['class'];

			if ( ! method_exists( $class_name, 'get_post_type_args' ) ) {
				throw new InvalidArgumentException( sprintf(
					/* translators: 1: Class name, 2: Post type slug. */
					__( 'List class %1$s must implement get_post_type_args() for "%2$s".', 'wc-customer-lists' ),
					$class_name,
					$post_type
				) );
			}

			register_post_type( $post_type, $class_name::get_post_type_args() );
		}
	}

	/**
	 * Create new list.
	 *
	 * @since 1.0.0
	 * @param string    $post_type List type.
	 * @param int       $owner_id  User ID.
	 * @param string    $title     Title.
	 * @return List_Engine New list.
	 * @throws InvalidArgumentException
	 */
	public static function create( string $post_type, int $owner_id, string $title ): List_Engine {
		$config     = self::get_list_config( $post_type );
		$max_per_user = (int) ( $config['max_lists'] ?? 0 );

		if ( $max_per_user > 0 ) {
			$existing = get_posts( [
				'author'        => $owner_id,
				'post_type'     => $post_type,
				'post_status'   => [ 'publish', 'private' ],
				'numberposts'   => $max_per_user + 1,
				'fields'        => 'ids',
				'no_found_rows' => true, // Perf.
			] );

			if ( count( $existing ) >= $max_per_user ) {
				throw new InvalidArgumentException( sprintf(
					/* translators: 1: Max lists, 2: Post type. */
					__( 'You can only create %1$d list(s) of type "%2$s".', 'wc-customer-lists' ),
					$max_per_user,
					$post_type
				) );
			}
		}

		$post_id = wp_insert_post( [
			'post_type'    => $post_type,
			'post_status'  => 'private',
			'post_title'   => $title,
			'post_author'  => $owner_id,
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
	 * Get list from post ID.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return List_Engine
	 * @throws InvalidArgumentException
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
