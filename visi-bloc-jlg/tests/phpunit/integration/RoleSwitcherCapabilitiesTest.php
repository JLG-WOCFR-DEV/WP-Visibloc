<?php

use PHPUnit\Framework\TestCase;
use Visibloc\Tests\Support\PluginFacade;
use Visibloc\Tests\Support\TestServices;

require_once __DIR__ . '/../role-switcher-test-loader.php';

class RoleSwitcherCapabilitiesTest extends TestCase {
    private PluginFacade $plugin;

    protected function setUp(): void {
        visibloc_test_reset_state();
        $this->plugin = TestServices::plugin();
        $this->plugin->storeRealUserId( null );
    }

    public function test_guest_preview_only_applies_to_current_request_user(): void {
        global $visibloc_test_state;

        $real_user_id = 7;

        $visibloc_test_state['effective_user_id']           = $real_user_id;
        $visibloc_test_state['can_preview_users'][ $real_user_id ] = true;
        $visibloc_test_state['allowed_preview_roles']       = [ 'administrator' ];
        $visibloc_test_state['preview_role']                = 'guest';
        $visibloc_test_state['current_user']                = new Visibloc_Test_User( 0, [] );

        $this->plugin->storeRealUserId( $real_user_id );

        $allcaps = [
            'read'         => true,
            'edit_posts'   => true,
            'level_1'      => true,
            'do_not_allow' => true,
        ];

        $other_user = new Visibloc_Test_User( 99, [ 'administrator' ] );
        $this->assertSame(
            $allcaps,
            $this->plugin->filterUserCapabilities( $allcaps, [], [], $other_user ),
            'Capabilities for unrelated users should remain unchanged during a guest preview.'
        );

        $guest_user_caps = $this->plugin->filterUserCapabilities( $allcaps, [], [], new Visibloc_Test_User( 0, [] ) );

        $this->assertArrayHasKey( 'exist', $guest_user_caps );
        $this->assertArrayHasKey( 'read', $guest_user_caps );
        $this->assertArrayHasKey( 'level_0', $guest_user_caps );
        $this->assertSame( true, $guest_user_caps['read'], 'Guest previews should keep the read capability.' );
        $this->assertTrue( $guest_user_caps['exist'], 'Guest previews should set the exist capability.' );
        $this->assertFalse( isset( $guest_user_caps['edit_posts'] ), 'Guest previews should not inherit privileged capabilities.' );
        $this->assertArrayHasKey( 'do_not_allow', $guest_user_caps );
        $this->assertTrue( $guest_user_caps['do_not_allow'], 'The do_not_allow capability should be preserved when stripping caps.' );
    }
}
