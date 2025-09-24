<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_enqueue_scripts', 'visibloc_jlg_enqueue_public_styles' );
function visibloc_jlg_enqueue_public_styles() {
    $plugin_main_file = __DIR__ . '/../visi-bloc-jlg.php';
    $can_preview       = visibloc_jlg_can_user_preview();
    $device_css        = visibloc_jlg_generate_device_visibility_css( $can_preview );
    $device_handle     = 'visibloc-jlg-device-visibility';

    wp_register_style( $device_handle, false, [], '1.1' );

    if ( '' !== $device_css ) {
        wp_enqueue_style( $device_handle );
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

function visibloc_jlg_generate_device_visibility_css( $can_preview ) {
    $mobile_bp            = absint( get_option( 'visibloc_breakpoint_mobile', 781 ) );
    $tablet_bp            = absint( get_option( 'visibloc_breakpoint_tablet', 1024 ) );
    $tablet_min_bp        = $mobile_bp + 1;
    $has_valid_tablet_bp  = ( $tablet_bp > $mobile_bp );
    $desktop_reference_bp = $has_valid_tablet_bp ? $tablet_bp : $mobile_bp;
    $desktop_min_bp       = $desktop_reference_bp + 1;

    $css_lines = [];

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

