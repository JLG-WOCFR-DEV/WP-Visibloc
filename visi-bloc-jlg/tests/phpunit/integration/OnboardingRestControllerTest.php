<?php

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/includes/rest/class-visibloc-onboarding-controller.php';
require_once dirname( __DIR__, 3 ) . '/includes/admin-settings.php';

final class OnboardingRestControllerTest extends TestCase {
    protected function setUp(): void {
        visibloc_test_reset_state();
        delete_option( 'visibloc_onboarding_drafts' );
        $GLOBALS['visibloc_test_state']['roles']['administrator']->capabilities['edit_posts'] = true;
        $GLOBALS['visibloc_test_state']['current_user'] = new Visibloc_Test_User( 7, [ 'administrator' ] );
    }

    protected function tearDown(): void {
        visibloc_test_reset_state();
    }

    public function test_permissions_check_requires_edit_posts_capability(): void {
        $controller = new Visibloc_Onboarding_Controller();
        $this->assertTrue( $controller->permissions_check(), 'Administrators should pass the permission check.' );

        $GLOBALS['visibloc_test_state']['current_user'] = new Visibloc_Test_User();
        $this->assertFalse( $controller->permissions_check(), 'Unauthenticated users should be denied.' );
    }

    public function test_get_item_returns_empty_payload_when_no_draft_exists(): void {
        $controller = new Visibloc_Onboarding_Controller();
        $response   = $controller->get_item( new Visibloc_Test_REST_Request() );
        $data       = $response->get_data();

        $this->assertArrayHasKey( 'draft', $data );
        $this->assertNull( $data['draft'] );
        $this->assertArrayHasKey( 'updatedAt', $data );
        $this->assertNull( $data['updatedAt'] );
    }

    public function test_update_item_sanitizes_and_persists_draft(): void {
        $controller = new Visibloc_Onboarding_Controller();
        $raw_payload = [
            'recipeId' => 'welcome-series<script>',
            'mode'     => 'EXPERT',
            'steps'    => [
                'objective' => [ 'goal' => '<strong>Convertir</strong>' ],
                'audience'  => [ 'roles' => [ 'subscriber<script>' ] ],
                'timing'    => [ 'cadence' => "3 rappels\n<script>" ],
                'content'   => [ 'fallback' => '<script>alert(1)</script>' ],
            ],
        ];
        $request    = new Visibloc_Test_REST_Request( $raw_payload );
        $response   = $controller->update_item( $request );
        $data       = $response->get_data();

        $this->assertSame( 'expert', $data['draft']['mode'] );
        $this->assertSame( 'welcome-series', $data['draft']['recipeId'] );
        $this->assertSame( 'Convertir', $data['draft']['steps']['objective']['goal'] );
        $this->assertSame( '3 rappels', $data['draft']['steps']['timing']['cadence'] );
        $this->assertSame( 'alert(1)', $data['draft']['steps']['content']['fallback'] );
        $this->assertArrayHasKey( 'updatedAt', $data );
        $this->assertIsInt( $data['updatedAt'] );

        $stored = visibloc_jlg_get_onboarding_draft_for_user( 7 );
        $this->assertSame( $data['draft'], $stored['data'] );
    }
}

final class Visibloc_Test_REST_Request {
    private $json_params;

    public function __construct( array $json_params = [] ) {
        $this->json_params = $json_params;
    }

    public function get_json_params() {
        return $this->json_params;
    }

    public function get_body_params() {
        return $this->json_params;
    }
}
