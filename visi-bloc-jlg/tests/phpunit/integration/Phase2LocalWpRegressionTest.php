<?php

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/includes/plugin-meta.php';
require_once dirname( __DIR__, 3 ) . '/includes/block-utils.php';
require_once dirname( __DIR__, 3 ) . '/includes/assets.php';
require_once dirname( __DIR__, 3 ) . '/includes/admin-settings.php';
require_once dirname( __DIR__, 3 ) . '/includes/integrations/crm-admin.php';

/**
 * LocalWP regressions against WP 7.1 / PHP 8.2 for Visi-Bloc #344.
 */
final class Phase2LocalWpRegressionTest extends TestCase {
    private string $plugin_dir;

    protected function setUp(): void {
        parent::setUp();

        $this->plugin_dir = dirname( __DIR__, 3 );

        if ( function_exists( 'visibloc_test_reset_state' ) ) {
            visibloc_test_reset_state();
        }

        visibloc_test_set_request_environment( [ 'is_admin' => true ] );
    }

    protected function tearDown(): void {
        if ( function_exists( 'visibloc_test_reset_state' ) ) {
            visibloc_test_reset_state();
        }

        parent::tearDown();
    }

    public function test_recipe_templates_do_not_throw_on_percent_in_gutenberg_json(): void {
        $slugs = [ 'welcome-series', 'woocommerce-cart-recovery', 'b2b-lead-nurturing' ];

        foreach ( $slugs as $slug ) {
            $markup = visibloc_jlg_get_recipe_template_markup( $slug );

            $this->assertIsString( $markup, sprintf( 'Recipe %s must return a string.', $slug ) );
            $this->assertNotSame( '', $markup, sprintf( 'Recipe %s must not be empty.', $slug ) );
        }

        $b2b = visibloc_jlg_get_recipe_template_markup( 'b2b-lead-nurturing' );

        $this->assertStringContainsString( '"width":"60%"', $b2b );
        $this->assertStringContainsString( 'flex-basis:60%', $b2b );
        $this->assertStringContainsString( '"width":"40%"', $b2b );
    }

    public function test_help_recipes_page_rendering_does_not_throw(): void {
        $recipes = visibloc_jlg_get_guided_recipes();

        $this->assertIsArray( $recipes );
        $this->assertNotEmpty( $recipes );

        ob_start();
        try {
            visibloc_jlg_render_guided_recipes_section( $recipes );
            visibloc_jlg_render_help_page_content();
        } catch ( Throwable $exception ) {
            ob_end_clean();
            $this->fail(
                'Help/recipes rendering threw: ' . $exception->getMessage()
                . ' in ' . $exception->getFile() . ':' . $exception->getLine()
            );
        }
        $html = ob_get_clean();

        $this->assertStringContainsString( 'class="wrap visibloc-jlg"', $html );
        $this->assertStringContainsString( '<h1>', $html );
        $this->assertStringContainsString( 'visibloc-guided-recipes', $html );
        $this->assertStringContainsString( 'b2b-lead-nurturing', $html );
    }

    public function test_crm_page_is_registered_with_capability_and_admin_php_href(): void {
        visibloc_jlg_add_admin_menu();

        $this->assertArrayHasKey( 'visi-bloc-jlg-help', $GLOBALS['visibloc_test_menu'] );
        $this->assertSame(
            'manage_options',
            $GLOBALS['visibloc_test_menu']['visi-bloc-jlg-help']['capability']
        );

        $this->assertArrayHasKey( 'visi-bloc-jlg-help', $GLOBALS['submenu'] );

        $crm_item = null;

        foreach ( $GLOBALS['submenu']['visi-bloc-jlg-help'] as $item ) {
            if ( isset( $item[2] ) && 'visi-bloc-jlg-crm' === $item[2] ) {
                $crm_item = $item;
                break;
            }
        }

        $this->assertNotNull( $crm_item, 'CRM submenu must be registered under the help parent.' );
        $this->assertSame( 'manage_options', $crm_item[1] );
        $this->assertSame( 'visi-bloc-jlg-crm', $crm_item[2] );
        $this->assertSame(
            'visibloc_jlg_render_crm_settings_page',
            $GLOBALS['visibloc_test_submenu']['visi-bloc-jlg-help'][0]['callback']
        );

        $href = visibloc_jlg_get_crm_settings_page_url();

        $this->assertSame(
            'https://example.test/wp-admin/admin.php?page=visi-bloc-jlg-crm',
            $href
        );
        $this->assertStringContainsString( 'admin.php?page=visi-bloc-jlg-crm', $href );
        $this->assertStringNotContainsString( '/wp-admin/visi-bloc-jlg-crm', $href );
    }

    public function test_crm_menu_registration_runs_after_parent_menu(): void {
        $crm_source  = $this->read_plugin_file( 'includes/integrations/crm-admin.php' );
        $help_source = $this->read_plugin_file( 'includes/admin-settings.php' );

        $this->assertMatchesRegularExpression(
            "/add_action\\(\\s*'admin_menu',\\s*'visibloc_jlg_register_crm_settings_page',\\s*11\\s*\\)/",
            $crm_source
        );
        $this->assertStringContainsString( 'visibloc_jlg_register_crm_settings_page()', $help_source );
        $this->assertStringContainsString( "admin.php?page=visi-bloc-jlg-crm", $crm_source );
    }

    public function test_canvas_styles_are_injected_into_block_editor_settings(): void {
        visibloc_jlg_enqueue_editor_canvas_assets();

        $this->assertTrue( wp_style_is( 'visibloc-jlg-editor-canvas', 'enqueued' ) );

        $settings = visibloc_jlg_inject_editor_canvas_styles( [] );

        $this->assertArrayHasKey( 'styles', $settings );
        $this->assertNotEmpty( $settings['styles'] );

        $css = '';

        foreach ( $settings['styles'] as $style ) {
            if ( isset( $style['css'] ) ) {
                $css .= (string) $style['css'];
            }
        }

        $this->assertStringContainsString( 'visibloc', $css );
    }

    public function test_iframe_sync_script_copies_styles_into_canvas_document(): void {
        $sync = $this->read_plugin_file( 'assets/editor-iframe-sync.js' );

        $this->assertStringContainsString( 'contentDocument', $sync );
        $this->assertStringContainsString( 'cloneNode', $sync );
        $this->assertStringContainsString( 'visibloc-jlg-editor-canvas', $sync );
        $this->assertStringContainsString( 'iframe[name="editor-canvas"]', $sync );
        $this->assertStringContainsString( 'stylesheet', $sync );
    }

    private function read_plugin_file( string $relative ): string {
        $path = $this->plugin_dir . '/' . ltrim( $relative, '/' );
        $this->assertFileExists( $path );

        $contents = file_get_contents( $path );
        $this->assertNotFalse( $contents );

        return $contents;
    }
}
