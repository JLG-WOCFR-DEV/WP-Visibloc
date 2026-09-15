<?php

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/includes/plugin-meta.php';
require_once dirname( __DIR__, 3 ) . '/includes/block-utils.php';
require_once dirname( __DIR__, 3 ) . '/includes/assets.php';
require_once dirname( __DIR__, 3 ) . '/includes/admin-settings.php';
require_once dirname( __DIR__, 3 ) . '/src/Plugin.php';

/**
 * Phase 2: WordPress 7.1 headers, iframe-safe editor assets, wp-admin charter.
 */
final class Phase2AdminCharterTest extends TestCase {
    private string $plugin_dir;

    protected function setUp(): void {
        parent::setUp();

        $this->plugin_dir = dirname( __DIR__, 3 );

        if ( function_exists( 'visibloc_test_reset_state' ) ) {
            visibloc_test_reset_state();
        }
    }

    protected function tearDown(): void {
        if ( function_exists( 'visibloc_test_reset_state' ) ) {
            visibloc_test_reset_state();
        }

        parent::tearDown();
    }

    public function test_plugin_header_declares_wordpress_71_compatibility(): void {
        $header = $this->read_plugin_file( 'visi-bloc-jlg.php' );

        $this->assertMatchesRegularExpression( '/Requires at least:\s*5\.8/', $header );
        $this->assertMatchesRegularExpression( '/Tested up to:\s*7\.1/', $header );
        $this->assertMatchesRegularExpression( '/Requires PHP:\s*7\.4/', $header );
    }

    public function test_readme_txt_declares_wordpress_71_compatibility(): void {
        $readme_path = $this->plugin_dir . '/readme.txt';

        $this->assertFileExists( $readme_path );

        $readme = file_get_contents( $readme_path );

        $this->assertNotFalse( $readme );
        $this->assertMatchesRegularExpression( '/Requires at least:\s*5\.8/', $readme );
        $this->assertMatchesRegularExpression( '/Tested up to:\s*7\.1/', $readme );
        $this->assertMatchesRegularExpression( '/Requires PHP:\s*7\.4/', $readme );
    }

    public function test_assets_php_registers_enqueue_block_assets_hook(): void {
        $assets = $this->read_plugin_file( 'includes/assets.php' );

        $this->assertStringContainsString(
            "add_action( 'enqueue_block_assets', 'visibloc_jlg_enqueue_editor_canvas_assets' )",
            $assets
        );
        $this->assertStringContainsString(
            'function visibloc_jlg_enqueue_editor_canvas_assets',
            $assets
        );
    }

    public function test_canvas_css_enqueues_in_admin_only(): void {
        visibloc_test_set_request_environment( [ 'is_admin' => true ] );

        visibloc_jlg_enqueue_editor_canvas_assets();

        $this->assertTrue(
            wp_style_is( 'visibloc-jlg-editor-canvas', 'enqueued' ),
            'Canvas CSS must load on enqueue_block_assets so WP 7.1 copies it into the editor iframe.'
        );

        visibloc_test_reset_assets();
        visibloc_test_set_request_environment( [ 'is_admin' => false ] );

        visibloc_jlg_enqueue_editor_canvas_assets();

        $this->assertFalse(
            wp_style_is( 'visibloc-jlg-editor-canvas', 'enqueued' ),
            'Canvas CSS is editor-only and must not load on the frontend via enqueue_block_assets.'
        );
    }

    public function test_editor_parent_assets_enqueue_iframe_sync_script(): void {
        $assets = $this->read_plugin_file( 'includes/assets.php' );

        $this->assertStringContainsString( "add_action( 'enqueue_block_editor_assets'", $assets );
        $this->assertStringContainsString( 'visibloc-jlg-editor-iframe-sync', $assets );
        $this->assertStringContainsString( 'assets/editor-iframe-sync.js', $assets );
        $this->assertStringContainsString( 'visibloc-jlg-editor-script', $assets );
    }

    public function test_admin_help_page_enqueues_plugin_styles_without_restyling_wp_chrome(): void {
        visibloc_jlg_enqueue_admin_styles( 'toplevel_page_visi-bloc-jlg-help' );

        $this->assertTrue( wp_style_is( 'visibloc-jlg-admin-styles', 'enqueued' ) );
        $this->assertTrue( wp_style_is( 'visibloc-jlg-admin-responsive', 'enqueued' ) );

        $admin_css = $this->read_plugin_file( 'admin-styles.css' );

        $this->assertStringContainsString( '--wp-admin-theme-color', $admin_css );
        $this->assertStringNotContainsString( '#4f46e5', $admin_css );
        $this->assertStringNotContainsString( '#6366f1', $admin_css );
        $this->assertStringNotContainsString( '#8b5cf6', $admin_css );
        $this->assertDoesNotMatchRegularExpression( '/(^|[^-])\\.button-primary\\s*\\{/', $admin_css );
        $this->assertStringNotContainsString( '#wpbody-content', $admin_css );
        $this->assertStringNotContainsString( '#wpadminbar', $admin_css );
    }

    public function test_help_and_crm_pages_use_wp_admin_charter_markup(): void {
        $help = $this->read_plugin_file( 'includes/admin-settings.php' );
        $crm  = $this->read_plugin_file( 'includes/integrations/crm-admin.php' );

        $this->assertStringContainsString( 'class="wrap visibloc-jlg"', $help );
        $this->assertStringContainsString( '<h1>', $help );
        $this->assertStringContainsString( 'nav-tab-wrapper', $help );
        $this->assertStringContainsString( 'nav-tab', $help );
        $this->assertStringContainsString( 'form-table', $help );
        $this->assertStringContainsString( 'submit_button(', $help );
        $this->assertStringContainsString( 'notice notice-success', $help );
        $this->assertStringContainsString( 'notice notice-error', $help );
        $this->assertStringNotContainsString( 'class="updated notice', $help );

        $this->assertStringContainsString( 'class="wrap"', $crm );
        $this->assertStringContainsString( '<h1>', $crm );
        $this->assertStringContainsString( 'form-table', $crm );
        $this->assertStringContainsString( 'submit_button(', $crm );
        $this->assertStringContainsString( 'notice notice-success', $crm );
    }

    public function test_settings_api_registers_core_options(): void {
        $plugin = new \VisiBloc\Plugin( $this->plugin_dir . '/visi-bloc-jlg.php' );
        $plugin->register_supported_blocks_setting();

        if ( method_exists( $plugin, 'register_plugin_settings' ) ) {
            $plugin->register_plugin_settings();
        }

        $expected = [
            'visibloc_supported_blocks',
            'visibloc_preview_roles',
            'visibloc_breakpoint_mobile',
            'visibloc_breakpoint_tablet',
            'visibloc_fallback_settings',
            'visibloc_debug_mode',
            'visibloc_onboarding_mode',
        ];

        foreach ( $expected as $option ) {
            $this->assertArrayHasKey(
                $option,
                $GLOBALS['visibloc_test_registered_settings'],
                sprintf( 'Option %s must be registered via the Settings API.', $option )
            );
            $this->assertSame(
                'visibloc',
                $GLOBALS['visibloc_test_registered_settings'][ $option ]['group']
            );
        }
    }

    public function test_editor_js_is_iframe_safe(): void {
        $index = $this->read_plugin_file( 'src/index.js' );
        $sync  = $this->read_plugin_file( 'assets/editor-iframe-sync.js' );

        $this->assertStringContainsString( 'getEditorDocuments', $index );
        $this->assertStringContainsString( 'iframe[name="editor-canvas"]', $index );
        $this->assertStringContainsString( 'toggleEditorBodyClass', $index );
        $this->assertStringContainsString( 'visibloc-high-visibility', $index );

        $this->assertStringContainsString( 'iframe[name="editor-canvas"]', $sync );
        $this->assertStringContainsString( 'contentDocument', $sync );
        $this->assertStringContainsString( 'visibloc-high-visibility', $sync );
        $this->assertStringContainsString( 'visibloc-compact-badges', $sync );
    }

    private function read_plugin_file( string $relative ): string {
        $path = $this->plugin_dir . '/' . ltrim( $relative, '/' );
        $this->assertFileExists( $path );

        $contents = file_get_contents( $path );
        $this->assertNotFalse( $contents );

        return $contents;
    }
}
