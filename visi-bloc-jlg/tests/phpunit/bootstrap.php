<?php
/**
 * Bootstrap for integration tests.
 */

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../../' );
}

if ( ! defined( 'WPINC' ) ) {
    define( 'WPINC', 'wp-includes' );
}

$GLOBALS['visibloc_posts']            = [];
$GLOBALS['visibloc_test_options']     = [];
$GLOBALS['visibloc_test_transients']  = [];
$GLOBALS['visibloc_test_post_types']  = [];
$GLOBALS['visibloc_test_taxonomies']  = [];
$GLOBALS['visibloc_test_terms']       = [];
$GLOBALS['visibloc_test_page_templates'] = [];
$GLOBALS['visibloc_test_woocommerce_attribute_taxonomies'] = [];
$GLOBALS['visibloc_test_counters']    = [];
$GLOBALS['visibloc_test_stats']        = [
    'get_posts_calls' => 0,
];

$GLOBALS['visibloc_test_request_environment'] = [
    'is_admin'    => false,
    'doing_ajax'  => false,
    'doing_cron'  => false,
    'referer'     => '',
    'request_uri' => '',
];

if ( ! class_exists( 'WP_Post' ) ) {
    #[AllowDynamicProperties]
    class WP_Post {
        public function __construct( array $data = [] ) {
            foreach ( $data as $key => $value ) {
                $this->{$key} = $value;
            }
        }
    }
}

if ( ! class_exists( 'WP_Term' ) ) {
    #[AllowDynamicProperties]
    class WP_Term {
        public function __construct( array $data = [] ) {
            foreach ( $data as $key => $value ) {
                $this->{$key} = $value;
            }
        }
    }
}

if ( ! class_exists( 'WP_Theme' ) ) {
    #[AllowDynamicProperties]
    class WP_Theme {
        public function get_page_templates() {
            $templates = $GLOBALS['visibloc_test_page_templates'] ?? [];

            return is_array( $templates ) ? $templates : [];
        }
    }
}

function visibloc_test_reset_request_environment() {
    $GLOBALS['visibloc_test_request_environment'] = [
        'is_admin'    => false,
        'doing_ajax'  => false,
        'doing_cron'  => false,
        'referer'     => '',
        'request_uri' => '',
    ];

    $_GET  = [];
    $_POST = [];

    if ( isset( $_SERVER['REQUEST_URI'] ) ) {
        unset( $_SERVER['REQUEST_URI'] );
    }
}

function visibloc_test_set_request_environment( array $overrides ) {
    $state = array_merge( $GLOBALS['visibloc_test_request_environment'], $overrides );

    $GLOBALS['visibloc_test_request_environment'] = $state;

    if ( array_key_exists( 'request_uri', $overrides ) ) {
        if ( '' === $state['request_uri'] ) {
            if ( isset( $_SERVER['REQUEST_URI'] ) ) {
                unset( $_SERVER['REQUEST_URI'] );
            }
        } else {
            $_SERVER['REQUEST_URI'] = $state['request_uri'];
        }
    }
}

function visibloc_test_track_counter( $key ) {
    $key = (string) $key;

    if ( '' === $key ) {
        return;
    }

    if ( ! isset( $GLOBALS['visibloc_test_counters'][ $key ] ) ) {
        $GLOBALS['visibloc_test_counters'][ $key ] = 0;
    }

    $GLOBALS['visibloc_test_counters'][ $key ]++;
}

if ( ! function_exists( 'is_admin' ) ) {
    function is_admin() {
        return ! empty( $GLOBALS['visibloc_test_request_environment']['is_admin'] );
    }
}

if ( ! function_exists( 'wp_doing_ajax' ) ) {
    function wp_doing_ajax() {
        return ! empty( $GLOBALS['visibloc_test_request_environment']['doing_ajax'] );
    }
}

if ( ! function_exists( 'wp_doing_cron' ) ) {
    function wp_doing_cron() {
        return ! empty( $GLOBALS['visibloc_test_request_environment']['doing_cron'] );
    }
}

if ( ! function_exists( 'wp_get_referer' ) ) {
    function wp_get_referer() {
        return $GLOBALS['visibloc_test_request_environment']['referer'] ?? '';
    }
}

if ( ! function_exists( 'admin_url' ) ) {
    function admin_url( $path = '' ) {
        $path = ltrim( (string) $path, '/' );

        return 'https://example.test/wp-admin/' . ( '' === $path ? '' : $path );
    }
}

visibloc_test_reset_request_environment();

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
    define( 'HOUR_IN_SECONDS', 3600 );
}

$GLOBALS['visibloc_test_state'] = [
    'effective_user_id'       => 0,
    'can_preview_users'       => [],
    'can_impersonate_users'   => [],
    'allowed_preview_roles'   => [ 'administrator' ],
    'preview_role'            => '',
    'current_user'            => null,
    'roles'                   => [
        'administrator' => (object) [ 'name' => 'Administrator', 'capabilities' => [] ],
        'editor'        => (object) [ 'name' => 'Editor', 'capabilities' => [] ],
        'guest'         => (object) [ 'name' => 'Guest', 'capabilities' => [] ],
    ],
    'timezone'                => 'UTC',
    'current_time'            => null,
];

function visibloc_test_reset_state() {
    $GLOBALS['visibloc_test_state'] = [
        'effective_user_id'       => 0,
        'can_preview_users'       => [],
        'can_impersonate_users'   => [],
        'allowed_preview_roles'   => [ 'administrator' ],
        'preview_role'            => '',
        'current_user'            => null,
        'roles'                   => [
            'administrator' => (object) [ 'name' => 'Administrator', 'capabilities' => [] ],
            'editor'        => (object) [ 'name' => 'Editor', 'capabilities' => [] ],
            'guest'         => (object) [ 'name' => 'Guest', 'capabilities' => [] ],
        ],
        'timezone'                => 'UTC',
        'current_time'            => null,
    ];

    $GLOBALS['visibloc_posts']           = [];
    $GLOBALS['visibloc_test_options']    = [];
    $GLOBALS['visibloc_test_transients'] = [];
    $GLOBALS['visibloc_test_object_cache'] = [];
    $GLOBALS['visibloc_test_post_types']  = [];
    $GLOBALS['visibloc_test_taxonomies']  = [];
    $GLOBALS['visibloc_test_terms']       = [];
    $GLOBALS['visibloc_test_page_templates'] = [];
    $GLOBALS['visibloc_test_woocommerce_attribute_taxonomies'] = [];
    $GLOBALS['visibloc_test_counters']    = [];
    $GLOBALS['visibloc_test_stats']      = [
        'get_posts_calls' => 0,
    ];

    visibloc_test_reset_request_environment();

    if ( function_exists( 'visibloc_jlg_get_fallback_settings' ) ) {
        visibloc_jlg_get_fallback_settings( true );
    }

    if ( function_exists( 'visibloc_jlg_get_global_fallback_markup' ) ) {
        visibloc_jlg_get_global_fallback_markup( true );
    }

    if ( function_exists( 'visibloc_jlg_get_fallback_blocks_cache_version' ) ) {
        visibloc_jlg_get_fallback_blocks_cache_version( true );
    }

    if ( function_exists( 'visibloc_jlg_get_user_visibility_context' ) ) {
        $preview_cache_reset = false;
        visibloc_jlg_get_user_visibility_context( [], $preview_cache_reset, true );
    }

    if ( function_exists( 'visibloc_jlg_clear_editor_data_cache' ) ) {
        visibloc_jlg_clear_editor_data_cache();
    }

    if ( function_exists( 'visibloc_jlg_flush_asset_versions_cache' ) ) {
        visibloc_jlg_flush_asset_versions_cache();
    }
}

if ( ! function_exists( 'get_post_types' ) ) {
    function get_post_types( $args = [], $output = 'names' ) {
        global $visibloc_test_post_types;

        visibloc_test_track_counter( 'get_post_types' );

        $definitions = is_array( $visibloc_test_post_types ?? null ) ? $visibloc_test_post_types : [];
        $filtered     = [];

        foreach ( $definitions as $slug => $definition ) {
            $definition_args   = isset( $definition['args'] ) && is_array( $definition['args'] ) ? $definition['args'] : [];
            $definition_object = $definition['object'] ?? (object) [];
            $matches           = true;

            foreach ( (array) $args as $key => $value ) {
                if ( ! array_key_exists( $key, $definition_args ) || $definition_args[ $key ] !== $value ) {
                    $matches = false;
                    break;
                }
            }

            if ( ! $matches ) {
                continue;
            }

            if ( 'objects' === $output ) {
                $filtered[ $slug ] = $definition_object;
            } else {
                $filtered[] = $slug;
            }
        }

        return $filtered;
    }
}

if ( ! function_exists( 'get_taxonomies' ) ) {
    function get_taxonomies( $args = [], $output = 'names' ) {
        global $visibloc_test_taxonomies;

        visibloc_test_track_counter( 'get_taxonomies' );

        $definitions = is_array( $visibloc_test_taxonomies ?? null ) ? $visibloc_test_taxonomies : [];
        $filtered    = [];

        foreach ( $definitions as $slug => $definition ) {
            $definition_args   = isset( $definition['args'] ) && is_array( $definition['args'] ) ? $definition['args'] : [];
            $definition_object = $definition['object'] ?? (object) [];
            $matches           = true;

            foreach ( (array) $args as $key => $value ) {
                if ( ! array_key_exists( $key, $definition_args ) || $definition_args[ $key ] !== $value ) {
                    $matches = false;
                    break;
                }
            }

            if ( ! $matches ) {
                continue;
            }

            if ( 'objects' === $output ) {
                $filtered[ $slug ] = $definition_object;
            } else {
                $filtered[] = $slug;
            }
        }

        return $filtered;
    }
}

if ( ! function_exists( 'taxonomy_exists' ) ) {
    function taxonomy_exists( $taxonomy ) {
        global $visibloc_test_taxonomies;

        $definitions = is_array( $visibloc_test_taxonomies ?? null ) ? $visibloc_test_taxonomies : [];

        return array_key_exists( $taxonomy, $definitions );
    }
}

if ( ! function_exists( 'get_terms' ) ) {
    function get_terms( $args = [] ) {
        global $visibloc_test_terms;

        visibloc_test_track_counter( 'get_terms' );

        if ( ! is_array( $args ) ) {
            return [];
        }

        $taxonomy = $args['taxonomy'] ?? '';
        $limit    = isset( $args['number'] ) ? (int) $args['number'] : 0;
        $order    = strtoupper( $args['order'] ?? 'ASC' );
        $orderby  = $args['orderby'] ?? 'name';

        $registered_terms = $visibloc_test_terms[ $taxonomy ] ?? [];
        $items            = [];

        foreach ( $registered_terms as $term ) {
            $items[] = new WP_Term(
                [
                    'term_id' => $term['term_id'] ?? 0,
                    'name'    => $term['name'] ?? '',
                    'slug'    => $term['slug'] ?? '',
                ]
            );
        }

        $comparator = static function ( WP_Term $a, WP_Term $b ) use ( $orderby ) {
            $value_a = $a->{$orderby} ?? '';
            $value_b = $b->{$orderby} ?? '';

            return strcasecmp( (string) $value_a, (string) $value_b );
        };

        usort( $items, $comparator );

        if ( 'DESC' === $order ) {
            $items = array_reverse( $items );
        }

        if ( $limit > 0 ) {
            $items = array_slice( $items, 0, $limit );
        }

        return $items;
    }
}

if ( ! function_exists( 'wp_get_theme' ) ) {
    function wp_get_theme() {
        return new WP_Theme();
    }
}

if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
    function wc_get_attribute_taxonomies() {
        visibloc_test_track_counter( 'wc_get_attribute_taxonomies' );

        return is_array( $GLOBALS['visibloc_test_woocommerce_attribute_taxonomies'] ?? null )
            ? $GLOBALS['visibloc_test_woocommerce_attribute_taxonomies']
            : [];
    }
}

if ( ! function_exists( 'wc_attribute_taxonomy_name' ) ) {
    function wc_attribute_taxonomy_name( $taxonomy ) {
        $taxonomy = (string) $taxonomy;

        if ( '' === $taxonomy ) {
            return '';
        }

        if ( 0 === strpos( $taxonomy, 'pa_' ) ) {
            return $taxonomy;
        }

        return 'pa_' . $taxonomy;
    }
}

function visibloc_test_set_timezone( $timezone_string ) {
    $GLOBALS['visibloc_test_state']['timezone'] = $timezone_string;
}

function wp_timezone() {
    $timezone_string = $GLOBALS['visibloc_test_state']['timezone'] ?? 'UTC';

    try {
        return new DateTimeZone( $timezone_string );
    } catch ( Exception $e ) {
        return new DateTimeZone( 'UTC' );
    }
}

function visibloc_test_set_current_time( $timestamp ) {
    $GLOBALS['visibloc_test_state']['current_time'] = is_numeric( $timestamp ) ? (int) $timestamp : null;
}

function visibloc_test_get_current_time() {
    if ( isset( $GLOBALS['visibloc_test_state']['current_time'] ) && null !== $GLOBALS['visibloc_test_state']['current_time'] ) {
        return (int) $GLOBALS['visibloc_test_state']['current_time'];
    }

    return time();
}

function visibloc_test_get_timezone_offset( $timestamp ) {
    $timezone = wp_timezone();

    $datetime = ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $timezone );

    return $datetime->getOffset();
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
    // No-op for tests.
}

$GLOBALS['visibloc_test_filters'] = [];

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
    if ( ! is_string( $hook ) || '' === $hook ) {
        return;
    }

    if ( ! is_callable( $callback ) ) {
        return;
    }

    $priority      = is_numeric( $priority ) ? (int) $priority : 10;
    $accepted_args = is_numeric( $accepted_args ) ? (int) $accepted_args : 1;
    $accepted_args = max( 1, $accepted_args );

    if ( ! isset( $GLOBALS['visibloc_test_filters'][ $hook ] ) ) {
        $GLOBALS['visibloc_test_filters'][ $hook ] = [];
    }

    if ( ! isset( $GLOBALS['visibloc_test_filters'][ $hook ][ $priority ] ) ) {
        $GLOBALS['visibloc_test_filters'][ $hook ][ $priority ] = [];
    }

    $GLOBALS['visibloc_test_filters'][ $hook ][ $priority ][] = [
        'callback'      => $callback,
        'accepted_args' => $accepted_args,
    ];
}

function wp_register_style( $handle, $src = '', $deps = [], $ver = false, $media = 'all' ) {
    // No-op for tests.
}

function wp_enqueue_style( $handle, $src = '', $deps = [], $ver = false, $media = 'all' ) {
    // No-op for tests.
}

function wp_add_inline_style( $handle, $data ) {
    // No-op for tests.
}

function apply_filters( $hook, $value ) {
    $args = func_get_args();

    if ( ! isset( $GLOBALS['visibloc_test_filters'][ $hook ] ) ) {
        return $value;
    }

    ksort( $GLOBALS['visibloc_test_filters'][ $hook ] );

    $filtered_value = $value;
    $args[1]        = $filtered_value;

    foreach ( $GLOBALS['visibloc_test_filters'][ $hook ] as $callbacks ) {
        foreach ( $callbacks as $callback_details ) {
            $callback_args = array_slice( $args, 1, $callback_details['accepted_args'] );

            if ( empty( $callback_args ) ) {
                $callback_args = [ $filtered_value ];
            } else {
                $callback_args[0] = $filtered_value;
            }

            $filtered_value = call_user_func_array( $callback_details['callback'], $callback_args );
            $args[1]        = $filtered_value;
        }
    }

    return $filtered_value;
}

function remove_all_filters( $hook, $priority = false ) {
    if ( ! isset( $GLOBALS['visibloc_test_filters'][ $hook ] ) ) {
        return;
    }

    if ( false === $priority ) {
        unset( $GLOBALS['visibloc_test_filters'][ $hook ] );

        return;
    }

    $priority = (int) $priority;

    if ( isset( $GLOBALS['visibloc_test_filters'][ $hook ][ $priority ] ) ) {
        unset( $GLOBALS['visibloc_test_filters'][ $hook ][ $priority ] );
    }

    if ( empty( $GLOBALS['visibloc_test_filters'][ $hook ] ) ) {
        unset( $GLOBALS['visibloc_test_filters'][ $hook ] );
    }
}

function remove_filter( $hook, $callback, $priority = 10 ) {
    if ( ! isset( $GLOBALS['visibloc_test_filters'][ $hook ] ) ) {
        return;
    }

    $priority = is_numeric( $priority ) ? (int) $priority : 10;

    if ( ! isset( $GLOBALS['visibloc_test_filters'][ $hook ][ $priority ] ) ) {
        return;
    }

    foreach ( $GLOBALS['visibloc_test_filters'][ $hook ][ $priority ] as $index => $details ) {
        if ( $details['callback'] === $callback ) {
            unset( $GLOBALS['visibloc_test_filters'][ $hook ][ $priority ][ $index ] );
        }
    }

    if ( empty( $GLOBALS['visibloc_test_filters'][ $hook ][ $priority ] ) ) {
        unset( $GLOBALS['visibloc_test_filters'][ $hook ][ $priority ] );
    }

    if ( empty( $GLOBALS['visibloc_test_filters'][ $hook ] ) ) {
        unset( $GLOBALS['visibloc_test_filters'][ $hook ] );
    }
}

if ( ! function_exists( 'visibloc_jlg_get_sanitized_query_arg' ) ) {
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

function visibloc_jlg_get_effective_user_id() {
    return $GLOBALS['visibloc_test_state']['effective_user_id'];
}

function visibloc_jlg_is_user_allowed_to_impersonate( $user_id ) {
    return ! empty( $GLOBALS['visibloc_test_state']['can_impersonate_users'][ absint( $user_id ) ] );
}

function visibloc_jlg_is_user_allowed_to_preview( $user_id ) {
    return ! empty( $GLOBALS['visibloc_test_state']['can_preview_users'][ absint( $user_id ) ] );
}

function visibloc_jlg_get_allowed_preview_roles() {
    return $GLOBALS['visibloc_test_state']['allowed_preview_roles'];
}

function visibloc_jlg_get_preview_role_from_cookie() {
    if ( isset( $_COOKIE['visibloc_preview_role'] ) ) {
        $cookie_value = $_COOKIE['visibloc_preview_role'];

        if ( ! is_string( $cookie_value ) ) {
            return null;
        }

        return sanitize_key( $cookie_value );
    }

    $state_preview_role = $GLOBALS['visibloc_test_state']['preview_role'];

    if ( ! is_string( $state_preview_role ) || '' === $state_preview_role ) {
        return null;
    }

    return sanitize_key( $state_preview_role );
}

function visibloc_jlg_get_preview_runtime_context( $reset_cache = false ) {
    $state = $GLOBALS['visibloc_test_state'];

    $effective_user_id = absint( $state['effective_user_id'] );
    $can_impersonate   = ! empty( $state['can_impersonate_users'][ $effective_user_id ] );
    $can_preview       = ! empty( $state['can_preview_users'][ $effective_user_id ] );
    $allowed_roles     = (array) $state['allowed_preview_roles'];
    $raw_preview_role  = visibloc_jlg_get_preview_role_from_cookie();
    $preview_role      = is_string( $raw_preview_role ) ? $raw_preview_role : '';

    $should_apply_preview_role = false;

    if ( '' !== $preview_role ) {
        if ( 'guest' === $preview_role ) {
            $can_preview = false;
            $should_apply_preview_role = ( $can_impersonate || ! empty( $state['can_preview_users'][ $effective_user_id ] ) );
        } else {
            if ( ! in_array( $preview_role, $allowed_roles, true ) ) {
                $can_preview = false;
            }

            $role_exists = isset( $state['roles'][ $preview_role ] );

            if ( ! $can_impersonate || ! $role_exists ) {
                $preview_role = '';
            } else {
                $should_apply_preview_role = true;
            }
        }
    }

    if ( '' === $preview_role ) {
        $should_apply_preview_role = false;
    }

    return [
        'can_impersonate'            => $can_impersonate,
        'can_preview_hidden_blocks'  => $can_preview,
        'had_preview_permission'     => ! empty( $state['can_preview_users'][ $effective_user_id ] ),
        'is_preview_role_neutralized'=> false,
        'preview_role'               => $preview_role,
        'should_apply_preview_role'  => $should_apply_preview_role,
    ];
}

function sanitize_key( $key ) {
    $key = strtolower( (string) $key );

    return preg_replace( '/[^a-z0-9_\-]/', '', $key );
}

function wp_unslash( $value ) {
    return $value;
}

function current_time( $type, $gmt = 0 ) {
    $timestamp = visibloc_test_get_current_time();

    if ( 'timestamp' === $type ) {
        if ( $gmt ) {
            return $timestamp;
        }

        return $timestamp + visibloc_test_get_timezone_offset( $timestamp );
    }

    $timezone = $gmt ? new DateTimeZone( 'UTC' ) : wp_timezone();

    return ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $timezone )->format( 'Y-m-d H:i:s' );
}

function current_datetime() {
    $timestamp = visibloc_test_get_current_time();

    return ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( wp_timezone() );
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data ) {
        return json_encode( $data );
    }
}

function wp_date( $format, $timestamp, $timezone = null ) {
    if ( $timezone instanceof DateTimeZone ) {
        $datetime = ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $timezone );
    } elseif ( is_string( $timezone ) && '' !== $timezone ) {
        try {
            $datetime = ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( new DateTimeZone( $timezone ) );
        } catch ( Exception $exception ) {
            $datetime = ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( wp_timezone() );
        }
    } else {
        $datetime = ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( wp_timezone() );
    }

    return $datetime->format( $format );
}

$GLOBALS['visibloc_test_object_cache'] = [];

function visibloc_test_cache_normalize_group( $group ) {
    return ( '' === $group ) ? 'default' : $group;
}

function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
    $group = visibloc_test_cache_normalize_group( $group );

    if ( isset( $GLOBALS['visibloc_test_object_cache'][ $group ][ $key ] ) ) {
        $entry = $GLOBALS['visibloc_test_object_cache'][ $group ][ $key ];

        if ( isset( $entry['expires'] ) && $entry['expires'] > 0 && $entry['expires'] < time() ) {
            unset( $GLOBALS['visibloc_test_object_cache'][ $group ][ $key ] );
        } else {
            if ( is_bool( $found ) || null === $found ) {
                $found = true;
            }

            return $entry['value'];
        }
    }

    if ( is_bool( $found ) || null === $found ) {
        $found = false;
    }

    return false;
}

function wp_cache_set( $key, $value, $group = '', $expire = 0 ) {
    $group   = visibloc_test_cache_normalize_group( $group );
    $expires = ( is_numeric( $expire ) && $expire > 0 ) ? ( time() + (int) $expire ) : 0;

    if ( ! isset( $GLOBALS['visibloc_test_object_cache'][ $group ] ) ) {
        $GLOBALS['visibloc_test_object_cache'][ $group ] = [];
    }

    $GLOBALS['visibloc_test_object_cache'][ $group ][ $key ] = [
        'value'   => $value,
        'expires' => $expires,
    ];

    return true;
}

function wp_cache_delete( $key, $group = '', $force = false ) {
    $group = visibloc_test_cache_normalize_group( $group );

    if ( isset( $GLOBALS['visibloc_test_object_cache'][ $group ][ $key ] ) ) {
        unset( $GLOBALS['visibloc_test_object_cache'][ $group ][ $key ] );

        if ( empty( $GLOBALS['visibloc_test_object_cache'][ $group ] ) ) {
            unset( $GLOBALS['visibloc_test_object_cache'][ $group ] );
        }
    }

    return true;
}

function set_transient( $key, $value, $expiration ) {
    $expires_at = ( is_numeric( $expiration ) && $expiration > 0 ) ? ( time() + (int) $expiration ) : 0;

    $GLOBALS['visibloc_test_transients'][ $key ] = [
        'value'   => $value,
        'expires' => $expires_at,
    ];

    return true;
}

function get_transient( $key ) {
    if ( empty( $GLOBALS['visibloc_test_transients'][ $key ] ) ) {
        return false;
    }

    $transient = $GLOBALS['visibloc_test_transients'][ $key ];

    if ( ! empty( $transient['expires'] ) && $transient['expires'] < time() ) {
        unset( $GLOBALS['visibloc_test_transients'][ $key ] );

        return false;
    }

    return $transient['value'];
}

function delete_transient( $key ) {
    if ( isset( $GLOBALS['visibloc_test_transients'][ $key ] ) ) {
        unset( $GLOBALS['visibloc_test_transients'][ $key ] );
    }

    return true;
}

function parse_blocks( $content ) {
    if ( ! is_string( $content ) || '' === $content ) {
        return [];
    }

    $pattern = '/<!--\s+(\/?)wp:([a-z0-9\/-_]+)(\s+(\{.*?\}))?\s*-->/i';

    if ( ! preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
        return [];
    }

    $stack       = [];
    $root_blocks = [];
    $total       = count( $matches[0] );

    for ( $index = 0; $index < $total; $index++ ) {
        $full_match = $matches[0][ $index ][0];
        $position   = $matches[0][ $index ][1];
        $length     = strlen( $full_match );
        $is_closing = '' !== $matches[1][ $index ][0];
        $block_name = $matches[2][ $index ][0];
        $attr_json  = isset( $matches[4][ $index ][0] ) ? trim( $matches[4][ $index ][0] ) : '';

        if ( $is_closing ) {
            while ( ! empty( $stack ) ) {
                $current = array_pop( $stack );

                if ( $current['name'] !== $block_name ) {
                    continue;
                }

                $block       = $current['block'];
                $inner_start = $current['inner_start'];
                $inner_html  = substr( $content, $inner_start, max( 0, $position - $inner_start ) );

                $block['innerHTML']    = $inner_html;
                $block['innerContent'] = [ $inner_html ];

                if ( ! empty( $stack ) ) {
                    $parent_index = count( $stack ) - 1;
                    $stack[ $parent_index ]['block']['innerBlocks'][] = $block;
                } else {
                    $root_blocks[] = $block;
                }

                break;
            }

            continue;
        }

        $attrs = [];

        if ( '' !== $attr_json ) {
            $decoded = json_decode( $attr_json, true );

            if ( is_array( $decoded ) ) {
                $attrs = $decoded;
            }
        }

        $block = [
            'blockName'    => $block_name,
            'attrs'        => $attrs,
            'innerBlocks'  => [],
            'innerHTML'    => '',
            'innerContent' => [],
        ];

        $stack[] = [
            'name'        => $block_name,
            'block'       => $block,
            'inner_start' => $position + $length,
        ];
    }

    while ( ! empty( $stack ) ) {
        $current = array_pop( $stack );
        $block   = $current['block'];

        $block['innerHTML']    = substr( $content, $current['inner_start'] );
        $block['innerContent'] = [ $block['innerHTML'] ];

        if ( ! empty( $stack ) ) {
            $parent_index = count( $stack ) - 1;
            $stack[ $parent_index ]['block']['innerBlocks'][] = $block;
        } else {
            $root_blocks[] = $block;
        }
    }

    return $root_blocks;
}

function get_post_field( $field, $post_id ) {
    if ( $post_id instanceof WP_Post ) {
        $post_id = $post_id->ID ?? 0;
    }

    $post_id = absint( $post_id );

    if ( $post_id <= 0 ) {
        return '';
    }

    $posts = $GLOBALS['visibloc_posts'] ?? [];

    if ( ! isset( $posts[ $post_id ] ) ) {
        return '';
    }

    $post = $posts[ $post_id ];

    if ( 'post_content' === $field ) {
        return $post['post_content'] ?? '';
    }

    if ( 'post_title' === $field ) {
        return $post['post_title'] ?? '';
    }

    return $post[ $field ] ?? '';
}

function get_post( $post_id ) {
    $post_id = absint( $post_id );

    if ( $post_id <= 0 ) {
        return null;
    }

    $posts = $GLOBALS['visibloc_posts'] ?? [];

    if ( ! isset( $posts[ $post_id ] ) ) {
        return null;
    }

    $post_data = $posts[ $post_id ];
    $post_data['ID'] = $post_id;

    return new WP_Post( $post_data );
}

if ( ! function_exists( 'get_posts' ) ) {
    function get_posts( $args = [] ) {
        $defaults = [
            'numberposts'      => 5,
            'offset'           => 0,
            'orderby'          => 'date',
            'order'            => 'DESC',
            'post_type'        => 'post',
            'post_status'      => 'publish',
            'suppress_filters' => true,
        ];

        if ( ! is_array( $args ) ) {
            $args = [];
        }

        $args      = array_merge( $defaults, $args );

        if ( isset( $GLOBALS['visibloc_test_stats'] ) ) {
            if ( ! isset( $GLOBALS['visibloc_test_stats']['get_posts_calls'] ) ) {
                $GLOBALS['visibloc_test_stats']['get_posts_calls'] = 0;
            }

            $GLOBALS['visibloc_test_stats']['get_posts_calls']++;
        }

        $posts     = $GLOBALS['visibloc_posts'] ?? [];
        $post_type = $args['post_type'];

        if ( is_array( $post_type ) ) {
            $post_types = array_map( 'strval', $post_type );
        } elseif ( 'any' === $post_type ) {
            $post_types = null;
        } else {
            $post_types = [ (string) $post_type ];
        }

        $post_status = $args['post_status'];

        if ( is_array( $post_status ) ) {
            $post_statuses = array_map( 'strval', $post_status );
        } elseif ( 'any' === $post_status ) {
            $post_statuses = null;
        } else {
            $post_statuses = [ (string) $post_status ];
        }

        $matching = [];
        $search   = '';

        if ( isset( $args['s'] ) && ( is_string( $args['s'] ) || is_numeric( $args['s'] ) ) ) {
            $search = strtolower( trim( (string) $args['s'] ) );
        }

        foreach ( $posts as $post_id => $post ) {
            $type  = $post['post_type'] ?? 'post';
            $state = $post['post_status'] ?? 'publish';

            if ( null !== $post_types && ! in_array( $type, $post_types, true ) ) {
                continue;
            }

            if ( null !== $post_statuses && ! in_array( $state, $post_statuses, true ) ) {
                continue;
            }

            if ( '' !== $search ) {
                $title   = strtolower( (string) ( $post['post_title'] ?? '' ) );
                $content = strtolower( (string) ( $post['post_content'] ?? '' ) );

                if ( false === strpos( $title, $search ) && false === strpos( $content, $search ) ) {
                    continue;
                }
            }

            $post['ID'] = (int) $post_id;
            $matching[] = $post;
        }

        $orderby = strtolower( (string) $args['orderby'] );
        $order   = strtoupper( (string) $args['order'] );

        $compare = static function ( $a, $b, $key ) {
            $value_a = $a[ $key ] ?? '';
            $value_b = $b[ $key ] ?? '';

            if ( is_numeric( $value_a ) && is_numeric( $value_b ) ) {
                return (int) $value_a <=> (int) $value_b;
            }

            return strcmp( (string) $value_a, (string) $value_b );
        };

        if ( 'title' === $orderby ) {
            usort(
                $matching,
                static function ( $a, $b ) use ( $order, $compare ) {
                    $result = $compare( $a, $b, 'post_title' );

                    return 'DESC' === $order ? -$result : $result;
                }
            );
        } elseif ( 'id' === $orderby ) {
            usort(
                $matching,
                static function ( $a, $b ) use ( $order, $compare ) {
                    $result = $compare( $a, $b, 'ID' );

                    return 'DESC' === $order ? -$result : $result;
                }
            );
        } else {
            usort(
                $matching,
                static function ( $a, $b ) use ( $order, $compare ) {
                    $result = $compare( $a, $b, 'post_date' );

                    return 'DESC' === $order ? -$result : $result;
                }
            );
        }

        $offset   = max( 0, (int) $args['offset'] );
        $limit    = (int) $args['numberposts'];
        $paged    = isset( $args['paged'] ) ? max( 0, (int) $args['paged'] ) : 0;
        $per_page = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : 0;

        if ( $paged > 0 && $per_page > 0 ) {
            $offset = max( 0, ( $paged - 1 ) * $per_page );
            $limit  = $per_page;
        }

        if ( $limit < 0 ) {
            $matching = array_slice( $matching, $offset );
        } else {
            $matching = array_slice( $matching, $offset, $limit );
        }

        return array_map(
            static function ( $post ) {
                return new WP_Post( $post );
            },
            $matching
        );
    }
}

function get_the_title( $post_id ) {
    return get_post_field( 'post_title', $post_id );
}

function get_edit_post_link( $post_id ) {
    $post_id = absint( $post_id );

    if ( $post_id <= 0 ) {
        return '';
    }

    return 'https://example.com/wp-admin/post.php?post=' . $post_id;
}

function update_option( $name, $value, $autoload = null ) {
    $GLOBALS['visibloc_test_options'][ $name ] = $value;

    if ( 'visibloc_supported_blocks' === $name && function_exists( 'visibloc_jlg_invalidate_supported_blocks_cache' ) ) {
        visibloc_jlg_invalidate_supported_blocks_cache();
    }

    return true;
}

function delete_option( $name ) {
    if ( isset( $GLOBALS['visibloc_test_options'][ $name ] ) ) {
        unset( $GLOBALS['visibloc_test_options'][ $name ] );
    }

    if ( 'visibloc_supported_blocks' === $name && function_exists( 'visibloc_jlg_invalidate_supported_blocks_cache' ) ) {
        visibloc_jlg_invalidate_supported_blocks_cache();
    }

    return true;
}

function __( $text ) {
    return $text;
}

function esc_attr__( $text ) {
    return $text;
}

function esc_attr( $text ) {
    return $text;
}

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        protected $errors = [];
        protected $error_data = [];

        public function __construct( $code = '', $message = '', $data = null ) {
            if ( '' !== $code ) {
                $this->add( $code, $message, $data );
            }
        }

        public function add( $code, $message, $data = null ) {
            if ( ! is_string( $code ) || '' === $code ) {
                return;
            }

            if ( ! isset( $this->errors[ $code ] ) ) {
                $this->errors[ $code ] = [];
            }

            $this->errors[ $code ][] = (string) $message;

            if ( null !== $data ) {
                $this->error_data[ $code ] = $data;
            }
        }

        public function get_error_codes() {
            return array_keys( $this->errors );
        }

        public function get_error_code() {
            $codes = $this->get_error_codes();

            return empty( $codes ) ? '' : $codes[0];
        }

        public function get_error_messages( $code = '' ) {
            if ( '' === $code ) {
                if ( empty( $this->errors ) ) {
                    return [];
                }

                return array_merge( ...array_values( $this->errors ) );
            }

            return isset( $this->errors[ $code ] ) ? $this->errors[ $code ] : [];
        }

        public function get_error_message( $code = '' ) {
            $messages = $this->get_error_messages( $code );

            return empty( $messages ) ? '' : $messages[0];
        }

        public function has_errors() {
            return ! empty( $this->errors );
        }
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) {
        return $thing instanceof WP_Error;
    }
}

if ( ! function_exists( 'wp_kses_post' ) ) {
    function wp_kses_post( $content ) {
        return is_string( $content ) ? $content : '';
    }
}

if ( ! function_exists( 'wpautop' ) ) {
    function wpautop( $pee ) {
        if ( ! is_string( $pee ) ) {
            return '';
        }

        $pee = trim( $pee );

        if ( '' === $pee ) {
            return '';
        }

        $pee = preg_replace( "/\r\n|\r/", "\n", $pee );
        $paragraphs = preg_split( "/\n{2,}/", $pee );

        $wrapped = [];

        foreach ( $paragraphs as $paragraph ) {
            $paragraph = trim( $paragraph );

            if ( '' === $paragraph ) {
                continue;
            }

            $paragraph = preg_replace( "/\n/", "<br />\n", $paragraph );
            $wrapped[] = '<p>' . $paragraph . '</p>';
        }

        return implode( "\n", $wrapped );
    }
}

class Visibloc_Test_User {
    public $ID;
    public $roles;

    public function __construct( $id = 0, array $roles = [] ) {
        $this->ID    = $id;
        $this->roles = $roles;
    }

    public function exists() {
        return (bool) $this->ID;
    }
}

function wp_get_current_user() {
    $user = $GLOBALS['visibloc_test_state']['current_user'];

    if ( $user instanceof Visibloc_Test_User ) {
        return $user;
    }

    return new Visibloc_Test_User();
}

function get_current_user_id() {
    $user = wp_get_current_user();

    if ( $user instanceof Visibloc_Test_User ) {
        return (int) $user->ID;
    }

    return 0;
}

function get_role( $role ) {
    $roles = $GLOBALS['visibloc_test_state']['roles'];

    return $roles[ $role ] ?? null;
}

if ( ! function_exists( 'wp_roles' ) ) {
    if ( ! class_exists( 'Visibloc_Test_Roles_Registry' ) ) {
        class Visibloc_Test_Roles_Registry {
            public function get_names() {
                visibloc_test_track_counter( 'wp_roles_get_names' );

                $names = [];

                foreach ( $GLOBALS['visibloc_test_state']['roles'] as $slug => $details ) {
                    $names[ $slug ] = $details->name ?? ucfirst( $slug );
                }

                return $names;
            }
        }
    }

    function wp_roles() {
        static $registry = null;

        if ( null === $registry ) {
            $registry = new Visibloc_Test_Roles_Registry();
        }

        return $registry;
    }
}

if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can( $capability ) {
        $user = wp_get_current_user();

        if ( ! ( $user instanceof Visibloc_Test_User ) || ! $user->exists() ) {
            return false;
        }

        $roles = (array) $user->roles;

        foreach ( $roles as $role ) {
            $role_object = get_role( $role );

            if ( null === $role_object ) {
                continue;
            }

            $capabilities = (array) ( $role_object->capabilities ?? [] );

            if ( ! empty( $capabilities[ $capability ] ) ) {
                return true;
            }
        }

        return false;
    }
}

function get_option( $name, $default = false ) {
    if ( 'visibloc_preview_roles' === $name ) {
        return $GLOBALS['visibloc_test_state']['allowed_preview_roles'];
    }

    if ( isset( $GLOBALS['visibloc_test_options'][ $name ] ) ) {
        return $GLOBALS['visibloc_test_options'][ $name ];
    }

    return $default;
}

function wp_is_post_revision( $post_id ) {
    return false;
}

if ( ! class_exists( 'WP_Query' ) ) {
    class WP_Query {
        public $posts = [];

        public function __construct( $args = [] ) {
            $posts        = $GLOBALS['visibloc_posts'] ?? [];
            $post_types   = isset( $args['post_type'] ) ? (array) $args['post_type'] : null;
            $post_status  = isset( $args['post_status'] ) ? (array) $args['post_status'] : null;
            $per_page     = isset( $args['posts_per_page'] ) ? max( 1, (int) $args['posts_per_page'] ) : count( $posts );
            $paged        = isset( $args['paged'] ) ? max( 1, (int) $args['paged'] ) : 1;
            $matching_ids = [];

            foreach ( $posts as $post_id => $post ) {
                $post_type  = $post['post_type'] ?? 'post';
                $post_state = $post['post_status'] ?? 'publish';

                if ( null !== $post_types && ! in_array( $post_type, $post_types, true ) ) {
                    continue;
                }

                if ( null !== $post_status && ! in_array( $post_state, $post_status, true ) ) {
                    continue;
                }

                $matching_ids[] = absint( $post_id );
            }

            sort( $matching_ids );

            $offset       = ( $paged - 1 ) * $per_page;
            $this->posts  = array_slice( $matching_ids, $offset, $per_page );
        }
    }
}

if ( ! function_exists( 'absint' ) ) {
    function absint( $maybeint ) {
        return (int) abs( $maybeint );
    }
}

require_once __DIR__ . '/../../includes/utils.php';
require_once __DIR__ . '/../../includes/datetime-utils.php';
require_once __DIR__ . '/../../includes/visibility-logic.php';

if ( ! function_exists( 'trailingslashit' ) ) {
    function trailingslashit( $value ) {
        $value = (string) $value;

        if ( '' === $value ) {
            return '/';
        }

        return rtrim( $value, '/\\' ) . '/';
    }
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( $file ) {
        return trailingslashit( dirname( (string) $file ) );
    }
}
