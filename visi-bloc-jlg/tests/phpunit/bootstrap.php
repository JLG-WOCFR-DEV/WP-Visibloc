<?php
/**
 * Bootstrap for integration tests.
 */

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../../' );
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
    return $GLOBALS['visibloc_test_state']['preview_role'];
}

function visibloc_jlg_get_preview_runtime_context() {
    $state = $GLOBALS['visibloc_test_state'];

    $effective_user_id = absint( $state['effective_user_id'] );
    $can_impersonate   = ! empty( $state['can_impersonate_users'][ $effective_user_id ] );
    $can_preview       = ! empty( $state['can_preview_users'][ $effective_user_id ] );
    $allowed_roles     = (array) $state['allowed_preview_roles'];
    $preview_role      = is_string( $state['preview_role'] ) ? $state['preview_role'] : '';

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

    return $default;
}

if ( ! function_exists( 'absint' ) ) {
    function absint( $maybeint ) {
        return (int) abs( $maybeint );
    }
}

require_once __DIR__ . '/../../includes/visibility-logic.php';
