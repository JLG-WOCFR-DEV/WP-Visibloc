<?php
/**
 * Plugin Name:       Visi-Bloc - JLG
 * Description:       Ajoute des options avancées pour cacher/afficher des blocs sur le site public.
 * Version:           1.1
 * Author:            Jérôme Le Gousse
 * Text Domain:       visi-bloc-jlg
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
    exit;
}

if ( ! defined( 'VISIBLOC_JLG_PLUGIN_FILE' ) ) {
    define( 'VISIBLOC_JLG_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'VISIBLOC_JLG_PLUGIN_DIR' ) ) {
    if ( function_exists( 'plugin_dir_path' ) ) {
        define( 'VISIBLOC_JLG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
    } else {
        define( 'VISIBLOC_JLG_PLUGIN_DIR', dirname( __FILE__ ) . '/' );
    }
}

if ( ! defined( 'VISIBLOC_JLG_PLUGIN_BASENAME' ) ) {
    if ( function_exists( 'plugin_basename' ) ) {
        define( 'VISIBLOC_JLG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
    } else {
        define( 'VISIBLOC_JLG_PLUGIN_BASENAME', basename( __FILE__ ) );
    }
}

if ( ! defined( 'VISIBLOC_JLG_PLUGIN_URL' ) ) {
    if ( function_exists( 'plugin_dir_url' ) ) {
        define( 'VISIBLOC_JLG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    } elseif ( function_exists( 'plugins_url' ) ) {
        define( 'VISIBLOC_JLG_PLUGIN_URL', rtrim( plugins_url( '', __FILE__ ), '/' ) . '/' );
    } else {
        define( 'VISIBLOC_JLG_PLUGIN_URL', '' );
    }
}

if ( ! defined( 'VISIBLOC_JLG_VERSION' ) ) {
    $visibloc_version = '0.0.0';

    if ( function_exists( 'get_file_data' ) ) {
        $plugin_data = get_file_data( __FILE__, [ 'Version' => 'Version' ] );

        if ( isset( $plugin_data['Version'] ) && '' !== $plugin_data['Version'] ) {
            $visibloc_version = $plugin_data['Version'];
        }
    } else {
        $plugin_contents = @file_get_contents( __FILE__ );

        if ( false !== $plugin_contents && preg_match( '/^\s*\*\s*Version:\s*(.+)$/mi', $plugin_contents, $matches ) ) {
            $visibloc_version = trim( $matches[1] );
        }
    }

    define( 'VISIBLOC_JLG_VERSION', $visibloc_version );
}

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/includes/i18n-inline.php';

use VisiBloc\Plugin;

$visibloc_jlg_plugin = new Plugin( __FILE__ );

if ( ! function_exists( 'visibloc_jlg' ) ) {
    function visibloc_jlg() {
        global $visibloc_jlg_plugin;

        return $visibloc_jlg_plugin;
    }
}

if ( ! function_exists( 'visibloc_jlg_get_sanitized_query_arg' ) ) {
    function visibloc_jlg_get_sanitized_query_arg( $key ) {
        return visibloc_jlg()->get_sanitized_query_arg( $key );
    }
}

if ( ! function_exists( 'visibloc_jlg_normalize_boolean' ) ) {
    function visibloc_jlg_normalize_boolean( $value ) {
        return visibloc_jlg()->normalize_boolean( $value );
    }
}

if ( ! function_exists( 'visibloc_jlg_can_user_preview' ) ) {
    function visibloc_jlg_can_user_preview() {
        return visibloc_jlg()->can_user_preview();
    }
}

add_action(
    'plugins_loaded',
    static function () use ( $visibloc_jlg_plugin ) {
        $visibloc_jlg_plugin->register();
    }
);
