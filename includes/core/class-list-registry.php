<?php
/**
 * List Registry & Factory.
 *
 * Responsible for registering list types, CPTs,
 * and instantiating the correct list objects.
 *
 * @package WC_Customer_Lists
 */

declared( 'ABSPATH' ) || exit;

namespace WC_Customer_Lists\Core;

use InvalidArgumentException;
use WP_Post;

class List_Registry {

	/**
	 * Registered list types.
	 *
	 * @var array<string,class-string<List_Engine>>
	 */
	protected static array $list_types = array();

	/**
	 * Register a list type.
	 *
	 * @param string $post_type  CPT slug.
	 * @param string $class_name Fully-qualified class name.
	 */
	public static function register_list_type( string $post_type, string $class_name ): void {
		if ( ! is_subclass_of( $class_name, List_Engine::class ) ) {
			throw new InvalidArgumentException( 'List class must extend List_Engine.' );
		}

		self::$list_types[ $post_type ] = $class_name;
	}

	/**
	 * Register all list CPTs.
	 *
	 * Each list class is responsible for defining
	 * its own CPT arguments.
	 */
	public static function register_post_types(): void {
		foreach ( self::$list_types as $post_type => $class_name ) {
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
	 * @param int    $owner_id Owner user ID.
	 * @param string $title    List title.
	 */
	public static function create( string $post_type, int $owner_id, string $title ): List_Engine {
		if ( ! isset( self::$list_types[ $post_type ] ) ) {
			throw new InvalidArgumentException( 'Unknown list type.' );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => $post_type,
				'post_status' => 'private',
				'post_title'  => $title,
				'post_author'=> $owner_id,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			throw new InvalidArgumentException( $post_id->get_error_message() );
		}

		$class = self::$list_types[ $post_type ];
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

		$class = self::$list_types[ $post_type ];

		return new $class( (int) $post_id );
	}
}
