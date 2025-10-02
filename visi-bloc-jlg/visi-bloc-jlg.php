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

if ( ! defined( 'VISIBLOC_JLG_VERSION' ) ) {
    $visibloc_version = '0.0.0';

    if ( function_exists( 'get_file_data' ) ) {
        $plugin_data      = get_file_data( __FILE__, [ 'Version' => 'Version' ] );
        $visibloc_version = isset( $plugin_data['Version'] ) && '' !== $plugin_data['Version']
            ? $plugin_data['Version']
            : $visibloc_version;
    } else {
        $plugin_contents = @file_get_contents( __FILE__ );

        if ( false !== $plugin_contents && preg_match( '/^\s*\*\s*Version:\s*(.+)$/mi', $plugin_contents, $matches ) ) {
            $visibloc_version = trim( $matches[1] );
        }
    }

    define( 'VISIBLOC_JLG_VERSION', $visibloc_version );
}

if ( ! defined( 'VISIBLOC_JLG_DEFAULT_SUPPORTED_BLOCKS' ) ) {
    define( 'VISIBLOC_JLG_DEFAULT_SUPPORTED_BLOCKS', [ 'core/group' ] );
}

$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( file_exists( $autoloader ) ) {
    require_once $autoloader;
}

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

if ( ! function_exists( 'visibloc_jlg_register_supported_blocks_setting' ) ) {
    function visibloc_jlg_register_supported_blocks_setting() {
        if ( ! function_exists( 'register_setting' ) ) {
            return;
        }

        register_setting(
            'visibloc',
            'visibloc_supported_blocks',
            [
                'type'              => 'array',
                'sanitize_callback' => 'visibloc_jlg_normalize_block_names',
                'default'           => [],
                'show_in_rest'      => [
                    'schema' => [
                        'type'  => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ]
        );
    }

    add_action( 'init', 'visibloc_jlg_register_supported_blocks_setting' );
}

if ( ! function_exists( 'visibloc_jlg_normalize_boolean' ) ) {
    /**
     * Convert a block attribute value to a strict boolean.
     *
     * Arrays and objects are treated as false to avoid PHP warnings triggered
     * by filter_var() while scalar/string values are normalized using
     * FILTER_VALIDATE_BOOLEAN. Invalid or empty values default to false.
     *
     * @param mixed $value Raw attribute value.
     * @return bool Normalized boolean value.
     */
    function visibloc_jlg_normalize_boolean( $value ) {
        if ( is_bool( $value ) ) {
            return $value;
        }

        if ( null === $value ) {
            return false;
        }

        if ( is_array( $value ) || is_object( $value ) ) {
            return false;
        }

        $filtered = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

        return true === $filtered;
    }
}


add_action(
    'plugins_loaded',
    static function () {
        if ( class_exists( \VisiBloc\Plugin::class ) ) {
            ( new \VisiBloc\Plugin( __DIR__ ) )->bootstrap();
        }
    }
);

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
