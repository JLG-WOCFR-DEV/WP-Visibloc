<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'VISIBLOC_JLG_MISSING_EDITOR_ASSETS_TRANSIENT' ) ) {
    define( 'VISIBLOC_JLG_MISSING_EDITOR_ASSETS_TRANSIENT', 'visibloc_jlg_missing_editor_assets' );
}

require_once __DIR__ . '/cache-constants.php';
require_once __DIR__ . '/datetime-utils.php';
require_once __DIR__ . '/fallback.php';
require_once __DIR__ . '/presets.php';
require_once __DIR__ . '/plugin-meta.php';

if ( ! function_exists( 'visibloc_jlg_get_asset_path' ) ) {
    /**
     * Build an absolute path inside the plugin directory.
     *
     * @param string $relative_path Relative file path.
     * @return string
     */
    function visibloc_jlg_get_asset_path( $relative_path ) {
        $base_path     = visibloc_jlg_get_plugin_dir_path();
        $relative_path = is_string( $relative_path ) ? $relative_path : (string) $relative_path;

        if ( function_exists( 'path_join' ) ) {
            return path_join( $base_path, $relative_path );
        }

        if ( '' === $relative_path ) {
            return $base_path;
        }

        return rtrim( $base_path, '/\\' ) . '/' . ltrim( $relative_path, '/\\' );
    }
}

if ( ! function_exists( 'visibloc_jlg_get_asset_url' ) ) {
    /**
     * Build an asset URL relative to the plugin directory.
     *
     * @param string $relative_path Relative asset path.
     * @return string
     */
    function visibloc_jlg_get_asset_url( $relative_path ) {
        $plugin_main_file = visibloc_jlg_get_plugin_main_file();
        $relative_path    = ltrim( (string) $relative_path, '/\\' );

        if ( function_exists( 'plugins_url' ) ) {
            return plugins_url( $relative_path, $plugin_main_file );
        }

        $base_url = defined( 'VISIBLOC_JLG_PLUGIN_URL' ) ? (string) VISIBLOC_JLG_PLUGIN_URL : '';

        if ( '' !== $base_url ) {
            if ( function_exists( 'trailingslashit' ) ) {
                $base_url = trailingslashit( $base_url );
            } else {
                $base_url = rtrim( $base_url, '/\\' ) . '/';
            }

            return $base_url . $relative_path;
        }

        $fallback = '';

        if ( function_exists( 'apply_filters' ) ) {
            $fallback = apply_filters( 'visibloc_jlg_asset_url_fallback', $fallback, $relative_path );
        }

        if ( '' !== $fallback ) {
            return $fallback;
        }

        if ( function_exists( 'do_action' ) ) {
            do_action( 'visibloc_jlg_missing_asset_url', $relative_path );
        }

        return '';
    }
}

if ( ! function_exists( 'visibloc_jlg_asset_versions_runtime_cache' ) ) {
    /**
     * Runtime cache accessor for computed asset versions.
     *
     * @param string|null $key   Cache key.
     * @param string|null $value Value to store.
     * @param bool        $reset Whether to flush the cache.
     * @return string|null
     */
    function visibloc_jlg_asset_versions_runtime_cache( $key = null, $value = null, $reset = false ) {
        static $cache = [];

        if ( $reset ) {
            $cache = [];

            return null;
        }

        if ( null === $key ) {
            return null;
        }

        if ( null !== $value ) {
            $cache[ $key ] = $value;

            return $cache[ $key ];
        }

        return $cache[ $key ] ?? null;
    }
}

if ( ! function_exists( 'visibloc_jlg_flush_asset_versions_cache' ) ) {
    /**
     * Clear the runtime cache storing computed asset versions.
     */
    function visibloc_jlg_flush_asset_versions_cache() {
        visibloc_jlg_asset_versions_runtime_cache( null, null, true );
    }
}

if ( ! function_exists( 'visibloc_jlg_get_asset_version' ) ) {
    /**
     * Retrieve a cache-busting version for an asset.
     *
     * @param string      $relative_path   Relative asset path.
     * @param string|null $default_version Optional default version when the file is missing.
     * @return string
     */
    function visibloc_jlg_get_asset_version( $relative_path, $default_version = null ) {
        $relative_path = ltrim( (string) $relative_path, '/\\' );
        $cache_key     = sprintf(
            '%s|%s',
            $relative_path,
            null === $default_version ? '__null__' : (string) $default_version
        );

        $cached_version = visibloc_jlg_asset_versions_runtime_cache( $cache_key );

        if ( null !== $cached_version ) {
            return $cached_version;
        }

        $absolute_path = visibloc_jlg_get_asset_path( $relative_path );

        if ( file_exists( $absolute_path ) ) {
            $file_version = filemtime( $absolute_path );

            if ( false !== $file_version ) {
                $version = (string) $file_version;

                visibloc_jlg_asset_versions_runtime_cache( $cache_key, $version );

                return $version;
            }
        }

        if ( null !== $default_version ) {
            $version = (string) $default_version;

            visibloc_jlg_asset_versions_runtime_cache( $cache_key, $version );

            return $version;
        }

        $version = visibloc_jlg_get_plugin_version();

        visibloc_jlg_asset_versions_runtime_cache( $cache_key, $version );

        return $version;
    }
}

if ( ! defined( 'VISIBLOC_JLG_EDITOR_DATA_CACHE_GROUP' ) ) {
    define( 'VISIBLOC_JLG_EDITOR_DATA_CACHE_GROUP', 'visibloc_jlg_editor_data' );
}

if ( ! defined( 'VISIBLOC_JLG_EDITOR_DATA_TRANSIENT_PREFIX' ) ) {
    define( 'VISIBLOC_JLG_EDITOR_DATA_TRANSIENT_PREFIX', 'visibloc_editor_data_' );
}

if ( ! defined( 'VISIBLOC_JLG_EDITOR_DATA_CACHE_TTL' ) ) {
    $minute = defined( 'MINUTE_IN_SECONDS' ) ? MINUTE_IN_SECONDS : 60;
    define( 'VISIBLOC_JLG_EDITOR_DATA_CACHE_TTL', 15 * $minute );
}

/**
 * Retrieve a normalized locale string for cache segmentation.
 *
 * @return string
 */
function visibloc_jlg_get_editor_data_locale() {
    static $locale = null;

    if ( null !== $locale ) {
        return $locale;
    }

    $candidates = [];

    if ( function_exists( 'determine_locale' ) ) {
        $candidates[] = determine_locale();
    }

    if ( function_exists( 'get_user_locale' ) ) {
        $candidates[] = get_user_locale();
    }

    if ( function_exists( 'get_locale' ) ) {
        $candidates[] = get_locale();
    }

    foreach ( $candidates as $candidate ) {
        if ( is_string( $candidate ) && '' !== $candidate ) {
            $locale = $candidate;
            break;
        }
    }

    if ( null === $locale ) {
        $locale = 'en_US';
    }

    return $locale;
}

/**
 * Build the cache prefix combining the plugin version and current locale.
 *
 * @return string
 */
function visibloc_jlg_get_editor_data_cache_prefix() {
    static $prefix = null;

    if ( null !== $prefix ) {
        return $prefix;
    }

    $version = visibloc_jlg_get_plugin_version();
    $locale  = visibloc_jlg_get_editor_data_locale();

    $prefix = sprintf( '%s:%s', $version, $locale );

    return $prefix;
}

/**
 * Map a data slug to the bucket key stored in cache engines.
 *
 * @param string $slug Data identifier.
 * @return string
 */
function visibloc_jlg_get_editor_data_bucket_key( $slug ) {
    $slug = is_string( $slug ) ? $slug : (string) $slug;

    if ( '' === $slug ) {
        $slug = 'default';
    }

    return visibloc_jlg_get_editor_data_cache_prefix() . ':' . $slug;
}

/**
 * Generate the transient key used to persist cached editor data.
 *
 * @param string $bucket_key Bucket identifier.
 * @return string
 */
function visibloc_jlg_get_editor_data_transient_key( $bucket_key ) {
    return VISIBLOC_JLG_EDITOR_DATA_TRANSIENT_PREFIX . md5( $bucket_key );
}

/**
 * Determine if the editor data cache is enabled for a given slug.
 *
 * @param string $slug Data identifier.
 * @return bool
 */
function visibloc_jlg_is_editor_data_cache_enabled( $slug ) {
    $enabled = true;

    if ( function_exists( 'apply_filters' ) ) {
        $enabled = apply_filters( 'visibloc_jlg_use_editor_data_cache', $enabled, $slug );
    }

    return (bool) $enabled;
}

/**
 * Retrieve a cached editor dataset or compute it via the provided callback.
 *
 * @param string   $slug        Data identifier.
 * @param callable $generator   Callback returning the uncached payload.
 * @param int|null $expiration  Optional cache duration in seconds.
 * @return mixed
 */
function visibloc_jlg_get_cached_editor_data( $slug, callable $generator, $expiration = null ) {
    if ( ! visibloc_jlg_is_editor_data_cache_enabled( $slug ) ) {
        return call_user_func( $generator );
    }

    $bucket_key    = visibloc_jlg_get_editor_data_bucket_key( $slug );
    $transient_key = visibloc_jlg_get_editor_data_transient_key( $bucket_key );
    $expiration    = ( null === $expiration ) ? VISIBLOC_JLG_EDITOR_DATA_CACHE_TTL : (int) max( 0, $expiration );

    if ( function_exists( 'wp_cache_get' ) ) {
        $cached = wp_cache_get( $bucket_key, VISIBLOC_JLG_EDITOR_DATA_CACHE_GROUP );

        if ( false !== $cached ) {
            return $cached;
        }
    }

    if ( function_exists( 'get_transient' ) ) {
        $transient = get_transient( $transient_key );

        if ( false !== $transient ) {
            if ( function_exists( 'wp_cache_set' ) ) {
                wp_cache_set( $bucket_key, $transient, VISIBLOC_JLG_EDITOR_DATA_CACHE_GROUP, $expiration );
            }

            return $transient;
        }
    }

    $data = call_user_func( $generator );

    if ( function_exists( 'wp_cache_set' ) ) {
        wp_cache_set( $bucket_key, $data, VISIBLOC_JLG_EDITOR_DATA_CACHE_GROUP, $expiration );
    }

    if ( function_exists( 'set_transient' ) ) {
        set_transient( $transient_key, $data, $expiration );
    }

    return $data;
}

/**
 * Return the list of cache slugs maintained for editor data.
 *
 * @return string[]
 */
function visibloc_jlg_get_editor_data_cache_slugs() {
    return [
        'post_types',
        'taxonomies',
        'templates',
        'role_groups',
        'roles',
        'timezones',
        'woocommerce_taxonomies',
    ];
}

/**
 * Determine if callbacks are registered for a given hook.
 *
 * @param string $hook Hook name to inspect.
 * @return bool
 */
function visibloc_jlg_has_active_filter( $hook ) {
    if ( ! is_string( $hook ) || '' === $hook ) {
        return false;
    }

    if ( function_exists( 'has_filter' ) ) {
        return false !== has_filter( $hook );
    }

    if ( isset( $GLOBALS['visibloc_test_filters'][ $hook ] ) && is_array( $GLOBALS['visibloc_test_filters'][ $hook ] ) ) {
        foreach ( $GLOBALS['visibloc_test_filters'][ $hook ] as $callbacks ) {
            if ( ! empty( $callbacks ) ) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Clear cached editor datasets.
 *
 * @param string|string[]|null $slugs Optional subset of cache buckets to purge.
 * @return void
 */
function visibloc_jlg_clear_editor_data_cache( $slugs = null ) {
    $available = visibloc_jlg_get_editor_data_cache_slugs();

    if ( null === $slugs ) {
        $targets = $available;
    } else {
        $slugs   = is_array( $slugs ) ? $slugs : [ $slugs ];
        $targets = [];

        foreach ( $slugs as $slug ) {
            $slug = is_string( $slug ) ? $slug : (string) $slug;

            if ( '' === $slug ) {
                continue;
            }

            if ( in_array( $slug, $available, true ) ) {
                $targets[] = $slug;
            }
        }
    }

    $targets = array_values( array_unique( $targets ) );

    foreach ( $targets as $slug ) {
        $bucket_key    = visibloc_jlg_get_editor_data_bucket_key( $slug );
        $transient_key = visibloc_jlg_get_editor_data_transient_key( $bucket_key );

        if ( function_exists( 'wp_cache_delete' ) ) {
            wp_cache_delete( $bucket_key, VISIBLOC_JLG_EDITOR_DATA_CACHE_GROUP );
        }

        if ( function_exists( 'delete_transient' ) ) {
            delete_transient( $transient_key );
        }
    }
}

add_action( 'wp_enqueue_scripts', 'visibloc_jlg_enqueue_public_styles' );
function visibloc_jlg_enqueue_public_styles() {
    visibloc_jlg_register_visual_preset_styles();
    $can_preview    = visibloc_jlg_can_user_preview();
    $default_mobile = 781;
    $default_tablet = 1024;
    $mobile_bp      = absint( get_option( 'visibloc_breakpoint_mobile', $default_mobile ) );
    $tablet_bp      = absint( get_option( 'visibloc_breakpoint_tablet', $default_tablet ) );
    $mobile_bp      = $mobile_bp > 0 ? $mobile_bp : $default_mobile;
    $tablet_bp      = $tablet_bp > 0 ? $tablet_bp : $default_tablet;
    $has_custom_breakpoints = ( $mobile_bp !== $default_mobile ) || ( $tablet_bp !== $default_tablet );
    $default_handle         = 'visibloc-jlg-device-visibility';
    $dynamic_handle         = 'visibloc-jlg-device-visibility-dynamic';
    $style_version          = visibloc_jlg_get_plugin_version();

    if ( $has_custom_breakpoints ) {
        wp_dequeue_style( $default_handle );
        wp_deregister_style( $default_handle );
        wp_register_style( $dynamic_handle, false, [], $style_version );
        $device_handle = $dynamic_handle;
    } else {
        wp_register_style(
            $default_handle,
            visibloc_jlg_get_asset_url( 'assets/device-visibility.css' ),
            [],
            $style_version
        );
        $device_handle = $default_handle;
    }

    wp_enqueue_style( $device_handle );

    $device_css = visibloc_jlg_generate_device_visibility_css( $can_preview, $mobile_bp, $tablet_bp );

    if ( '' !== $device_css ) {
        wp_add_inline_style( $device_handle, $device_css );
    }

    if ( $can_preview ) {
        wp_enqueue_style(
            'visibloc-jlg-public-styles',
            visibloc_jlg_get_asset_url( 'admin-styles.css' ),
            [],
            $style_version
        );
    }
}

add_action( 'admin_enqueue_scripts', 'visibloc_jlg_enqueue_admin_styles' );
function visibloc_jlg_enqueue_admin_styles( $hook_suffix ) {
    if ( 'toplevel_page_visi-bloc-jlg-help' !== $hook_suffix ) {
        return;
    }

    visibloc_jlg_register_visual_preset_styles();

    $style_version    = visibloc_jlg_get_plugin_version();

    wp_enqueue_style(
        'visibloc-jlg-admin-responsive',
        visibloc_jlg_get_asset_url( 'assets/admin-responsive.css' ),
        [],
        $style_version
    );
}

add_action( 'admin_enqueue_scripts', 'visibloc_jlg_enqueue_admin_supported_blocks_script' );
function visibloc_jlg_enqueue_admin_supported_blocks_script( $hook_suffix ) {
    if ( 'toplevel_page_visi-bloc-jlg-help' !== $hook_suffix ) {
        return;
    }

    $script_relative_path  = 'assets/admin-supported-blocks.js';
    $default_script_version = visibloc_jlg_get_plugin_version();
    $script_version        = visibloc_jlg_get_asset_version( $script_relative_path, $default_script_version );

    wp_enqueue_script(
        'visibloc-jlg-supported-blocks-search',
        visibloc_jlg_get_asset_url( $script_relative_path ),
        [],
        $script_version,
        true
    );
}

add_action( 'admin_enqueue_scripts', 'visibloc_jlg_enqueue_admin_navigation_script' );
function visibloc_jlg_enqueue_admin_navigation_script( $hook_suffix ) {
    if ( 'toplevel_page_visi-bloc-jlg-help' !== $hook_suffix ) {
        return;
    }

    $script_relative_path   = 'assets/admin-nav.js';
    $default_script_version = visibloc_jlg_get_plugin_version();
    $script_version         = visibloc_jlg_get_asset_version( $script_relative_path, $default_script_version );

    wp_enqueue_script(
        'visibloc-jlg-admin-navigation',
        visibloc_jlg_get_asset_url( $script_relative_path ),
        [ 'wp-dom-ready' ],
        $script_version,
        true
    );
}

add_action( 'admin_enqueue_scripts', 'visibloc_jlg_enqueue_admin_recipes_script' );
function visibloc_jlg_enqueue_admin_recipes_script( $hook_suffix ) {
    if ( 'toplevel_page_visi-bloc-jlg-help' !== $hook_suffix ) {
        return;
    }

    $script_relative_path   = 'assets/admin-recipes.js';
    $default_script_version = visibloc_jlg_get_plugin_version();
    $script_version         = visibloc_jlg_get_asset_version( $script_relative_path, $default_script_version );

    wp_enqueue_script(
        'visibloc-jlg-admin-recipes',
        visibloc_jlg_get_asset_url( $script_relative_path ),
        [ 'wp-dom-ready', 'wp-i18n' ],
        $script_version,
        true
    );

    if ( function_exists( 'wp_set_script_translations' ) ) {
        wp_set_script_translations( 'visibloc-jlg-admin-recipes', 'visi-bloc-jlg' );
    }
}

add_action( 'enqueue_block_editor_assets', 'visibloc_jlg_enqueue_editor_assets' );
function visibloc_jlg_enqueue_editor_assets() {
    $asset_file_path = visibloc_jlg_get_asset_path( 'build/index.asset.php' );
    if ( ! file_exists( $asset_file_path ) ) {
        visibloc_jlg_flag_missing_editor_assets();

        return;
    }

    visibloc_jlg_clear_missing_editor_assets_flag();
    visibloc_jlg_register_visual_preset_styles();
    $asset_file = include( $asset_file_path );
    wp_enqueue_script(
        'visibloc-jlg-editor-script',
        visibloc_jlg_get_asset_url( 'build/index.js' ),
        $asset_file['dependencies'],
        $asset_file['version'],
        true
    );
    wp_set_script_translations(
        'visibloc-jlg-editor-script',
        'visi-bloc-jlg',
        function_exists( 'trailingslashit' )
            ? trailingslashit( visibloc_jlg_get_asset_path( 'languages' ) )
            : visibloc_jlg_get_asset_path( 'languages' )
    );
    wp_enqueue_style(
        'visibloc-jlg-editor-style',
        visibloc_jlg_get_asset_url( 'build/index.css' ),
        [],
        $asset_file['version']
    );
    wp_localize_script(
        'visibloc-jlg-editor-script',
        'VisiBlocData',
        [
            'roles'            => visibloc_jlg_get_editor_roles(),
            'supportedBlocks'  => visibloc_jlg_get_supported_blocks(),
            'postTypes'        => visibloc_jlg_get_editor_post_types(),
            'taxonomies'       => visibloc_jlg_get_editor_taxonomies(),
            'templates'        => visibloc_jlg_get_editor_templates(),
            'daysOfWeek'       => visibloc_jlg_get_editor_days_of_week(),
            'timezones'        => visibloc_jlg_get_editor_timezones(),
            'roleGroups'       => visibloc_jlg_get_editor_role_groups(),
            'userSegments'     => visibloc_jlg_get_editor_user_segments(),
            'loginStatuses'    => visibloc_jlg_get_editor_login_statuses(),
            'woocommerceTaxonomies' => visibloc_jlg_get_editor_woocommerce_taxonomies(),
            'commonQueryParams' => visibloc_jlg_get_editor_common_query_params(),
            'commonCookies'     => visibloc_jlg_get_editor_common_cookies(),
            'fallbackSettings' => visibloc_jlg_get_editor_fallback_settings(),
            'fallbackBlocks'   => visibloc_jlg_get_editor_fallback_blocks(),
            'fallbackBlocksEndpoint' => visibloc_jlg_get_fallback_blocks_rest_url(),
            'visualPresets'    => visibloc_jlg_get_editor_visual_presets(),
            'guidedRecipes'    => visibloc_jlg_get_editor_guided_recipes(),
            'editorPreferences' => visibloc_jlg_get_editor_preferences_payload(),
            'editorPreferencesEndpoint' => visibloc_jlg_get_editor_preferences_rest_url(),
        ]
    );
}

function visibloc_jlg_get_editor_post_types() {
    return visibloc_jlg_get_cached_editor_data(
        'post_types',
        static function () {
            $post_types = get_post_types(
                [
                    'public' => true,
                ],
                'objects'
            );

            if ( empty( $post_types ) || ! is_array( $post_types ) ) {
                return [];
            }

            $items = [];

            foreach ( $post_types as $slug => $post_type ) {
                if ( ! is_string( $slug ) || '' === $slug ) {
                    continue;
                }

                $label = $slug;

                if ( is_object( $post_type ) ) {
                    if ( isset( $post_type->labels->singular_name ) && is_string( $post_type->labels->singular_name ) && '' !== $post_type->labels->singular_name ) {
                        $label = $post_type->labels->singular_name;
                    } elseif ( isset( $post_type->label ) && is_string( $post_type->label ) && '' !== $post_type->label ) {
                        $label = $post_type->label;
                    }
                }

                $items[] = [
                    'value' => $slug,
                    'label' => $label,
                ];
            }

            usort(
                $items,
                static function ( $first, $second ) {
                    return strcasecmp( (string) ( $first['label'] ?? '' ), (string) ( $second['label'] ?? '' ) );
                }
            );

            return $items;
        }
    );
}

function visibloc_jlg_get_editor_taxonomies() {
    $generator = static function () {
        $taxonomies = get_taxonomies(
            [
                'public' => true,
            ],
            'objects'
        );

        if ( empty( $taxonomies ) || ! is_array( $taxonomies ) ) {
            return [];
        }

        $items = [];

        foreach ( $taxonomies as $slug => $taxonomy ) {
            if ( ! is_string( $slug ) || '' === $slug ) {
                continue;
            }

            $label = $slug;

            if ( is_object( $taxonomy ) ) {
                if ( isset( $taxonomy->labels->singular_name ) && is_string( $taxonomy->labels->singular_name ) && '' !== $taxonomy->labels->singular_name ) {
                    $label = $taxonomy->labels->singular_name;
                } elseif ( isset( $taxonomy->label ) && is_string( $taxonomy->label ) && '' !== $taxonomy->label ) {
                    $label = $taxonomy->label;
                }
            }

            $term_options = [];

            if ( taxonomy_exists( $slug ) ) {
                $term_query_args = [
                    'taxonomy'   => $slug,
                    'hide_empty' => false,
                    'number'     => 200, // Default limit for editor term suggestions.
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                ];

                /**
                 * Filter the query arguments used to retrieve taxonomy terms for the editor.
                 *
                 * The default arguments include a limit of 200 terms to avoid large responses.
                 *
                 * @param array  $term_query_args Query arguments passed to {@see get_terms()}.
                 * @param string $slug            Taxonomy slug being queried.
                 */
                $term_query_args = apply_filters( 'visibloc_jlg_editor_terms_query_args', $term_query_args, $slug );

                $terms = get_terms( $term_query_args );

                if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
                    foreach ( $terms as $term ) {
                        if ( ! $term instanceof WP_Term ) {
                            continue;
                        }

                        $term_slug = $term->slug;

                        if ( ! is_string( $term_slug ) || '' === $term_slug ) {
                            $term_slug = (string) $term->term_id;
                        }

                        $term_options[] = [
                            'value' => $term_slug,
                            'label' => $term->name,
                        ];
                    }

                    usort(
                        $term_options,
                        static function ( $first, $second ) {
                            return strcasecmp( (string) ( $first['label'] ?? '' ), (string) ( $second['label'] ?? '' ) );
                        }
                    );
                }
            }

            $rest_base      = '';
            $rest_namespace = '';

            if ( is_object( $taxonomy ) ) {
                if ( isset( $taxonomy->rest_base ) && is_string( $taxonomy->rest_base ) ) {
                    $rest_base = $taxonomy->rest_base;
                }

                if ( isset( $taxonomy->rest_namespace ) && is_string( $taxonomy->rest_namespace ) ) {
                    $rest_namespace = $taxonomy->rest_namespace;
                }
            }

            $items[] = [
                'slug'          => $slug,
                'label'         => $label,
                'terms'         => $term_options,
                'rest_base'     => '' !== $rest_base ? $rest_base : $slug,
                'rest_namespace'=> '' !== $rest_namespace ? $rest_namespace : 'wp/v2',
            ];
        }

        usort(
            $items,
            static function ( $first, $second ) {
                return strcasecmp( (string) ( $first['label'] ?? '' ), (string) ( $second['label'] ?? '' ) );
            }
        );

        return $items;
    };

    if ( visibloc_jlg_has_active_filter( 'visibloc_jlg_editor_terms_query_args' ) ) {
        return $generator();
    }

    return visibloc_jlg_get_cached_editor_data( 'taxonomies', $generator );
}

function visibloc_jlg_get_editor_guided_recipes() {
    $recipes = [
        [
            'id'          => 'welcome-series',
            'title'       => __( 'Série de bienvenue personnalisée', 'visi-bloc-jlg' ),
            'description' => __( 'Active une campagne d’onboarding avec planification et repli textuel.', 'visi-bloc-jlg' ),
            'severity'    => 'critical',
            'attributes'  => [
                'deviceVisibility'   => 'all',
                'isSchedulingEnabled'=> true,
                'scheduleWindowDays' => 7,
                'roles'              => [ 'subscriber', 'customer' ],
                'advancedRules'      => [
                    [
                        'type'     => 'logged_in_status',
                        'operator' => 'is',
                        'value'    => 'logged_in',
                    ],
                ],
                'fallback'           => [
                    'behavior' => 'text',
                    'message'  => __( 'Merci de votre visite ! Ce contenu n’est plus disponible pour votre profil.', 'visi-bloc-jlg' ),
                ],
            ],
        ],
        [
            'id'          => 'woocommerce-cart-recovery',
            'title'       => __( 'Relance panier WooCommerce', 'visi-bloc-jlg' ),
            'description' => __( 'Cible les clients avec un panier actif et prépare un repli de sécurité.', 'visi-bloc-jlg' ),
            'severity'    => 'high',
            'attributes'  => [
                'deviceVisibility'   => 'hide-on-mobile',
                'isSchedulingEnabled'=> false,
                'roles'              => [ 'customer' ],
                'advancedRules'      => [
                    [
                        'type'     => 'woocommerce_cart',
                        'operator' => 'contains',
                    ],
                ],
                'fallback'           => [
                    'behavior' => 'inherit',
                ],
            ],
        ],
        [
            'id'          => 'b2b-lead-nurturing',
            'title'       => __( 'Parcours lead nurturing B2B', 'visi-bloc-jlg' ),
            'description' => __( 'Filtre un segment marketing et crée un repli axé accompagnement.', 'visi-bloc-jlg' ),
            'severity'    => 'medium',
            'attributes'  => [
                'deviceVisibility'   => 'all',
                'isSchedulingEnabled'=> true,
                'scheduleWindowDays' => 14,
                'roles'              => [ 'editor', 'author' ],
                'advancedRules'      => [
                    [
                        'type'     => 'user_segment',
                        'operator' => 'matches',
                        'segment'  => 'crm_mql',
                    ],
                ],
                'fallback'           => [
                    'behavior' => 'text',
                    'message'  => __( 'Contactez notre équipe pour recevoir une alternative personnalisée.', 'visi-bloc-jlg' ),
                ],
            ],
        ],
    ];

    /**
     * Filter the guided recipes exposed in the editor.
     *
     * @param array $recipes Recipes definitions.
     */
    return apply_filters( 'visibloc_jlg_editor_guided_recipes', $recipes );
}

function visibloc_jlg_get_editor_templates() {
    return visibloc_jlg_get_cached_editor_data(
        'templates',
        static function () {
            $templates = [];

            if ( function_exists( 'wp_get_theme' ) ) {
                $theme = wp_get_theme();

                if ( $theme instanceof WP_Theme ) {
                    $page_templates = $theme->get_page_templates();

                    if ( is_array( $page_templates ) ) {
                        foreach ( $page_templates as $template_name => $template_file ) {
                            if ( ! is_string( $template_file ) ) {
                                continue;
                            }

                            $label = is_string( $template_name ) && '' !== $template_name
                                ? $template_name
                                : $template_file;

                            $templates[] = [
                                'value' => $template_file,
                                'label' => $label,
                            ];
                        }
                    }
                }
            }

            $default_label = __( 'Modèle par défaut', 'visi-bloc-jlg' );

            $templates[] = [
                'value' => '',
                'label' => $default_label,
            ];

            usort(
                $templates,
                static function ( $first, $second ) {
                    return strcasecmp( (string) ( $first['label'] ?? '' ), (string) ( $second['label'] ?? '' ) );
                }
            );

            return $templates;
        }
    );
}

function visibloc_jlg_get_editor_days_of_week() {
    $days = [
        'mon' => __( 'Lundi', 'visi-bloc-jlg' ),
        'tue' => __( 'Mardi', 'visi-bloc-jlg' ),
        'wed' => __( 'Mercredi', 'visi-bloc-jlg' ),
        'thu' => __( 'Jeudi', 'visi-bloc-jlg' ),
        'fri' => __( 'Vendredi', 'visi-bloc-jlg' ),
        'sat' => __( 'Samedi', 'visi-bloc-jlg' ),
        'sun' => __( 'Dimanche', 'visi-bloc-jlg' ),
    ];

    $items = [];

    foreach ( $days as $value => $label ) {
        $items[] = [
            'value' => $value,
            'label' => $label,
        ];
    }

    return $items;
}

function visibloc_jlg_get_editor_timezones() {
    return visibloc_jlg_get_cached_editor_data(
        'timezones',
        static function () {
            return visibloc_jlg_get_timezone_options();
        }
    );
}

function visibloc_jlg_get_role_group_definitions() {
    $generator = static function () {
        $roles = function_exists( 'wp_roles' ) ? wp_roles()->get_names() : [];

        if ( ! is_array( $roles ) ) {
            $roles = [];
        }

        $groups = [];

        if ( ! empty( $roles ) ) {
            $groups[] = [
                'value' => 'all_registered',
                'label' => __( 'Utilisateurs connectés (tous)', 'visi-bloc-jlg' ),
                'roles' => array_keys( $roles ),
            ];
        }

        foreach ( $roles as $slug => $label ) {
            if ( ! is_string( $slug ) || '' === $slug ) {
                continue;
            }

            $groups[] = [
                'value' => $slug,
                'label' => $label,
                'roles' => [ $slug ],
            ];
        }

        $groups = apply_filters( 'visibloc_jlg_role_groups', $groups, $roles );

        $sanitized = [];

        foreach ( (array) $groups as $group ) {
            if ( ! is_array( $group ) ) {
                continue;
            }

            $value = isset( $group['value'] ) ? (string) $group['value'] : '';

            if ( '' === $value ) {
                continue;
            }

            $label = isset( $group['label'] ) ? (string) $group['label'] : $value;
            $roles_list = [];

            if ( isset( $group['roles'] ) && is_array( $group['roles'] ) ) {
                foreach ( $group['roles'] as $role_slug ) {
                    if ( is_string( $role_slug ) && '' !== $role_slug ) {
                        $roles_list[] = $role_slug;
                    }
                }
            }

            $roles_list = array_values( array_unique( $roles_list ) );

            $sanitized[] = [
                'value' => $value,
                'label' => $label,
                'roles' => $roles_list,
            ];
        }

        return $sanitized;
    };

    if ( visibloc_jlg_has_active_filter( 'visibloc_jlg_role_groups' ) ) {
        return $generator();
    }

    return visibloc_jlg_get_cached_editor_data( 'role_groups', $generator );
}

function visibloc_jlg_get_editor_role_groups() {
    return visibloc_jlg_get_role_group_definitions();
}

/**
 * Retrieve marketing segments exposed to the block editor.
 *
 * @return array<int, array<string, string>>
 */
function visibloc_jlg_get_editor_user_segments() {
    /**
     * Filters the list of marketing segments available in the editor UI.
     *
     * Each item should be an associative array containing a required `value` key
     * and an optional `label` used for display.
     *
     * @since 1.1.1
     *
     * @param array<int, array<string, string>> $segments Declared user segments.
     */
    $segments = function_exists( 'apply_filters' )
        ? apply_filters(
            'visibloc_jlg_user_segments',
            []
        )
        : [];

    if ( ! is_array( $segments ) ) {
        return [];
    }

    $items = [];

    foreach ( $segments as $segment ) {
        if ( ! is_array( $segment ) ) {
            continue;
        }

        $value = isset( $segment['value'] ) ? (string) $segment['value'] : '';

        if ( '' === $value ) {
            continue;
        }

        $label = isset( $segment['label'] ) && is_string( $segment['label'] ) && '' !== trim( $segment['label'] )
            ? $segment['label']
            : $value;

        $items[] = [
            'value' => $value,
            'label' => $label,
        ];
    }

    usort(
        $items,
        static function ( $first, $second ) {
            return strcasecmp( (string) ( $first['label'] ?? '' ), (string) ( $second['label'] ?? '' ) );
        }
    );

    return $items;
}

/**
 * Retrieve the list of available roles for editor controls.
 *
 * @return array<string, string> Map of role slugs to translated labels.
 */
function visibloc_jlg_get_editor_roles() {
    $generator = static function () {
        $roles = function_exists( 'wp_roles' ) ? wp_roles()->get_names() : [];

        if ( ! is_array( $roles ) ) {
            $roles = [];
        }

        /**
         * Filters the role labels exposed to the editor visibility controls.
         *
         * @since 1.1.1
         *
         * @param array<string, string> $roles Associative array of role slugs to labels.
         */
        $roles = apply_filters( 'visibloc_jlg_role_labels', $roles );

        $sanitized = [];

        foreach ( (array) $roles as $slug => $label ) {
            if ( ! is_string( $slug ) ) {
                continue;
            }

            $normalized_slug = trim( $slug );
            $normalized_slug = '' !== $normalized_slug ? sanitize_key( $normalized_slug ) : '';

            if ( '' === $normalized_slug ) {
                continue;
            }

            $label_value = '';

            if ( is_string( $label ) && '' !== trim( $label ) ) {
                $label_value = trim( $label );

                if ( function_exists( 'wp_strip_all_tags' ) ) {
                    $label_value = wp_strip_all_tags( $label_value );
                } else {
                    $label_value = strip_tags( $label_value );
                }

                $label_value = trim( $label_value );
            }

            if ( '' === $label_value ) {
                $label_value = ucwords( str_replace( [ '-', '_' ], ' ', $normalized_slug ) );
            }

            $sanitized[ $normalized_slug ] = $label_value;
        }

        return $sanitized;
    };

    if ( visibloc_jlg_has_active_filter( 'visibloc_jlg_role_labels' ) ) {
        return $generator();
    }

    return visibloc_jlg_get_cached_editor_data( 'roles', $generator );
}

function visibloc_jlg_get_editor_login_statuses() {
    return [
        [
            'value' => 'logged_in',
            'label' => __( 'Utilisateur connecté', 'visi-bloc-jlg' ),
        ],
        [
            'value' => 'logged_out',
            'label' => __( 'Visiteur déconnecté', 'visi-bloc-jlg' ),
        ],
    ];
}

function visibloc_jlg_get_editor_woocommerce_taxonomies() {
    $generator = static function () {
        $taxonomies = [
            'product_cat' => __( 'Catégories de produits', 'visi-bloc-jlg' ),
            'product_tag' => __( 'Étiquettes de produits', 'visi-bloc-jlg' ),
        ];

        if ( function_exists( 'wc_get_attribute_taxonomies' ) && function_exists( 'wc_attribute_taxonomy_name' ) ) {
            $attribute_taxonomies = wc_get_attribute_taxonomies();

            if ( is_array( $attribute_taxonomies ) ) {
                foreach ( $attribute_taxonomies as $taxonomy ) {
                    if ( empty( $taxonomy->attribute_name ) ) {
                        continue;
                    }

                    $attribute_name = wc_attribute_taxonomy_name( $taxonomy->attribute_name );
                    $label = ! empty( $taxonomy->attribute_label )
                        ? $taxonomy->attribute_label
                        : $attribute_name;

                    $taxonomies[ $attribute_name ] = $label;
                }
            }
        }

        $taxonomies = apply_filters( 'visibloc_jlg_woocommerce_taxonomies', $taxonomies );

        $items = [];

        foreach ( $taxonomies as $slug => $label ) {
            if ( ! is_string( $slug ) || '' === $slug ) {
                continue;
            }

            $taxonomy_slug = $slug;

            if ( ! taxonomy_exists( $taxonomy_slug ) ) {
                continue;
            }

            $term_options = [];
            $terms = get_terms(
                [
                    'taxonomy'   => $taxonomy_slug,
                    'hide_empty' => false,
                    'number'     => 200,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                ]
            );

            if ( ! is_wp_error( $terms ) ) {
                foreach ( $terms as $term ) {
                    if ( ! $term instanceof WP_Term ) {
                        continue;
                    }

                    $term_value = $term->slug;

                    if ( '' === $term_value ) {
                        $term_value = (string) $term->term_id;
                    }

                    $term_options[] = [
                        'value' => $term_value,
                        'label' => $term->name,
                    ];
                }
            }

            $items[] = [
                'slug'  => $taxonomy_slug,
                'label' => is_string( $label ) && '' !== $label ? $label : $taxonomy_slug,
                'terms' => $term_options,
            ];
        }

        usort(
            $items,
            static function ( $first, $second ) {
                return strcasecmp( (string) ( $first['label'] ?? '' ), (string) ( $second['label'] ?? '' ) );
            }
        );

        return $items;
    };

    if (
        visibloc_jlg_has_active_filter( 'visibloc_jlg_woocommerce_taxonomies' )
        || visibloc_jlg_has_active_filter( 'visibloc_jlg_editor_terms_query_args' )
    ) {
        return $generator();
    }

    return visibloc_jlg_get_cached_editor_data( 'woocommerce_taxonomies', $generator );
}

function visibloc_jlg_get_editor_common_query_params() {
    $params = [
        [ 'value' => 'utm_source', 'label' => 'utm_source' ],
        [ 'value' => 'utm_medium', 'label' => 'utm_medium' ],
        [ 'value' => 'utm_campaign', 'label' => 'utm_campaign' ],
        [ 'value' => 'utm_term', 'label' => 'utm_term' ],
        [ 'value' => 'utm_content', 'label' => 'utm_content' ],
        [ 'value' => 'gclid', 'label' => 'gclid' ],
        [ 'value' => 'fbclid', 'label' => 'fbclid' ],
        [ 'value' => 'ref', 'label' => 'ref' ],
    ];

    $params = apply_filters( 'visibloc_jlg_common_query_params', $params );

    $sanitized = [];

    foreach ( (array) $params as $param ) {
        if ( ! is_array( $param ) ) {
            continue;
        }

        $value = isset( $param['value'] ) ? (string) $param['value'] : '';

        if ( '' === $value ) {
            continue;
        }

        $label = isset( $param['label'] ) ? (string) $param['label'] : $value;

        $sanitized[] = [
            'value' => $value,
            'label' => $label,
        ];
    }

    return $sanitized;
}

function visibloc_jlg_get_editor_common_cookies() {
    $cookies = [
        [ 'value' => 'woocommerce_cart_hash', 'label' => 'woocommerce_cart_hash' ],
        [ 'value' => 'woocommerce_items_in_cart', 'label' => 'woocommerce_items_in_cart' ],
        [ 'value' => 'woocommerce_recently_viewed', 'label' => 'woocommerce_recently_viewed' ],
        [ 'value' => 'wp-wpml_current_language', 'label' => 'wp-wpml_current_language' ],
        [ 'value' => 'pll_language', 'label' => 'pll_language' ],
        [ 'value' => 'visibloc_preview_role', 'label' => 'visibloc_preview_role' ],
    ];

    $cookies = apply_filters( 'visibloc_jlg_common_cookies', $cookies );

    $sanitized = [];

    foreach ( (array) $cookies as $cookie ) {
        if ( ! is_array( $cookie ) ) {
            continue;
        }

        $value = isset( $cookie['value'] ) ? (string) $cookie['value'] : '';

        if ( '' === $value ) {
            continue;
        }

        $label = isset( $cookie['label'] ) ? (string) $cookie['label'] : $value;

        $sanitized[] = [
            'value' => $value,
            'label' => $label,
        ];
    }

    return $sanitized;
}

function visibloc_jlg_clear_editor_post_types_cache_on_change( $post_type = null, $args = [] ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    visibloc_jlg_clear_editor_data_cache( 'post_types' );
}

function visibloc_jlg_clear_editor_taxonomy_cache_on_change( $taxonomy = null ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    visibloc_jlg_clear_editor_data_cache( [ 'taxonomies', 'woocommerce_taxonomies' ] );
}

function visibloc_jlg_clear_editor_templates_cache_on_switch() {
    visibloc_jlg_clear_editor_data_cache( 'templates' );
}

function visibloc_jlg_clear_editor_role_groups_cache_on_change() {
    visibloc_jlg_clear_editor_data_cache( [ 'role_groups', 'roles' ] );
}

function visibloc_jlg_clear_editor_timezones_cache_on_change() {
    visibloc_jlg_clear_editor_data_cache( 'timezones' );
}

if ( function_exists( 'add_action' ) ) {
    add_action( 'registered_post_type', 'visibloc_jlg_clear_editor_post_types_cache_on_change', 100, 2 );
    add_action( 'unregistered_post_type', 'visibloc_jlg_clear_editor_post_types_cache_on_change', 100, 1 );
    add_action( 'registered_taxonomy', 'visibloc_jlg_clear_editor_taxonomy_cache_on_change', 100, 1 );
    add_action( 'unregistered_taxonomy', 'visibloc_jlg_clear_editor_taxonomy_cache_on_change', 100, 1 );
    add_action( 'created_term', 'visibloc_jlg_clear_editor_taxonomy_cache_on_change', 100, 3 );
    add_action( 'edited_term', 'visibloc_jlg_clear_editor_taxonomy_cache_on_change', 100, 3 );
    add_action( 'delete_term', 'visibloc_jlg_clear_editor_taxonomy_cache_on_change', 100, 4 );
    add_action( 'set_object_terms', 'visibloc_jlg_clear_editor_taxonomy_cache_on_change', 100, 6 );
    add_action( 'switch_theme', 'visibloc_jlg_clear_editor_templates_cache_on_switch' );
    add_action( 'after_switch_theme', 'visibloc_jlg_clear_editor_templates_cache_on_switch' );
    add_action( 'add_role', 'visibloc_jlg_clear_editor_role_groups_cache_on_change' );
    add_action( 'remove_role', 'visibloc_jlg_clear_editor_role_groups_cache_on_change' );
    add_action( 'set_user_role', 'visibloc_jlg_clear_editor_role_groups_cache_on_change', 100, 3 );
    add_action( 'update_option_timezone_string', 'visibloc_jlg_clear_editor_timezones_cache_on_change', 100, 0 );
    add_action( 'update_option_gmt_offset', 'visibloc_jlg_clear_editor_timezones_cache_on_change', 100, 0 );
}

function visibloc_jlg_flag_missing_editor_assets() {
    $transient_key = VISIBLOC_JLG_MISSING_EDITOR_ASSETS_TRANSIENT;

    if ( ! function_exists( 'get_transient' ) || ! function_exists( 'set_transient' ) ) {
        return;
    }

    if ( false !== get_transient( $transient_key ) ) {
        return;
    }

    set_transient( $transient_key, [
        'timestamp' => time(),
    ], 0 );

    if ( function_exists( 'error_log' ) ) {
        error_log( '[Visi-Bloc JLG] Les assets de l\'éditeur sont introuvables. Exécutez « npm install && npm run build ».' );
    }
}

function visibloc_jlg_clear_missing_editor_assets_flag() {
    if ( ! function_exists( 'delete_transient' ) ) {
        return;
    }

    delete_transient( VISIBLOC_JLG_MISSING_EDITOR_ASSETS_TRANSIENT );
}

function visibloc_jlg_render_missing_editor_assets_notice() {
    if ( ! function_exists( 'get_transient' ) ) {
        return;
    }

    $flag = get_transient( VISIBLOC_JLG_MISSING_EDITOR_ASSETS_TRANSIENT );

    if ( false === $flag ) {
        return;
    }

    if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $message = __( 'Les assets de l\'éditeur Visi-Bloc sont introuvables. Exécutez la commande suivante puis rechargez cette page :', 'visi-bloc-jlg' );
    $command = 'npm install && npm run build';

    echo '<div class="notice notice-error"><p>'
        . visibloc_jlg_escape_admin_notice_text( $message )
        . ' <code>'
        . visibloc_jlg_escape_admin_notice_text( $command )
        . '</code></p></div>';
}

function visibloc_jlg_escape_admin_notice_text( $text ) {
    if ( function_exists( 'esc_html' ) ) {
        return esc_html( $text );
    }

    return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

add_action( 'admin_notices', 'visibloc_jlg_render_missing_editor_assets_notice' );

function visibloc_jlg_build_device_visibility_css( $can_preview, $mobile_bp, $tablet_bp ) {
    $default_mobile_bp = 781;
    $default_tablet_bp = 1024;

    $mobile_bp = (int) $mobile_bp;
    $tablet_bp = (int) $tablet_bp;

    $css_lines      = [];
    $current_blocks = visibloc_jlg_build_visibility_blocks( $mobile_bp, $tablet_bp );

    $css_lines = array_merge(
        $css_lines,
        visibloc_jlg_render_visibility_blocks( $current_blocks, 'display: none !important;' )
    );

    $css_lines[] = '@media (orientation: portrait) {';
    $css_lines[] = '    .vb-hide-on-portrait,';
    $css_lines[] = '    .vb-landscape-only {';
    $css_lines[] = '        display: none !important;';
    $css_lines[] = '    }';
    $css_lines[] = '}';
    $css_lines[] = '@media (orientation: landscape) {';
    $css_lines[] = '    .vb-hide-on-landscape,';
    $css_lines[] = '    .vb-portrait-only {';
    $css_lines[] = '        display: none !important;';
    $css_lines[] = '    }';
    $css_lines[] = '}';

    $has_custom_breakpoints = ( $mobile_bp !== $default_mobile_bp ) || ( $tablet_bp !== $default_tablet_bp );

    if ( $has_custom_breakpoints ) {
        $default_blocks = visibloc_jlg_build_visibility_blocks( $default_mobile_bp, $default_tablet_bp );
        $reset_blocks   = visibloc_jlg_calculate_reset_blocks( $default_blocks, $current_blocks );

        if ( ! empty( $reset_blocks ) ) {
            $css_lines = array_merge(
                $css_lines,
                visibloc_jlg_render_visibility_blocks( $reset_blocks, 'display: revert !important;' )
            );
        }
    }

    if ( $can_preview ) {
        $preview_selectors = [
            '.vb-desktop-only',
            '.vb-tablet-only',
            '.vb-mobile-only',
            '.vb-hide-on-desktop',
            '.vb-hide-on-tablet',
            '.vb-hide-on-mobile',
            '.vb-portrait-only',
            '.vb-landscape-only',
            '.vb-hide-on-portrait',
            '.vb-hide-on-landscape',
        ];

        $selectors_without_pseudo = implode( ', ', $preview_selectors );
        $badge_selectors          = implode(
            ', ',
            array_map(
                static function ( $selector ) {
                    return $selector . ' > .visibloc-status-badge';
                },
                $preview_selectors
            )
        );

        $css_lines[] = '.visibloc-status-badge {';
        $css_lines[] = '    --visibloc-badge-bg: #f8fafc;';
        $css_lines[] = '    --visibloc-badge-border: #0f172a;';
        $css_lines[] = '    --visibloc-badge-foreground: #0f172a;';
        $css_lines[] = '    --visibloc-badge-pattern: none;';
        $css_lines[] = '    display: inline-flex;';
        $css_lines[] = '    align-items: center;';
        $css_lines[] = '    justify-content: center;';
        $css_lines[] = '    gap: 6px;';
        $css_lines[] = '    padding: 4px 12px;';
        $css_lines[] = '    border-radius: 999px;';
        $css_lines[] = '    border: 2px solid var(--visibloc-badge-border);';
        $css_lines[] = '    background-color: var(--visibloc-badge-bg);';
        $css_lines[] = '    background-image: var(--visibloc-badge-pattern);';
        $css_lines[] = '    background-size: 16px 16px;';
        $css_lines[] = '    color: var(--visibloc-badge-foreground);';
        $css_lines[] = '    font-size: 11px;';
        $css_lines[] = '    font-family: var(--wp-admin-font-family, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif);';
        $css_lines[] = '    font-weight: 700;';
        $css_lines[] = '    letter-spacing: 0.02em;';
        $css_lines[] = '    line-height: 1.4;';
        $css_lines[] = '    text-transform: uppercase;';
        $css_lines[] = '    box-shadow: none;';
        $css_lines[] = '}';
        $css_lines[] = '.visibloc-status-badge__icon {';
        $css_lines[] = '    display: inline-flex;';
        $css_lines[] = '    align-items: center;';
        $css_lines[] = '    justify-content: center;';
        $css_lines[] = '    width: 1.1em;';
        $css_lines[] = '    height: 1.1em;';
        $css_lines[] = '}';
        $css_lines[] = '.visibloc-status-badge__icon svg {';
        $css_lines[] = '    width: 100%;';
        $css_lines[] = '    height: 100%;';
        $css_lines[] = '    display: block;';
        $css_lines[] = '}';
        $css_lines[] = '.visibloc-status-badge__description {';
        $css_lines[] = '    display: block;';
        $css_lines[] = '    font-weight: 600;';
        $css_lines[] = '    letter-spacing: normal;';
        $css_lines[] = '    text-transform: none;';
        $css_lines[] = '}';
        $css_lines[] = '.visibloc-status-badge__label {';
        $css_lines[] = '    display: inline-block;';
        $css_lines[] = '}';
        $css_lines[] = '.visibloc-status-badge--hidden {';
        $css_lines[] = '    --visibloc-badge-bg: rgba(239, 68, 68, 0.14);';
        $css_lines[] = '    --visibloc-badge-border: #b91c1c;';
        $css_lines[] = '    --visibloc-badge-foreground: #7f1d1d;';
        $css_lines[] = '    --visibloc-badge-pattern: repeating-linear-gradient(135deg, rgba(185, 28, 28, 0.2) 0, rgba(185, 28, 28, 0.2) 6px, transparent 6px, transparent 12px);';
        $css_lines[] = '}';
        $css_lines[] = '.visibloc-status-badge--schedule {';
        $css_lines[] = '    --visibloc-badge-bg: rgba(22, 163, 74, 0.16);';
        $css_lines[] = '    --visibloc-badge-border: #15803d;';
        $css_lines[] = '    --visibloc-badge-foreground: #166534;';
        $css_lines[] = '    --visibloc-badge-pattern: repeating-linear-gradient(135deg, rgba(34, 197, 94, 0.18) 0, rgba(34, 197, 94, 0.18) 8px, transparent 8px, transparent 16px);';
        $css_lines[] = '}';
        $css_lines[] = '.visibloc-status-badge--schedule-error {';
        $css_lines[] = '    --visibloc-badge-bg: rgba(239, 68, 68, 0.24);';
        $css_lines[] = '    --visibloc-badge-border: #991b1b;';
        $css_lines[] = '    --visibloc-badge-foreground: #7f1d1d;';
        $css_lines[] = '    --visibloc-badge-pattern: repeating-linear-gradient(45deg, rgba(153, 27, 27, 0.28) 0, rgba(153, 27, 27, 0.28) 5px, transparent 5px, transparent 10px);';
        $css_lines[] = '}';
        $css_lines[] = '.visibloc-status-badge--advanced {';
        $css_lines[] = '    --visibloc-badge-bg: rgba(147, 51, 234, 0.18);';
        $css_lines[] = '    --visibloc-badge-border: #7c3aed;';
        $css_lines[] = '    --visibloc-badge-foreground: #5b21b6;';
        $css_lines[] = '    --visibloc-badge-pattern: repeating-linear-gradient(135deg, rgba(124, 58, 237, 0.18) 0, rgba(124, 58, 237, 0.18) 7px, transparent 7px, transparent 14px);';
        $css_lines[] = '}';
        $css_lines[] = '.visibloc-status-badge--fallback {';
        $css_lines[] = '    --visibloc-badge-bg: rgba(30, 64, 175, 0.16);';
        $css_lines[] = '    --visibloc-badge-border: #1d4ed8;';
        $css_lines[] = '    --visibloc-badge-foreground: #1e3a8a;';
        $css_lines[] = '    --visibloc-badge-pattern: repeating-linear-gradient(135deg, rgba(37, 99, 235, 0.16) 0, rgba(37, 99, 235, 0.16) 10px, transparent 10px, transparent 20px);';
        $css_lines[] = '}';
        $css_lines[] = '@media (prefers-contrast: more) {';
        $css_lines[] = '    .visibloc-status-badge {';
        $css_lines[] = '        border-width: 3px;';
        $css_lines[] = '        background-image: none !important;';
        $css_lines[] = '        box-shadow: inset 0 0 0 1px currentColor;';
        $css_lines[] = '    }';
        $css_lines[] = '}';
        $css_lines[] = '@media (prefers-color-scheme: dark) {';
        $css_lines[] = '    .visibloc-status-badge {';
        $css_lines[] = '        --visibloc-badge-bg: rgba(148, 163, 184, 0.26);';
        $css_lines[] = '        --visibloc-badge-border: #cbd5f5;';
        $css_lines[] = '        --visibloc-badge-foreground: #e2e8f0;';
        $css_lines[] = '    }';
        $css_lines[] = '    .visibloc-status-badge--hidden {';
        $css_lines[] = '        --visibloc-badge-foreground: #fecaca;';
        $css_lines[] = '        --visibloc-badge-border: #f87171;';
        $css_lines[] = '    }';
        $css_lines[] = '    .visibloc-status-badge--schedule {';
        $css_lines[] = '        --visibloc-badge-foreground: #bbf7d0;';
        $css_lines[] = '        --visibloc-badge-border: #4ade80;';
        $css_lines[] = '    }';
        $css_lines[] = '    .visibloc-status-badge--schedule-error {';
        $css_lines[] = '        --visibloc-badge-foreground: #fecaca;';
        $css_lines[] = '        --visibloc-badge-border: #f87171;';
        $css_lines[] = '    }';
        $css_lines[] = '    .visibloc-status-badge--advanced {';
        $css_lines[] = '        --visibloc-badge-foreground: #ddd6fe;';
        $css_lines[] = '        --visibloc-badge-border: #c4b5fd;';
        $css_lines[] = '    }';
        $css_lines[] = '    .visibloc-status-badge--fallback {';
        $css_lines[] = '        --visibloc-badge-foreground: #bfdbfe;';
        $css_lines[] = '        --visibloc-badge-border: #93c5fd;';
        $css_lines[] = '    }';
        $css_lines[] = '}';

        $css_lines[] = sprintf(
            '%s { position: relative; outline: 2px dashed #0073aa; outline-offset: 2px; }',
            $selectors_without_pseudo
        );

        $css_lines[] = sprintf(
            '%s { position: absolute; bottom: -2px; right: -2px; padding: 2px 8px; }',
            $badge_selectors
        );
        $css_lines[] = '.vb-label-top > .visibloc-status-badge { bottom: auto; top: -2px; transform: translateY(-100%); border-radius: 3px 3px 0 0; }';

        $css_lines[] = '@media (max-width: 782px) {';
        $css_lines[] = sprintf(
            '    %s { position: static; display: inline-flex; margin: 0 0 8px; right: auto; bottom: auto; left: auto; top: auto; transform: none; border-radius: 999px; padding: 4px 12px; box-shadow: none; }',
            $badge_selectors
        );
        $css_lines[] = '    .vb-label-top > .visibloc-status-badge { margin-bottom: 8px; }';
        $css_lines[] = '}';
    }

    return implode( "\n", $css_lines );
}

/**
 * Generates the device visibility CSS while orchestrating the caching layer.
 *
 * The pure CSS construction is delegated to visibloc_jlg_build_device_visibility_css().
 * Cache entries are cleared via visibloc_jlg_clear_caches() and naturally expire thanks to
 * the transient timeout defined below.
 */
function visibloc_jlg_generate_device_visibility_css( $can_preview, $mobile_bp = null, $tablet_bp = null ) {
    $default_mobile_bp = 781;
    $default_tablet_bp = 1024;

    if ( null === $mobile_bp ) {
        $mobile_bp = absint( get_option( 'visibloc_breakpoint_mobile', $default_mobile_bp ) );
    }

    if ( null === $tablet_bp ) {
        $tablet_bp = absint( get_option( 'visibloc_breakpoint_tablet', $default_tablet_bp ) );
    }

    $mobile_bp = $mobile_bp > 0 ? $mobile_bp : $default_mobile_bp;
    $tablet_bp = $tablet_bp > 0 ? $tablet_bp : $default_tablet_bp;

    $cache_group = VISIBLOC_JLG_DEVICE_CSS_CACHE_GROUP;
    $cache_key   = VISIBLOC_JLG_DEVICE_CSS_CACHE_KEY;
    $version     = visibloc_jlg_get_plugin_version();
    $bucket_key  = sprintf(
        '%s:%d:%d:%d',
        $version,
        $can_preview ? 1 : 0,
        (int) $mobile_bp,
        (int) $tablet_bp
    );

    $transient_key = VISIBLOC_JLG_DEVICE_CSS_TRANSIENT_PREFIX . $bucket_key;

    if ( function_exists( 'get_transient' ) ) {
        $transient_css = get_transient( $transient_key );

        if ( false !== $transient_css ) {
            return $transient_css;
        }
    }

    $cached_css = wp_cache_get( $cache_key, $cache_group );

    if ( is_array( $cached_css ) && array_key_exists( $bucket_key, $cached_css ) ) {
        return $cached_css[ $bucket_key ];
    }

    $css = visibloc_jlg_build_device_visibility_css( $can_preview, $mobile_bp, $tablet_bp );

    if ( ! is_array( $cached_css ) ) {
        $cached_css = [];
    }

    $cached_css[ $bucket_key ] = $css;

    if ( function_exists( 'set_transient' ) ) {
        // The transient expiration prevents stale CSS in case visibloc_jlg_clear_caches() is not triggered.
        set_transient( $transient_key, $css, VISIBLOC_JLG_DEVICE_CSS_TRANSIENT_EXPIRATION );
    }

    visibloc_jlg_register_device_css_bucket( $bucket_key );

    wp_cache_set( $cache_key, $cached_css, $cache_group );

    return $css;
}

/**
 * Keep track of generated CSS buckets so visibloc_jlg_clear_caches() can remove them.
 *
 * @param string $bucket_key Unique bucket identifier.
 * @return void
 */
function visibloc_jlg_register_device_css_bucket( $bucket_key ) {
    if ( ! function_exists( 'get_option' ) || ! function_exists( 'update_option' ) ) {
        return;
    }

    $registered_buckets = get_option( VISIBLOC_JLG_DEVICE_CSS_BUCKET_OPTION, [] );

    if ( ! is_array( $registered_buckets ) ) {
        $registered_buckets = [];
    }

    if ( in_array( $bucket_key, $registered_buckets, true ) ) {
        return;
    }

    $registered_buckets[] = $bucket_key;
    update_option( VISIBLOC_JLG_DEVICE_CSS_BUCKET_OPTION, $registered_buckets );
}

function visibloc_jlg_build_visibility_blocks( $mobile_bp, $tablet_bp ) {
    $mobile_bp = (int) $mobile_bp;
    $tablet_bp = (int) $tablet_bp;

    $has_valid_tablet = $tablet_bp > $mobile_bp;
    $blocks           = [
        [
            'min'       => null,
            'max'       => $mobile_bp,
            'selectors' => [
                '.vb-hide-on-mobile',
                '.vb-tablet-only',
                '.vb-desktop-only',
            ],
        ],
    ];

    if ( $has_valid_tablet ) {
        $blocks[] = [
            'min'       => $mobile_bp + 1,
            'max'       => $tablet_bp,
            'selectors' => [
                '.vb-hide-on-tablet',
                '.vb-mobile-only',
                '.vb-desktop-only',
            ],
        ];
    }

    $desktop_reference = $has_valid_tablet ? $tablet_bp : $mobile_bp;

    $blocks[] = [
        'min'       => $desktop_reference + 1,
        'max'       => null,
        'selectors' => [
            '.vb-hide-on-desktop',
            '.vb-mobile-only',
            '.vb-tablet-only',
        ],
    ];

    return $blocks;
}

function visibloc_jlg_render_visibility_blocks( array $blocks, $declaration ) {
    $css_lines = [];

    foreach ( $blocks as $block ) {
        if ( empty( $block['selectors'] ) ) {
            continue;
        }

        $min = isset( $block['min'] ) ? (int) $block['min'] : null;
        $max = isset( $block['max'] ) ? (int) $block['max'] : null;

        if ( isset( $min, $max ) && $min > $max ) {
            continue;
        }

        $media_query = visibloc_jlg_format_media_query( $min, $max );

        if ( null === $media_query ) {
            continue;
        }

        $css_lines[] = $media_query;

        $selector_count = count( $block['selectors'] );

        foreach ( $block['selectors'] as $index => $selector ) {
            $is_last = ( $index === $selector_count - 1 );

            if ( ! $is_last ) {
                $css_lines[] = sprintf( '    %s,', $selector );
                continue;
            }

            $css_lines[] = sprintf( '    %s {', $selector );

            $declarations = visibloc_jlg_normalize_block_declarations( $selector, $declaration );

            foreach ( $declarations as $line ) {
                $css_lines[] = sprintf( '        %s', $line );
            }

            $css_lines[] = '    }';
        }

        $css_lines[] = '}';
    }

    return $css_lines;
}

function visibloc_jlg_normalize_block_declarations( $selector, $declaration ) {
    $declarations = is_array( $declaration ) ? $declaration : [ $declaration ];
    $normalized   = [];

    foreach ( $declarations as $value ) {
        $trimmed = trim( (string) $value );

        if ( '' === $trimmed ) {
            continue;
        }

        if ( ';' !== substr( $trimmed, -1 ) ) {
            $trimmed .= ';';
        }

        $normalized[] = $trimmed;
    }

    $requires_fallback = false;

    foreach ( $normalized as $value ) {
        if ( false !== stripos( $value, 'display: revert' ) ) {
            $requires_fallback = true;
            break;
        }
    }

    if ( $requires_fallback ) {
        $fallback = visibloc_jlg_get_display_fallback_for_selector( $selector );

        if ( null !== $fallback ) {
            $fallback = trim( $fallback );

            if ( ';' !== substr( $fallback, -1 ) ) {
                $fallback .= ';';
            }

            $normalized = array_values(
                array_filter(
                    $normalized,
                    static function ( $value ) use ( $fallback ) {
                        return 0 !== strcasecmp( trim( $value ), $fallback );
                    }
                )
            );

            array_unshift( $normalized, $fallback );
        }
    }

    return $normalized;
}

function visibloc_jlg_get_display_fallback_for_selector( $selector ) {
    if ( 0 !== strpos( $selector, '.vb-' ) ) {
        return null;
    }

    $fallback = 'display: block !important;';

    if ( false !== strpos( $selector, '-only' ) ) {
        return $fallback;
    }

    if ( preg_match( '/^\\.vb-hide-on-(mobile|tablet|desktop|portrait|landscape)$/', $selector ) ) {
        return $fallback;
    }

    return null;
}

function visibloc_jlg_format_media_query( $min, $max ) {
    if ( isset( $min ) && isset( $max ) ) {
        return sprintf( '@media (min-width: %1$dpx) and (max-width: %2$dpx) {', $min, $max );
    }

    if ( isset( $min ) ) {
        return sprintf( '@media (min-width: %dpx) {', $min );
    }

    if ( isset( $max ) ) {
        return sprintf( '@media (max-width: %dpx) {', $max );
    }

    return null;
}

function visibloc_jlg_calculate_reset_blocks( array $default_blocks, array $current_blocks ) {
    $default_ranges = visibloc_jlg_collect_selector_ranges( $default_blocks );
    $current_ranges = visibloc_jlg_collect_selector_ranges( $current_blocks );
    $reset_blocks   = [];

    foreach ( $default_ranges as $selector => $ranges ) {
        $current = $current_ranges[ $selector ] ?? [];

        foreach ( $ranges as $range ) {
            $differences = visibloc_jlg_range_difference( $range, $current );

            foreach ( $differences as $difference ) {
                $key = sprintf(
                    '%s:%s',
                    isset( $difference['min'] ) ? $difference['min'] : '',
                    isset( $difference['max'] ) ? $difference['max'] : ''
                );

                if ( ! isset( $reset_blocks[ $key ] ) ) {
                    $reset_blocks[ $key ] = [
                        'min'       => isset( $difference['min'] ) ? $difference['min'] : null,
                        'max'       => isset( $difference['max'] ) ? $difference['max'] : null,
                        'selectors' => [],
                    ];
                }

                if ( ! in_array( $selector, $reset_blocks[ $key ]['selectors'], true ) ) {
                    $reset_blocks[ $key ]['selectors'][] = $selector;
                }
            }
        }
    }

    if ( empty( $reset_blocks ) ) {
        return [];
    }

    $reset_blocks = array_values( $reset_blocks );
    usort( $reset_blocks, 'visibloc_jlg_compare_blocks' );

    return $reset_blocks;
}

function visibloc_jlg_collect_selector_ranges( array $blocks ) {
    $ranges = [];

    foreach ( $blocks as $block ) {
        $range = [
            'min' => isset( $block['min'] ) ? (int) $block['min'] : null,
            'max' => isset( $block['max'] ) ? (int) $block['max'] : null,
        ];

        foreach ( $block['selectors'] as $selector ) {
            if ( ! isset( $ranges[ $selector ] ) ) {
                $ranges[ $selector ] = [];
            }

            $ranges[ $selector ][] = $range;
        }
    }

    return $ranges;
}

function visibloc_jlg_range_difference( array $range, array $sub_ranges ) {
    if ( empty( $sub_ranges ) ) {
        return [ $range ];
    }

    $segments = [ $range ];
    usort( $sub_ranges, 'visibloc_jlg_compare_ranges' );

    foreach ( $sub_ranges as $sub_range ) {
        $next_segments = [];

        foreach ( $segments as $segment ) {
            $parts = visibloc_jlg_subtract_segment( $segment, $sub_range );

            foreach ( $parts as $part ) {
                if ( ! isset( $part['min'] ) && ! isset( $part['max'] ) ) {
                    continue;
                }

                if ( isset( $part['min'], $part['max'] ) && $part['min'] > $part['max'] ) {
                    continue;
                }

                $next_segments[] = [
                    'min' => isset( $part['min'] ) ? (int) $part['min'] : null,
                    'max' => isset( $part['max'] ) ? (int) $part['max'] : null,
                ];
            }
        }

        $segments = $next_segments;

        if ( empty( $segments ) ) {
            break;
        }
    }

    return $segments;
}

function visibloc_jlg_subtract_segment( array $segment, array $sub_range ) {
    $segment_min = isset( $segment['min'] ) ? (int) $segment['min'] : PHP_INT_MIN;
    $segment_max = isset( $segment['max'] ) ? (int) $segment['max'] : PHP_INT_MAX;
    $sub_min     = isset( $sub_range['min'] ) ? (int) $sub_range['min'] : PHP_INT_MIN;
    $sub_max     = isset( $sub_range['max'] ) ? (int) $sub_range['max'] : PHP_INT_MAX;

    if ( $sub_max < $segment_min || $sub_min > $segment_max ) {
        return [ $segment ];
    }

    $results = [];

    if ( $sub_min > $segment_min ) {
        $results[] = [
            'min' => isset( $segment['min'] ) ? (int) $segment['min'] : null,
            'max' => isset( $sub_range['min'] ) ? ( (int) $sub_range['min'] - 1 ) : null,
        ];
    }

    if ( $sub_max < $segment_max ) {
        $results[] = [
            'min' => isset( $sub_range['max'] ) ? ( (int) $sub_range['max'] + 1 ) : null,
            'max' => isset( $segment['max'] ) ? (int) $segment['max'] : null,
        ];
    }

    return array_values(
        array_filter(
            $results,
            static function ( $candidate ) {
                if ( ! isset( $candidate['min'] ) && ! isset( $candidate['max'] ) ) {
                    return false;
                }

                if ( isset( $candidate['min'], $candidate['max'] ) && $candidate['min'] > $candidate['max'] ) {
                    return false;
                }

                return true;
            }
        )
    );
}

function visibloc_jlg_compare_blocks( $a, $b ) {
    return visibloc_jlg_compare_ranges( $a, $b );
}

function visibloc_jlg_compare_ranges( $a, $b ) {
    $a_min = isset( $a['min'] ) ? (int) $a['min'] : PHP_INT_MIN;
    $b_min = isset( $b['min'] ) ? (int) $b['min'] : PHP_INT_MIN;

    if ( $a_min === $b_min ) {
        $a_max = isset( $a['max'] ) ? (int) $a['max'] : PHP_INT_MAX;
        $b_max = isset( $b['max'] ) ? (int) $b['max'] : PHP_INT_MAX;

        if ( $a_max === $b_max ) {
            return 0;
        }

        return ( $a_max < $b_max ) ? -1 : 1;
    }

    return ( $a_min < $b_min ) ? -1 : 1;
}

