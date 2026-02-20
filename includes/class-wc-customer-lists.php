public static function activate() {
    // Ensure that necessary setup procedures are run before activating the plugin
    // This might include flushing rewrite rules or creating default options
    if ( ! get_option( 'customer_lists_activated' ) ) {
        // This option indicates whether the customer lists functionality has been initialized
        // Run the activation routines only if the plugin has not been activated previously
        update_option( 'customer_lists_activated', true );

        // Flush rewrite rules to ensure custom endpoints are registered
        flush_rewrite_rules();

        // Optionally perform other setup tasks such as creating default user roles or capabilities
        // Add additional setup logic here as needed
    }
    // Log or trigger any additional processes needed for plugin activation
    error_log( 'Customer Lists plugin activated.' );
}