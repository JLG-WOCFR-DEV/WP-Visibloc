<?php

use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class CliExportSettingsTest extends TestCase {
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
                '    public static $error_messages = [];' .
                '    public static function add_command( $name, $callable ) {' .
                '        self::$commands[ $name ] = $callable;' .
                '    }' .
                '    public static function log( $message ) {' .
                '        self::$log_messages[] = (string) $message;' .
                '    }' .
                '    public static function success( $message ) {' .
                '        self::$success_messages[] = (string) $message;' .
                '    }' .
                '    public static function error( $message ) {' .
                '        self::$error_messages[] = (string) $message;' .
                '        throw new RuntimeException( (string) $message );' .
                '    }' .
                '}'
            );
        }

        if ( ! function_exists( 'visibloc_jlg_get_settings_snapshot' ) ) {
            require_once __DIR__ . '/../../../includes/admin-settings.php';
        }

        if ( empty( WP_CLI::$commands ) || ! isset( WP_CLI::$commands['visibloc export-settings'] ) ) {
            require_once __DIR__ . '/../../../includes/cli.php';
        }

        visibloc_test_reset_state();

        if ( ! defined( 'VISIBLOC_JLG_VERSION' ) ) {
            define( 'VISIBLOC_JLG_VERSION', '9.9.9-test' );
        }

        WP_CLI::$log_messages     = [];
        WP_CLI::$success_messages = [];
        WP_CLI::$error_messages   = [];
    }

    protected function tearDown(): void {
        $GLOBALS['visibloc_test_options']  = [];
        WP_CLI::$commands                  = [];
        WP_CLI::$log_messages              = [];
        WP_CLI::$success_messages          = [];
        WP_CLI::$error_messages            = [];

        parent::tearDown();
    }

    public function test_export_settings_outputs_snapshot_to_stdout(): void {
        $this->assertArrayHasKey( 'visibloc export-settings', WP_CLI::$commands );

        $command = WP_CLI::$commands['visibloc export-settings'];
        $this->assertIsCallable( $command );

        $GLOBALS['visibloc_test_state']['allowed_preview_roles'] = [ 'administrator', 'editor' ];

        update_option( 'visibloc_supported_blocks', [ 'core/group', 'core/paragraph' ] );
        update_option( 'visibloc_breakpoint_mobile', 640 );
        update_option( 'visibloc_breakpoint_tablet', 980 );
        update_option( 'visibloc_debug_mode', 'on' );
        update_option(
            'visibloc_fallback_settings',
            [
                'mode'     => 'text',
                'text'     => 'Bonjour <strong>monde</strong>!',
                'block_id' => 0,
            ]
        );

        call_user_func( $command, [], [] );

        $this->assertNotEmpty( WP_CLI::$log_messages );
        $json = end( WP_CLI::$log_messages );

        $decoded = json_decode( $json, true );
        $this->assertIsArray( $decoded );

        $this->assertSame( [ 'core/group', 'core/paragraph' ], $decoded['supported_blocks'] );
        $this->assertSame(
            [
                'mobile' => 640,
                'tablet' => 980,
            ],
            $decoded['breakpoints']
        );
        $this->assertSame( [ 'administrator', 'editor' ], $decoded['preview_roles'] );
        $this->assertSame( 'on', $decoded['debug_mode'] );
        $this->assertSame(
            [
                'mode'     => 'text',
                'text'     => 'Bonjour <strong>monde</strong>!',
                'block_id' => 0,
            ],
            $decoded['fallback']
        );
        $this->assertArrayHasKey( 'exported_at', $decoded );
        $this->assertArrayHasKey( 'version', $decoded );
        $this->assertIsString( $decoded['version'] );
        $this->assertNotSame( '', $decoded['version'] );

        $this->assertSame( [ 'Settings snapshot exported.' ], WP_CLI::$success_messages );
    }

    public function test_export_settings_writes_snapshot_to_file(): void {
        $command = WP_CLI::$commands['visibloc export-settings'];
        $this->assertIsCallable( $command );

        update_option( 'visibloc_supported_blocks', [ 'core/group' ] );

        $output_file = tempnam( sys_get_temp_dir(), 'visibloc-settings-' );

        try {
            call_user_func( $command, [], [ 'output' => $output_file ] );
        } catch ( RuntimeException $exception ) {
            $this->fail( 'Export command threw an unexpected error: ' . $exception->getMessage() );
        }

        $this->assertFileExists( $output_file );

        $contents = file_get_contents( $output_file );
        $this->assertIsString( $contents );

        $decoded = json_decode( $contents, true );
        $this->assertIsArray( $decoded );
        $this->assertSame( [ 'core/group' ], $decoded['supported_blocks'] );

        $this->assertSame(
            [ sprintf( 'Settings snapshot written to %s.', $output_file ) ],
            WP_CLI::$success_messages
        );

        unlink( $output_file );
    }
}
