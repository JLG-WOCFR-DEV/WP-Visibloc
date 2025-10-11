<?php

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/includes/cache-constants.php';
require_once __DIR__ . '/../role-switcher-test-loader.php';

class UninstallCleanupTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();

        $GLOBALS['visibloc_test_options'] = [];
        $GLOBALS['visibloc_test_transients'] = [];
        $GLOBALS['visibloc_test_object_cache'] = [];
    }

    protected function tearDown(): void {
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
}
