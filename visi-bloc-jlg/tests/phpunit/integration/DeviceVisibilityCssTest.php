<?php

use PHPUnit\Framework\TestCase;
use Visibloc\Tests\Support\PluginFacade;
use Visibloc\Tests\Support\TestServices;

require_once __DIR__ . '/../../../includes/assets.php';
require_once __DIR__ . '/../../../includes/admin-settings.php';

class DeviceVisibilityCssTest extends TestCase {
    private PluginFacade $plugin;

    protected function setUp(): void {
        parent::setUp();

        if ( isset( $GLOBALS['visibloc_test_object_cache'] ) ) {
            $GLOBALS['visibloc_test_object_cache'] = [];
        }

        $this->plugin = TestServices::plugin();
        $this->plugin->clearCaches();
    }

    public function test_mobile_breakpoint_lower_than_default_unhides_tablet_classes(): void {
        $css = $this->plugin->generateDeviceVisibilityCss( false, 600, 1024 );

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
        $css   = $this->plugin->generateDeviceVisibilityCss( false, 600, 1024 );
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
            $this->plugin->getDisplayFallbackForSelector( '.vb-hide-on-desktop' )
        );
    }

    public function test_only_selectors_keep_block_fallback(): void {
        $this->assertSame(
            'display: block !important;',
            $this->plugin->getDisplayFallbackForSelector( '.vb-tablet-only' )
        );
    }

    public function test_default_breakpoints_generate_expected_media_queries(): void {
        $css = $this->plugin->generateDeviceVisibilityCss( false, 781, 1024 );

        $this->assertStringContainsString('@media (max-width: 781px)', $css);
        $this->assertStringContainsString('@media (min-width: 782px) and (max-width: 1024px)', $css);
        $this->assertStringContainsString('@media (min-width: 1025px)', $css);
    }

    public function test_custom_breakpoints_generate_expected_media_queries(): void {
        $css = $this->plugin->generateDeviceVisibilityCss( false, 900, 1200 );

        $this->assertStringContainsString('@media (max-width: 900px)', $css);
        $this->assertStringContainsString('@media (min-width: 901px) and (max-width: 1200px)', $css);
        $this->assertStringContainsString('@media (min-width: 1201px)', $css);
        $this->assertStringContainsString('@media (min-width: 782px) and (max-width: 900px)', $css);
        $this->assertStringContainsString('@media (min-width: 1025px) and (max-width: 1200px)', $css);
    }

    public function test_custom_breakpoints_use_block_fallback_for_hide_on_selectors(): void {
        $css   = $this->plugin->generateDeviceVisibilityCss( false, 900, 1200 );
        $block = $this->extractMediaQueryBlock( $css, '@media (min-width: 1025px) and (max-width: 1200px)' );

        $this->assertNotNull( $block );
        $this->assertStringContainsString('display: block !important;', $block);
        $this->assertStringContainsString('display: revert !important;', $block);
        $this->assertStringNotContainsString('display: initial !important;', $block);
    }

    public function test_normalize_block_declarations_adds_single_block_fallback_inline(): void {
        $declarations = $this->plugin->normalizeBlockDeclarations(
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
        $declarations = $this->plugin->normalizeBlockDeclarations(
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
        $declarations = $this->plugin->normalizeBlockDeclarations(
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

        $css = $this->plugin->generateDeviceVisibilityCss( false, 600, 1024 );

        $this->assertSame( $expected, $css );
    }

    public function test_clear_caches_removes_cached_device_css(): void {
        $initial = $this->plugin->generateDeviceVisibilityCss( false, 600, 1024 );

        $cache = wp_cache_get( 'visibloc_device_css_cache', 'visibloc_jlg' );
        $this->assertIsArray( $cache );
        $cache_key = sprintf( '%s:%d:%d:%d', VISIBLOC_JLG_VERSION, 0, 600, 1024 );
        $this->assertArrayHasKey( $cache_key, $cache );
        $this->assertSame( $initial, $cache[ $cache_key ] );

        $this->plugin->clearCaches();

        $cache_after_clear = wp_cache_get( 'visibloc_device_css_cache', 'visibloc_jlg' );
        $this->assertFalse( $cache_after_clear );
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
