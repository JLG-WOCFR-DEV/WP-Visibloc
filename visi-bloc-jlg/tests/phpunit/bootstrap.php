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
    ];
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
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

function sanitize_key( $key ) {
    $key = strtolower( (string) $key );

    return preg_replace( '/[^a-z0-9_\-]/', '', $key );
}

function wp_unslash( $value ) {
    return $value;
}

function current_time( $type, $gmt = 0 ) {
    if ( 'timestamp' === $type ) {
        return time();
    }

    return date( 'Y-m-d H:i:s' );
}

function visibloc_jlg_parse_schedule_datetime( $value ) {
    if ( empty( $value ) ) {
        return null;
    }

    $timestamp = strtotime( $value );

    return false === $timestamp ? null : $timestamp;
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
