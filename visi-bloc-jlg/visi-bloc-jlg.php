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

require_once __DIR__ . '/includes/plugin-meta.php';
require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/audit-log.php';
require_once __DIR__ . '/includes/integrations/class-visibloc-crm-sync.php';

if ( is_admin() ) {
    require_once __DIR__ . '/includes/integrations/crm-admin.php';
}

visibloc_jlg_get_plugin_version();

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

// The global helper is defined inside includes/utils.php. Keep the function
// available even if third-parties load this file directly before the utils
// helpers.
if ( ! function_exists( 'visibloc_jlg_normalize_boolean' ) ) {
    function visibloc_jlg_normalize_boolean( $value ) {
        return visibloc_jlg_normalize_boolean_value( $value );
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
        Visibloc_CRM_Sync::init();
    }
);
