<?php

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/includes/cache-constants.php';
require_once __DIR__ . '/../role-switcher-test-loader.php';

class UninstallCleanupTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();

        visibloc_test_reset_state();
        visibloc_jlg_store_real_user_id( null );

        $_GET    = [];
        $_COOKIE = [];

        $GLOBALS['visibloc_test_options'] = [];
        $GLOBALS['visibloc_test_transients'] = [];
        $GLOBALS['visibloc_test_object_cache'] = [];
    }

    protected function tearDown(): void {
        $_GET    = [];
        $_COOKIE = [];

        $GLOBALS['visibloc_test_options'] = [];
        $GLOBALS['visibloc_test_transients'] = [];
        $GLOBALS['visibloc_test_object_cache'] = [];

        parent::tearDown();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_uninstall_removes_insights_option(): void {
        update_option( 'visibloc_insights', [ 'enabled' => true ] );

        $this->assertSame(
            [ 'enabled' => true ],
            get_option( 'visibloc_insights' )
        );

        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            define( 'WP_UNINSTALL_PLUGIN', true );
        }

        require dirname( __DIR__, 3 ) . '/uninstall.php';

        $this->assertSame(
            '__default__',
            get_option( 'visibloc_insights', '__default__' )
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_supported_blocks_option_is_removed_on_uninstall(): void {
        update_option( 'visibloc_supported_blocks', [ 'core/paragraph' ] );

        $this->assertSame(
            [ 'core/paragraph' ],
            get_option( 'visibloc_supported_blocks' )
        );

        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            define( 'WP_UNINSTALL_PLUGIN', true );
        }

        require dirname( __DIR__, 3 ) . '/uninstall.php';

        $this->assertSame(
            '__default__',
            get_option( 'visibloc_supported_blocks', '__default__' )
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_uninstall_clears_editor_asset_and_device_css_caches(): void {
        set_transient( 'visibloc_jlg_missing_editor_assets', 'yes', 0 );
        wp_cache_set( VISIBLOC_JLG_DEVICE_CSS_CACHE_KEY, [ 'cached' => true ], VISIBLOC_JLG_DEVICE_CSS_CACHE_GROUP );

        $this->assertSame( 'yes', get_transient( 'visibloc_jlg_missing_editor_assets' ) );
        $this->assertSame(
            [ 'cached' => true ],
            wp_cache_get( VISIBLOC_JLG_DEVICE_CSS_CACHE_KEY, VISIBLOC_JLG_DEVICE_CSS_CACHE_GROUP )
        );

        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            define( 'WP_UNINSTALL_PLUGIN', true );
        }

        require dirname( __DIR__, 3 ) . '/uninstall.php';

        $this->assertFalse( get_transient( 'visibloc_jlg_missing_editor_assets' ) );
        $this->assertFalse( wp_cache_get( VISIBLOC_JLG_DEVICE_CSS_CACHE_KEY, VISIBLOC_JLG_DEVICE_CSS_CACHE_GROUP ) );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_uninstall_removes_fallback_settings_and_device_css_transients(): void {
        $bucket_keys = [ 'bucket-one', 'bucket-two' ];

        update_option( 'visibloc_fallback_settings', [ 'mobile' => 'hidden' ] );
        update_option( VISIBLOC_JLG_DEVICE_CSS_BUCKET_OPTION, $bucket_keys );

        wp_cache_set( 'visibloc_fallback_settings', [ 'mobile' => 'hidden' ], 'visibloc_jlg' );
        wp_cache_set( VISIBLOC_JLG_DEVICE_CSS_BUCKET_OPTION, $bucket_keys, VISIBLOC_JLG_DEVICE_CSS_CACHE_GROUP );

        foreach ( $bucket_keys as $bucket_key ) {
            $transient_name = VISIBLOC_JLG_DEVICE_CSS_TRANSIENT_PREFIX . $bucket_key;

            set_transient( $transient_name, 'css-' . $bucket_key, 0 );
            wp_cache_set( $transient_name, [ 'cached' => true ], 'visibloc_jlg' );

            $this->assertSame( 'css-' . $bucket_key, get_transient( $transient_name ) );
            $this->assertSame(
                [ 'cached' => true ],
                wp_cache_get( $transient_name, 'visibloc_jlg' )
            );
        }

        $this->assertSame( [ 'mobile' => 'hidden' ], get_option( 'visibloc_fallback_settings' ) );
        $this->assertSame( $bucket_keys, get_option( VISIBLOC_JLG_DEVICE_CSS_BUCKET_OPTION ) );
        $this->assertSame(
            [ 'mobile' => 'hidden' ],
            wp_cache_get( 'visibloc_fallback_settings', 'visibloc_jlg' )
        );
        $this->assertSame(
            $bucket_keys,
            wp_cache_get( VISIBLOC_JLG_DEVICE_CSS_BUCKET_OPTION, VISIBLOC_JLG_DEVICE_CSS_CACHE_GROUP )
        );

        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            define( 'WP_UNINSTALL_PLUGIN', true );
        }

        require dirname( __DIR__, 3 ) . '/uninstall.php';

        $this->assertSame(
            '__default__',
            get_option( 'visibloc_fallback_settings', '__default__' )
        );
        $this->assertSame(
            '__default__',
            get_option( VISIBLOC_JLG_DEVICE_CSS_BUCKET_OPTION, '__default__' )
        );
        $this->assertFalse( wp_cache_get( 'visibloc_fallback_settings', 'visibloc_jlg' ) );
        $this->assertFalse( wp_cache_get( VISIBLOC_JLG_DEVICE_CSS_BUCKET_OPTION, VISIBLOC_JLG_DEVICE_CSS_CACHE_GROUP ) );

        foreach ( $bucket_keys as $bucket_key ) {
            $transient_name = VISIBLOC_JLG_DEVICE_CSS_TRANSIENT_PREFIX . $bucket_key;

            $this->assertFalse( get_transient( $transient_name ) );
            $this->assertFalse( wp_cache_get( $transient_name, 'visibloc_jlg' ) );
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_uninstall_purges_preview_cookie_and_runtime_context(): void {
        global $visibloc_test_state;

        $real_user_id = 52;

        $visibloc_test_state['effective_user_id']             = $real_user_id;
        $visibloc_test_state['current_user']                  = new Visibloc_Test_User( $real_user_id, [ 'administrator' ] );
        $visibloc_test_state['can_preview_users'][ $real_user_id ] = true;
        $visibloc_test_state['can_impersonate_users'][ $real_user_id ] = true;
        $visibloc_test_state['allowed_preview_roles']         = [ 'administrator' ];

        visibloc_jlg_store_real_user_id( $real_user_id );

        $_COOKIE['visibloc_preview_role'] = 'guest';

        $initial_context = visibloc_jlg_get_preview_runtime_context( true );

        $this->assertSame( 'guest', $initial_context['preview_role'], 'Guest preview should be active before uninstall.' );
        $this->assertTrue( $initial_context['should_apply_preview_role'], 'Guest preview should apply before uninstall.' );

        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            define( 'WP_UNINSTALL_PLUGIN', true );
        }

        require dirname( __DIR__, 3 ) . '/uninstall.php';

        $this->assertArrayNotHasKey( 'visibloc_preview_role', $_COOKIE, 'Uninstall should remove the preview cookie.' );

        $refreshed_context = visibloc_jlg_get_preview_runtime_context( true );

        $this->assertSame( '', $refreshed_context['preview_role'], 'Runtime context should be reset after uninstall.' );
        $this->assertFalse( $refreshed_context['should_apply_preview_role'], 'No preview role should apply after uninstall.' );

        $this->assertNotEmpty( $GLOBALS['visibloc_test_cookie_log'], 'Uninstall should attempt to expire the preview cookie.' );
        $last_cookie = end( $GLOBALS['visibloc_test_cookie_log'] );

        $this->assertSame( '', $last_cookie['value'], 'Expired cookie should be set with an empty value.' );
        $this->assertLessThanOrEqual( time(), $last_cookie['expires'], 'Expired cookie should not extend the preview session.' );
    }
}
