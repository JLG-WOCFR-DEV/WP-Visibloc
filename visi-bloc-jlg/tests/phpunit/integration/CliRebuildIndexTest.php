<?php

use PHPUnit\Framework\TestCase;
use Visibloc\Tests\Support\PluginFacade;
use Visibloc\Tests\Support\TestServices;

/**
 * @runTestsInSeparateProcesses
 */
class CliRebuildIndexTest extends TestCase {
    private PluginFacade $plugin;

    protected function setUp(): void {
        parent::setUp();

        if ( ! defined( 'WP_CLI' ) ) {
            define( 'WP_CLI', true );
        }

        if ( ! class_exists( 'WP_CLI' ) ) {
            eval(
                'class WP_CLI {' .
                '    public static $commands = [];' .
                '    public static $log_messages = [];' .
                '    public static $success_messages = [];' .
                '    public static function add_command( $name, $callable ) {' .
                '        self::$commands[ $name ] = $callable;' .
                '    }' .
                '    public static function log( $message ) {' .
                '        self::$log_messages[] = (string) $message;' .
                '    }' .
                '    public static function success( $message ) {' .
                '        self::$success_messages[] = (string) $message;' .
                '    }' .
                '}'
            );
        }

        if ( ! function_exists( 'visibloc_jlg_rebuild_group_block_summary_index' ) ) {
            require_once __DIR__ . '/../../../includes/admin-settings.php';
        }

        if ( empty( WP_CLI::$commands ) || ! isset( WP_CLI::$commands['visibloc rebuild-index'] ) ) {
            require_once __DIR__ . '/../../../includes/cli.php';
        }

        visibloc_test_reset_state();

        $GLOBALS['visibloc_posts']           = [];
        $GLOBALS['visibloc_test_options']    = [];
        $GLOBALS['visibloc_test_transients'] = [];

        $this->plugin = TestServices::plugin();

        if ( function_exists( 'visibloc_jlg_store_group_block_summary_index' ) ) {
            $this->plugin->storeGroupBlockSummaryIndex( [] );
        }

        if ( function_exists( 'visibloc_jlg_clear_caches' ) ) {
            $this->plugin->clearCaches();
        }

        WP_CLI::$log_messages     = [];
        WP_CLI::$success_messages = [];
    }

    protected function tearDown(): void {
        if ( function_exists( 'visibloc_jlg_store_group_block_summary_index' ) ) {
            $this->plugin->storeGroupBlockSummaryIndex( [] );
        }

        if ( function_exists( 'visibloc_jlg_clear_caches' ) ) {
            $this->plugin->clearCaches();
        }

        $GLOBALS['visibloc_posts']           = [];
        $GLOBALS['visibloc_test_options']    = [];
        $GLOBALS['visibloc_test_transients'] = [];

        parent::tearDown();
    }

    public function test_rebuild_index_command_scans_posts_and_clears_caches(): void {
        $this->assertArrayHasKey( 'visibloc rebuild-index', WP_CLI::$commands );

        $command = WP_CLI::$commands['visibloc rebuild-index'];
        $this->assertIsCallable( $command );

        $hidden_group_content = <<<'HTML'
<!-- wp:core/group {"isHidden":true} -->
<div class="wp-block-group">Hidden group</div>
<!-- /wp:core/group -->
HTML;

        $device_group_content = <<<'HTML'
<!-- wp:core/group {"deviceVisibility":"mobile"} -->
<div class="wp-block-group">Mobile only</div>
<!-- /wp:core/group -->
HTML;

        $paragraph_content = <<<'HTML'
<!-- wp:paragraph -->
<p>Regular content without target blocks.</p>
<!-- /wp:paragraph -->
HTML;

        $GLOBALS['visibloc_posts'] = [
            101 => [
                'post_content' => $hidden_group_content,
                'post_type'    => 'post',
                'post_status'  => 'publish',
            ],
            102 => [
                'post_content' => $paragraph_content,
                'post_type'    => 'post',
                'post_status'  => 'draft',
            ],
            103 => [
                'post_content' => $device_group_content,
                'post_type'    => 'page',
                'post_status'  => 'private',
            ],
        ];

        set_transient( 'visibloc_hidden_posts', [ 'cached' ], 3600 );
        set_transient( 'visibloc_group_block_metadata', [ 'cached' ], 3600 );

        call_user_func( $command );

        $summaries = $this->plugin->getGroupBlockSummaryIndex();

        $this->assertSame( 2, count( $summaries ) );
        $this->assertArrayHasKey( 101, $summaries );
        $this->assertArrayHasKey( 103, $summaries );

        $this->assertSame(
            [
                'Scanned 3 posts.',
                'Created 2 index entries.',
            ],
            WP_CLI::$log_messages
        );

        $this->assertSame( [ 'Group block summary caches cleared.' ], WP_CLI::$success_messages );

        $this->assertFalse( get_transient( 'visibloc_hidden_posts' ) );
        $this->assertFalse( get_transient( 'visibloc_group_block_metadata' ) );
    }
}
