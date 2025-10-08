<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'visibloc_jlg_get_plugin_main_file' ) ) {
    /**
     * Retrieve the absolute path to the main plugin file.
     *
     * @return string
     */
    function visibloc_jlg_get_plugin_main_file() {
        if ( defined( 'VISIBLOC_JLG_PLUGIN_FILE' ) && '' !== VISIBLOC_JLG_PLUGIN_FILE ) {
            return VISIBLOC_JLG_PLUGIN_FILE;
        }

        return __DIR__ . '/../visi-bloc-jlg.php';
    }
}

if ( ! function_exists( 'visibloc_jlg_get_plugin_dir_path' ) ) {
    /**
     * Retrieve the absolute plugin directory path.
     *
     * @return string
     */
    function visibloc_jlg_get_plugin_dir_path() {
        if ( defined( 'VISIBLOC_JLG_PLUGIN_DIR' ) && '' !== VISIBLOC_JLG_PLUGIN_DIR ) {
            return rtrim( VISIBLOC_JLG_PLUGIN_DIR, '/\\' ) . '/';
        }

        return rtrim( dirname( visibloc_jlg_get_plugin_main_file() ), '/\\' ) . '/';
    }
}

if ( ! function_exists( 'visibloc_jlg_resolve_plugin_version' ) ) {
    /**
     * Resolve the plugin version from the file header.
     *
     * @return string
     */
    function visibloc_jlg_resolve_plugin_version() {
        static $cached_version = null;

        if ( null !== $cached_version ) {
            return $cached_version;
        }

        $cached_version   = '0.0.0';
        $plugin_main_file = visibloc_jlg_get_plugin_main_file();

        if ( function_exists( 'get_file_data' ) ) {
            $plugin_data = get_file_data( $plugin_main_file, [ 'Version' => 'Version' ] );

            if ( isset( $plugin_data['Version'] ) && '' !== $plugin_data['Version'] ) {
                $cached_version = $plugin_data['Version'];
            }

            return $cached_version;
        }

        if ( is_readable( $plugin_main_file ) ) {
            $plugin_contents = file_get_contents( $plugin_main_file );

            if ( false !== $plugin_contents && preg_match( '/^\s*\*\s*Version:\s*(.+)$/mi', $plugin_contents, $matches ) ) {
                $cached_version = trim( $matches[1] );
            }
        }

        return $cached_version;
    }
}

if ( ! function_exists( 'visibloc_jlg_define_version_constant' ) ) {
    /**
     * Ensure the global version constant exists.
     *
     * @return string The defined version.
     */
    function visibloc_jlg_define_version_constant() {
        if ( ! defined( 'VISIBLOC_JLG_VERSION' ) ) {
            define( 'VISIBLOC_JLG_VERSION', visibloc_jlg_resolve_plugin_version() );
        }

        return VISIBLOC_JLG_VERSION;
    }
}

if ( ! function_exists( 'visibloc_jlg_get_plugin_version' ) ) {
    /**
     * Retrieve the plugin version ensuring the constant is initialized.
     *
     * @return string
     */
    function visibloc_jlg_get_plugin_version() {
        if ( defined( 'VISIBLOC_JLG_VERSION' ) ) {
            return VISIBLOC_JLG_VERSION;
        }

        return visibloc_jlg_define_version_constant();
    }
}

if ( ! function_exists( 'visibloc_jlg_define_default_supported_blocks' ) ) {
    /**
     * Ensure the default supported blocks constant is declared.
     *
     * @return array The list of default block slugs.
     */
    function visibloc_jlg_define_default_supported_blocks() {
        if ( ! defined( 'VISIBLOC_JLG_DEFAULT_SUPPORTED_BLOCKS' ) ) {
            define( 'VISIBLOC_JLG_DEFAULT_SUPPORTED_BLOCKS', [ 'core/group' ] );
        }

        return (array) VISIBLOC_JLG_DEFAULT_SUPPORTED_BLOCKS;
    }
}

if ( ! function_exists( 'visibloc_jlg_get_default_supported_blocks' ) ) {
    /**
     * Retrieve the default supported blocks list.
     *
     * @return array
     */
    function visibloc_jlg_get_default_supported_blocks() {
        return visibloc_jlg_define_default_supported_blocks();
    }
}

