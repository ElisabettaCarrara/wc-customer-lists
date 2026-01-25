<?php
/**
 * List Registry & Factory.
 *
 * Responsible for registering list types, CPTs,
 * providing capability flags, and instantiating the correct list objects.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

namespace WC_Customer_Lists\Core;

use InvalidArgumentException;
use WP_Post;

class List_Registry {

	/**
	 * Registered list types.
	 *
	 * CPT slug => configuration array:
	 * - class (required)
	 * - supports_events (bool)
	 * - supports_auto_cart (bool)
	 * - max_per_user (int)
	 *
	 * @var array<string,array>
	 */
	protected static array $list_types = [
    'wc_bridal_list' => [
        'class'              => \WC_Customer_Lists\Lists\Bridal_List::class,
        'supports_events'    => true,
        'supports_auto_cart' => true,
        'max_per_user'       => 1,
    ],
    'wc_baby_list' => [
        'class'              => \WC_Customer_Lists\Lists\Baby_List::class, // <-- fixed here
        'supports_events'    => true,
        'supports_auto_cart' => true,
        'max_per_user'       => 1,
    ],
    'wc_event_list' => [
        'class'              => \WC_Customer_Lists\Lists\Generic_Event_List::class,
        'supports_events'    => true,
        'supports_auto_cart' => true,
        'max_per_user'       => 0,
    ],
    'wc_wishlist' => [
        'class'              => \WC_Customer_Lists\Lists\Wishlist::class,
        'supports_events'    => false,
        'supports_auto_cart' => false,
        'max_per_user'       => 0,
    ],
];

	/**
	 * Register a list type.
	 *
	 * @param string $post_type CPT slug.
	 * @param array  $config    Configuration array (class, flags, max_per_user).
	 */
	public static function register_list_type( string $post_type, array $config ): void {
		if ( empty( $config['class'] ) || ! is_subclass_of( $config['class'], List_Engine::class ) ) {
			throw new InvalidArgumentException( 'List class must extend List_Engine.' );
		}

		self::$list_types[ $post_type ] = $config;
	}

	/**
	 * Get configuration for a list type.
	 *
	 * @param string $post_type
	 * @return array
	 */
	public static function get_list_config( string $post_type ): array {
		if ( ! isset( self::$list_types[ $post_type ] ) ) {
			throw new InvalidArgumentException( sprintf( 'Unknown list type "%s".', $post_type ) );
		}

		return self::$list_types[ $post_type ];
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
	 * Get maximum number of lists per user for this type.
	 */
	public static function get_max_per_user( string $post_type ): int {
		$config = self::get_list_config( $post_type );
		return isset( $config['max_per_user'] ) ? (int) $config['max_per_user'] : 0;
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
	 * @param string $post_type List CPT slug.
	 * @param int    $owner_id  Owner user ID.
	 * @param string $title     List title.
	 * @return List_Engine
	 */
	public static function create( string $post_type, int $owner_id, string $title ): List_Engine {
		$max_per_user = self::get_max_per_user( $post_type );

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
