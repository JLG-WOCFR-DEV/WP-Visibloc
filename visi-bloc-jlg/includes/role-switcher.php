<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'visibloc_jlg_handle_role_switching' );
function visibloc_jlg_handle_role_switching() {
    $user_id = get_current_user_id();
    if( ! $user_id ) return;
    $real_user = get_userdata( $user_id );
    if ( ! $real_user || ! in_array( 'administrator', (array) $real_user->roles ) ) { return; }
    if ( ! function_exists( 'get_editable_roles' ) ) { require_once ABSPATH . 'wp-admin/includes/user.php'; }
    $previewable_roles = array_keys( get_editable_roles() );
    if ( ! in_array( 'guest', $previewable_roles, true ) ) {
        $previewable_roles[] = 'guest';
    }
    $cookie_name = 'visibloc_preview_role';
    if ( isset( $_GET['preview_role'] ) ) {
        $role_to_preview = sanitize_key( wp_unslash( $_GET['preview_role'] ) );
        if ( ! in_array( $role_to_preview, $previewable_roles, true ) ) {
            error_log( sprintf( 'Visibloc role switcher: invalid preview role requested (%s).', $role_to_preview ) );
            return;
        }
        $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
        if ( ! $role_to_preview || ! $nonce || ! wp_verify_nonce( $nonce, 'visibloc_switch_role_' . $role_to_preview ) ) {
            return;
        }
        setcookie( $cookie_name, $role_to_preview, [
            'expires'  => time() + 3600,
            'path'     => COOKIEPATH,
            'domain'   => COOKIE_DOMAIN,
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ] );
        wp_safe_redirect( remove_query_arg( [ 'preview_role', '_wpnonce' ] ) );
        exit;
    }
    if ( isset( $_GET['stop_preview_role'] ) ) {
        $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'visibloc_switch_role_stop' ) ) {
            return;
        }
        setcookie( $cookie_name, '', [
            'expires'  => time() - 3600,
            'path'     => COOKIEPATH,
            'domain'   => COOKIE_DOMAIN,
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ] );
        wp_safe_redirect( remove_query_arg( [ 'stop_preview_role', '_wpnonce' ] ) );
        exit;
    }
}

add_filter( 'show_admin_bar', 'visibloc_jlg_force_admin_bar_during_guest_preview', 20 );
function visibloc_jlg_force_admin_bar_during_guest_preview( $show ) {
    if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return $show;
    }

    $cookie_name = 'visibloc_preview_role';
    $current_preview_role = isset( $_COOKIE[ $cookie_name ] ) ? sanitize_key( wp_unslash( $_COOKIE[ $cookie_name ] ) ) : null;

    if ( $current_preview_role === 'guest' && get_current_user_id() ) {
        return true;
    }

    return $show;
}

add_action( 'admin_bar_menu', 'visibloc_jlg_add_role_switcher_menu', 999 );
function visibloc_jlg_add_role_switcher_menu( $wp_admin_bar ) {
    $user_id = get_current_user_id();
    $cookie_name = 'visibloc_preview_role';
    $current_preview_role = isset( $_COOKIE[$cookie_name] ) ? sanitize_key( wp_unslash( $_COOKIE[$cookie_name] ) ) : null;
    $force_admin_bar = ( $current_preview_role === 'guest' );

    if ( ! $user_id ) { return; }

    $should_display_admin_bar = is_admin_bar_showing() || $force_admin_bar;
    if ( ! $should_display_admin_bar ) { return; }
    $real_user = get_userdata( $user_id );
    if ( ! $real_user || ! in_array( 'administrator', (array) $real_user->roles ) ) { return; }
    if ( ! function_exists( 'get_editable_roles' ) ) { require_once ABSPATH . 'wp-admin/includes/user.php'; }
    
    $base_url = remove_query_arg( [ 'preview_role', 'stop_preview_role', '_wpnonce' ] );
    if ( $current_preview_role ) {
        $role_names = wp_roles()->get_names();
        $display_name = $current_preview_role === 'guest' ? __( 'Visiteur (Déconnecté)', 'visi-bloc-jlg' ) : ( $role_names[ $current_preview_role ] ?? ucfirst( $current_preview_role ) );
        $wp_admin_bar->add_node([
            'id'    => 'visibloc-alert',
            'title' => sprintf(
                /* translators: %s: role name used for preview. */
                esc_html__( '⚠️ Aperçu : %s', 'visi-bloc-jlg' ),
                esc_html( $display_name )
            ),
            'href'  => '#',
            'meta'  => [ 'style' => 'background-color: #d54e21 !important;' ],
        ]);
        $stop_preview_url = add_query_arg( 'stop_preview_role', 'true', $base_url );
        $stop_preview_url = wp_nonce_url( $stop_preview_url, 'visibloc_switch_role_stop' );
        $wp_admin_bar->add_node([
            'id'     => 'visibloc-stop-preview',
            'title'  => esc_html__( '✅ Retour à ma vue', 'visi-bloc-jlg' ),
            'href'   => $stop_preview_url,
            'parent' => 'top-secondary',
        ]);
    }
    $wp_admin_bar->add_node([
        'id'    => 'visibloc-role-switcher',
        'title' => sprintf(
            '<span class="ab-icon dashicons-groups"></span>%s',
            esc_html__( 'Aperçu en tant que', 'visi-bloc-jlg' )
        ),
        'href'  => '#',
    ]);
    $guest_preview_url = add_query_arg( 'preview_role', 'guest', $base_url );
    $guest_preview_url = wp_nonce_url( $guest_preview_url, 'visibloc_switch_role_guest' );
    $wp_admin_bar->add_node([
        'id'     => 'visibloc-role-guest',
        'title'  => esc_html__( 'Visiteur (Déconnecté)', 'visi-bloc-jlg' ),
        'href'   => $guest_preview_url,
        'parent' => 'visibloc-role-switcher',
    ]);
    foreach ( get_editable_roles() as $slug => $details ) {
        $preview_url = add_query_arg( 'preview_role', $slug, $base_url );
        $preview_url = wp_nonce_url( $preview_url, 'visibloc_switch_role_' . $slug );
        $wp_admin_bar->add_node(['id' => 'visibloc-role-' . $slug, 'title' => $details['name'], 'href' => $preview_url, 'parent' => 'visibloc-role-switcher']);
    }
}

add_filter( 'user_has_cap', 'visibloc_jlg_filter_user_capabilities', 999, 4 );
function visibloc_jlg_filter_user_capabilities( $allcaps, $caps, $args, $user ) {
    if ( is_admin() || wp_doing_ajax() || ( defined('REST_REQUEST') && REST_REQUEST ) ) {
        return $allcaps;
    }
    $cookie_name = 'visibloc_preview_role';
    if ( isset( $_COOKIE[$cookie_name] ) && is_object( $user ) && $user->ID === get_current_user_id() ) {
        $preview_role = sanitize_key( wp_unslash( $_COOKIE[$cookie_name] ) );
        if ( $preview_role === 'guest' ) {
            $guest_caps = [
                'exist'   => true,
                'read'    => true,
                'level_0' => true,
            ];

            if ( isset( $allcaps['do_not_allow'] ) ) {
                $guest_caps['do_not_allow'] = $allcaps['do_not_allow'];
            }

            return $guest_caps;
        }
        $role_object = get_role( $preview_role );
        if ( $role_object ) { return $role_object->capabilities; }
    }
    return $allcaps;
}
