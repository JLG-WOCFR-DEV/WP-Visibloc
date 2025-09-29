<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'VISIBLOC_JLG_VERSION' ) ) {
    $visibloc_version = '0.0.0';
    $plugin_main_file = __DIR__ . '/../visi-bloc-jlg.php';

    if ( is_readable( $plugin_main_file ) ) {
        $plugin_contents = file_get_contents( $plugin_main_file );

        if ( false !== $plugin_contents && preg_match( '/^\s*\*\s*Version:\s*(.+)$/mi', $plugin_contents, $matches ) ) {
            $visibloc_version = trim( $matches[1] );
        }
    }

    define( 'VISIBLOC_JLG_VERSION', $visibloc_version );
}

add_action( 'wp_enqueue_scripts', 'visibloc_jlg_enqueue_public_styles' );
function visibloc_jlg_enqueue_public_styles() {
    $plugin_main_file = __DIR__ . '/../visi-bloc-jlg.php';
    $can_preview      = visibloc_jlg_can_user_preview();
    $default_mobile   = 781;
    $default_tablet   = 1024;
    $mobile_bp        = absint( get_option( 'visibloc_breakpoint_mobile', $default_mobile ) );
    $tablet_bp        = absint( get_option( 'visibloc_breakpoint_tablet', $default_tablet ) );
    $mobile_bp        = $mobile_bp > 0 ? $mobile_bp : $default_mobile;
    $tablet_bp        = $tablet_bp > 0 ? $tablet_bp : $default_tablet;
    $has_custom_breakpoints = ( $mobile_bp !== $default_mobile ) || ( $tablet_bp !== $default_tablet );
    $device_style_src       = plugins_url( 'assets/device-visibility.css', $plugin_main_file );
    $default_handle         = 'visibloc-jlg-device-visibility';
    $dynamic_handle         = 'visibloc-jlg-device-visibility-dynamic';

    if ( $has_custom_breakpoints ) {
        wp_dequeue_style( $default_handle );
        wp_deregister_style( $default_handle );
        wp_register_style( $dynamic_handle, false, [], '1.1' );
        $device_handle = $dynamic_handle;
    } else {
        wp_register_style( $default_handle, $device_style_src, [], '1.1' );
        $device_handle = $default_handle;
    }

    wp_enqueue_style( $device_handle );

    $device_css = visibloc_jlg_generate_device_visibility_css( $can_preview, $mobile_bp, $tablet_bp );

    if ( '' !== $device_css ) {
        wp_add_inline_style( $device_handle, $device_css );
    }

    if ( $can_preview ) {
        wp_enqueue_style( 'visibloc-jlg-public-styles', plugins_url( 'admin-styles.css', $plugin_main_file ) );
    }
}

add_action( 'enqueue_block_editor_assets', 'visibloc_jlg_enqueue_editor_assets' );
function visibloc_jlg_enqueue_editor_assets() {
    $plugin_main_file = __DIR__ . '/../visi-bloc-jlg.php';
    $asset_file_path = plugin_dir_path( __DIR__ ) . 'build/index.asset.php';
    if ( ! file_exists( $asset_file_path ) ) { return; }
    $asset_file = include( $asset_file_path );
    wp_enqueue_script( 'visibloc-jlg-editor-script', plugins_url( 'build/index.js', $plugin_main_file ), $asset_file['dependencies'], $asset_file['version'], true );
    wp_set_script_translations( 'visibloc-jlg-editor-script', 'visi-bloc-jlg', plugin_dir_path( __DIR__ ) . 'languages' );
    wp_enqueue_style( 'visibloc-jlg-editor-style', plugins_url( 'build/index.css', $plugin_main_file ), [], $asset_file['version'] );
    wp_localize_script('visibloc-jlg-editor-script', 'VisiBlocData', ['roles' => wp_roles()->get_names()]);
}

function visibloc_jlg_generate_device_visibility_css( $can_preview, $mobile_bp = null, $tablet_bp = null ) {
    $default_mobile_bp = 781;
    $default_tablet_bp = 1024;

    if ( null === $mobile_bp ) {
        $mobile_bp = absint( get_option( 'visibloc_breakpoint_mobile', $default_mobile_bp ) );
    }

    if ( null === $tablet_bp ) {
        $tablet_bp = absint( get_option( 'visibloc_breakpoint_tablet', $default_tablet_bp ) );
    }

    $mobile_bp = $mobile_bp > 0 ? $mobile_bp : $default_mobile_bp;
    $tablet_bp = $tablet_bp > 0 ? $tablet_bp : $default_tablet_bp;

    $cache_group = 'visibloc_jlg';
    $cache_key   = 'visibloc_device_css_cache';
    $bucket_key  = sprintf(
        '%s:%d:%d:%d',
        VISIBLOC_JLG_VERSION,
        $can_preview ? 1 : 0,
        (int) $mobile_bp,
        (int) $tablet_bp
    );

    $cached_css = wp_cache_get( $cache_key, $cache_group );

    if ( is_array( $cached_css ) && array_key_exists( $bucket_key, $cached_css ) ) {
        return $cached_css[ $bucket_key ];
    }

    $css_lines      = [];
    $current_blocks = visibloc_jlg_build_visibility_blocks( $mobile_bp, $tablet_bp );

    $css_lines = array_merge(
        $css_lines,
        visibloc_jlg_render_visibility_blocks( $current_blocks, 'display: none !important;' )
    );

    $has_custom_breakpoints = ( $mobile_bp !== $default_mobile_bp ) || ( $tablet_bp !== $default_tablet_bp );

    if ( $has_custom_breakpoints ) {
        $default_blocks = visibloc_jlg_build_visibility_blocks( $default_mobile_bp, $default_tablet_bp );
        $reset_blocks   = visibloc_jlg_calculate_reset_blocks( $default_blocks, $current_blocks );

        if ( ! empty( $reset_blocks ) ) {
            $css_lines = array_merge(
                $css_lines,
                visibloc_jlg_render_visibility_blocks( $reset_blocks, 'display: revert !important;' )
            );
        }
    }

    if ( $can_preview ) {
        $css_lines[] = '.vb-desktop-only, .vb-tablet-only, .vb-mobile-only, .vb-hide-on-desktop, .vb-hide-on-tablet, .vb-hide-on-mobile { position: relative; outline: 2px dashed #0073aa; outline-offset: 2px; }';
        $css_lines[] = '.vb-desktop-only::before, .vb-tablet-only::before, .vb-mobile-only::before, .vb-hide-on-desktop::before, .vb-hide-on-tablet::before, .vb-hide-on-mobile::before { content: attr(data-visibloc-label); position: absolute; bottom: -2px; right: -2px; background-color: #0073aa; color: white; padding: 2px 8px; font-size: 11px; font-family: sans-serif; font-weight: bold; z-index: 99; border-radius: 3px 0 3px 0; }';

        $labels = [
            '.vb-hide-on-mobile::before'   => __( 'Caché sur Mobile', 'visi-bloc-jlg' ),
            '.vb-hide-on-tablet::before'   => __( 'Caché sur Tablette', 'visi-bloc-jlg' ),
            '.vb-hide-on-desktop::before'  => __( 'Caché sur Desktop', 'visi-bloc-jlg' ),
            '.vb-mobile-only::before'      => __( 'Visible sur Mobile Uniquement', 'visi-bloc-jlg' ),
            '.vb-tablet-only::before'      => __( 'Visible sur Tablette Uniquement', 'visi-bloc-jlg' ),
            '.vb-desktop-only::before'     => __( 'Visible sur Desktop Uniquement', 'visi-bloc-jlg' ),
        ];

        foreach ( $labels as $selector => $label ) {
            $css_lines[] = sprintf(
                '%s { content: %s; }',
                $selector,
                wp_json_encode( $label )
            );
        }
    }

    $css = implode( "\n", $css_lines );

    if ( ! is_array( $cached_css ) ) {
        $cached_css = [];
    }

    $cached_css[ $bucket_key ] = $css;

    wp_cache_set( $cache_key, $cached_css, $cache_group );

    return $css;
}

function visibloc_jlg_build_visibility_blocks( $mobile_bp, $tablet_bp ) {
    $mobile_bp = (int) $mobile_bp;
    $tablet_bp = (int) $tablet_bp;

    $has_valid_tablet = $tablet_bp > $mobile_bp;
    $blocks           = [
        [
            'min'       => null,
            'max'       => $mobile_bp,
            'selectors' => [
                '.vb-hide-on-mobile',
                '.vb-tablet-only',
                '.vb-desktop-only',
            ],
        ],
    ];

    if ( $has_valid_tablet ) {
        $blocks[] = [
            'min'       => $mobile_bp + 1,
            'max'       => $tablet_bp,
            'selectors' => [
                '.vb-hide-on-tablet',
                '.vb-mobile-only',
                '.vb-desktop-only',
            ],
        ];
    }

    $desktop_reference = $has_valid_tablet ? $tablet_bp : $mobile_bp;

    $blocks[] = [
        'min'       => $desktop_reference + 1,
        'max'       => null,
        'selectors' => [
            '.vb-hide-on-desktop',
            '.vb-mobile-only',
            '.vb-tablet-only',
        ],
    ];

    return $blocks;
}

function visibloc_jlg_render_visibility_blocks( array $blocks, $declaration ) {
    $css_lines = [];

    foreach ( $blocks as $block ) {
        if ( empty( $block['selectors'] ) ) {
            continue;
        }

        $min = isset( $block['min'] ) ? (int) $block['min'] : null;
        $max = isset( $block['max'] ) ? (int) $block['max'] : null;

        if ( isset( $min, $max ) && $min > $max ) {
            continue;
        }

        $media_query = visibloc_jlg_format_media_query( $min, $max );

        if ( null === $media_query ) {
            continue;
        }

        $css_lines[] = $media_query;

        $selector_count = count( $block['selectors'] );
        foreach ( $block['selectors'] as $index => $selector ) {
            $is_last = ( $index === $selector_count - 1 );
            $css_lines[] = sprintf(
                '    %s%s',
                $selector,
                $is_last ? ' { ' . $declaration . ' }' : ','
            );
        }

        $css_lines[] = '}';
    }

    return $css_lines;
}

function visibloc_jlg_format_media_query( $min, $max ) {
    if ( isset( $min ) && isset( $max ) ) {
        return sprintf( '@media (min-width: %1$dpx) and (max-width: %2$dpx) {', $min, $max );
    }

    if ( isset( $min ) ) {
        return sprintf( '@media (min-width: %dpx) {', $min );
    }

    if ( isset( $max ) ) {
        return sprintf( '@media (max-width: %dpx) {', $max );
    }

    return null;
}

function visibloc_jlg_calculate_reset_blocks( array $default_blocks, array $current_blocks ) {
    $default_ranges = visibloc_jlg_collect_selector_ranges( $default_blocks );
    $current_ranges = visibloc_jlg_collect_selector_ranges( $current_blocks );
    $reset_blocks   = [];

    foreach ( $default_ranges as $selector => $ranges ) {
        $current = $current_ranges[ $selector ] ?? [];

        foreach ( $ranges as $range ) {
            $differences = visibloc_jlg_range_difference( $range, $current );

            foreach ( $differences as $difference ) {
                $key = sprintf(
                    '%s:%s',
                    isset( $difference['min'] ) ? $difference['min'] : '',
                    isset( $difference['max'] ) ? $difference['max'] : ''
                );

                if ( ! isset( $reset_blocks[ $key ] ) ) {
                    $reset_blocks[ $key ] = [
                        'min'       => isset( $difference['min'] ) ? $difference['min'] : null,
                        'max'       => isset( $difference['max'] ) ? $difference['max'] : null,
                        'selectors' => [],
                    ];
                }

                if ( ! in_array( $selector, $reset_blocks[ $key ]['selectors'], true ) ) {
                    $reset_blocks[ $key ]['selectors'][] = $selector;
                }
            }
        }
    }

    if ( empty( $reset_blocks ) ) {
        return [];
    }

    $reset_blocks = array_values( $reset_blocks );
    usort( $reset_blocks, 'visibloc_jlg_compare_blocks' );

    return $reset_blocks;
}

function visibloc_jlg_collect_selector_ranges( array $blocks ) {
    $ranges = [];

    foreach ( $blocks as $block ) {
        $range = [
            'min' => isset( $block['min'] ) ? (int) $block['min'] : null,
            'max' => isset( $block['max'] ) ? (int) $block['max'] : null,
        ];

        foreach ( $block['selectors'] as $selector ) {
            if ( ! isset( $ranges[ $selector ] ) ) {
                $ranges[ $selector ] = [];
            }

            $ranges[ $selector ][] = $range;
        }
    }

    return $ranges;
}

function visibloc_jlg_range_difference( array $range, array $sub_ranges ) {
    if ( empty( $sub_ranges ) ) {
        return [ $range ];
    }

    $segments = [ $range ];
    usort( $sub_ranges, 'visibloc_jlg_compare_ranges' );

    foreach ( $sub_ranges as $sub_range ) {
        $next_segments = [];

        foreach ( $segments as $segment ) {
            $parts = visibloc_jlg_subtract_segment( $segment, $sub_range );

            foreach ( $parts as $part ) {
                if ( ! isset( $part['min'] ) && ! isset( $part['max'] ) ) {
                    continue;
                }

                if ( isset( $part['min'], $part['max'] ) && $part['min'] > $part['max'] ) {
                    continue;
                }

                $next_segments[] = [
                    'min' => isset( $part['min'] ) ? (int) $part['min'] : null,
                    'max' => isset( $part['max'] ) ? (int) $part['max'] : null,
                ];
            }
        }

        $segments = $next_segments;

        if ( empty( $segments ) ) {
            break;
        }
    }

    return $segments;
}

function visibloc_jlg_subtract_segment( array $segment, array $sub_range ) {
    $segment_min = isset( $segment['min'] ) ? (int) $segment['min'] : PHP_INT_MIN;
    $segment_max = isset( $segment['max'] ) ? (int) $segment['max'] : PHP_INT_MAX;
    $sub_min     = isset( $sub_range['min'] ) ? (int) $sub_range['min'] : PHP_INT_MIN;
    $sub_max     = isset( $sub_range['max'] ) ? (int) $sub_range['max'] : PHP_INT_MAX;

    if ( $sub_max < $segment_min || $sub_min > $segment_max ) {
        return [ $segment ];
    }

    $results = [];

    if ( $sub_min > $segment_min ) {
        $results[] = [
            'min' => isset( $segment['min'] ) ? (int) $segment['min'] : null,
            'max' => isset( $sub_range['min'] ) ? ( (int) $sub_range['min'] - 1 ) : null,
        ];
    }

    if ( $sub_max < $segment_max ) {
        $results[] = [
            'min' => isset( $sub_range['max'] ) ? ( (int) $sub_range['max'] + 1 ) : null,
            'max' => isset( $segment['max'] ) ? (int) $segment['max'] : null,
        ];
    }

    return array_values(
        array_filter(
            $results,
            static function ( $candidate ) {
                if ( ! isset( $candidate['min'] ) && ! isset( $candidate['max'] ) ) {
                    return false;
                }

                if ( isset( $candidate['min'], $candidate['max'] ) && $candidate['min'] > $candidate['max'] ) {
                    return false;
                }

                return true;
            }
        )
    );
}

function visibloc_jlg_compare_blocks( $a, $b ) {
    return visibloc_jlg_compare_ranges( $a, $b );
}

function visibloc_jlg_compare_ranges( $a, $b ) {
    $a_min = isset( $a['min'] ) ? (int) $a['min'] : PHP_INT_MIN;
    $b_min = isset( $b['min'] ) ? (int) $b['min'] : PHP_INT_MIN;

    if ( $a_min === $b_min ) {
        $a_max = isset( $a['max'] ) ? (int) $a['max'] : PHP_INT_MAX;
        $b_max = isset( $b['max'] ) ? (int) $b['max'] : PHP_INT_MAX;

        if ( $a_max === $b_max ) {
            return 0;
        }

        return ( $a_max < $b_max ) ? -1 : 1;
    }

    return ( $a_min < $b_min ) ? -1 : 1;
}

