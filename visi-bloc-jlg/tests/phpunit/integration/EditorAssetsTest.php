<?php

use PHPUnit\Framework\TestCase;
use Visibloc\Tests\Support\PluginFacade;
use Visibloc\Tests\Support\TestServices;

require_once __DIR__ . '/../../../includes/assets.php';

class EditorAssetsTest extends TestCase {
    private string $assetFile;
    private string $assetBackup;
    private $previousUser;
    private array $previousRoles;
    private PluginFacade $plugin;

    protected function setUp(): void {
        parent::setUp();

        $this->assetFile   = dirname( __DIR__, 3 ) . '/build/index.asset.php';
        $this->assetBackup = $this->assetFile . '.bak-test';
        $this->previousUser = $GLOBALS['visibloc_test_state']['current_user'] ?? null;
        $this->previousRoles = $GLOBALS['visibloc_test_state']['roles'] ?? [];

        if ( isset( $GLOBALS['visibloc_test_transients'][ VISIBLOC_JLG_MISSING_EDITOR_ASSETS_TRANSIENT ] ) ) {
            unset( $GLOBALS['visibloc_test_transients'][ VISIBLOC_JLG_MISSING_EDITOR_ASSETS_TRANSIENT ] );
        }

        $this->plugin = TestServices::plugin();
    }

    protected function tearDown(): void {
        if ( file_exists( $this->assetBackup ) && ! file_exists( $this->assetFile ) ) {
            rename( $this->assetBackup, $this->assetFile );
        } elseif ( file_exists( $this->assetBackup ) ) {
            unlink( $this->assetBackup );
        }

        $GLOBALS['visibloc_test_state']['current_user'] = $this->previousUser;
        $GLOBALS['visibloc_test_state']['roles']        = $this->previousRoles;

        delete_transient( VISIBLOC_JLG_MISSING_EDITOR_ASSETS_TRANSIENT );

        parent::tearDown();
    }

    public function test_missing_asset_sets_transient_flag(): void {
        $this->assertFileExists( $this->assetFile );
        $this->temporarilyRemoveAssetFile();

        try {
            $this->assertFalse( get_transient( VISIBLOC_JLG_MISSING_EDITOR_ASSETS_TRANSIENT ) );

            $this->plugin->enqueueEditorAssets();

            $this->assertNotFalse( get_transient( VISIBLOC_JLG_MISSING_EDITOR_ASSETS_TRANSIENT ) );
        } finally {
            $this->restoreAssetFile();
        }
    }

    public function test_notice_rendered_for_users_who_manage_options(): void {
        set_transient( VISIBLOC_JLG_MISSING_EDITOR_ASSETS_TRANSIENT, true, 0 );

        $administrator = $GLOBALS['visibloc_test_state']['roles']['administrator'] ?? (object) [
            'name'         => 'Administrator',
            'capabilities' => [],
        ];
        $administrator->capabilities['manage_options'] = true;
        $GLOBALS['visibloc_test_state']['roles']['administrator'] = $administrator;
        $GLOBALS['visibloc_test_state']['current_user']            = new Visibloc_Test_User( 1, [ 'administrator' ] );

        ob_start();
        $this->plugin->renderMissingEditorAssetsNotice();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'notice notice-error', $output );
        $this->assertStringContainsString( 'npm install && npm run build', $output );
    }

    public function test_notice_not_rendered_for_users_without_capability(): void {
        set_transient( VISIBLOC_JLG_MISSING_EDITOR_ASSETS_TRANSIENT, true, 0 );
        $GLOBALS['visibloc_test_state']['current_user'] = new Visibloc_Test_User( 2, [ 'editor' ] );

        $editor = $GLOBALS['visibloc_test_state']['roles']['editor'] ?? (object) [
            'name'         => 'Editor',
            'capabilities' => [],
        ];
        unset( $editor->capabilities['manage_options'] );
        $GLOBALS['visibloc_test_state']['roles']['editor'] = $editor;

        ob_start();
        $this->plugin->renderMissingEditorAssetsNotice();
        $output = ob_get_clean();

        $this->assertSame( '', trim( $output ) );
    }

    private function temporarilyRemoveAssetFile(): void {
        if ( file_exists( $this->assetBackup ) ) {
            unlink( $this->assetBackup );
        }

        rename( $this->assetFile, $this->assetBackup );
    }

    private function restoreAssetFile(): void {
        if ( file_exists( $this->assetBackup ) ) {
            rename( $this->assetBackup, $this->assetFile );
        }
    }
}
