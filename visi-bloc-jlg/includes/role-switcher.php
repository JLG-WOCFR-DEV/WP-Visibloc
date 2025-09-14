<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'visibloc_jlg_handle_role_switching' );
function visibloc_jlg_handle_role_switching() {
    $user_id = get_current_user_id();
    if( ! $user_id ) return;
    $real_user = get_userdata( $user_id );
    if ( ! $real_user || ! in_array( 'administrator', (array) $real_user->roles ) ) { return; }
    $cookie_name = 'visibloc_preview_role';
    if ( isset( $_GET['preview_role'] ) ) {
        $role_to_preview = sanitize_key( $_GET['preview_role'] );
        setcookie( $cookie_name, $role_to_preview, time() + 3600, COOKIEPATH, COOKIE_DOMAIN );
        wp_redirect( remove_query_arg( 'preview_role' ) );
        exit;
    }
    if ( isset( $_GET['stop_preview_role'] ) ) {
        setcookie( $cookie_name, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
        wp_redirect( remove_query_arg( 'stop_preview_role' ) );
        exit;
    }
}

add_action( 'admin_bar_menu', 'visibloc_jlg_add_role_switcher_menu', 999 );
function visibloc_jlg_add_role_switcher_menu( $wp_admin_bar ) {
    $user_id = get_current_user_id();
    if ( ! $user_id || ! is_admin_bar_showing() ) { return; }
    $real_user = get_userdata( $user_id );
    if ( ! $real_user || ! in_array( 'administrator', (array) $real_user->roles ) ) { return; }
    if ( ! function_exists( 'get_editable_roles' ) ) { require_once ABSPATH . 'wp-admin/includes/user.php'; }
    $cookie_name = 'visibloc_preview_role';
    $current_preview_role = isset( $_COOKIE[$cookie_name] ) ? sanitize_key( $_COOKIE[$cookie_name] ) : null;
    if ( $current_preview_role ) {
        $role_names = wp_roles()->get_names();
        $display_name = $current_preview_role === 'guest' ? 'Visiteur (Déconnecté)' : ($role_names[$current_preview_role] ?? ucfirst($current_preview_role));
        $wp_admin_bar->add_node(['id' => 'visibloc-alert', 'title' => '⚠️ Aperçu : ' . esc_html( $display_name ), 'href' => '#', 'meta' => ['style' => 'background-color: #d54e21 !important;']]);
        $wp_admin_bar->add_node(['id' => 'visibloc-stop-preview', 'title' => '✅ Retour à ma vue', 'href' => add_query_arg( 'stop_preview_role', 'true' ), 'parent' => 'top-secondary']);
    }
    $wp_admin_bar->add_node(['id' => 'visibloc-role-switcher', 'title' => '<span class="ab-icon dashicons-groups"></span>Aperçu en tant que', 'href' => '#']);
    $wp_admin_bar->add_node(['id' => 'visibloc-role-guest', 'title' => 'Visiteur (Déconnecté)', 'href' => add_query_arg( 'preview_role', 'guest' ), 'parent' => 'visibloc-role-switcher']);
    foreach ( get_editable_roles() as $slug => $details ) {
        $wp_admin_bar->add_node(['id' => 'visibloc-role-' . $slug, 'title' => $details['name'], 'href' => add_query_arg( 'preview_role', $slug ), 'parent' => 'visibloc-role-switcher']);
    }
}

add_filter( 'user_has_cap', 'visibloc_jlg_filter_user_capabilities', 999, 4 );
function visibloc_jlg_filter_user_capabilities( $allcaps, $caps, $args, $user ) {
    if ( is_admin() || wp_doing_ajax() || ( defined('REST_REQUEST') && REST_REQUEST ) ) {
        return $allcaps;
    }
    $cookie_name = 'visibloc_preview_role';
    if ( isset( $_COOKIE[$cookie_name] ) && is_object($user) && $user->ID === get_current_user_id() ) {
        $preview_role = sanitize_key( $_COOKIE[$cookie_name] );
        if ( $preview_role === 'guest' ) { return []; }
        $role_object = get_role( $preview_role );
        if ( $role_object ) { return $role_object->capabilities; }
    }
    return $allcaps;
}
