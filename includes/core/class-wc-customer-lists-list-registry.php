<?php
/**
 * List Registry.
 *
 * Registers list types and CPTs and acts as a factory for list objects.
 *
 * @package WC_Customer_Lists
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Customer_Lists_List_Registry {

	/**
	 * Registered list types.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	protected static array $list_types = array(
		'wc_bridal_list' => array(
			'class'              => 'WC_Customer_Lists_Bridal_List',
			'supports_events'    => true,
			'supports_auto_cart' => true,
		),
		'wc_baby_list' => array(
			'class'              => 'WC_Customer_Lists_Baby_List',
			'supports_events'    => true,
			'supports_auto_cart' => true,
		),
		'wc_event_list' => array(
			'class'              => 'WC_Customer_Lists_Generic_Event_List',
			'supports_events'    => true,
			'supports_auto_cart' => true,
		),
		'wc_wishlist' => array(
			'class'              => 'WC_Customer_Lists_Wishlist',
			'supports_events'    => false,
			'supports_auto_cart' => false,
		),
	);

	/**
	 * Register all list CPTs.
	 *
	 * @since 1.0.0
	 */
	public static function register_post_types(): void {
		foreach ( self::$list_types as $post_type => $config ) {
			$class_name = $config['class'];

			if ( ! class_exists( $class_name ) || ! method_exists( $class_name, 'get_post_type_args' ) ) {
				continue;
			}

			register_post_type(
				$post_type,
				$class_name::get_post_type_args()
			);
		}
	}

	/**
	 * Get list configuration merged with settings.
	 *
	 * @since 1.0.0
	 */
	public static function get_list_config( string $post_type ): array {
		if ( ! isset( self::$list_types[ $post_type ] ) ) {
			return array();
		}

		$config   = self::$list_types[ $post_type ];
		$settings = get_option( 'wc_customer_lists_settings', array() );
		$limits   = $settings['list_limits'][ $post_type ] ?? array();

		$config['max_lists']             = (int) ( $limits['max_lists'] ?? 0 );
		$config['max_items']             = (int) ( $limits['max_items'] ?? 0 );
		$config['not_purchased_action']  = $limits['not_purchased_action'] ?? 'keep';

		return $config;
	}

	/**
	 * Check if list supports events.
	 *
	 * @since 1.0.0
	 */
	public static function supports_events( string $post_type ): bool {
		$config = self::get_list_config( $post_type );
		return ! empty( $config['supports_events'] );
	}

	/**
	 * Check if list supports auto-cart.
	 *
	 * @since 1.0.0
	 */
	public static function supports_auto_cart( string $post_type ): bool {
		$config = self::get_list_config( $post_type );
		return ! empty( $config['supports_auto_cart'] );
	}

	/**
	 * Get max lists per user.
	 *
	 * @since 1.0.0
	 */
	public static function get_max_per_user( string $post_type ): int {
		$config = self::get_list_config( $post_type );
		return (int) ( $config['max_lists'] ?? 0 );
	}
}
