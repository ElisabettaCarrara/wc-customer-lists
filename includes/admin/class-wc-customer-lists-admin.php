<?php
/**
 * Admin functionality for WC Customer Lists.
 *
 * @package WC_Customer_Lists
 */

defined( 'ABSPATH' ) || exit;

final class WC_Customer_Lists_Admin {

    /**
     * Option name used to store settings.
     */
    private const OPTION_NAME = 'wc_customer_lists_settings';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Register admin menu.
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
     * Register settings, sections and fields.
     */
    public function register_settings(): void {

        register_setting(
            'wc_customer_lists',
            self::OPTION_NAME,
            [ $this, 'sanitize_settings' ]
        );

        add_settings_section(
            'wc_customer_lists_main',
            __( 'General settings', 'wc-customer-lists' ),
            '__return_false',
            'wc-customer-lists'
        );

        add_settings_field(
            'enabled_lists',
            __( 'Enabled list types', 'wc-customer-lists' ),
            [ $this, 'render_enabled_lists_field' ],
            'wc-customer-lists',
            'wc_customer_lists_main'
        );

        add_settings_field(
            'max_lists_per_user',
            __( 'Maximum lists per user', 'wc-customer-lists' ),
            [ $this, 'render_number_field' ],
            'wc-customer-lists',
            'wc_customer_lists_main',
            [
                'key'   => 'max_lists_per_user',
                'min'   => 0,
                'label' => __( '0 = unlimited', 'wc-customer-lists' ),
            ]
        );

        add_settings_field(
            'max_items_per_list',
            __( 'Maximum items per list', 'wc-customer-lists' ),
            [ $this, 'render_number_field' ],
            'wc-customer-lists',
            'wc_customer_lists_main',
            [
                'key'   => 'max_items_per_list',
                'min'   => 0,
                'label' => __( '0 = unlimited', 'wc-customer-lists' ),
            ]
        );

        add_settings_field(
            'display_locations',
            __( 'Display locations', 'wc-customer-lists' ),
            [ $this, 'render_display_locations_field' ],
            'wc-customer-lists',
            'wc_customer_lists_main'
        );
    }

    /**
     * Enqueue admin styles.
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
     * Render settings page wrapper.
     */
    public function render_settings_page(): void {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Customer Lists Settings', 'wc-customer-lists' ); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'wc_customer_lists' );
                do_settings_sections( 'wc-customer-lists' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render enabled list types field.
     */
    public function render_enabled_lists_field(): void {

        $options       = get_option( self::OPTION_NAME, [] );
        $enabled_lists = $options['enabled_lists'] ?? [];

        $list_types = \WC_Customer_Lists\Core\List_Registry::get_registered_types();

        if ( empty( $list_types ) ) {
            esc_html_e( 'No list types detected.', 'wc-customer-lists' );
            return;
        }

        foreach ( $list_types as $slug => $config ) {

            $label   = $config['label'] ?? $slug;
            $checked = ! empty( $enabled_lists[ $slug ] );
            ?>
            <label style="display:block;margin-bottom:6px;">
                <input type="checkbox"
                       name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enabled_lists][<?php echo esc_attr( $slug ); ?>]"
                       value="1"
                       <?php checked( $checked ); ?> />
                <?php echo esc_html( $label ); ?>
            </label>
            <?php
        }
    }

    /**
     * Render a generic number field.
     */
    public function render_number_field( array $args ): void {

        $options = get_option( self::OPTION_NAME, [] );
        $key     = $args['key'];
        $value   = isset( $options[ $key ] ) ? absint( $options[ $key ] ) : 0;
        ?>
        <input type="number"
               name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>]"
               value="<?php echo esc_attr( $value ); ?>"
               min="<?php echo esc_attr( $args['min'] ); ?>" />
        <p class="description"><?php echo esc_html( $args['label'] ); ?></p>
        <?php
    }

    /**
     * Render display location checkboxes.
     */
    public function render_display_locations_field(): void {

        $options = get_option( self::OPTION_NAME, [] );
        ?>
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[show_on_product_page]"
                   value="1"
                   <?php checked( ! empty( $options['show_on_product_page'] ) ); ?> />
            <?php esc_html_e( 'Product page', 'wc-customer-lists' ); ?>
        </label>
        <br />
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[show_on_shop_loop]"
                   value="1"
                   <?php checked( ! empty( $options['show_on_shop_loop'] ) ); ?> />
            <?php esc_html_e( 'Shop / archive pages', 'wc-customer-lists' ); ?>
        </label>
        <?php
    }

    /**
     * Sanitize and validate settings.
     */
    public function sanitize_settings( array $input ): array {

        $sanitized = [];

        // Enabled lists
        $sanitized['enabled_lists'] = [];
        $registered = \WC_Customer_Lists\Core\List_Registry::get_registered_types();

        foreach ( $registered as $slug => $config ) {
            $sanitized['enabled_lists'][ $slug ] =
                ! empty( $input['enabled_lists'][ $slug ] ) ? 1 : 0;
        }

        $sanitized['max_lists_per_user'] =
            isset( $input['max_lists_per_user'] ) ? absint( $input['max_lists_per_user'] ) : 0;

        $sanitized['max_items_per_list'] =
            isset( $input['max_items_per_list'] ) ? absint( $input['max_items_per_list'] ) : 0;

        $sanitized['show_on_product_page'] =
            ! empty( $input['show_on_product_page'] ) ? 1 : 0;

        $sanitized['show_on_shop_loop'] =
            ! empty( $input['show_on_shop_loop'] ) ? 1 : 0;

        return $sanitized;
    }
}

// Bootstrap admin
new WC_Customer_Lists_Admin();
