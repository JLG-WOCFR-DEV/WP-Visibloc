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

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
    // No-op for tests.
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
    return $value;
}

function visibloc_jlg_is_admin_or_technical_request() {
    return false;
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
            return '';
        }

        return sanitize_key( $cookie_value );
    }

    return $GLOBALS['visibloc_test_state']['preview_role'];
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
            return $timestamp - visibloc_test_get_timezone_offset( $timestamp );
        }

        return $timestamp;
    }

    $timezone = $gmt ? new DateTimeZone( 'UTC' ) : wp_timezone();

    return ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $timezone )->format( 'Y-m-d H:i:s' );
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data ) {
        return json_encode( $data );
    }
}

function visibloc_jlg_parse_schedule_datetime( $value ) {
    if ( empty( $value ) ) {
        return null;
    }

    $datetime = date_create_immutable( $value, wp_timezone() );

    if ( false === $datetime ) {
        return null;
    }

    return $datetime->getTimestamp();
}

function wp_date( $format, $timestamp ) {
    return gmdate( $format, $timestamp );
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

    return (object) $post_data;
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

if ( ! function_exists( 'visibloc_jlg_normalize_boolean' ) ) {
    function visibloc_jlg_normalize_boolean( $value ) {
        if ( is_bool( $value ) ) {
            return $value;
        }

        if ( is_array( $value ) || is_object( $value ) ) {
            return false;
        }

        $filtered = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

        return true === $filtered;
    }
}

require_once __DIR__ . '/../../includes/visibility-logic.php';
