<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

require_once __DIR__ . '/includes/cache-constants.php';

// Supprime les options de la base de données
delete_option( 'visibloc_debug_mode' );
delete_option( 'visibloc_breakpoint_mobile' );
delete_option( 'visibloc_breakpoint_tablet' );
delete_option( 'visibloc_preview_roles' );
delete_option( 'visibloc_group_block_summary' );
delete_option( 'visibloc_supported_blocks' );
delete_option( 'visibloc_fallback_settings' );
delete_option( 'visibloc_insights' );
wp_cache_delete( 'visibloc_fallback_settings', 'visibloc_jlg' );

$fallback_transients = get_option( 'visibloc_fallback_blocks_transients', [] );

if ( is_array( $fallback_transients ) ) {
    foreach ( array_unique( array_map( 'strval', $fallback_transients ) ) as $transient_key ) {
        if ( '' === $transient_key ) {
            continue;
        }

        delete_transient( $transient_key );
    }
}

delete_option( 'visibloc_fallback_blocks_transients' );
delete_option( 'visibloc_fallback_blocks_cache_version' );
wp_cache_delete( 'visibloc_fallback_blocks_version', 'visibloc_jlg' );

// Supprime les transients de cache du plugin
delete_transient( 'visibloc_hidden_posts' );
delete_transient( 'visibloc_device_posts' );
delete_transient( 'visibloc_scheduled_posts' );
delete_transient( 'visibloc_group_block_metadata' );
delete_transient( 'visibloc_jlg_missing_editor_assets' );

$registered_buckets = get_option( VISIBLOC_JLG_DEVICE_CSS_BUCKET_OPTION, [] );

if ( is_array( $registered_buckets ) ) {
    foreach ( array_unique( array_map( 'strval', $registered_buckets ) ) as $bucket_key ) {
        if ( '' === $bucket_key ) {
            continue;
        }

        $transient_name = VISIBLOC_JLG_DEVICE_CSS_TRANSIENT_PREFIX . $bucket_key;

        delete_transient( $transient_name );
        wp_cache_delete( $transient_name, VISIBLOC_JLG_DEVICE_CSS_CACHE_GROUP );
    }
}

delete_option( VISIBLOC_JLG_DEVICE_CSS_BUCKET_OPTION );
wp_cache_delete( VISIBLOC_JLG_DEVICE_CSS_BUCKET_OPTION, VISIBLOC_JLG_DEVICE_CSS_CACHE_GROUP );

wp_cache_delete( VISIBLOC_JLG_DEVICE_CSS_CACHE_KEY, VISIBLOC_JLG_DEVICE_CSS_CACHE_GROUP );
