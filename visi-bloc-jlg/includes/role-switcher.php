<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $visibloc_jlg_real_user_id;
if ( ! isset( $visibloc_jlg_real_user_id ) ) {
    $visibloc_jlg_real_user_id = null;
}

if ( ! function_exists( 'visibloc_jlg_get_preview_role_from_cookie' ) ) {
    function visibloc_jlg_get_preview_role_from_cookie() {
        $cookie_name = 'visibloc_preview_role';

        if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
            return null;
        }

        $cookie_value = $_COOKIE[ $cookie_name ];

        if ( ! is_string( $cookie_value ) ) {
            return '';
        }

        return sanitize_key( wp_unslash( $cookie_value ) );
    }
}

function visibloc_jlg_store_real_user_id( $user_id ) {
    global $visibloc_jlg_real_user_id;

    if ( null === $user_id ) {
        $visibloc_jlg_real_user_id = null;

        return;
    }

    $visibloc_jlg_real_user_id = absint( $user_id );
}

/**
 * Determine whether the current request should ignore any preview overrides.
 *
 * Guest impersonation should remain active for front-end requests (including
 * AJAX/REST calls triggered from the public site) but must be disabled inside
 * the admin or during purely technical operations such as cron, CLI, or admin
 * initiated background calls.
 *
 * @return bool
 */
if ( ! function_exists( 'visibloc_jlg_is_admin_or_technical_request' ) ) {
    function visibloc_jlg_is_admin_or_technical_request() {
        if ( is_admin() ) {
            return true;
        }

        if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
            return true;
        }

        if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
            return true;
        }

        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            return true;
        }

        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

        $is_admin_path = function( $url ) {
            if ( empty( $url ) ) {
                return false;
            }

            $admin_url = admin_url();

            return 0 === strpos( $url, $admin_url );
        };

        if ( wp_doing_ajax() ) {
            // AJAX calls always hit admin-ajax.php but we only want to opt-out when
            // they originate from the dashboard (e.g. Gutenberg/editor requests).
            if ( false !== strpos( $request_uri, '/wp-admin/' ) ) {
                $referer = wp_get_referer();

                if ( $is_admin_path( $referer ) ) {
                    return true;
                }
            }
        }

        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            $referer = wp_get_referer();

            if ( $is_admin_path( $referer ) ) {
                return true;
            }

            $context = visibloc_jlg_get_sanitized_query_arg( 'context' );

            if ( 'edit' === $context ) {
                return true;
            }
        }

        return false;
    }
}

function visibloc_jlg_get_stored_real_user_id() {
    global $visibloc_jlg_real_user_id;

    return $visibloc_jlg_real_user_id ? absint( $visibloc_jlg_real_user_id ) : 0;
}

if ( ! function_exists( 'visibloc_jlg_get_allowed_preview_roles' ) ) {
    function visibloc_jlg_get_allowed_preview_roles() {
        $allowed_roles = get_option( 'visibloc_preview_roles', [ 'administrator' ] );

        if ( ! is_array( $allowed_roles ) ) {
            $allowed_roles = [ 'administrator' ];
        }

        $allowed_roles = array_filter( array_map( 'sanitize_key', $allowed_roles ) );

        if ( empty( $allowed_roles ) ) {
            $allowed_roles = [ 'administrator' ];
        }

        return array_values( array_unique( $allowed_roles ) );
    }
}

/**
 * Retrieve the list of roles that are allowed to impersonate another role.
 *
 * By default only administrators are permitted, but developers can extend the
 * list via the {@see 'visibloc_jlg_allowed_impersonator_roles'} filter.
 *
 * @return string[] Sanitized role slugs.
 */
function visibloc_jlg_get_allowed_impersonator_roles() {
    $default_roles = [ 'administrator' ];
    $roles         = apply_filters( 'visibloc_jlg_allowed_impersonator_roles', $default_roles );

    if ( ! is_array( $roles ) ) {
        $roles = $default_roles;
    }

    $roles = array_filter( array_map( 'sanitize_key', $roles ) );

    if ( empty( $roles ) ) {
        $roles = $default_roles;
    }

    return array_values( array_unique( $roles ) );
}

if ( ! function_exists( 'visibloc_jlg_is_user_allowed_to_preview' ) ) {
    function visibloc_jlg_is_user_allowed_to_preview( $user_id ) {
        $user_id = absint( $user_id );

        if ( ! $user_id ) {
            return false;
        }

        $user = get_userdata( $user_id );

        if ( ! $user ) {
            return false;
        }

        $allowed_roles = visibloc_jlg_get_allowed_preview_roles();

        foreach ( (array) $user->roles as $role ) {
            if ( in_array( $role, $allowed_roles, true ) ) {
                return true;
            }
        }

        return false;
    }
}

/**
 * Determine whether a user can impersonate another role on the front end.
 *
 * @param int $user_id User ID to evaluate.
 * @return bool True if the user can impersonate, false otherwise.
 */
if ( ! function_exists( 'visibloc_jlg_is_user_allowed_to_impersonate' ) ) {
    function visibloc_jlg_is_user_allowed_to_impersonate( $user_id ) {
        $user_id = absint( $user_id );

        if ( ! $user_id ) {
            return false;
        }

        $user = get_userdata( $user_id );

        if ( ! $user ) {
            return false;
        }

        $allowed_roles = visibloc_jlg_get_allowed_impersonator_roles();

        foreach ( (array) $user->roles as $role ) {
            if ( in_array( $role, $allowed_roles, true ) ) {
                return true;
            }
        }

        return false;
    }
}

if ( ! function_exists( 'visibloc_jlg_get_effective_user_id' ) ) {
    function visibloc_jlg_get_effective_user_id() {
        $stored_id = visibloc_jlg_get_stored_real_user_id();

        if ( $stored_id ) {
            return $stored_id;
        }

        return get_current_user_id();
    }
}

add_filter( 'determine_current_user', 'visibloc_jlg_maybe_impersonate_guest', 99 );
function visibloc_jlg_maybe_impersonate_guest( $user_id ) {
    if ( visibloc_jlg_is_admin_or_technical_request() ) {
        visibloc_jlg_store_real_user_id( null );

        return $user_id;
    }

    $preview_role = visibloc_jlg_get_preview_role_from_cookie();

    if ( ! $preview_role ) {
        visibloc_jlg_store_real_user_id( null );

        return $user_id;
    }

    $stored_real_user_id = visibloc_jlg_get_stored_real_user_id();
    $real_user_id        = $user_id ? absint( $user_id ) : $stored_real_user_id;

    if ( $real_user_id ) {
        visibloc_jlg_store_real_user_id( $real_user_id );
    } else {
        visibloc_jlg_store_real_user_id( null );
    }

    if ( ! visibloc_jlg_is_user_allowed_to_preview( $real_user_id ) ) {
        visibloc_jlg_purge_preview_cookie();
        visibloc_jlg_store_real_user_id( null );

        return $user_id;
    }

    if ( 'guest' !== $preview_role && ! visibloc_jlg_is_user_allowed_to_impersonate( $real_user_id ) ) {
        visibloc_jlg_purge_preview_cookie();

        return $user_id;
    }

    if ( 'guest' === $preview_role ) {
        return 0;
    }

    return $user_id;
}

function visibloc_jlg_get_preview_cookie_lifetime() {
    $default_lifetime = HOUR_IN_SECONDS;
    $lifetime         = apply_filters( 'visibloc_jlg_preview_cookie_lifetime', $default_lifetime );

    if ( ! is_numeric( $lifetime ) ) {
        $lifetime = $default_lifetime;
    }

    $lifetime = (int) $lifetime;

    if ( $lifetime < 0 ) {
        $lifetime = 0;
    }

    return $lifetime;
}

function visibloc_jlg_get_preview_cookie_expiration_time( $reference_time = null ) {
    $reference_time = null === $reference_time ? time() : (int) $reference_time;

    $lifetime = visibloc_jlg_get_preview_cookie_lifetime();

    if ( $lifetime <= 0 ) {
        return 0;
    }

    return $reference_time + $lifetime;
}

function visibloc_jlg_get_expired_preview_cookie_time() {
    $lifetime = visibloc_jlg_get_preview_cookie_lifetime();

    if ( $lifetime <= 0 ) {
        $lifetime = HOUR_IN_SECONDS;
    }

    return time() - max( 1, $lifetime );
}

function visibloc_jlg_set_preview_cookie( $value, $expires ) {
    $cookie_name = 'visibloc_preview_role';
    $cookie_args = [
        'expires'  => (int) $expires,
        'path'     => COOKIEPATH,
        'domain'   => COOKIE_DOMAIN,
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];

    if ( PHP_VERSION_ID < 70300 ) {
        $cookie_domain = empty( $cookie_args['domain'] ) ? '' : $cookie_args['domain'];
        $result        = setcookie(
            $cookie_name,
            $value,
            $cookie_args['expires'],
            $cookie_args['path'],
            $cookie_domain,
            $cookie_args['secure'],
            $cookie_args['httponly']
        );

        if ( ! headers_sent() ) {
            $cookie_segments = [];
            $cookie_segments[] = $cookie_name . '=' . rawurlencode( $value );

            if ( $cookie_args['expires'] ) {
                $cookie_segments[] = 'Expires=' . gmdate( 'D, d-M-Y H:i:s T', $cookie_args['expires'] );
            }

            if ( $cookie_args['path'] ) {
                $cookie_segments[] = 'Path=' . $cookie_args['path'];
            }

            if ( ! empty( $cookie_domain ) ) {
                $cookie_segments[] = 'Domain=' . $cookie_domain;
            }

            if ( $cookie_args['secure'] ) {
                $cookie_segments[] = 'Secure';
            }

            if ( $cookie_args['httponly'] ) {
                $cookie_segments[] = 'HttpOnly';
            }

            $cookie_segments[] = 'SameSite=' . $cookie_args['samesite'];

            header( 'Set-Cookie: ' . implode( '; ', $cookie_segments ), false );
        }

        return $result;
    }

    return setcookie( $cookie_name, $value, $cookie_args );
}

/**
 * Build the absolute URL for the current request.
 *
 * @return string
 */
function visibloc_jlg_get_current_request_url() {
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

    if ( '' === $request_uri ) {
        return esc_url_raw( home_url( '/' ) );
    }

    if ( preg_match( '#^https?://#i', $request_uri ) ) {
        return esc_url_raw( $request_uri );
    }

    $first_char = $request_uri[0];

    if ( '?' === $first_char ) {
        $request_uri = '/' . $request_uri;
    } elseif ( '/' !== $first_char ) {
        $request_uri = '/' . ltrim( $request_uri, '/' );
    }

    $scheme = is_ssl() ? 'https' : 'http';

    $home_url   = home_url();
    $home_parts = wp_parse_url( $home_url );

    if ( isset( $home_parts['scheme'] ) ) {
        $scheme = $home_parts['scheme'];
    }

    $host = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : '';

    if ( '' === $host && isset( $home_parts['host'] ) ) {
        $host = $home_parts['host'];

        if ( isset( $home_parts['port'] ) ) {
            $host .= ':' . $home_parts['port'];
        }
    }

    if ( '' === $host ) {
        return esc_url_raw( $request_uri );
    }

    $url = $scheme . '://' . $host . $request_uri;

    return esc_url_raw( $url );
}

/**
 * Retrieve the base URL for the role preview switcher links.
 *
 * @return string Absolute URL without the preview query arguments.
 */
function visibloc_jlg_get_preview_switch_base_url() {
    $current_url = visibloc_jlg_get_current_request_url();
    $base_url    = remove_query_arg( [ 'preview_role', 'stop_preview_role', '_wpnonce', 'preview_status' ], $current_url );

    if ( ! $base_url ) {
        $base_url = home_url( '/' );
    }

    return esc_url_raw( $base_url );
}

function visibloc_jlg_purge_preview_cookie() {
    static $purged = false;

    if ( $purged ) {
        return;
    }

    $purged = true;

    visibloc_jlg_set_preview_cookie( '', visibloc_jlg_get_expired_preview_cookie_time() );

    if ( isset( $_COOKIE['visibloc_preview_role'] ) ) {
        unset( $_COOKIE['visibloc_preview_role'] );
    }

    if ( function_exists( 'visibloc_jlg_get_preview_runtime_context' ) ) {
        visibloc_jlg_get_preview_runtime_context( true );
    }
}

/**
 * Retrieve the preview context for a given user.
 *
 * @param int $user_id User ID.
 * @return array{
 *     user_id:int,
 *     can_impersonate:bool,
 *     can_preview:bool,
 *     previewable_roles:string[],
 * }
 */
function visibloc_jlg_get_user_preview_context( $user_id ) {
    $user_id = absint( $user_id );

    $context = [
        'user_id'           => $user_id,
        'can_impersonate'   => false,
        'can_preview'       => false,
        'previewable_roles' => [],
    ];

    if ( ! $user_id ) {
        return $context;
    }

    $context['can_impersonate'] = visibloc_jlg_is_user_allowed_to_impersonate( $user_id );
    $context['can_preview']     = visibloc_jlg_is_user_allowed_to_preview( $user_id );

    if ( $context['can_impersonate'] ) {
        if ( ! function_exists( 'get_editable_roles' ) ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        $context['previewable_roles'] = array_keys( get_editable_roles() );
    }

    if ( $context['can_preview'] && ! in_array( 'guest', $context['previewable_roles'], true ) ) {
        $context['previewable_roles'][] = 'guest';
    }

    return $context;
}

/**
 * Retrieve the runtime preview context used across front-end rendering.
 *
 * @return array{
 *     can_impersonate:bool,
 *     can_preview_hidden_blocks:bool,
 *     had_preview_permission:bool,
 *     is_preview_role_neutralized:bool,
 *     preview_role:string,
 *     should_apply_preview_role:bool,
 * }
 */
if ( ! function_exists( 'visibloc_jlg_get_preview_runtime_context' ) ) {
    function visibloc_jlg_get_preview_runtime_context( $reset_cache = false ) {
        static $cached_context = null;

        if ( $reset_cache ) {
            $cached_context = null;
        }

        if ( null !== $cached_context ) {
            return $cached_context;
        }

        $is_preview_role_neutralized = visibloc_jlg_is_admin_or_technical_request();
        $effective_user_id           = visibloc_jlg_get_effective_user_id();

        $can_impersonate           = $effective_user_id ? visibloc_jlg_is_user_allowed_to_impersonate( $effective_user_id ) : false;
        $can_preview_hidden_blocks = $effective_user_id ? visibloc_jlg_is_user_allowed_to_preview( $effective_user_id ) : false;
        $had_preview_permission    = $can_preview_hidden_blocks;

        $allowed_preview_roles = visibloc_jlg_get_allowed_preview_roles();

        $preview_role = '';

        if ( ! $is_preview_role_neutralized ) {
            $raw_preview_role = visibloc_jlg_get_preview_role_from_cookie();

            if ( is_string( $raw_preview_role ) ) {
                $preview_role = $raw_preview_role;
            }
        }

        if ( $can_preview_hidden_blocks && $is_preview_role_neutralized ) {
            $can_preview_hidden_blocks = false;
        }

        $should_apply_preview_role = false;

        if ( '' !== $preview_role ) {
            if ( 'guest' === $preview_role ) {
                $can_preview_hidden_blocks = false;
                $should_apply_preview_role = ( $had_preview_permission || $can_impersonate );
            } else {
                if ( ! in_array( $preview_role, $allowed_preview_roles, true ) ) {
                    $can_preview_hidden_blocks = false;
                }

                if ( ! $can_impersonate || ! get_role( $preview_role ) ) {
                    $preview_role = '';
                } else {
                    $should_apply_preview_role = true;
                }
            }
        }

        if ( '' === $preview_role ) {
            $should_apply_preview_role = false;
        }

        $cached_context = [
            'can_impersonate'            => $can_impersonate,
            'can_preview_hidden_blocks'  => $can_preview_hidden_blocks,
            'had_preview_permission'     => $had_preview_permission,
            'is_preview_role_neutralized'=> $is_preview_role_neutralized,
            'preview_role'               => $preview_role,
            'should_apply_preview_role'  => $should_apply_preview_role,
        ];

        return $cached_context;
    }
}

add_action( 'init', 'visibloc_jlg_handle_role_switching' );
function visibloc_jlg_handle_role_switching() {
    $context = visibloc_jlg_get_user_preview_context( visibloc_jlg_get_effective_user_id() );

    if ( ! $context['can_impersonate'] && ! $context['can_preview'] ) {
        return;
    }

    $previewable_roles = $context['previewable_roles'];

    if ( empty( $previewable_roles ) ) {
        return;
    }

    $has_preview_role_param = isset( $_GET['preview_role'] );
    $has_stop_request       = isset( $_GET['stop_preview_role'] );

    if ( ! $has_preview_role_param && ! $has_stop_request ) {
        $current_preview_role = visibloc_jlg_get_preview_role_from_cookie();

        if ( is_string( $current_preview_role ) && '' !== $current_preview_role ) {
            $should_refresh_cookie = false;

            if ( 'guest' === $current_preview_role ) {
                $should_refresh_cookie = $context['can_preview'];
            } elseif ( $context['can_impersonate'] && in_array( $current_preview_role, $previewable_roles, true ) ) {
                $should_refresh_cookie = (bool) get_role( $current_preview_role );
            }

            if ( $should_refresh_cookie ) {
                $expiration = visibloc_jlg_get_preview_cookie_expiration_time();
                visibloc_jlg_set_preview_cookie( $current_preview_role, $expiration );
            }
        }
    }

    if ( $has_preview_role_param ) {
        $role_to_preview = visibloc_jlg_get_sanitized_query_arg( 'preview_role' );

        if ( '' === $role_to_preview ) {
            return;
        }

        $nonce = isset( $_GET['_wpnonce'] ) ? wp_unslash( $_GET['_wpnonce'] ) : '';
        if ( ! is_string( $nonce ) || '' === $nonce || ! wp_verify_nonce( $nonce, 'visibloc_switch_role_' . $role_to_preview ) ) {
            return;
        }

        if ( ! in_array( $role_to_preview, $previewable_roles, true ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                trigger_error( sprintf( 'Visibloc role switcher: invalid preview role requested (%s).', $role_to_preview ), E_USER_NOTICE );
            }

            visibloc_jlg_purge_preview_cookie();

            $redirect_target = add_query_arg(
                'preview_status',
                'invalid_role',
                visibloc_jlg_get_preview_switch_base_url()
            );

            wp_safe_redirect( $redirect_target );
            exit;
        }

        if ( 'guest' === $role_to_preview && ! $context['can_preview'] ) {
            return;
        }

        if ( 'guest' !== $role_to_preview && ! $context['can_impersonate'] ) {
            return;
        }

        $expiration = visibloc_jlg_get_preview_cookie_expiration_time();
        visibloc_jlg_set_preview_cookie( $role_to_preview, $expiration );

        $redirect_target = visibloc_jlg_get_preview_switch_base_url();

        wp_safe_redirect( $redirect_target );
        exit;
    }
    if ( $has_stop_request ) {
        $nonce = isset( $_GET['_wpnonce'] ) ? wp_unslash( $_GET['_wpnonce'] ) : '';
        if ( ! is_string( $nonce ) || '' === $nonce || ! wp_verify_nonce( $nonce, 'visibloc_switch_role_stop' ) ) {
            return;
        }
        visibloc_jlg_purge_preview_cookie();

        $redirect_target = visibloc_jlg_get_preview_switch_base_url();

        wp_safe_redirect( $redirect_target );
        exit;
    }
}

add_filter( 'show_admin_bar', 'visibloc_jlg_force_admin_bar_during_guest_preview', 20 );
function visibloc_jlg_force_admin_bar_during_guest_preview( $show ) {
    if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return $show;
    }

    $current_preview_role = visibloc_jlg_get_preview_role_from_cookie();
    $real_user_id         = visibloc_jlg_get_stored_real_user_id();

    if ( $current_preview_role === 'guest' && $real_user_id ) {
        return true;
    }

    return $show;
}

add_action( 'admin_bar_menu', 'visibloc_jlg_add_role_switcher_menu', 999 );
function visibloc_jlg_add_role_switcher_menu( $wp_admin_bar ) {
    $user_id              = visibloc_jlg_get_effective_user_id();
    $current_preview_role = visibloc_jlg_get_preview_role_from_cookie();
    $preview_status       = visibloc_jlg_get_sanitized_query_arg( 'preview_status' );
    $force_admin_bar      = ( $current_preview_role === 'guest' );

    if ( ! $user_id ) {
        return;
    }

    if ( ! $force_admin_bar && ! is_admin_bar_showing() ) {
        return;
    }

    $context = visibloc_jlg_get_user_preview_context( $user_id );

    if ( ! $context['can_impersonate'] && ! $context['can_preview'] ) {
        return;
    }

    $previewable_roles = $context['previewable_roles'];

    if ( empty( $previewable_roles ) ) {
        return;
    }

    $can_impersonate = $context['can_impersonate'];
    $can_preview     = $context['can_preview'];

    $editable_roles = [];

    if ( $can_impersonate ) {
        if ( ! function_exists( 'get_editable_roles' ) ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        $editable_roles = get_editable_roles();
    }

    $base_url = visibloc_jlg_get_preview_switch_base_url();

    if ( 'invalid_role' === $preview_status ) {
        $wp_admin_bar->add_node([
            'id'    => 'visibloc-preview-error',
            'title' => esc_html__( 'Le rôle demandé n’est pas disponible pour l’aperçu.', 'visi-bloc-jlg' ),
            'href'  => '#',
            'meta'  => [ 'style' => 'background-color: #d63638 !important;' ],
        ]);
    }

    if ( $current_preview_role ) {
        $role_names   = wp_roles()->get_names();
        $display_name = 'guest' === $current_preview_role
            ? __( 'Visiteur (Déconnecté)', 'visi-bloc-jlg' )
            : ( $role_names[ $current_preview_role ] ?? ucfirst( $current_preview_role ) );

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

    if ( $can_preview ) {
        $guest_preview_url = add_query_arg( 'preview_role', 'guest', $base_url );
        $guest_preview_url = wp_nonce_url( $guest_preview_url, 'visibloc_switch_role_guest' );

        $wp_admin_bar->add_node([
            'id'     => 'visibloc-role-guest',
            'title'  => esc_html__( 'Visiteur (Déconnecté)', 'visi-bloc-jlg' ),
            'href'   => $guest_preview_url,
            'parent' => 'visibloc-role-switcher',
        ]);
    }

    if ( $can_impersonate ) {
        foreach ( $editable_roles as $slug => $details ) {
            if ( ! in_array( $slug, $previewable_roles, true ) ) {
                continue;
            }

            $preview_url = add_query_arg( 'preview_role', $slug, $base_url );
            $preview_url = wp_nonce_url( $preview_url, 'visibloc_switch_role_' . $slug );

            $wp_admin_bar->add_node([
                'id'     => 'visibloc-role-' . $slug,
                'title'  => esc_html( isset( $details['name'] ) ? $details['name'] : $slug ),
                'href'   => $preview_url,
                'parent' => 'visibloc-role-switcher',
            ]);
        }
    }
}

add_filter( 'user_has_cap', 'visibloc_jlg_filter_user_capabilities', 999, 4 );
function visibloc_jlg_filter_user_capabilities( $allcaps, $caps, $args, $user ) {
    if ( visibloc_jlg_is_admin_or_technical_request() ) {
        return $allcaps;
    }

    $preview_role = visibloc_jlg_get_preview_role_from_cookie();

    if ( ! $preview_role || ! is_object( $user ) ) {
        return $allcaps;
    }

    $real_user_id = visibloc_jlg_get_stored_real_user_id();

    if ( ! $real_user_id ) {
        $real_user_id = get_current_user_id();
        if ( $real_user_id ) {
            visibloc_jlg_store_real_user_id( $real_user_id );
        }
    }

    if ( ! visibloc_jlg_is_user_allowed_to_preview( $real_user_id ) ) {
        visibloc_jlg_purge_preview_cookie();
        visibloc_jlg_store_real_user_id( null );

        return $allcaps;
    }

    if ( 'guest' !== $preview_role && $user->ID !== visibloc_jlg_get_effective_user_id() ) {
        return $allcaps;
    }

    if ( 'guest' === $preview_role ) {
        $current_user_id = get_current_user_id();

        if ( (int) $user->ID !== (int) $current_user_id ) {
            return $allcaps;
        }

        $role_object = get_role( 'guest' );
        $guest_caps  = $role_object ? $role_object->capabilities : [];

        $guest_caps['exist']   = true;
        $guest_caps['read']    = true;
        $guest_caps['level_0'] = true;

        if ( isset( $allcaps['do_not_allow'] ) ) {
            $guest_caps['do_not_allow'] = $allcaps['do_not_allow'];
        }

        return $guest_caps;
    }

    // Ignore non-guest previews for users that are not allowed to impersonate roles.
    if ( ! visibloc_jlg_is_user_allowed_to_impersonate( $real_user_id ) ) {
        visibloc_jlg_purge_preview_cookie();

        return $allcaps;
    }

    $role_object = get_role( $preview_role );

    if ( $role_object ) {
        $caps_for_role = $role_object->capabilities;

        if ( isset( $allcaps['do_not_allow'] ) ) {
            $caps_for_role['do_not_allow'] = $allcaps['do_not_allow'];
        }

        return $caps_for_role;
    }

    return $allcaps;
}
