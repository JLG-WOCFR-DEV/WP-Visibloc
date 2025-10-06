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

if ( ! function_exists( 'visibloc_jlg_cli_export_settings_command' ) ) {
    /**
     * Handle the `wp visibloc export-settings` command.
     *
     * @param array $args       Positional command arguments (unused).
     * @param array $assoc_args Associative command arguments.
     */
    function visibloc_jlg_cli_export_settings_command( $args, $assoc_args ) {
        if ( ! function_exists( 'visibloc_jlg_get_settings_snapshot' ) ) {
            require_once __DIR__ . '/admin-settings.php';
        }

        if ( function_exists( 'visibloc_jlg_get_fallback_settings' ) ) {
            visibloc_jlg_get_fallback_settings( true );
        }

        $snapshot = visibloc_jlg_get_settings_snapshot();
        $json     = wp_json_encode( $snapshot );

        if ( false === $json ) {
            WP_CLI::error( 'Unable to encode the settings snapshot as JSON.' );
        }

        $decoded = json_decode( $json, true );
        if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
            $pretty = json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

            if ( false !== $pretty ) {
                $json = $pretty;
            }
        }

        $output_path = isset( $assoc_args['output'] ) ? trim( (string) $assoc_args['output'] ) : '';

        if ( '' !== $output_path ) {
            $directory = dirname( $output_path );

            if ( '' !== $directory && '.' !== $directory && ! is_dir( $directory ) ) {
                WP_CLI::error( sprintf( 'Directory does not exist: %s', $directory ) );
            }

            $bytes_written = file_put_contents( $output_path, $json );

            if ( false === $bytes_written ) {
                WP_CLI::error( sprintf( 'Unable to write settings snapshot to %s.', $output_path ) );
            }

            WP_CLI::success( sprintf( 'Settings snapshot written to %s.', $output_path ) );

            return;
        }

        WP_CLI::log( $json );
        WP_CLI::success( 'Settings snapshot exported.' );
    }
}

WP_CLI::add_command( 'visibloc rebuild-index', 'visibloc_jlg_cli_rebuild_index_command' );
WP_CLI::add_command( 'visibloc export-settings', 'visibloc_jlg_cli_export_settings_command' );

