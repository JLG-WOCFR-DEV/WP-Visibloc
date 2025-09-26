<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../includes/assets.php';

class DeviceVisibilityCssTest extends TestCase {
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
