<?php
// No direct access.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$post_types = [ 'wcbabylist', 'wcbridallist', 'wceventlist', 'wcwishlist' ];
foreach ( $post_types as $pt ) {
    $posts = get_posts( [ 
        'post_type' => $pt, 
        'post_status' => 'any', 
        'numberposts' => -1 
    ] );
    foreach ( $posts as $post ) {
        wp_delete_post( $post->ID, true );
    }
}
delete_option( 'wc_customer_lists_settings' );
