<?php
/**
 * List Registry.
 *
 * Registers list types and CPTs. Provides configuration lookup.
 *
 * @package WC_Customer_Lists
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Customer_Lists_List_Registry {

	/**
	 * Registered list types.
	 *
	 * @var array<string,array{
	 *     class: string,
	 *     label: string,
	 *     description: string,
	 *     supports_events: bool,
	 *     supports_auto_cart: bool
	 * }>
	 */
	protected static array $list_types = [
		'wc_bridal_list' => [
			'class'              => 'WC_Customer_Lists_Bridal_List',
			'label'              => __( 'Bridal/Wedding List', 'wc-customer-lists' ),
			'description'        => __( 'Wedding registry lists.', 'wc-customer-lists' ),
			'supports_events'    => true,
			'supports_auto_cart' => true,
		],
		'wc_baby_list' => [
			'class'              => 'WC_Customer_Lists_Baby_List',
			'label'              => __( 'Baby List', 'wc-customer-lists' ),
			'description'        => __( 'Baby registry lists.', 'wc-customer-lists' ),
			'supports_events'    => true,
			'supports_auto_cart' => true,
		],
		'wc_event_list' => [
			'class'              => 'WC_Customer_Lists_Generic_Event_List',
			'label'              => __( 'Event List', 'wc-customer-lists' ),
			'description'        => __( 'Generic event registry lists.', 'wc-customer-lists' ),
			'supports_events'    => true,
			'supports_auto_cart' => true,
		],
		'wc_wishlist' => [
			'class'              => 'WC_Customer_Lists_Wishlist',
			'label'              => __( 'Wishlist', 'wc-customer-lists' ),
			'description'        => __( 'Simple product wishlists.', 'wc-customer-lists' ),
			'supports_events'    => false,
			'supports_auto_cart' => false,
		],
	];

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
	 * Get all registered list types.
	 *
	 * @since 1.0.0
	 */
	public static function get_all_list_types(): array {
		return self::$list_types;
	}

	/**
	 * Get enabled list types from settings.
	 *
	 * @since 1.0.0
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
	 * Get list configuration merged with settings limits.
	 *
	 * @since 1.0.0
	 * @param string $post_type List CPT.
	 * @return array<string,mixed>
	 */
	public static function get_list_config( string $post_type ): array {
		if ( ! isset( self::$list_types[ $post_type ] ) ) {
			return [];
		}

		$config   = self::$list_types[ $post_type ];
		$settings = get_option( 'wc_customer_lists_settings', [] );
		$limits   = $settings['list_limits'][ $post_type ] ?? [];

		// Merge limits.
		$config['max_lists']             = (int) ( $limits['max_lists'] ?? 0 );
		$config['max_items']             = (int) ( $limits['max_items'] ?? 0 );
		$config['not_purchased_action']  = in_array( 
			$limits['not_purchased_action'] ?? 'keep', 
			[ 'keep', 'remove', 'purchased_only' ], 
			true 
		) ? $limits['not_purchased_action'] : 'keep';

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
	 * Get max lists per user from config.
	 *
	 * @since 1.0.0
	 */
	public static function get_max_per_user( string $post_type ): int {
		$config = self::get_list_config( $post_type );
		return (int) ( $config['max_lists'] ?? 0 );
	}

	/**
	 * Get max items per list from config.
	 *
	 * @since 1.0.0
	 */
	public static function get_max_per_list( string $post_type ): int {
		$config = self::get_list_config( $post_type );
		return (int) ( $config['max_items'] ?? 0 );
	}
}
