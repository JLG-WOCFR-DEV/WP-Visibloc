<?php

use PHPUnit\Framework\TestCase;

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
        wp_cache_set( 'visibloc_device_css_cache', [ 'cached' => true ], 'visibloc_jlg' );

        $this->assertSame( 'yes', get_transient( 'visibloc_jlg_missing_editor_assets' ) );
        $this->assertSame(
            [ 'cached' => true ],
            wp_cache_get( 'visibloc_device_css_cache', 'visibloc_jlg' )
        );

        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            define( 'WP_UNINSTALL_PLUGIN', true );
        }

        require dirname( __DIR__, 3 ) . '/uninstall.php';

        $this->assertFalse( get_transient( 'visibloc_jlg_missing_editor_assets' ) );
        $this->assertFalse( wp_cache_get( 'visibloc_device_css_cache', 'visibloc_jlg' ) );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_uninstall_removes_fallback_settings_and_device_css_transients(): void {
        $bucket_keys = [ 'bucket-one', 'bucket-two' ];

        update_option( 'visibloc_fallback_settings', [ 'mobile' => 'hidden' ] );
        update_option( 'visibloc_device_css_transients', $bucket_keys );

        foreach ( $bucket_keys as $bucket_key ) {
            $transient_name = sprintf( 'visibloc_device_css_%s', $bucket_key );

            set_transient( $transient_name, 'css-' . $bucket_key, 0 );
            wp_cache_set( $transient_name, [ 'cached' => true ], 'visibloc_jlg' );

            $this->assertSame( 'css-' . $bucket_key, get_transient( $transient_name ) );
            $this->assertSame(
                [ 'cached' => true ],
                wp_cache_get( $transient_name, 'visibloc_jlg' )
            );
        }

        $this->assertSame( [ 'mobile' => 'hidden' ], get_option( 'visibloc_fallback_settings' ) );
        $this->assertSame( $bucket_keys, get_option( 'visibloc_device_css_transients' ) );

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
            get_option( 'visibloc_device_css_transients', '__default__' )
        );

        foreach ( $bucket_keys as $bucket_key ) {
            $transient_name = sprintf( 'visibloc_device_css_%s', $bucket_key );

            $this->assertFalse( get_transient( $transient_name ) );
            $this->assertFalse( wp_cache_get( $transient_name, 'visibloc_jlg' ) );
        }
    }
}
