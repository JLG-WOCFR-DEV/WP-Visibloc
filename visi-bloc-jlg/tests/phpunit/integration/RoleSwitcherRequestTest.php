<?php

use PHPUnit\Framework\TestCase;

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
    define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'COOKIEPATH' ) ) {
    define( 'COOKIEPATH', '/' );
}

if ( ! defined( 'COOKIE_DOMAIN' ) ) {
    define( 'COOKIE_DOMAIN', '' );
}

if ( ! class_exists( 'Visibloc_Test_Redirect_Exception' ) ) {
    class Visibloc_Test_Redirect_Exception extends Exception {}
}

global $visibloc_test_redirect_state;
$visibloc_test_redirect_state = [];

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $hook, $value ) {
        return $value;
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text ) {
        return $text;
    }
}

if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $text ) {
        return $text;
    }
}

if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( $url ) {
        return $url;
    }
}

if ( ! function_exists( 'admin_url' ) ) {
    function admin_url( $path = '' ) {
        $path = ltrim( $path, '/' );

        return 'https://example.test/wp-admin/' . $path;
    }
}

if ( ! function_exists( 'home_url' ) ) {
    function home_url( $path = '/' ) {
        $path = '/' . ltrim( $path, '/' );

        return 'https://example.test' . $path;
    }
}

if ( ! function_exists( 'wp_parse_url' ) ) {
    function wp_parse_url( $url ) {
        return parse_url( $url );
    }
}

if ( ! function_exists( 'is_ssl' ) ) {
    function is_ssl() {
        return false;
    }
}

if ( ! function_exists( 'is_admin' ) ) {
    function is_admin() {
        return false;
    }
}

if ( ! function_exists( 'wp_doing_ajax' ) ) {
    function wp_doing_ajax() {
        return false;
    }
}

if ( ! function_exists( 'wp_doing_cron' ) ) {
    function wp_doing_cron() {
        return false;
    }
}

if ( ! function_exists( 'wp_get_referer' ) ) {
    function wp_get_referer() {
        return '';
    }
}

if ( ! function_exists( 'is_admin_bar_showing' ) ) {
    function is_admin_bar_showing() {
        return true;
    }
}

if ( ! function_exists( 'wp_roles' ) ) {
    class Visibloc_Test_Roles_Registry {
        public function get_names() {
            $names = [];

            foreach ( $GLOBALS['visibloc_test_state']['roles'] as $slug => $details ) {
                $names[ $slug ] = $details->name ?? ucfirst( $slug );
            }

            return $names;
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

if ( ! function_exists( 'get_editable_roles' ) ) {
    function get_editable_roles() {
        $roles = [];

        foreach ( $GLOBALS['visibloc_test_state']['roles'] as $slug => $details ) {
            $roles[ $slug ] = [ 'name' => $details->name ?? ucfirst( $slug ) ];
        }

        return $roles;
    }
}

if ( ! function_exists( 'add_query_arg' ) ) {
    function add_query_arg( $key, $value = null, $url = '' ) {
        if ( is_array( $key ) ) {
            $params = $key;
            $url    = (string) $value;
        } else {
            $params = [ $key => $value ];
            $url    = (string) $url;
        }

        $fragment = '';
        $fragment_position = strpos( $url, '#' );

        if ( false !== $fragment_position ) {
            $fragment = substr( $url, $fragment_position );
            $url      = substr( $url, 0, $fragment_position );
        }

        $query      = '';
        $query_pos  = strpos( $url, '?' );
        $base       = $url;

        if ( false !== $query_pos ) {
            $query = substr( $url, $query_pos + 1 );
            $base  = substr( $url, 0, $query_pos );
        }

        parse_str( $query, $query_args );

        foreach ( $params as $param_key => $param_value ) {
            if ( null === $param_value ) {
                unset( $query_args[ $param_key ] );
            } else {
                $query_args[ $param_key ] = $param_value;
            }
        }

        $new_query = http_build_query( $query_args );

        $result = $base;

        if ( '' !== $new_query ) {
            $result .= '?' . $new_query;
        }

        return $result . $fragment;
    }
}

if ( ! function_exists( 'remove_query_arg' ) ) {
    function remove_query_arg( $keys, $url = '' ) {
        $keys = (array) $keys;
        $fragment = '';
        $fragment_position = strpos( $url, '#' );

        if ( false !== $fragment_position ) {
            $fragment = substr( $url, $fragment_position );
            $url      = substr( $url, 0, $fragment_position );
        }

        $query_pos = strpos( $url, '?' );

        if ( false === $query_pos ) {
            return $url . $fragment;
        }

        $base  = substr( $url, 0, $query_pos );
        $query = substr( $url, $query_pos + 1 );

        parse_str( $query, $query_args );

        foreach ( $keys as $key ) {
            unset( $query_args[ $key ] );
        }

        $new_query = http_build_query( $query_args );

        if ( '' === $new_query ) {
            return $base . $fragment;
        }

        return $base . '?' . $new_query . $fragment;
    }
}

if ( ! function_exists( 'wp_nonce_url' ) ) {
    function wp_nonce_url( $url, $action ) {
        return add_query_arg( '_wpnonce', 'nonce-' . $action, $url );
    }
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
    function wp_verify_nonce( $nonce, $action ) {
        return $nonce === 'nonce-' . $action;
    }
}

if ( ! function_exists( 'wp_safe_redirect' ) ) {
    function wp_safe_redirect( $location, $status = 302, $x_redirect_by = 'WordPress' ) {
        global $visibloc_test_redirect_state;

        $visibloc_test_redirect_state = [
            'location'      => $location,
            'status'        => $status,
            'x_redirect_by' => $x_redirect_by,
        ];

        throw new Visibloc_Test_Redirect_Exception();
    }
}

require_once __DIR__ . '/../role-switcher-test-loader.php';

class Visibloc_Test_Admin_Bar {
    /** @var array<string,array> */
    public $nodes = [];

    public function add_node( array $node ) {
        $this->nodes[ $node['id'] ] = $node;
    }
}

class RoleSwitcherRequestTest extends TestCase {
    protected function setUp(): void {
        visibloc_test_reset_state();
        visibloc_jlg_store_real_user_id( null );

        $_GET    = [];
        $_COOKIE = [];
        $_SERVER = [
            'HTTP_HOST'    => 'example.test',
            'REQUEST_URI'  => '/',
        ];

        global $visibloc_test_redirect_state;
        $visibloc_test_redirect_state = [];
        $GLOBALS['visibloc_test_cookie_log'] = [];

        if ( function_exists( 'header_remove' ) && ! headers_sent() ) {
            header_remove();
        }
    }

    protected function tearDown(): void {
        if ( function_exists( 'header_remove' ) && ! headers_sent() ) {
            header_remove();
        }

        $_GET = [];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_purge_preview_cookie_resets_cookie_and_runtime_context(): void {
        global $visibloc_test_state;

        $user_id = 29;

        $visibloc_test_state['effective_user_id']             = $user_id;
        $visibloc_test_state['current_user']                  = new Visibloc_Test_User( $user_id, [ 'administrator' ] );
        $visibloc_test_state['can_preview_users'][ $user_id ] = true;

        $_COOKIE['visibloc_preview_role'] = 'guest';

        $initial_context = visibloc_jlg_get_preview_runtime_context( true );

        $this->assertSame( 'guest', $initial_context['preview_role'], 'Guest preview should be recognized before purge.' );
        $this->assertTrue( $initial_context['should_apply_preview_role'], 'Guest preview should apply before purge.' );

        visibloc_jlg_purge_preview_cookie();

        $this->assertArrayNotHasKey( 'visibloc_preview_role', $_COOKIE, 'Preview cookie should be removed from the request.' );
        $this->assertNull( visibloc_jlg_get_preview_role_from_cookie(), 'Preview cookie helper should return null after purge.' );

        $refreshed_context = visibloc_jlg_get_preview_runtime_context( true );

        $this->assertSame( '', $refreshed_context['preview_role'], 'Runtime context should no longer report an active preview role.' );
        $this->assertFalse( $refreshed_context['should_apply_preview_role'], 'Runtime context should not apply any preview role after purge.' );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_preview_cookie_is_purged_when_role_is_removed(): void {
        global $visibloc_test_state;

        $user_id = 37;

        $visibloc_test_state['effective_user_id']                 = $user_id;
        $visibloc_test_state['current_user']                      = new Visibloc_Test_User( $user_id, [ 'administrator' ] );
        $visibloc_test_state['can_preview_users'][ $user_id ]     = true;
        $visibloc_test_state['can_impersonate_users'][ $user_id ] = true;
        $visibloc_test_state['allowed_preview_roles']             = [ 'administrator', 'custom_role' ];
        $visibloc_test_state['roles']['administrator']            = (object) [ 'name' => 'Administrator', 'capabilities' => [] ];
        $visibloc_test_state['roles']['custom_role']              = (object) [ 'name' => 'Custom role', 'capabilities' => [] ];

        $_COOKIE['visibloc_preview_role'] = 'custom_role';

        $initial_context = visibloc_jlg_get_preview_runtime_context( true );

        $this->assertSame( 'custom_role', $initial_context['preview_role'], 'Runtime context should acknowledge the custom role before it is removed.' );
        $this->assertTrue( $initial_context['should_apply_preview_role'], 'Custom role preview should apply while the role exists.' );

        unset( $visibloc_test_state['roles']['custom_role'] );

        $maybe_user_id = visibloc_jlg_maybe_impersonate_guest( $user_id );

        $this->assertSame( $user_id, $maybe_user_id, 'User ID should remain unchanged when impersonation is cancelled.' );
        $this->assertArrayNotHasKey( 'visibloc_preview_role', $_COOKIE, 'Preview cookie should be cleared when the role disappears.' );
        $this->assertNull( visibloc_jlg_get_preview_role_from_cookie(), 'Preview cookie helper should no longer expose a removed role.' );
        $this->assertSame( 0, visibloc_jlg_get_stored_real_user_id(), 'Stored real user ID should be reset after purging the preview role.' );

        $cookie = $this->getLatestCookieLog();

        $this->assertNotNull( $cookie, 'Purging a removed role should trigger a cookie update.' );
        $this->assertSame( '', $cookie['value'] ?? null, 'Purged preview cookie should store an empty role value.' );
        $this->assertLessThan( time(), $cookie['expires'] ?? PHP_INT_MAX, 'Purged preview cookie should expire in the past.' );

        $refreshed_context = visibloc_jlg_get_preview_runtime_context( true );

        $this->assertSame( '', $refreshed_context['preview_role'], 'Runtime context should not expose a preview role once it is removed.' );
        $this->assertFalse( $refreshed_context['should_apply_preview_role'], 'Runtime context should not apply preview logic after the role is purged.' );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_valid_preview_role_request_sets_cookie_and_updates_admin_bar(): void {
        global $visibloc_test_state, $visibloc_test_redirect_state;

        $user_id = 11;

        $visibloc_test_state['effective_user_id']             = $user_id;
        $visibloc_test_state['current_user']                  = new Visibloc_Test_User( $user_id, [ 'administrator' ] );
        $visibloc_test_state['can_preview_users'][ $user_id ] = true;
        $visibloc_test_state['can_impersonate_users'][ $user_id ] = true;
        $visibloc_test_state['allowed_preview_roles']         = [ 'administrator', 'editor' ];
        $visibloc_test_state['roles']['editor']               = (object) [ 'name' => 'Editor', 'capabilities' => [] ];

        $_GET = [
            'preview_role' => 'editor',
            '_wpnonce'     => 'nonce-visibloc_switch_role_editor',
            'foo'          => 'bar',
        ];

        $_SERVER['REQUEST_URI'] = '/page/?preview_role=editor&_wpnonce=nonce-visibloc_switch_role_editor&foo=bar';

        $expected_expiration = visibloc_jlg_get_preview_cookie_expiration_time();

        try {
            visibloc_jlg_handle_role_switching();
            $this->fail( 'Expected redirect exception was not thrown.' );
        } catch ( Visibloc_Test_Redirect_Exception $exception ) {
            // Expected path.
        }

        $this->assertSame( 'https://example.test/page/?foo=bar', $visibloc_test_redirect_state['location'], 'Redirect should drop control parameters.' );

        $cookie = $this->getLatestCookieLog();

        $this->assertNotNull( $cookie, 'Preview request should record a cookie update.' );
        $this->assertSame( 'editor', $cookie['value'] ?? null, 'Preview cookie should store the requested role.' );

        $expiration = $cookie['expires'] ?? null;
        $this->assertIsInt( $expiration );
        $this->assertGreaterThanOrEqual( $expected_expiration - 2, $expiration );
        $this->assertLessThanOrEqual( $expected_expiration + 2, $expiration );

        $visibloc_test_state['preview_role'] = 'editor';

        $this->emulateRedirectRequest( $visibloc_test_redirect_state['location'] );

        $admin_bar = new Visibloc_Test_Admin_Bar();
        visibloc_jlg_add_role_switcher_menu( $admin_bar );

        $this->assertArrayHasKey( 'visibloc-alert', $admin_bar->nodes, 'Active preview should inject alert node.' );
        $this->assertStringContainsString( 'Aperçu : Editor', $admin_bar->nodes['visibloc-alert']['title'] );

        $this->assertArrayHasKey( 'visibloc-stop-preview', $admin_bar->nodes );
        $this->assertStringContainsString( 'stop_preview_role=true', $admin_bar->nodes['visibloc-stop-preview']['href'] );
        $this->assertStringContainsString( '_wpnonce=nonce-visibloc_switch_role_stop', $admin_bar->nodes['visibloc-stop-preview']['href'] );

        $this->assertArrayHasKey( 'visibloc-role-switcher', $admin_bar->nodes );
        $this->assertArrayHasKey( 'visibloc-role-guest', $admin_bar->nodes, 'Guest option should be available for impersonators.' );
        $this->assertStringContainsString( 'preview_role=guest', $admin_bar->nodes['visibloc-role-guest']['href'] );
        $this->assertArrayHasKey( 'visibloc-role-editor', $admin_bar->nodes );
        $this->assertStringContainsString( 'preview_role=editor', $admin_bar->nodes['visibloc-role-editor']['href'] );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_preview_role_redirect_uses_site_host_when_host_header_differs(): void {
        global $visibloc_test_state, $visibloc_test_redirect_state;

        $user_id = 17;

        $visibloc_test_state['effective_user_id']             = $user_id;
        $visibloc_test_state['current_user']                  = new Visibloc_Test_User( $user_id, [ 'administrator' ] );
        $visibloc_test_state['can_preview_users'][ $user_id ] = true;
        $visibloc_test_state['can_impersonate_users'][ $user_id ] = true;
        $visibloc_test_state['allowed_preview_roles']         = [ 'administrator', 'editor' ];
        $visibloc_test_state['roles']['editor']               = (object) [ 'name' => 'Editor', 'capabilities' => [] ];

        $_GET = [
            'preview_role' => 'editor',
            '_wpnonce'     => 'nonce-visibloc_switch_role_editor',
        ];

        $_SERVER['HTTP_HOST']   = 'malicious.test';
        $_SERVER['REQUEST_URI'] = '/page/?preview_role=editor&_wpnonce=nonce-visibloc_switch_role_editor';

        try {
            visibloc_jlg_handle_role_switching();
            $this->fail( 'Expected redirect exception was not thrown.' );
        } catch ( Visibloc_Test_Redirect_Exception $exception ) {
            // Expected path.
        }

        $this->assertSame( 'https://example.test/page/', $visibloc_test_redirect_state['location'], 'Redirect host should match the site host even when the request host differs.' );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_invalid_role_request_purges_cookie_and_flags_error(): void {
        global $visibloc_test_state, $visibloc_test_redirect_state;

        $user_id = 17;

        $visibloc_test_state['effective_user_id']             = $user_id;
        $visibloc_test_state['current_user']                  = new Visibloc_Test_User( $user_id, [ 'administrator' ] );
        $visibloc_test_state['can_preview_users'][ $user_id ] = true;
        $visibloc_test_state['can_impersonate_users'][ $user_id ] = true;
        $visibloc_test_state['allowed_preview_roles']         = [ 'administrator', 'editor' ];
        $visibloc_test_state['roles']['editor']               = (object) [ 'name' => 'Editor', 'capabilities' => [] ];

        $_GET = [
            'preview_role' => 'subscriber',
            '_wpnonce'     => 'nonce-visibloc_switch_role_subscriber',
            'foo'          => 'bar',
        ];

        $_SERVER['REQUEST_URI'] = '/page/?preview_role=subscriber&_wpnonce=nonce-visibloc_switch_role_subscriber&foo=bar';

        try {
            visibloc_jlg_handle_role_switching();
            $this->fail( 'Expected redirect exception was not thrown.' );
        } catch ( Visibloc_Test_Redirect_Exception $exception ) {
            // Expected.
        }

        $this->assertArrayHasKey( 'location', $visibloc_test_redirect_state );
        $this->assertStringNotContainsString( 'preview_role', $visibloc_test_redirect_state['location'] );
        $this->assertStringContainsString( 'preview_status=invalid_role', $visibloc_test_redirect_state['location'] );

        $cookie = $this->getLatestCookieLog();
        $this->assertNotNull( $cookie, 'Invalid role should trigger a purge cookie log entry.' );
        $this->assertSame( '', $cookie['value'] ?? null, 'Purged cookie should be empty.' );

        $expiration = $cookie['expires'] ?? null;
        $this->assertIsInt( $expiration );
        $this->assertLessThan( time(), $expiration, 'Purged cookie should expire immediately.' );

        $visibloc_test_state['preview_role'] = '';

        $this->emulateRedirectRequest( $visibloc_test_redirect_state['location'] );

        $admin_bar = new Visibloc_Test_Admin_Bar();
        visibloc_jlg_add_role_switcher_menu( $admin_bar );

        $this->assertArrayHasKey( 'visibloc-preview-error', $admin_bar->nodes, 'Invalid role redirect should surface an error notice.' );
        $this->assertStringContainsString( 'rôle demandé', $admin_bar->nodes['visibloc-preview-error']['title'] );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_stop_preview_request_clears_cookie_and_removes_alert(): void {
        global $visibloc_test_state, $visibloc_test_redirect_state;

        $user_id = 23;

        $visibloc_test_state['effective_user_id']             = $user_id;
        $visibloc_test_state['current_user']                  = new Visibloc_Test_User( $user_id, [ 'administrator' ] );
        $visibloc_test_state['can_preview_users'][ $user_id ] = true;
        $visibloc_test_state['can_impersonate_users'][ $user_id ] = false;
        $visibloc_test_state['allowed_preview_roles']         = [ 'administrator' ];
        $visibloc_test_state['preview_role']                  = 'guest';

        $_GET = [ 'foo' => 'bar' ];
        $_SERVER['REQUEST_URI'] = '/page/?foo=bar';

        $admin_bar = new Visibloc_Test_Admin_Bar();
        visibloc_jlg_add_role_switcher_menu( $admin_bar );

        $this->assertArrayHasKey( 'visibloc-alert', $admin_bar->nodes, 'Guest preview should display alert in toolbar.' );
        $this->assertStringContainsString( 'Visiteur (Déconnecté)', $admin_bar->nodes['visibloc-alert']['title'] );
        $this->assertArrayHasKey( 'visibloc-stop-preview', $admin_bar->nodes, 'Guest preview should expose stop link.' );

        $_GET = [
            'stop_preview_role' => 'true',
            '_wpnonce'          => 'nonce-visibloc_switch_role_stop',
            'foo'               => 'bar',
        ];

        $_SERVER['REQUEST_URI'] = '/page/?stop_preview_role=true&_wpnonce=nonce-visibloc_switch_role_stop&foo=bar';

        try {
            visibloc_jlg_handle_role_switching();
            $this->fail( 'Expected redirect exception was not thrown.' );
        } catch ( Visibloc_Test_Redirect_Exception $exception ) {
            // Expected.
        }

        $this->assertSame( 'https://example.test/page/?foo=bar', $visibloc_test_redirect_state['location'], 'Stop preview redirect should clean control params.' );

        $cookie = $this->getLatestCookieLog();
        $this->assertNotNull( $cookie, 'Stop preview should emit cookie purge.' );
        $this->assertSame( '', $cookie['value'] ?? null );
        $this->assertLessThan( time(), $cookie['expires'] ?? time() );

        $visibloc_test_state['preview_role'] = '';
        $this->emulateRedirectRequest( $visibloc_test_redirect_state['location'] );

        $admin_bar_after = new Visibloc_Test_Admin_Bar();
        visibloc_jlg_add_role_switcher_menu( $admin_bar_after );

        $this->assertArrayNotHasKey( 'visibloc-alert', $admin_bar_after->nodes, 'Toolbar alert should disappear once preview stops.' );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_frontend_model_includes_dynamic_breakpoints(): void {
        global $visibloc_test_state;

        $user_id = 37;

        $visibloc_test_state['effective_user_id']             = $user_id;
        $visibloc_test_state['current_user']                  = new Visibloc_Test_User( $user_id, [ 'administrator' ] );
        $visibloc_test_state['can_preview_users'][ $user_id ] = true;
        $visibloc_test_state['can_impersonate_users'][ $user_id ] = true;
        $visibloc_test_state['allowed_preview_roles']         = [ 'administrator', 'editor' ];
        $visibloc_test_state['roles']['editor']               = (object) [ 'name' => 'Editor', 'capabilities' => [] ];

        delete_option( 'visibloc_breakpoint_mobile' );
        delete_option( 'visibloc_breakpoint_tablet' );

        $model = visibloc_jlg_get_role_switcher_frontend_model( true );

        $this->assertIsArray( $model, 'Frontend model should be available when preview is allowed.' );
        $this->assertArrayHasKey( 'breakpoints', $model );
        $this->assertSame(
            [
                'mobile' => 781,
                'tablet' => 1024,
            ],
            $model['breakpoints']
        );
        $this->assertArrayHasKey( 'toggle_max_width', $model );
        $this->assertSame( 1024, $model['toggle_max_width'], 'Default breakpoints should favour the tablet width.' );

        update_option( 'visibloc_breakpoint_mobile', 640 );
        update_option( 'visibloc_breakpoint_tablet', 900 );

        $custom_model = visibloc_jlg_get_role_switcher_frontend_model( true );

        $this->assertSame( 640, $custom_model['breakpoints']['mobile'] );
        $this->assertSame( 900, $custom_model['breakpoints']['tablet'] );
        $this->assertSame( 900, $custom_model['toggle_max_width'], 'Custom tablet breakpoint should define the toggle threshold.' );

        update_option( 'visibloc_breakpoint_mobile', 1280 );
        update_option( 'visibloc_breakpoint_tablet', 900 );

        $wide_model = visibloc_jlg_get_role_switcher_frontend_model( true );

        $this->assertSame( 1280, $wide_model['breakpoints']['mobile'] );
        $this->assertSame( 900, $wide_model['breakpoints']['tablet'] );
        $this->assertSame( 1280, $wide_model['toggle_max_width'], 'The widest breakpoint should control the toggle visibility.' );
    }

    public function test_min_width_filter_cannot_exceed_configured_breakpoints(): void {
        global $visibloc_test_state;

        $user_id = 41;

        $visibloc_test_state['effective_user_id']             = $user_id;
        $visibloc_test_state['current_user']                  = new Visibloc_Test_User( $user_id, [ 'administrator' ] );
        $visibloc_test_state['can_preview_users'][ $user_id ] = true;
        $visibloc_test_state['can_impersonate_users'][ $user_id ] = true;
        $visibloc_test_state['allowed_preview_roles']         = [ 'administrator', 'editor' ];
        $visibloc_test_state['roles']['editor']               = (object) [ 'name' => 'Editor', 'capabilities' => [] ];

        update_option( 'visibloc_breakpoint_mobile', 960 );
        update_option( 'visibloc_breakpoint_tablet', 1200 );

        $high_filter = static function ( int $min_width ): int {
            return 2000;
        };

        add_filter( 'visibloc_jlg_role_switcher_min_width', $high_filter, 10, 1 );

        $model = visibloc_jlg_get_role_switcher_frontend_model( true );

        $this->assertSame( 1200, $model['toggle_max_width'], 'The filter should be clamped to the widest breakpoint.' );

        remove_filter( 'visibloc_jlg_role_switcher_min_width', $high_filter, 10 );

        $reduced_filter = static function ( int $min_width ): int {
            return 980;
        };

        add_filter( 'visibloc_jlg_role_switcher_min_width', $reduced_filter, 10, 1 );

        $reduced_model = visibloc_jlg_get_role_switcher_frontend_model( true );

        $this->assertSame( 980, $reduced_model['toggle_max_width'], 'Filter values below the breakpoints should be honoured.' );

        remove_filter( 'visibloc_jlg_role_switcher_min_width', $reduced_filter, 10 );

        $negative_filter = static function ( int $min_width ): int {
            return -500;
        };

        add_filter( 'visibloc_jlg_role_switcher_min_width', $negative_filter, 10, 1 );

        $fallback_model = visibloc_jlg_get_role_switcher_frontend_model( true );

        $this->assertSame( 1200, $fallback_model['toggle_max_width'], 'Negative values should be treated as a no-op.' );

        remove_filter( 'visibloc_jlg_role_switcher_min_width', $negative_filter, 10 );

        delete_option( 'visibloc_breakpoint_mobile' );
        delete_option( 'visibloc_breakpoint_tablet' );
    }

    public function test_external_absolute_request_uri_is_neutralized(): void {
        $_SERVER['REQUEST_URI'] = 'https://malicious.test/suspicious/?preview_role=guest&_wpnonce=fake';

        $base_url = visibloc_jlg_get_preview_switch_base_url();

        $this->assertSame( 'https://example.test/', $base_url );
    }

    private function getLatestCookieLog(): ?array {
        global $visibloc_test_cookie_log;

        if ( empty( $visibloc_test_cookie_log ) ) {
            return null;
        }

        return $visibloc_test_cookie_log[ array_key_last( $visibloc_test_cookie_log ) ];
    }

    private function emulateRedirectRequest( string $url ): void {
        $parts = parse_url( $url );
        $path  = $parts['path'] ?? '/';
        $query = $parts['query'] ?? '';
        $host  = $parts['host'] ?? 'example.test';

        $_SERVER['HTTP_HOST']   = $host;
        $_SERVER['REQUEST_URI'] = $path . ( '' !== $query ? '?' . $query : '' );

        parse_str( $query, $query_args );
        $_GET = $query_args;
    }
}
