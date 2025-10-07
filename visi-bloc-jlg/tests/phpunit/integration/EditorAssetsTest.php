<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../includes/assets.php';
require_once __DIR__ . '/../../../includes/admin-settings.php';

class EditorAssetsTest extends TestCase {
    private string $assetFile;
    private string $assetBackup;

    protected function setUp(): void {
        parent::setUp();

        if ( function_exists( 'visibloc_test_reset_state' ) ) {
            visibloc_test_reset_state();
        }

        $this->assetFile   = dirname( __DIR__, 3 ) . '/build/index.asset.php';
        $this->assetBackup = $this->assetFile . '.bak-test';

        if ( isset( $GLOBALS['visibloc_test_transients'][ VISIBLOC_JLG_MISSING_EDITOR_ASSETS_TRANSIENT ] ) ) {
            unset( $GLOBALS['visibloc_test_transients'][ VISIBLOC_JLG_MISSING_EDITOR_ASSETS_TRANSIENT ] );
        }
    }

    protected function tearDown(): void {
        if ( file_exists( $this->assetBackup ) && ! file_exists( $this->assetFile ) ) {
            rename( $this->assetBackup, $this->assetFile );
        } elseif ( file_exists( $this->assetBackup ) ) {
            unlink( $this->assetBackup );
        }

        delete_transient( VISIBLOC_JLG_MISSING_EDITOR_ASSETS_TRANSIENT );

        if ( function_exists( 'visibloc_jlg_clear_editor_data_cache' ) ) {
            visibloc_jlg_clear_editor_data_cache();
        }

        if ( function_exists( 'visibloc_test_reset_state' ) ) {
            visibloc_test_reset_state();
        }

        parent::tearDown();
    }

    public function test_missing_asset_sets_transient_flag(): void {
        $this->assertFileExists( $this->assetFile );
        $this->temporarilyRemoveAssetFile();

        try {
            $this->assertFalse( get_transient( VISIBLOC_JLG_MISSING_EDITOR_ASSETS_TRANSIENT ) );

            visibloc_jlg_enqueue_editor_assets();

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
        visibloc_jlg_render_missing_editor_assets_notice();
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
        visibloc_jlg_render_missing_editor_assets_notice();
        $output = ob_get_clean();

        $this->assertSame( '', trim( $output ) );
    }

    public function test_editor_post_types_results_cached(): void {
        global $visibloc_test_post_types, $visibloc_test_counters;

        $visibloc_test_post_types = [
            'post' => [
                'args'   => [ 'public' => true ],
                'object' => (object) [
                    'label'  => 'Articles',
                    'labels' => (object) [ 'singular_name' => 'Article' ],
                ],
            ],
            'page' => [
                'args'   => [ 'public' => true ],
                'object' => (object) [
                    'label'  => 'Pages',
                    'labels' => (object) [ 'singular_name' => 'Page' ],
                ],
            ],
            'private_type' => [
                'args'   => [ 'public' => false ],
                'object' => (object) [ 'label' => 'Privé' ],
            ],
        ];

        $visibloc_test_counters['get_post_types'] = 0;

        $first = visibloc_jlg_get_editor_post_types();

        $this->assertCount( 2, $first );
        $this->assertSame( 1, $visibloc_test_counters['get_post_types'] ?? 0 );

        $visibloc_test_post_types['post']['object']->labels->singular_name = 'Article modifié';

        $second = visibloc_jlg_get_editor_post_types();

        $this->assertSame( $first, $second );
        $this->assertSame( 1, $visibloc_test_counters['get_post_types'] ?? 0 );
    }

    public function test_editor_taxonomies_cache_limits_term_queries(): void {
        global $visibloc_test_taxonomies, $visibloc_test_terms, $visibloc_test_counters;

        $visibloc_test_taxonomies = [
            'category' => [
                'args'   => [ 'public' => true ],
                'object' => (object) [
                    'labels' => (object) [ 'singular_name' => 'Catégorie' ],
                ],
            ],
        ];

        $visibloc_test_terms = [
            'category' => [
                [ 'term_id' => 1, 'name' => 'Actualités', 'slug' => 'actualites' ],
                [ 'term_id' => 2, 'name' => 'Mises à jour', 'slug' => 'mises-a-jour' ],
            ],
        ];

        $visibloc_test_counters['get_taxonomies'] = 0;
        $visibloc_test_counters['get_terms']      = 0;

        $first  = visibloc_jlg_get_editor_taxonomies();
        $second = visibloc_jlg_get_editor_taxonomies();

        $this->assertSame( $first, $second );
        $this->assertSame( 1, $visibloc_test_counters['get_taxonomies'] ?? 0 );
        $this->assertSame( 1, $visibloc_test_counters['get_terms'] ?? 0 );
    }

    public function test_editor_cache_can_be_cleared_explicitly(): void {
        global $visibloc_test_post_types, $visibloc_test_counters;

        $visibloc_test_post_types = [
            'post' => [
                'args'   => [ 'public' => true ],
                'object' => (object) [
                    'label'  => 'Articles',
                    'labels' => (object) [ 'singular_name' => 'Article' ],
                ],
            ],
            'page' => [
                'args'   => [ 'public' => true ],
                'object' => (object) [
                    'label'  => 'Pages',
                    'labels' => (object) [ 'singular_name' => 'Page' ],
                ],
            ],
        ];

        $visibloc_test_counters['get_post_types'] = 0;

        $initial = visibloc_jlg_get_editor_post_types();
        $this->assertCount( 2, $initial );
        $this->assertSame( 1, $visibloc_test_counters['get_post_types'] ?? 0 );

        visibloc_jlg_clear_editor_data_cache( 'post_types' );

        $visibloc_test_post_types['post']['object']->labels->singular_name = 'Article mis à jour';

        $refreshed = visibloc_jlg_get_editor_post_types();
        $labels    = [];

        foreach ( $refreshed as $definition ) {
            if ( isset( $definition['value'], $definition['label'] ) ) {
                $labels[ $definition['value'] ] = $definition['label'];
            }
        }

        $this->assertSame( 2, $visibloc_test_counters['get_post_types'] ?? 0 );
        $this->assertSame( 'Article mis à jour', $labels['post'] ?? '' );
    }

    public function test_editor_cache_respects_disable_filter(): void {
        global $visibloc_test_post_types, $visibloc_test_counters;

        $visibloc_test_post_types = [
            'post' => [
                'args'   => [ 'public' => true ],
                'object' => (object) [
                    'label'  => 'Articles',
                    'labels' => (object) [ 'singular_name' => 'Article' ],
                ],
            ],
        ];

        $visibloc_test_counters['get_post_types'] = 0;

        $filter = static function ( $enabled, $slug ) {
            if ( 'post_types' === $slug ) {
                return false;
            }

            return $enabled;
        };

        add_filter( 'visibloc_jlg_use_editor_data_cache', $filter, 10, 2 );

        try {
            visibloc_jlg_get_editor_post_types();
            visibloc_jlg_get_editor_post_types();

            $this->assertSame( 2, $visibloc_test_counters['get_post_types'] ?? 0 );
        } finally {
            remove_filter( 'visibloc_jlg_use_editor_data_cache', $filter, 10 );
        }
    }

    public function test_editor_roles_results_cached_and_sanitized(): void {
        global $visibloc_test_state, $visibloc_test_counters;

        $visibloc_test_state['roles'] = [
            ' administrator ' => (object) [ 'name' => '  <strong>Administrateur</strong>  ' ],
            'editor<script>' => (object) [ 'name' => '<span>Éditeur</span>' ],
            'emptylabel'      => (object) [ 'name' => '' ],
        ];

        $visibloc_test_counters['wp_roles_get_names'] = 0;

        $first = visibloc_jlg_get_editor_roles();

        $this->assertSame( 1, $visibloc_test_counters['wp_roles_get_names'] ?? 0 );
        $this->assertArrayHasKey( 'administrator', $first );
        $this->assertSame( 'Administrateur', $first['administrator'] );
        $this->assertArrayHasKey( 'editorscript', $first );
        $this->assertSame( 'Éditeur', $first['editorscript'] );
        $this->assertArrayHasKey( 'emptylabel', $first );
        $this->assertSame( 'Emptylabel', $first['emptylabel'] );

        $visibloc_test_state['roles'][' administrator ']->name = 'Nouvel Administrateur';

        $second = visibloc_jlg_get_editor_roles();

        $this->assertSame( $first, $second );
        $this->assertSame( 1, $visibloc_test_counters['wp_roles_get_names'] ?? 0 );
    }

    public function test_editor_roles_cache_cleared_on_role_changes(): void {
        global $visibloc_test_state, $visibloc_test_counters;

        $visibloc_test_state['roles'] = [
            'administrator' => (object) [ 'name' => 'Administrateur' ],
        ];

        $visibloc_test_counters['wp_roles_get_names'] = 0;

        $initial = visibloc_jlg_get_editor_roles();

        $this->assertSame( 1, $visibloc_test_counters['wp_roles_get_names'] ?? 0 );
        $this->assertSame( 'Administrateur', $initial['administrator'] ?? '' );

        $visibloc_test_state['roles']['administrator']->name = 'Responsable';

        $cached = visibloc_jlg_get_editor_roles();

        $this->assertSame( 'Administrateur', $cached['administrator'] ?? '' );
        $this->assertSame( 1, $visibloc_test_counters['wp_roles_get_names'] ?? 0 );

        visibloc_jlg_clear_editor_role_groups_cache_on_change();

        $refreshed = visibloc_jlg_get_editor_roles();

        $this->assertSame( 'Responsable', $refreshed['administrator'] ?? '' );
        $this->assertSame( 2, $visibloc_test_counters['wp_roles_get_names'] ?? 0 );
    }

    public function test_editor_roles_cache_disabled_when_filtered(): void {
        global $visibloc_test_state, $visibloc_test_counters;

        $visibloc_test_state['roles'] = [
            'administrator' => (object) [ 'name' => 'Administrateur' ],
        ];

        $visibloc_test_counters['wp_roles_get_names'] = 0;
        $filter_calls                                     = 0;

        add_filter(
            'visibloc_jlg_role_labels',
            static function ( $roles ) use ( &$filter_calls ) {
                $filter_calls++;
                $roles['custom'] = 'Personnalisé';

                return $roles;
            }
        );

        try {
            $first = visibloc_jlg_get_editor_roles();
            $second = visibloc_jlg_get_editor_roles();

            $this->assertArrayHasKey( 'custom', $first );
            $this->assertArrayHasKey( 'custom', $second );
            $this->assertSame( 2, $filter_calls );
            $this->assertSame( 2, $visibloc_test_counters['wp_roles_get_names'] ?? 0 );
        } finally {
            remove_all_filters( 'visibloc_jlg_role_labels' );
        }
    }

    public function test_global_cache_clear_flushes_editor_data(): void {
        global $visibloc_test_post_types, $visibloc_test_counters;

        $visibloc_test_post_types = [
            'post' => [
                'args'   => [ 'public' => true ],
                'object' => (object) [
                    'label'  => 'Articles',
                    'labels' => (object) [ 'singular_name' => 'Article' ],
                ],
            ],
        ];

        $visibloc_test_counters['get_post_types'] = 0;

        visibloc_jlg_get_editor_post_types();

        $visibloc_test_post_types['post']['object']->labels->singular_name = 'Nouvel article';

        visibloc_jlg_clear_caches();

        $recomputed = visibloc_jlg_get_editor_post_types();
        $labels     = [];

        foreach ( $recomputed as $definition ) {
            if ( isset( $definition['value'], $definition['label'] ) ) {
                $labels[ $definition['value'] ] = $definition['label'];
            }
        }

        $this->assertSame( 2, $visibloc_test_counters['get_post_types'] ?? 0 );
        $this->assertSame( 'Nouvel article', $labels['post'] ?? '' );
    }

    public function test_user_segments_filter_populates_editor_data(): void {
        $callback = static function () {
            return [
                [
                    'value' => 'vip_clients',
                    'label' => 'VIP clients',
                ],
                [
                    'value' => 'warm_leads',
                    'label' => 'Leads chauds',
                ],
                [
                    'value' => '',
                    'label' => 'Should be ignored',
                ],
                [
                    'value' => 'fallback_segment',
                ],
            ];
        };

        add_filter( 'visibloc_jlg_user_segments', $callback );

        try {
            $segments = visibloc_jlg_get_editor_user_segments();

            $this->assertSame(
                [
                    [ 'value' => 'fallback_segment', 'label' => 'fallback_segment' ],
                    [ 'value' => 'warm_leads', 'label' => 'Leads chauds' ],
                    [ 'value' => 'vip_clients', 'label' => 'VIP clients' ],
                ],
                $segments,
                'Segments should be sanitized and sorted alphabetically by label.'
            );
        } finally {
            remove_filter( 'visibloc_jlg_user_segments', $callback );
        }
    }

    public function test_editor_common_cookies_filter_sanitizes_entries(): void {
        add_filter(
            'visibloc_jlg_common_cookies',
            static function () {
                return [
                    [ 'value' => 'session_cookie', 'label' => 'Session Cookie' ],
                    [ 'value' => '', 'label' => 'Ignored' ],
                    'not-an-array',
                    [ 'value' => 'custom_cookie' ],
                ];
            }
        );

        try {
            $cookies = visibloc_jlg_get_editor_common_cookies();

            $this->assertSame(
                [
                    [ 'value' => 'session_cookie', 'label' => 'Session Cookie' ],
                    [ 'value' => 'custom_cookie', 'label' => 'custom_cookie' ],
                ],
                $cookies,
                'Custom cookie definitions should be sanitized and retain labels when provided.'
            );
        } finally {
            remove_all_filters( 'visibloc_jlg_common_cookies' );
        }
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
