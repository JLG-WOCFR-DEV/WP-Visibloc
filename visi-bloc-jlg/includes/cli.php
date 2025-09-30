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

if ( ! function_exists( 'visibloc_jlg_cli_count_posts_to_scan' ) ) {
    /**
     * Count the total number of posts scanned when rebuilding the group block summary index.
     *
     * @return int
     */
    function visibloc_jlg_cli_count_posts_to_scan() {
        $post_types = apply_filters( 'visibloc_jlg_scanned_post_types', [ 'post', 'page', 'wp_template', 'wp_template_part' ] );
        $page       = 1;
        $scanned    = 0;

        while ( true ) {
            $query = new WP_Query(
                [
                    'post_type'              => $post_types,
                    'post_status'            => [ 'publish', 'future', 'draft', 'pending', 'private' ],
                    'posts_per_page'         => 100,
                    'paged'                  => $page,
                    'fields'                 => 'ids',
                    'no_found_rows'          => true,
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                ]
            );

            if ( empty( $query->posts ) ) {
                break;
            }

            $scanned += count( $query->posts );
            $page++;
        }

        return $scanned;
    }
}

if ( ! function_exists( 'visibloc_jlg_cli_calculate_rebuild_stats' ) ) {
    /**
     * Execute the index rebuild and return statistics about the operation.
     *
     * @return array{
     *     scanned_posts:int,
     *     entries_count:int,
     * }
     */
    function visibloc_jlg_cli_calculate_rebuild_stats() {
        $summaries     = visibloc_jlg_rebuild_group_block_summary_index();
        $entries_count = is_countable( $summaries ) ? count( $summaries ) : 0;
        $scanned_posts = visibloc_jlg_cli_count_posts_to_scan();

        return [
            'scanned_posts' => $scanned_posts,
            'entries_count' => $entries_count,
        ];
    }
}

if ( ! function_exists( 'visibloc_jlg_cli_rebuild_index_command' ) ) {
    /**
     * Handle the `wp visibloc rebuild-index` command.
     */
    function visibloc_jlg_cli_rebuild_index_command() {
        $stats = visibloc_jlg_cli_calculate_rebuild_stats();

        WP_CLI::log( sprintf( 'Scanned %d posts and created %d index entries.', $stats['scanned_posts'], $stats['entries_count'] ) );

        visibloc_jlg_clear_caches();

        WP_CLI::success( 'Group block summary caches cleared.' );
    }
}

WP_CLI::add_command( 'visibloc rebuild-index', 'visibloc_jlg_cli_rebuild_index_command' );

