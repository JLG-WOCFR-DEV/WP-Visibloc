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
    static $cached_can_preview = null;

    if ( null !== $cached_can_preview ) {
        return $cached_can_preview;
    }

    if ( function_exists( 'visibloc_jlg_get_preview_runtime_context' ) ) {
        $runtime_context = visibloc_jlg_get_preview_runtime_context();
        $cached_can_preview = ! empty( $runtime_context['can_preview_hidden_blocks'] );

        return $cached_can_preview;
    }

    $cached_can_preview = false;

    return $cached_can_preview;
}
