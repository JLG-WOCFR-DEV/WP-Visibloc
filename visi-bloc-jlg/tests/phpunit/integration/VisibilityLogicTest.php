<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../includes/assets.php';

class VisibilityLogicTest extends TestCase {
    protected function setUp(): void {
        visibloc_test_reset_state();
    }

    public function test_administrator_impersonating_editor_sees_editor_view_without_hidden_blocks(): void {
        global $visibloc_test_state;

        $visibloc_test_state['effective_user_id'] = 1;
        $visibloc_test_state['can_preview_users'][1] = true;
        $visibloc_test_state['can_impersonate_users'][1] = true;
        $visibloc_test_state['allowed_preview_roles'] = [ 'administrator' ];
        $visibloc_test_state['preview_role'] = 'editor';
        $visibloc_test_state['current_user'] = new Visibloc_Test_User( 1, [ 'administrator' ] );

        $visible_for_editors = [
            'blockName' => 'core/group',
            'attrs'     => [
                'visibilityRoles' => [ 'editor' ],
            ],
        ];

        $this->assertSame(
            '<p>Editor content</p>',
            visibloc_jlg_render_block_filter( '<p>Editor content</p>', $visible_for_editors ),
            'Blocks targeted to editors should remain visible during editor preview.'
        );

        $admin_only_block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'visibilityRoles' => [ 'administrator' ],
            ],
        ];

        $this->assertSame(
            '',
            visibloc_jlg_render_block_filter( '<p>Administrator only</p>', $admin_only_block ),
            'Administrator-only blocks must be hidden when previewing as an editor.'
        );

        $hidden_block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'isHidden' => true,
            ],
        ];

        $this->assertSame(
            '',
            visibloc_jlg_render_block_filter( '<p>Hidden content</p>', $hidden_block ),
            'Hidden blocks should not appear without preview permission for the simulated role.'
        );
    }

    public function test_visibility_roles_accepts_string_role_values(): void {
        global $visibloc_test_state;

        $visibloc_test_state['current_user'] = new Visibloc_Test_User( 2, [ 'editor' ] );

        $block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'visibilityRoles' => 'editor',
            ],
        ];

        $this->assertSame(
            '<p>Visible content</p>',
            visibloc_jlg_render_block_filter( '<p>Visible content</p>', $block ),
            'A scalar string value should be treated as a single role entry.'
        );
    }

    public function test_visibility_roles_accepts_logged_out_string_marker(): void {
        $block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'visibilityRoles' => 'logged-out',
            ],
        ];

        $this->assertSame(
            '<p>Guest content</p>',
            visibloc_jlg_render_block_filter( '<p>Guest content</p>', $block ),
            'The logged-out marker should work when passed as a scalar value.'
        );
    }

    public function test_guest_preview_forces_logged_out_state_without_impersonation(): void {
        global $visibloc_test_state;

        $visibloc_test_state['effective_user_id']             = 3;
        $visibloc_test_state['can_preview_users'][3]          = true;
        $visibloc_test_state['can_impersonate_users'][3]      = false;
        $visibloc_test_state['allowed_preview_roles']         = [ 'administrator' ];
        $visibloc_test_state['preview_role']                  = 'guest';
        $visibloc_test_state['current_user']                  = new Visibloc_Test_User( 3, [ 'editor' ] );

        $logged_in_block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'visibilityRoles' => [ 'logged-in' ],
            ],
        ];

        $this->assertSame(
            '',
            visibloc_jlg_render_block_filter( '<p>Members content</p>', $logged_in_block ),
            'Previewing as a guest should hide blocks reserved for logged-in users even without impersonation rights.'
        );

        $logged_out_block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'visibilityRoles' => [ 'logged-out' ],
            ],
        ];

        $this->assertSame(
            '<p>Guest view</p>',
            visibloc_jlg_render_block_filter( '<p>Guest view</p>', $logged_out_block ),
            'Previewing as a guest should expose content intended for visitors.'
        );
    }

    public function test_scheduled_block_hidden_outside_window_without_preview_permission(): void {
        $block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'isSchedulingEnabled' => true,
                'publishStartDate'    => '2099-01-01 00:00:00',
                'publishEndDate'      => '2099-01-02 00:00:00',
            ],
        ];

        $this->assertSame(
            '',
            visibloc_jlg_render_block_filter( '<p>Scheduled content</p>', $block ),
            'Scheduled blocks must remain hidden outside their window when no preview privilege is granted.'
        );
    }

    public function test_scheduled_block_shows_preview_wrapper_for_authorized_user(): void {
        global $visibloc_test_state;

        $visibloc_test_state['effective_user_id']       = 9;
        $visibloc_test_state['current_user']            = new Visibloc_Test_User( 9, [ 'administrator' ] );
        $visibloc_test_state['can_preview_users'][9]    = true;
        $visibloc_test_state['allowed_preview_roles']   = [ 'administrator' ];
        $visibloc_test_state['preview_role']            = '';

        $block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'isSchedulingEnabled' => true,
                'publishStartDate'    => '2099-01-01 00:00:00',
                'publishEndDate'      => '2099-01-02 00:00:00',
            ],
        ];

        $output = visibloc_jlg_render_block_filter( '<p>Scheduled content</p>', $block );

        $this->assertStringContainsString( 'bloc-schedule-apercu', $output );
        $this->assertStringContainsString( 'Programmé (Début:', $output );
        $this->assertStringContainsString( '<p>Scheduled content</p>', $output );
    }

    public function test_scheduled_block_uses_site_timezone_window(): void {
        visibloc_test_set_timezone( 'Europe/Paris' );

        $block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'isSchedulingEnabled' => true,
                'publishStartDate'    => '2024-07-10 10:00:00',
                'publishEndDate'      => '2024-07-10 18:00:00',
            ],
        ];

        visibloc_test_set_current_time( visibloc_jlg_parse_schedule_datetime( '2024-07-10 09:30:00' ) );

        $this->assertSame(
            '',
            visibloc_jlg_render_block_filter( '<p>Scheduled content</p>', $block ),
            'Blocks should remain hidden before the scheduled start in the configured site timezone.'
        );

        visibloc_test_set_current_time( visibloc_jlg_parse_schedule_datetime( '2024-07-10 11:00:00' ) );

        $this->assertSame(
            '<p>Scheduled content</p>',
            visibloc_jlg_render_block_filter( '<p>Scheduled content</p>', $block ),
            'Blocks should become visible once the local start time has passed.'
        );
    }

    public function test_generate_device_visibility_css_respects_preview_context(): void {
        $css_without_preview = visibloc_jlg_generate_device_visibility_css( false, 781, 1024 );
        $this->assertSame( '', trim( $css_without_preview ) );

        $css_with_custom_breakpoints = visibloc_jlg_generate_device_visibility_css( false, 700, 900 );
        $this->assertStringContainsString( '@media (max-width: 700px)', $css_with_custom_breakpoints );
        $this->assertStringContainsString( '@media (min-width: 901px)', $css_with_custom_breakpoints );
        $this->assertStringNotContainsString( 'outline: 2px dashed', $css_with_custom_breakpoints );

        $css_with_preview = visibloc_jlg_generate_device_visibility_css( true, 781, 1024 );
        $this->assertStringContainsString( 'outline: 2px dashed #0073aa', $css_with_preview );
        $this->assertStringContainsString( 'Visible sur Desktop Uniquement', $css_with_preview );
    }
}
