<?php
/**
 * WC Customer Lists - Admin Class
 * 
 * Handles admin settings page, menu registration, and asset enqueuing.
 * 
 * @package WC_Customer_Lists\Admin
 */

namespace WC_Customer_Lists\Admin;

use WC_Customer_Lists\Core\List_Registry;

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
			static function(): void {
				echo '<p>' . esc_html__( 'Select which list types are enabled on your site.', 'wc-customer-lists' ) . '</p>';
			},
			'wc_customer_lists_group'
		);
	}

	public function enqueue_assets( string $hook ): void {
		// Only load on our settings page
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
		$enabled_lists   = $this->settings['enabled_lists'] ?? [];
		$list_limits     = $this->settings['list_limits'] ?? [];
		$list_configs    = List_Registry::get_enabled_list_types();
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
					<?php foreach ( $list_configs as $post_type => $config ) : ?>
						<?php 
						$enabled = in_array( $post_type, $enabled_lists, true );
						$limits  = $list_limits[ $post_type ] ?? [];
						?>
						<tr>
							<th scope="row">
								<label for="<?php echo esc_attr( $post_type ); ?>">
									<?php echo esc_html( $config['label'] ?? $post_type ); ?>
								</label>
							</th>
							<td>
								<input 
									type="checkbox"
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
												<input 
													type="number"
													name="<?php echo esc_attr( self::OPTION_NAME ); ?>[list_limits][<?php echo esc_attr( $post_type ); ?>][max_lists]"
													value="<?php echo esc_attr( $limits['max_lists'] ?? 0 ); ?>"
													min="0" step="1">
											</label>
										</p>
										<p>
											<label>
												<?php esc_html_e( 'Max items per list:', 'wc-customer-lists' ); ?>
												<input 
													type="number"
													name="<?php echo esc_attr( self::OPTION_NAME ); ?>[list_limits][<?php echo esc_attr( $post_type ); ?>][max_items]"
													value="<?php echo esc_attr( $limits['max_items'] ?? 0 ); ?>"
													min="0" step="1">
											</label>
										</p>
										<p>
											<label>
												<?php esc_html_e( 'Not purchased items:', 'wc-customer-lists' ); ?>
												<select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[list_limits][<?php echo esc_attr( $post_type ); ?>][not_purchased_action]">
													<option value="keep" <?php selected( $limits['not_purchased_action'] ?? 'keep', 'keep' ); ?>>
														<?php esc_html_e( 'Keep', 'wc-customer-lists' ); ?>
													</option>
													<option value="remove" <?php selected( $limits['not_purchased_action'] ?? 'keep', 'remove' ); ?>>
														<?php esc_html_e( 'Remove', 'wc-customer-lists' ); ?>
													</option>
													<option value="purchased_only" <?php selected( $limits['not_purchased_action'] ?? 'keep', 'purchased_only' ); ?>>
														<?php esc_html_e( 'Purchased Only', 'wc-customer-lists' ); ?>
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
				static function( $type ) use ( $input ): bool {
					$all_types = array_keys( List_Registry::get_all_list_types() );
					return in_array( $type, $all_types, true );
				}
			),
			'list_limits'   => [],
		];

		foreach ( $output['enabled_lists'] as $type ) {
			$settings = $input['list_limits'][ $type ] ?? [];

			$output['list_limits'][ $type ] = [
				'max_lists'            => max( 0, (int) ( $settings['max_lists'] ?? 0 ) ),
				'max_items'            => max( 0, (int) ( $settings['max_items'] ?? 0 ) ),
				'not_purchased_action' => in_array(
					$settings['not_purchased_action'] ?? '',
					[ 'keep', 'remove', 'purchased_only' ],
					true
				) ? $settings['not_purchased_action'] : 'keep',
			];
		}

		return $output;
	}
}
