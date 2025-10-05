<?php

namespace VisiBloc;

class Plugin {
    /**
     * Absolute path to the main plugin file.
     *
     * @var string
     */
    private $plugin_file;

    /**
     * Absolute path to the plugin directory.
     *
     * @var string
     */
    private $plugin_dir;

    /**
     * Cached plugin basename.
     *
     * @var string
     */
    private $plugin_basename;

    /**
     * Cached preview capability result.
     *
     * @var bool|null
     */
    private $cached_can_preview = null;

    /**
     * Instantiate the plugin wrapper.
     *
     * @param string $plugin_file Absolute path to the main plugin file.
     */
    public function __construct( $plugin_file ) {
        $this->plugin_file     = $plugin_file;
        $this->plugin_dir      = dirname( $plugin_file );
        $this->plugin_basename = function_exists( 'plugin_basename' )
            ? plugin_basename( $plugin_file )
            : basename( $plugin_file );
    }

    /**
     * Register the plugin hooks and dependencies.
     */
    public function register() {
        $this->define_version_constant();
        $this->define_default_supported_blocks();
        $this->register_settings_hooks();
        $this->register_assets_hooks();
        $this->register_visibility_hooks();
        $this->register_role_switcher_hooks();
        $this->register_cli_commands();

        if ( function_exists( 'add_action' ) ) {
            add_action( 'init', [ $this, 'load_textdomain' ] );
        }
    }

    /**
     * Register the WordPress settings hooks.
     */
    protected function register_settings_hooks() {
        require_once $this->plugin_dir . '/includes/block-utils.php';
        require_once $this->plugin_dir . '/includes/admin-settings.php';

        add_action( 'init', [ $this, 'register_supported_blocks_setting' ] );
    }

    /**
     * Register public/admin asset hooks.
     */
    protected function register_assets_hooks() {
        require_once $this->plugin_dir . '/includes/assets.php';
    }

    /**
     * Register hooks responsible for block visibility.
     */
    protected function register_visibility_hooks() {
        require_once $this->plugin_dir . '/includes/datetime-utils.php';
        require_once $this->plugin_dir . '/includes/visibility-logic.php';
    }

    /**
     * Register hooks that power the role switcher UX.
     */
    protected function register_role_switcher_hooks() {
        require_once $this->plugin_dir . '/includes/role-switcher.php';
    }

    /**
     * Register WP-CLI specific commands when available.
     */
    protected function register_cli_commands() {
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            require_once $this->plugin_dir . '/includes/cli.php';
        }
    }

    /**
     * Load the plugin text domain for translations.
     */
    public function load_textdomain() {
        if ( function_exists( 'load_plugin_textdomain' ) ) {
            load_plugin_textdomain(
                'visi-bloc-jlg',
                false,
                dirname( $this->plugin_basename ) . '/languages'
            );
        }
    }

    /**
     * Register the "visibloc_supported_blocks" option.
     */
    public function register_supported_blocks_setting() {
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

    /**
     * Retrieve a sanitized value from the request.
     *
     * @param string $key Query arg key.
     * @return string
     */
    public function get_sanitized_query_arg( $key ) {
        if ( ! isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return '';
        }

        $value = $_GET[ $key ]; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if ( ! is_string( $value ) ) {
            return '';
        }

        return sanitize_key( wp_unslash( $value ) );
    }

    /**
     * Convert a value into a strict boolean representation.
     *
     * @param mixed $value Raw value.
     * @return bool
     */
    public function normalize_boolean( $value ) {
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

    /**
     * Determine if the current user can preview hidden blocks.
     *
     * @return bool
     */
    public function can_user_preview() {
        if ( null !== $this->cached_can_preview ) {
            return $this->cached_can_preview;
        }

        if ( function_exists( 'visibloc_jlg_get_preview_runtime_context' ) ) {
            $runtime_context           = visibloc_jlg_get_preview_runtime_context();
            $this->cached_can_preview = ! empty( $runtime_context['can_preview_hidden_blocks'] );

            return $this->cached_can_preview;
        }

        $this->cached_can_preview = false;

        return $this->cached_can_preview;
    }

    /**
     * Ensure the plugin version constant is defined.
     */
    protected function define_version_constant() {
        if ( defined( 'VISIBLOC_JLG_VERSION' ) ) {
            return;
        }

        $version = '0.0.0';

        if ( function_exists( 'get_file_data' ) ) {
            $plugin_data = get_file_data( $this->plugin_file, [ 'Version' => 'Version' ] );
            $version     = isset( $plugin_data['Version'] ) && '' !== $plugin_data['Version']
                ? $plugin_data['Version']
                : $version;
        } else {
            $plugin_contents = @file_get_contents( $this->plugin_file );

            if ( false !== $plugin_contents && preg_match( '/^\s*\*\s*Version:\s*(.+)$/mi', $plugin_contents, $matches ) ) {
                $version = trim( $matches[1] );
            }
        }

        define( 'VISIBLOC_JLG_VERSION', $version );
    }

    /**
     * Ensure the default supported blocks constant is declared.
     */
    protected function define_default_supported_blocks() {
        if ( ! defined( 'VISIBLOC_JLG_DEFAULT_SUPPORTED_BLOCKS' ) ) {
            define( 'VISIBLOC_JLG_DEFAULT_SUPPORTED_BLOCKS', [ 'core/group' ] );
        }
    }
}
