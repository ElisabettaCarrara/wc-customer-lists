<?php
/**
 * Admin functionality for WC Customer Lists.
 *
 * Handles settings page, dynamic list-type fields,
 * and saving with sanitization.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

use WC_Customer_Lists\Core\List_Registry;

final class WC_Customer_Lists_Admin {

    /**
     * Option name in WP options table.
     */
    private const OPTION_NAME = 'wc_customer_lists_settings';

    /**
     * Holds current settings.
     *
     * @var array
     */
    private array $settings = [];

    public function __construct() {
        $this->settings = get_option( self::OPTION_NAME, [] );

        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Register menu page.
     */
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

    /**
     * Register settings with WP Settings API.
     */
    public function register_settings(): void {
        register_setting(
            'wc_customer_lists_group',
            self::OPTION_NAME,
            [ $this, 'sanitize_settings' ]
        );

        add_settings_section(
            'wc_customer_lists_main',
            __( 'Enabled List Types', 'wc-customer-lists' ),
            fn() => echo '<p>' . esc_html__( 'Select which list types are enabled on your site.', 'wc-customer-lists' ) . '</p>',
            'wc_customer_lists_group'
        );
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_assets(): void {
        wp_enqueue_style(
            'wc-customer-lists-admin',
            plugins_url( 'assets/css/admin.css', WC_CUSTOMER_LISTS_FILE ),
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'wc-customer-lists-admin',
            plugins_url( 'assets/js/admin.js', WC_CUSTOMER_LISTS_FILE ),
            ['jquery'],
            '1.0.0',
            true
        );
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page(): void {
        $enabled_lists = $this->settings['enabled_lists'] ?? [];
        $lists_settings = $this->settings['lists_settings'] ?? [];
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
                    <?php foreach ( List_Registry::$list_types as $post_type => $config ) :
                        $enabled = in_array( $post_type, $enabled_lists, true );
                        ?>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr( $post_type ); ?>">
                                    <?php echo esc_html( $post_type ); ?>
                                </label>
                            </th>
                            <td>
                                <input type="checkbox"
                                       name="<?php echo self::OPTION_NAME; ?>[enabled_lists][]"
                                       id="<?php echo esc_attr( $post_type ); ?>"
                                       value="<?php echo esc_attr( $post_type ); ?>"
                                    <?php checked( $enabled ); ?>
                                >
                                <p class="description"><?php echo esc_html( $config['class'] ); ?></p>

                                <?php if ( $enabled ) :
                                    $list_opts = $lists_settings[ $post_type ] ?? [];
                                    $max_lists  = $list_opts['max_lists'] ?? $config['max_per_user'] ?? 0;
                                    $max_items  = $list_opts['max_items'] ?? 0;
                                    $not_purchased = $list_opts['not_purchased'] ?? 'keep';
                                    ?>
                                    <div class="wc-list-settings">
                                        <p>
                                            <label>
                                                <?php esc_html_e( 'Default max lists per user:', 'wc-customer-lists' ); ?>
                                                <input type="number"
                                                       name="<?php echo self::OPTION_NAME; ?>[lists_settings][<?php echo esc_attr( $post_type ); ?>][max_lists]"
                                                       value="<?php echo esc_attr( $max_lists ); ?>"
                                                       min="0"
                                                       step="1">
                                            </label>
                                        </p>
                                        <p>
                                            <label>
                                                <?php esc_html_e( 'Default max items per list:', 'wc-customer-lists' ); ?>
                                                <input type="number"
                                                       name="<?php echo self::OPTION_NAME; ?>[lists_settings][<?php echo esc_attr( $post_type ); ?>][max_items]"
                                                       value="<?php echo esc_attr( $max_items ); ?>"
                                                       min="0"
                                                       step="1">
                                            </label>
                                        </p>
                                        <p>
                                            <label>
                                                <?php esc_html_e( 'Not purchased items behavior:', 'wc-customer-lists' ); ?>
                                                <select
                                                    name="<?php echo self::OPTION_NAME; ?>[lists_settings][<?php echo esc_attr( $post_type ); ?>][not_purchased]">
                                                    <option value="keep" <?php selected( $not_purchased, 'keep' ); ?>><?php esc_html_e( 'Keep', 'wc-customer-lists' ); ?></option>
                                                    <option value="remove" <?php selected( $not_purchased, 'remove' ); ?>><?php esc_html_e( 'Remove', 'wc-customer-lists' ); ?></option>
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

    /**
     * Sanitize settings before saving.
     *
     * @param array $input
     * @return array
     */
    public function sanitize_settings( array $input ): array {
        $output = [];

        // Enabled lists
        $all_types = array_keys( List_Registry::$list_types );
        $output['enabled_lists'] = array_filter( (array) ( $input['enabled_lists'] ?? [] ), function( $type ) use ( $all_types ) {
            return in_array( $type, $all_types, true );
        } );

        // Per-list settings
        $output['lists_settings'] = [];
        foreach ( $output['enabled_lists'] as $type ) {
            $settings = $input['lists_settings'][ $type ] ?? [];

            $output['lists_settings'][ $type ] = [
                'max_lists'     => max( 0, intval( $settings['max_lists'] ?? 0 ) ),
                'max_items'     => max( 0, intval( $settings['max_items'] ?? 0 ) ),
                'not_purchased' => in_array( $settings['not_purchased'] ?? '', ['keep', 'remove'], true )
                    ? $settings['not_purchased']
                    : 'keep',
            ];
        }

        return $output;
    }
}

// Bootstrap admin
new WC_Customer_Lists_Admin();
