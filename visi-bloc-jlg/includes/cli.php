<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

if ( ! function_exists( 'visibloc_jlg_cli_rebuild_index_command' ) ) {
    /**
     * Handle the `wp visibloc rebuild-index` command.
     */
    function visibloc_jlg_cli_rebuild_index_command() {
        $scanned_posts = 0;
        $summaries     = visibloc_jlg_rebuild_group_block_summary_index( $scanned_posts );
        $entries_count = is_countable( $summaries ) ? count( $summaries ) : 0;

        WP_CLI::log( sprintf( 'Scanned %d posts.', $scanned_posts ) );
        WP_CLI::log( sprintf( 'Created %d index entries.', $entries_count ) );

        visibloc_jlg_clear_caches();

        WP_CLI::success( 'Group block summary caches cleared.' );
    }
}

WP_CLI::add_command( 'visibloc rebuild-index', 'visibloc_jlg_cli_rebuild_index_command' );

