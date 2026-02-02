<?php
/**
 * Admin functionality for WC Customer Lists.
 *
 * Standalone settings page with dynamic list-type fields.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

final class WC_Customer_Lists_Admin {

	private const OPTION_NAME = 'wc_customer_lists_settings';

	private array $settings = [];

	public function __construct() {
		$this->settings = get_option( self::OPTION_NAME, [] );

		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function register_menu(): void {
		add_menu_page(
			__( 'Customer Lists', 'wc-customer-lists' ),
			__( 'Customer Lists', 'wc-customer-lists' ),
			'manage_options',
			'wc-customer-lists',
			[ $this, 'render_settings_page' ],
			'dashicons-heart',
			56
		);
	}

	public function register_settings(): void {
		register_setting(
			'wc_customer_lists_group',
			self::OPTION_NAME,
			[ $this, 'sanitize_settings' ]
		);

		add_settings_section(
			'wc_customer_lists_main',
			__( 'Enabled List Types', 'wc-customer-lists' ),
			[ $this, 'settings_section_intro' ],
			'wc_customer_lists_group'
		);
	}

	/**
	 * Settings section intro callback.
	 */
	public function settings_section_intro(): void {
		echo '<p>' . esc_html__( 'Select enabled list types and configure limits.', 'wc-customer-lists' ) . '</p>';
	}

	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_wc-customer-lists' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wc-customer-lists-admin',
			WC_CUSTOMER_LISTS_PLUGIN_URL . 'includes/assets/css/admin.css',
			[],
			WC_CUSTOMER_LISTS_VERSION
		);

		wp_enqueue_script(
			'wc-customer-lists-admin',
			WC_CUSTOMER_LISTS_PLUGIN_URL . 'includes/assets/js/admin.js',
			[ 'jquery' ],
			WC_CUSTOMER_LISTS_VERSION,
			true
		);
	}

	public function render_settings_page(): void {
		$enabled_lists = $this->settings['enabled_lists'] ?? [];
		$list_limits   = $this->settings['list_limits'] ?? [];
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Customer Lists Settings', 'wc-customer-lists' ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'wc_customer_lists_group' );
				do_settings_sections( 'wc_customer_lists_group' );
				?>

				<h2><?php esc_html_e( 'List Types', 'wc-customer-lists' ); ?></h2>
				<table class="form-table">
					<tbody>
					<?php foreach ( WC_Customer_Lists_List_Registry::get_all_list_types() as $post_type => $config ) :
						$enabled = in_array( $post_type, $enabled_lists, true );
						$limits  = $list_limits[ $post_type ] ?? [];
						?>
						<tr>
							<th scope="row">
								<label for="<?php echo esc_attr( $post_type ); ?>">
									<?php echo esc_html( $config['label'] ?? ucwords( str_replace( '_', ' ', $post_type ) ) ); ?>
								</label>
							</th>
							<td>
								<input type="checkbox"
								       name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enabled_lists][]"
								       id="<?php echo esc_attr( $post_type ); ?>"
								       value="<?php echo esc_attr( $post_type ); ?>"
									<?php checked( $enabled ); ?>
								>
								<p class="description"><?php echo esc_html( $config['description'] ?? '' ); ?></p>

								<?php if ( $enabled ) : ?>
									<div class="wc-list-settings">
										<p>
											<label>
												<?php esc_html_e( 'Max lists per user:', 'wc-customer-lists' ); ?>
												<input type="number"
												       name="<?php echo esc_attr( self::OPTION_NAME ); ?>[list_limits][<?php echo esc_attr( $post_type ); ?>][max_lists]"
												       value="<?php echo esc_attr( $limits['max_lists'] ?? 0 ); ?>"
												       min="0" step="1">
											</label>
										</p>
										<p>
											<label>
												<?php esc_html_e( 'Max items per list:', 'wc-customer-lists' ); ?>
												<input type="number"
												       name="<?php echo esc_attr( self::OPTION_NAME ); ?>[list_limits][<?php echo esc_attr( $post_type ); ?>][max_items]"
												       value="<?php echo esc_attr( $limits['max_items'] ?? 0 ); ?>"
												       min="0" step="1">
											</label>
										</p>
										<p>
											<label>
												<?php esc_html_e( 'Not purchased behavior:', 'wc-customer-lists' ); ?>
												<select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[list_limits][<?php echo esc_attr( $post_type ); ?>][not_purchased_action]">
													<option value="keep" <?php selected( $limits['not_purchased_action'] ?? 'keep', 'keep' ); ?>>
														<?php esc_html_e( 'Keep all', 'wc-customer-lists' ); ?>
													</option>
													<option value="remove" <?php selected( $limits['not_purchased_action'] ?? 'keep', 'remove' ); ?>>
														<?php esc_html_e( 'Remove purchased', 'wc-customer-lists' ); ?>
													</option>
													<option value="purchased_only" <?php selected( $limits['not_purchased_action'] ?? 'keep', 'purchased_only' ); ?>>
														<?php esc_html_e( 'Purchased only', 'wc-customer-lists' ); ?>
													</option>
												</select>
											</label>
										</p>
									</div>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function sanitize_settings( array $input ): array {
		$output = [
			'enabled_lists' => array_filter(
				(array) ( $input['enabled_lists'] ?? [] ),
				fn( $type ) => in_array( $type, array_keys( WC_Customer_Lists_List_Registry::get_all_list_types() ), true )
			),
			'list_limits'   => [],
		];

		foreach ( $output['enabled_lists'] as $type ) {
			$settings = $input['list_limits'][ $type ] ?? [];

			$output['list_limits'][ $type ] = [
				'max_lists'             => max( 0, (int) ( $settings['max_lists'] ?? 0 ) ),
				'max_items'             => max( 0, (int) ( $settings['max_items'] ?? 0 ) ),
				'not_purchased_action'  => match( $settings['not_purchased_action'] ?? '' ) {
					'keep', 'remove', 'purchased_only' => $settings['not_purchased_action'],
					default => 'keep',
				},
			];
		}

		return $output;
	}
}
