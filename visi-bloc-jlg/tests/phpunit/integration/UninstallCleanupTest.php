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
}
