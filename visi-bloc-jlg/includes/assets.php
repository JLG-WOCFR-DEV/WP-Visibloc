<?php
if ( ! defined( 'ABSPATH' ) ) exit;

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
    $device_handle          = $has_custom_breakpoints ? 'visibloc-jlg-device-visibility-dynamic' : 'visibloc-jlg-device-visibility';
    $device_style_src       = plugins_url( 'assets/device-visibility.css', $plugin_main_file );

    if ( $has_custom_breakpoints ) {
        wp_register_style( $device_handle, false, [], '1.1' );
    } else {
        wp_register_style( $device_handle, $device_style_src, [], '1.1' );
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

    $css_lines = [];

    $has_valid_tablet_bp  = ( $tablet_bp > $mobile_bp );
    $tablet_min_bp        = $mobile_bp + 1;
    $desktop_reference_bp = $has_valid_tablet_bp ? $tablet_bp : $mobile_bp;
    $desktop_min_bp       = $desktop_reference_bp + 1;

    $css_lines[] = sprintf(
        '@media (max-width: %dpx) {',
        $mobile_bp
    );
    $css_lines[] = '    .vb-hide-on-mobile,';
    $css_lines[] = '    .vb-tablet-only,';
    $css_lines[] = '    .vb-desktop-only { display: none !important; }';
    $css_lines[] = '}';

    if ( $has_valid_tablet_bp ) {
        $css_lines[] = sprintf(
            '@media (min-width: %1$dpx) and (max-width: %2$dpx) {',
            $tablet_min_bp,
            $tablet_bp
        );
        $css_lines[] = '    .vb-hide-on-tablet,';
        $css_lines[] = '    .vb-mobile-only,';
        $css_lines[] = '    .vb-desktop-only { display: none !important; }';
        $css_lines[] = '}';
    }

    $css_lines[] = sprintf(
        '@media (min-width: %dpx) {',
        $desktop_min_bp
    );
    $css_lines[] = '    .vb-hide-on-desktop,';
    $css_lines[] = '    .vb-mobile-only,';
    $css_lines[] = '    .vb-tablet-only { display: none !important; }';
    $css_lines[] = '}';

    $has_custom_breakpoints = ( $mobile_bp !== $default_mobile_bp ) || ( $tablet_bp !== $default_tablet_bp );

    if ( $has_custom_breakpoints ) {
        $static_blocks = [
            [
                'min'       => null,
                'max'       => $default_mobile_bp,
                'selectors' => [
                    '.vb-hide-on-mobile',
                    '.vb-tablet-only',
                    '.vb-desktop-only',
                ],
            ],
            [
                'min'       => $default_mobile_bp + 1,
                'max'       => $default_tablet_bp,
                'selectors' => [
                    '.vb-hide-on-tablet',
                    '.vb-mobile-only',
                    '.vb-desktop-only',
                ],
            ],
            [
                'min'       => $default_tablet_bp + 1,
                'max'       => null,
                'selectors' => [
                    '.vb-hide-on-desktop',
                    '.vb-mobile-only',
                    '.vb-tablet-only',
                ],
            ],
        ];

        $visible_ranges = [
            '.vb-hide-on-mobile' => [ [ $mobile_bp + 1, null ] ],
            '.vb-tablet-only'    => $has_valid_tablet_bp ? [ [ $tablet_min_bp, $tablet_bp ] ] : [],
            '.vb-desktop-only'   => [ [ $desktop_min_bp, null ] ],
            '.vb-hide-on-tablet' => $has_valid_tablet_bp ? [ [ null, $mobile_bp ], [ $tablet_bp + 1, null ] ] : [ [ null, null ] ],
            '.vb-mobile-only'    => [ [ null, $mobile_bp ] ],
            '.vb-hide-on-desktop'=> [ [ null, $desktop_min_bp - 1 ] ],
        ];

        $reset_blocks = [];

        foreach ( $static_blocks as $block ) {
            $static_min = isset( $block['min'] ) ? (int) $block['min'] : null;
            $static_max = isset( $block['max'] ) ? (int) $block['max'] : null;

            foreach ( $block['selectors'] as $selector ) {
                if ( empty( $visible_ranges[ $selector ] ) ) {
                    continue;
                }

                foreach ( $visible_ranges[ $selector ] as $visible_range ) {
                    $range_min = isset( $visible_range[0] ) ? (int) $visible_range[0] : null;
                    $range_max = isset( $visible_range[1] ) ? (int) $visible_range[1] : null;

                    $intersection = visibloc_jlg_intersect_ranges( $static_min, $static_max, $range_min, $range_max );

                    if ( null === $intersection ) {
                        continue;
                    }

                    $key = sprintf(
                        '%s:%s',
                        null === $intersection['min'] ? '' : $intersection['min'],
                        null === $intersection['max'] ? '' : $intersection['max']
                    );

                    if ( ! isset( $reset_blocks[ $key ] ) ) {
                        $reset_blocks[ $key ] = [
                            'min'       => $intersection['min'],
                            'max'       => $intersection['max'],
                            'selectors' => [],
                        ];
                    }

                    if ( ! in_array( $selector, $reset_blocks[ $key ]['selectors'], true ) ) {
                        $reset_blocks[ $key ]['selectors'][] = $selector;
                    }
                }
            }
        }

        if ( ! empty( $reset_blocks ) ) {
            uasort(
                $reset_blocks,
                function ( $a, $b ) {
                    $a_min = isset( $a['min'] ) ? $a['min'] : PHP_INT_MIN;
                    $b_min = isset( $b['min'] ) ? $b['min'] : PHP_INT_MIN;

                    if ( $a_min === $b_min ) {
                        $a_max = isset( $a['max'] ) ? $a['max'] : PHP_INT_MAX;
                        $b_max = isset( $b['max'] ) ? $b['max'] : PHP_INT_MAX;

                        if ( $a_max === $b_max ) {
                            return 0;
                        }

                        return ( $a_max < $b_max ) ? -1 : 1;
                    }

                    return ( $a_min < $b_min ) ? -1 : 1;
                }
            );

            foreach ( $reset_blocks as $reset ) {
                $min = $reset['min'];
                $max = $reset['max'];

                if ( isset( $min, $max ) && $min > $max ) {
                    continue;
                }

                if ( isset( $min ) && isset( $max ) ) {
                    $css_lines[] = sprintf(
                        '@media (min-width: %1$dpx) and (max-width: %2$dpx) {',
                        $min,
                        $max
                    );
                } elseif ( isset( $min ) ) {
                    $css_lines[] = sprintf(
                        '@media (min-width: %dpx) {',
                        $min
                    );
                } elseif ( isset( $max ) ) {
                    $css_lines[] = sprintf(
                        '@media (max-width: %dpx) {',
                        $max
                    );
                } else {
                    continue;
                }

                $selector_count = count( $reset['selectors'] );
                foreach ( $reset['selectors'] as $index => $selector ) {
                    $is_last = ( $index === $selector_count - 1 );
                    $css_lines[] = sprintf(
                        '    %s%s',
                        $selector,
                        $is_last ? ' { display: revert !important; }' : ','
                    );
                }

                $css_lines[] = '}';
            }
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

    return implode( "\n", $css_lines );
}

function visibloc_jlg_intersect_ranges( $min_a, $max_a, $min_b, $max_b ) {
    $range_a_min = isset( $min_a ) ? (int) $min_a : -INF;
    $range_a_max = isset( $max_a ) ? (int) $max_a : INF;
    $range_b_min = isset( $min_b ) ? (int) $min_b : -INF;
    $range_b_max = isset( $max_b ) ? (int) $max_b : INF;

    $intersection_min = max( $range_a_min, $range_b_min );
    $intersection_max = min( $range_a_max, $range_b_max );

    if ( $intersection_min > $intersection_max ) {
        return null;
    }

    $resolved_min = is_infinite( $intersection_min ) ? null : (int) $intersection_min;
    $resolved_max = is_infinite( $intersection_max ) ? null : (int) $intersection_max;

    if ( isset( $resolved_min, $resolved_max ) && $resolved_min > $resolved_max ) {
        return null;
    }

    return [
        'min' => $resolved_min,
        'max' => $resolved_max,
    ];
}

