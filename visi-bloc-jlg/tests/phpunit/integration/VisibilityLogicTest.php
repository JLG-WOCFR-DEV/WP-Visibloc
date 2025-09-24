<?php

use PHPUnit\Framework\TestCase;

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
}
