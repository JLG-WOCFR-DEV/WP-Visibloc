<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../role-switcher-test-loader.php';

class UninstallCleanupTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();

        $GLOBALS['visibloc_test_options'] = [];
    }

    protected function tearDown(): void {
        $GLOBALS['visibloc_test_options'] = [];

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
}
