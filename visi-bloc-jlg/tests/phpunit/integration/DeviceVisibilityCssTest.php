<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../includes/assets.php';
require_once __DIR__ . '/../../../includes/admin-settings.php';

class DeviceVisibilityCssTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();

        if ( isset( $GLOBALS['visibloc_test_object_cache'] ) ) {
            $GLOBALS['visibloc_test_object_cache'] = [];
        }

        if ( isset( $GLOBALS['visibloc_test_transients'] ) ) {
            $GLOBALS['visibloc_test_transients'] = [];
        }

        if ( isset( $GLOBALS['visibloc_test_options']['visibloc_device_css_transient_keys'] ) ) {
            unset( $GLOBALS['visibloc_test_options']['visibloc_device_css_transient_keys'] );
        }

        if ( function_exists( 'visibloc_jlg_clear_caches' ) ) {
            visibloc_jlg_clear_caches();
        }
    }

    public function test_mobile_breakpoint_lower_than_default_unhides_tablet_classes(): void {
        $css = visibloc_jlg_generate_device_visibility_css( false, 600, 1024 );

        $this->assertStringContainsString('@media (max-width: 600px)', $css);
        $this->assertStringNotContainsString('@media (max-width: 781px)', $css);
        $block = $this->extractMediaQueryBlock( $css, '@media (min-width: 601px) and (max-width: 781px)' );

        $this->assertNotNull( $block );
        $this->assertStringContainsString('.vb-hide-on-mobile,', $block);
        $this->assertStringContainsString(
            ".vb-tablet-only {\n        display: block !important;\n        display: revert !important;\n    }",
            $block
        );
    }

    public function test_mobile_breakpoint_lower_than_default_does_not_hide_classes_above_new_threshold(): void {
        $css   = visibloc_jlg_generate_device_visibility_css( false, 600, 1024 );
        $block = $this->extractMediaQueryBlock( $css, '@media (min-width: 601px) and (max-width: 781px)' );

        $this->assertNotNull( $block );
        $this->assertStringContainsString('.vb-hide-on-mobile,', $block);
        $this->assertStringContainsString('display: block !important;', $block);
        $this->assertStringContainsString('display: revert !important;', $block);
        $this->assertStringNotContainsString('display: none !important;', $block);
    }

    public function test_hide_on_selectors_use_block_fallback(): void {
        $this->assertSame(
            'display: block !important;',
            visibloc_jlg_get_display_fallback_for_selector( '.vb-hide-on-desktop' )
        );
    }

    public function test_only_selectors_keep_block_fallback(): void {
        $this->assertSame(
            'display: block !important;',
            visibloc_jlg_get_display_fallback_for_selector( '.vb-tablet-only' )
        );
    }

    public function test_default_breakpoints_generate_expected_media_queries(): void {
        $css = visibloc_jlg_generate_device_visibility_css( false, 781, 1024 );

        $this->assertStringContainsString('@media (max-width: 781px)', $css);
        $this->assertStringContainsString('@media (min-width: 782px) and (max-width: 1024px)', $css);
        $this->assertStringContainsString('@media (min-width: 1025px)', $css);
    }

    public function test_custom_breakpoints_generate_expected_media_queries(): void {
        $css = visibloc_jlg_generate_device_visibility_css( false, 900, 1200 );

        $this->assertStringContainsString('@media (max-width: 900px)', $css);
        $this->assertStringContainsString('@media (min-width: 901px) and (max-width: 1200px)', $css);
        $this->assertStringContainsString('@media (min-width: 1201px)', $css);
        $this->assertStringContainsString('@media (min-width: 782px) and (max-width: 900px)', $css);
        $this->assertStringContainsString('@media (min-width: 1025px) and (max-width: 1200px)', $css);
    }

    public function test_custom_breakpoints_use_block_fallback_for_hide_on_selectors(): void {
        $css   = visibloc_jlg_generate_device_visibility_css( false, 900, 1200 );
        $block = $this->extractMediaQueryBlock( $css, '@media (min-width: 1025px) and (max-width: 1200px)' );

        $this->assertNotNull( $block );
        $this->assertStringContainsString('display: block !important;', $block);
        $this->assertStringContainsString('display: revert !important;', $block);
        $this->assertStringNotContainsString('display: initial !important;', $block);
    }

    public function test_normalize_block_declarations_adds_single_block_fallback_inline(): void {
        $declarations = visibloc_jlg_normalize_block_declarations(
            '.vb-tablet-only',
            [
                'display: revert !important',
                'color: red',
            ]
        );

        $this->assertSame(
            [
                'display: block !important;',
                'display: revert !important;',
                'color: red;',
            ],
            $declarations
        );
    }

    public function test_normalize_block_declarations_keeps_existing_block_fallback_only_once(): void {
        $declarations = visibloc_jlg_normalize_block_declarations(
            '.vb-tablet-only',
            [
                'display: block !important;',
                'display: revert !important;',
            ]
        );

        $this->assertSame(
            [
                'display: block !important;',
                'display: revert !important;',
            ],
            $declarations
        );
    }

    public function test_normalize_block_declarations_handles_block_with_whitespace(): void {
        $declarations = visibloc_jlg_normalize_block_declarations(
            '.vb-tablet-only',
            [
                "  display: block !important;   ",
                'display: revert !important;',
            ]
        );

        $this->assertSame(
            [
                'display: block !important;',
                'display: revert !important;',
            ],
            $declarations
        );
    }

    public function test_cached_css_is_returned_when_available(): void {
        $expected = '/* cached css */';
        $cache_key = sprintf( '%s:%d:%d:%d', VISIBLOC_JLG_VERSION, 0, 600, 1024 );

        wp_cache_set(
            'visibloc_device_css_cache',
            [ $cache_key => $expected ],
            'visibloc_jlg'
        );

        $css = visibloc_jlg_generate_device_visibility_css( false, 600, 1024 );

        $this->assertSame( $expected, $css );
    }

    public function test_transient_css_is_reused_without_recalculation(): void {
        $initial_css = visibloc_jlg_generate_device_visibility_css( false, 600, 1024 );
        $bucket_key  = sprintf( '%s:%d:%d:%d', VISIBLOC_JLG_VERSION, 0, 600, 1024 );
        $transient_key = visibloc_jlg_get_device_css_transient_key( $bucket_key );

        $this->assertArrayHasKey( $transient_key, $GLOBALS['visibloc_test_transients'] );

        wp_cache_delete( 'visibloc_device_css_cache', 'visibloc_jlg' );

        $second_css = visibloc_jlg_generate_device_visibility_css( false, 600, 1024 );

        $this->assertSame( $initial_css, $second_css );

        $found = null;
        $cache = wp_cache_get( 'visibloc_device_css_cache', 'visibloc_jlg', false, $found );

        $this->assertFalse( $cache );
        $this->assertFalse( $found );

        $registered_transients = get_option( 'visibloc_device_css_transient_keys', [] );
        $this->assertContains( $transient_key, $registered_transients );
    }

    public function test_clear_caches_removes_cached_device_css(): void {
        $initial = visibloc_jlg_generate_device_visibility_css( false, 600, 1024 );

        $cache = wp_cache_get( 'visibloc_device_css_cache', 'visibloc_jlg' );
        $this->assertIsArray( $cache );
        $cache_key = sprintf( '%s:%d:%d:%d', VISIBLOC_JLG_VERSION, 0, 600, 1024 );
        $this->assertArrayHasKey( $cache_key, $cache );
        $this->assertSame( $initial, $cache[ $cache_key ] );

        $transient_key = visibloc_jlg_get_device_css_transient_key( $cache_key );
        $this->assertArrayHasKey( $transient_key, $GLOBALS['visibloc_test_transients'] );

        visibloc_jlg_clear_caches();

        $cache_after_clear = wp_cache_get( 'visibloc_device_css_cache', 'visibloc_jlg' );
        $this->assertFalse( $cache_after_clear );

        $this->assertArrayNotHasKey( $transient_key, $GLOBALS['visibloc_test_transients'] );
        $this->assertSame( [], get_option( 'visibloc_device_css_transient_keys', [] ) );
    }

    private function extractMediaQueryBlock( string $css, string $query ): ?string {
        $position = strpos( $css, $query );

        if ( false === $position ) {
            return null;
        }

        $start = strpos( $css, '{', $position );

        if ( false === $start ) {
            return null;
        }

        $depth   = 0;
        $length  = strlen( $css );

        for ( $index = $start; $index < $length; $index++ ) {
            $character = $css[ $index ];

            if ( '{' === $character ) {
                $depth++;
            } elseif ( '}' === $character ) {
                $depth--;

                if ( 0 === $depth ) {
                    $block = substr( $css, $start + 1, $index - $start - 1 );

                    return trim( $block );
                }
            }
        }

        return null;
    }
}
