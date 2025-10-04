<?php

use PHPUnit\Framework\TestCase;

class SettingsSnapshotImportExportTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();

        if ( ! function_exists( 'visibloc_jlg_get_settings_snapshot' ) ) {
            require_once dirname( __DIR__, 3 ) . '/includes/admin-settings.php';
        }

        visibloc_test_reset_state();

        $GLOBALS['visibloc_posts']           = [];
        $GLOBALS['visibloc_test_options']    = [];
        $GLOBALS['visibloc_test_transients'] = [];

        visibloc_jlg_get_fallback_settings( true );
        visibloc_jlg_get_global_fallback_markup( true );
    }

    protected function tearDown(): void {
        $GLOBALS['visibloc_posts']           = [];
        $GLOBALS['visibloc_test_options']    = [];
        $GLOBALS['visibloc_test_transients'] = [];

        visibloc_test_reset_state();

        visibloc_jlg_get_fallback_settings( true );
        visibloc_jlg_get_global_fallback_markup( true );

        parent::tearDown();
    }

    public function test_export_then_import_restores_fallback_settings(): void {
        $initial_fallback = [
            'mode'     => 'text',
            'text'     => '<strong>Contenu de repli</strong>',
            'block_id' => 0,
        ];

        update_option( 'visibloc_fallback_settings', $initial_fallback );
        visibloc_jlg_get_fallback_settings( true );

        $snapshot = visibloc_jlg_get_settings_snapshot();

        $this->assertArrayHasKey( 'fallback', $snapshot, 'The snapshot should expose fallback settings.' );
        $this->assertSame(
            visibloc_jlg_normalize_fallback_settings( $initial_fallback ),
            $snapshot['fallback'],
            'Exported fallback settings should be normalized.'
        );

        update_option(
            'visibloc_fallback_settings',
            [
                'mode'     => 'none',
                'text'     => '',
                'block_id' => 0,
            ]
        );
        visibloc_jlg_get_fallback_settings( true );

        $result = visibloc_jlg_import_settings_snapshot( wp_json_encode( $snapshot ) );

        $this->assertTrue( $result, 'Importing a valid snapshot should succeed.' );

        $restored = get_option( 'visibloc_fallback_settings', [] );

        $this->assertSame(
            visibloc_jlg_normalize_fallback_settings( $initial_fallback ),
            visibloc_jlg_normalize_fallback_settings( $restored ),
            'Fallback settings should be restored after an export/import round-trip.'
        );
    }
}
