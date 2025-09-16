<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_enqueue_scripts', 'visibloc_jlg_enqueue_public_styles' );
function visibloc_jlg_enqueue_public_styles() {
    $plugin_main_file = __DIR__ . '/../visi-bloc-jlg.php';
    if ( visibloc_jlg_can_user_preview() ) {
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
    wp_enqueue_style( 'visibloc-jlg-editor-style', plugins_url( 'build/index.css', $plugin_main_file ), [], $asset_file['version'] );
    wp_localize_script('visibloc-jlg-editor-script', 'VisiBlocData', ['roles' => wp_roles()->get_names()]);
}

add_action( 'wp_head', 'visibloc_jlg_add_device_visibility_styles' );
function visibloc_jlg_add_device_visibility_styles() {
    $can_preview = visibloc_jlg_can_user_preview();
    $mobile_bp = get_option( 'visibloc_breakpoint_mobile', 781 );
    $tablet_bp = get_option( 'visibloc_breakpoint_tablet', 1024 );
    $tablet_min_bp = $mobile_bp + 1;
    $desktop_min_bp = $tablet_bp + 1;
    ?>
    <style id="visibloc-jlg-styles">
        <?php if ( ! $can_preview ) : ?>
        .vb-desktop-only, .vb-tablet-only, .vb-mobile-only { display: none; }
        @media (max-width: <?php echo intval( $mobile_bp ); ?>px) { .vb-hide-on-mobile { display: none !important; } .vb-mobile-only { display: block !important; } }
        @media (min-width: <?php echo intval( $tablet_min_bp ); ?>px) and (max-width: <?php echo intval( $tablet_bp ); ?>px) { .vb-hide-on-tablet { display: none !important; } .vb-tablet-only { display: block !important; } }
        @media (min-width: <?php echo intval( $desktop_min_bp ); ?>px) { .vb-hide-on-desktop { display: none !important; } .vb-desktop-only { display: block !important; } }
        <?php else: ?>
        .vb-desktop-only, .vb-tablet-only, .vb-mobile-only, .vb-hide-on-desktop, .vb-hide-on-tablet, .vb-hide-on-mobile { position: relative; outline: 2px dashed #0073aa; outline-offset: 2px; }
        .vb-desktop-only::before, .vb-tablet-only::before, .vb-mobile-only::before, .vb-hide-on-desktop::before, .vb-hide-on-tablet::before, .vb-hide-on-mobile::before { content: attr(data-visibloc-label); position: absolute; bottom: -2px; right: -2px; background-color: #0073aa; color: white; padding: 2px 8px; font-size: 11px; font-family: sans-serif; font-weight: bold; z-index: 99; border-radius: 3px 0 3px 0; }
        .vb-hide-on-mobile::before { content: <?php echo wp_json_encode( __( 'Caché sur Mobile', 'visi-bloc-jlg' ) ); ?>; }
        .vb-hide-on-tablet::before { content: <?php echo wp_json_encode( __( 'Caché sur Tablette', 'visi-bloc-jlg' ) ); ?>; }
        .vb-hide-on-desktop::before { content: <?php echo wp_json_encode( __( 'Caché sur Desktop', 'visi-bloc-jlg' ) ); ?>; }
        .vb-mobile-only::before { content: <?php echo wp_json_encode( __( 'Visible sur Mobile Uniquement', 'visi-bloc-jlg' ) ); ?>; }
        .vb-tablet-only::before { content: <?php echo wp_json_encode( __( 'Visible sur Tablette Uniquement', 'visi-bloc-jlg' ) ); ?>; }
        .vb-desktop-only::before { content: <?php echo wp_json_encode( __( 'Visible sur Desktop Uniquement', 'visi-bloc-jlg' ) ); ?>; }
        <?php endif; ?>
    </style>
    <?php
}
