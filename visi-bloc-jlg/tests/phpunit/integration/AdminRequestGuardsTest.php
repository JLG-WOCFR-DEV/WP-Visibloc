<?php

use PHPUnit\Framework\TestCase;
use Visibloc\Tests\Support\PluginFacade;
use Visibloc\Tests\Support\TestServices;

require_once __DIR__ . '/../role-switcher-test-loader.php';

class AdminRequestGuardsTest extends TestCase {
    private PluginFacade $plugin;

    protected function setUp(): void {
        parent::setUp();

        visibloc_test_reset_state();
        $this->plugin = TestServices::plugin();
    }

    protected function tearDown(): void {
        visibloc_test_reset_state();

        parent::tearDown();
    }

    private function configureEnvironment( array $overrides = [], array $query_args = [] ): void {
        visibloc_test_set_request_environment( $overrides );

        $_GET = [];

        foreach ( $query_args as $key => $value ) {
            if ( null === $value ) {
                unset( $_GET[ $key ] );
                continue;
            }

            $_GET[ $key ] = $value;
        }
    }

    public function test_classic_admin_request_is_detected(): void {
        $this->configureEnvironment(
            [
                'is_admin'    => true,
                'request_uri' => '/wp-admin/index.php',
            ]
        );

        $this->assertTrue( $this->plugin->isAdminOrTechnicalRequest() );
    }

    public function test_dashboard_ajax_request_is_detected(): void {
        $this->configureEnvironment(
            [
                'doing_ajax'  => true,
                'request_uri' => '/wp-admin/admin-ajax.php',
                'referer'     => admin_url( 'post.php' ),
            ]
        );

        $this->assertTrue( $this->plugin->isAdminOrTechnicalRequest() );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_rest_request_with_edit_context_is_detected(): void {
        require_once __DIR__ . '/../role-switcher-test-loader.php';

        visibloc_test_reset_state();

        $this->configureEnvironment(
            [
                'request_uri' => '/wp-json/wp/v2/posts?context=edit',
                'referer'     => 'https://example.test/wp-json/wp/v2/posts',
            ],
            [
                'context' => 'edit',
            ]
        );

        if ( ! defined( 'REST_REQUEST' ) ) {
            define( 'REST_REQUEST', true );
        }

        $this->assertTrue( $this->plugin->isAdminOrTechnicalRequest() );
    }

    public function test_cron_execution_is_detected(): void {
        $this->configureEnvironment(
            [
                'doing_cron' => true,
            ]
        );

        $this->assertTrue( $this->plugin->isAdminOrTechnicalRequest() );
    }

    public function test_front_end_request_is_not_detected(): void {
        $this->configureEnvironment(
            [
                'request_uri' => '/',
                'referer'     => 'https://example.test/',
            ]
        );

        $this->assertFalse( $this->plugin->isAdminOrTechnicalRequest() );
    }
}
