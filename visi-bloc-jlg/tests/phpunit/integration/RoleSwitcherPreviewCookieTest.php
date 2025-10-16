<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../role-switcher-test-loader.php';

class RoleSwitcherPreviewCookieTest extends TestCase {
    /** @var array<string,mixed> */
    private $previousCookies;

    protected function setUp(): void {
        parent::setUp();

        visibloc_test_reset_state();

        $this->previousCookies = isset( $_COOKIE ) && is_array( $_COOKIE ) ? $_COOKIE : [];
        $_COOKIE               = [];
    }

    protected function tearDown(): void {
        $_COOKIE = $this->previousCookies;

        parent::tearDown();
    }

    public function test_returns_null_when_cookie_missing(): void {
        unset( $_COOKIE['visibloc_preview_role'] );

        $this->assertNull( visibloc_jlg_get_preview_role_from_cookie() );
    }

    public function test_returns_null_when_cookie_not_string(): void {
        $_COOKIE['visibloc_preview_role'] = [ 'administrator' ];

        $this->assertNull( visibloc_jlg_get_preview_role_from_cookie() );
    }

    public function test_returns_null_when_sanitized_cookie_is_empty(): void {
        $_COOKIE['visibloc_preview_role'] = '    ';

        $this->assertNull( visibloc_jlg_get_preview_role_from_cookie() );
    }

    public function test_returns_sanitized_cookie_value(): void {
        $_COOKIE['visibloc_preview_role'] = '  Administrator@ ';

        $this->assertSame( 'administrator', visibloc_jlg_get_preview_role_from_cookie() );
    }
}
