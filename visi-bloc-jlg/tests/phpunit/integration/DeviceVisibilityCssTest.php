<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../includes/assets.php';

class DeviceVisibilityCssTest extends TestCase {
    public function test_mobile_breakpoint_lower_than_default_unhides_tablet_classes(): void {
        $css = visibloc_jlg_generate_device_visibility_css( false, 600, 1024 );

        $this->assertStringContainsString('@media (max-width: 600px)', $css);
        $this->assertStringNotContainsString('@media (max-width: 781px)', $css);
        $this->assertMatchesRegularExpression(
            '/@media \\(min-width: 601px\\) and \\(max-width: 781px\\) {\\s+\\.vb-hide-on-mobile,\\s+\\.vb-tablet-only \\{ display: revert !important; \\}\\s+}/',
            $css
        );
    }
}
