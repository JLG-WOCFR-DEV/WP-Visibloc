<?php
/**
 * Plugin Name:       Visi-Bloc - JLG
 * Description:       Ajoute des options avancées pour cacher/afficher des blocs sur le site public.
 * Version:           1.1
 * Author:            Jérôme Le Gousse
 * Text Domain:       visi-bloc-jlg
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) { exit; }

if ( ! function_exists( 'visibloc_jlg_get_sanitized_query_arg' ) ) {
    /**
     * Retrieve a sanitized value from the $_GET superglobal using sanitize_key.
     *
     * Only string values are accepted to avoid unexpected sanitization results
     * or PHP notices when arrays/objects are provided.
     *
     * @param string $key Query arg key to read from $_GET.
     * @return string Sanitized string or an empty string when unavailable/invalid.
     */
    function visibloc_jlg_get_sanitized_query_arg( $key ) {
        if ( ! isset( $_GET[ $key ] ) ) {
            return '';
        }

        $value = $_GET[ $key ];

        if ( ! is_string( $value ) ) {
            return '';
        }

        return sanitize_key( wp_unslash( $value ) );
    }
}

// Charge les différents modules du plugin
require_once __DIR__ . '/includes/datetime-utils.php';
require_once __DIR__ . '/includes/admin-settings.php';
require_once __DIR__ . '/includes/assets.php';
require_once __DIR__ . '/includes/visibility-logic.php';
require_once __DIR__ . '/includes/role-switcher.php';

/**
 * Initialise la localisation du plugin.
 */
function visi_bloc_jlg_load_textdomain() {
    load_plugin_textdomain( 'visi-bloc-jlg', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'visi_bloc_jlg_load_textdomain' );

/**
 * Vérifie si l'utilisateur actuellement connecté a un rôle autorisé à voir les aperçus.
 *
 * @return bool True si l'utilisateur peut voir les aperçus, sinon false.
 */
function visibloc_jlg_can_user_preview() {
    if ( function_exists( 'visibloc_jlg_get_allowed_preview_roles' ) ) {
        $allowed_roles = visibloc_jlg_get_allowed_preview_roles();
    } else {
        $allowed_roles = (array) get_option( 'visibloc_preview_roles', [ 'administrator' ] );
        $allowed_roles = array_map( 'sanitize_key', $allowed_roles );

        if ( empty( $allowed_roles ) ) {
            $allowed_roles = [ 'administrator' ];
        }
    }

    if ( function_exists( 'visibloc_jlg_get_preview_role_from_cookie' ) ) {
        $preview_role_cookie = visibloc_jlg_get_preview_role_from_cookie();
    } else {
        if ( isset( $_COOKIE['visibloc_preview_role'] ) && is_string( $_COOKIE['visibloc_preview_role'] ) ) {
            $preview_role_cookie = sanitize_key( wp_unslash( $_COOKIE['visibloc_preview_role'] ) );
        } else {
            $preview_role_cookie = '';
        }
    }

    if ( 'guest' === $preview_role_cookie ) {
        return false;
    }

    if ( $preview_role_cookie && 'guest' !== $preview_role_cookie && ! in_array( $preview_role_cookie, $allowed_roles, true ) ) {
        return false;
    }

    if ( function_exists( 'visibloc_jlg_get_effective_user_id' ) ) {
        $user_id = visibloc_jlg_get_effective_user_id();
    } elseif ( function_exists( 'visibloc_jlg_get_stored_real_user_id' ) ) {
        $user_id = visibloc_jlg_get_stored_real_user_id();

        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
    } else {
        $user_id = get_current_user_id();
    }

    $user_id = absint( $user_id );

    if ( ! $user_id ) {
        return false;
    }

    $user = get_userdata( $user_id );

    if ( ! $user ) {
        return false;
    }

    if ( count( array_intersect( (array) $user->roles, $allowed_roles ) ) > 0 ) {
        return true;
    }

    return false;
}
