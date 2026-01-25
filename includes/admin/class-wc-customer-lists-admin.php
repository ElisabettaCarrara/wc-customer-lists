<?php
/**
 * Admin functionality for WC Customer Lists.
 *
 * Handles settings page with dynamic list type enable/disable checkboxes.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

use WC_Customer_Lists\Core\List_Registry;

final class WC_Customer_Lists_Admin {

    /** Option name for storing settings */
    public const OPTION_NAME = 'wc_customer_lists_settings';

    /** Holds current settings */
    private array $settings = [];

    public function __construct() {
        $this->settings = get_option( self::OPTION_NAME, $this->default_settings() );

        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Default settings.
     *
     * @return array
     */
    private function default_settings(): array {
        return [
            'enabled_lists' => array_keys( List_Registry::$list_types ), // all enabled by default
        ];
    }

    /**
     * Register admin menu page.
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
     * Register settings and sanitization callback.
     */
    public function register_settings(): void {
        register_setting(
            'wc_customer_lists_group',
            self::OPTION_NAME,
            [
                'sanitize_callback' => [ $this, 'sanitize_settings' ],
                'default'           => $this->default_settings(),
            ]
        );
    }

    /**
     * Sanitization callback for settings.
     *
     * @param array $input
     * @return array
     */
    public function sanitize_settings( array $input ): array {
        $sanitized = [
            'enabled_lists' => [],
        ];

        if ( ! empty( $input['enabled_lists'] ) && is_array( $input['enabled_lists'] ) ) {
            foreach ( $input['enabled_lists'] as $post_type ) {
                // Only allow known list types
                if ( isset( List_Registry::$list_types[ $post_type ] ) ) {
                    $sanitized['enabled_lists'][] = $post_type;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Enqueue admin CSS/JS.
     */
    public function enqueue_assets(): void {
        wp_enqueue_style(
            'wc-customer-lists-admin',
            plugins_url( 'assets/css/admin.css', WC_CUSTOMER_LISTS_FILE ),
            [],
            '1.0.0'
        );
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page(): void {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Customer Lists Settings', 'wc-customer-lists' ); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'wc_customer_lists_group' );
                do_settings_sections( 'wc_customer_lists_group' );
                ?>

                <h2><?php esc_html_e( 'Enable Lists', 'wc-customer-lists' ); ?></h2>
                <p><?php esc_html_e( 'Select which list types are available to users.', 'wc-customer-lists' ); ?></p>

                <table class="form-table">
                    <tbody>
                    <?php foreach ( List_Registry::$list_types as $post_type => $config ) : 
                        $checked = in_array( $post_type, $this->settings['enabled_lists'], true );
                        $label   = (new ReflectionClass($config['class']))->getShortName(); // e.g., Wishlist, Bridal_List
                        ?>
                        <tr>
                            <th scope="row"><?php echo esc_html( $label ); ?></th>
                            <td>
                                <input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enabled_lists][]"
                                       value="<?php echo esc_attr( $post_type ); ?>" <?php checked( $checked ); ?> />
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
}

// Bootstrap admin
new WC_Customer_Lists_Admin();
