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
        $this->assertStringContainsString('.vb-tablet-only { display: revert !important; }', $block);
    }

    public function test_mobile_breakpoint_lower_than_default_does_not_hide_classes_above_new_threshold(): void {
        $css   = visibloc_jlg_generate_device_visibility_css( false, 600, 1024 );
        $block = $this->extractMediaQueryBlock( $css, '@media (min-width: 601px) and (max-width: 781px)' );

        $this->assertNotNull( $block );
        $this->assertStringContainsString('.vb-hide-on-mobile,', $block);
        $this->assertStringContainsString('.vb-tablet-only { display: revert !important; }', $block);
        $this->assertStringNotContainsString('display: none !important;', $block);
    }

    public function test_cached_css_is_returned_when_available(): void {
        $expected = '/* cached css */';
        wp_cache_set(
            'visibloc_device_css_cache',
            [ '0:600:1024' => $expected ],
            'visibloc_jlg'
        );

        $css = visibloc_jlg_generate_device_visibility_css( false, 600, 1024 );

        $this->assertSame( $expected, $css );
    }

    public function test_clear_caches_removes_cached_device_css(): void {
        $initial = visibloc_jlg_generate_device_visibility_css( false, 600, 1024 );

        $cache = wp_cache_get( 'visibloc_device_css_cache', 'visibloc_jlg' );
        $this->assertIsArray( $cache );
        $this->assertArrayHasKey( '0:600:1024', $cache );
        $this->assertSame( $initial, $cache['0:600:1024'] );

        visibloc_jlg_clear_caches();

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
