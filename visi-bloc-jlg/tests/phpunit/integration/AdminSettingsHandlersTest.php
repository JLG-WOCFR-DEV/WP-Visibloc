<?php

use PHPUnit\Framework\TestCase;

if ( ! class_exists( 'Visibloc_Test_Redirect_Exception' ) ) {
    class Visibloc_Test_Redirect_Exception extends Exception {}
}

if ( ! function_exists( 'add_query_arg' ) ) {
    function add_query_arg( $key, $value = null, $url = '' ) {
        if ( is_array( $key ) ) {
            $params = $key;
            $url    = (string) $value;
        } else {
            $params = [ $key => $value ];
            $url    = (string) $url;
        }

        $fragment = '';
        $fragment_position = strpos( $url, '#' );

        if ( false !== $fragment_position ) {
            $fragment = substr( $url, $fragment_position );
            $url      = substr( $url, 0, $fragment_position );
        }

        $query_pos = strpos( $url, '?' );
        $base      = $url;
        $query     = '';

        if ( false !== $query_pos ) {
            $query = substr( $url, $query_pos + 1 );
            $base  = substr( $url, 0, $query_pos );
        }

        parse_str( $query, $query_args );

        foreach ( $params as $param_key => $param_value ) {
            if ( null === $param_value ) {
                unset( $query_args[ $param_key ] );
            } else {
                $query_args[ $param_key ] = $param_value;
            }
        }

        $new_query = http_build_query( $query_args );

        if ( '' === $new_query ) {
            return $base . $fragment;
        }

        return $base . '?' . $new_query . $fragment;
    }
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
    function wp_verify_nonce( $nonce, $action ) {
        return $nonce === 'nonce-' . $action;
    }
}

if ( ! function_exists( 'wp_safe_redirect' ) ) {
    function wp_safe_redirect( $location, $status = 302, $x_redirect_by = 'WordPress' ) {
        global $visibloc_test_redirect_state;

        $visibloc_test_redirect_state = [
            'location'      => $location,
            'status'        => $status,
            'x_redirect_by' => $x_redirect_by,
        ];

        throw new Visibloc_Test_Redirect_Exception();
    }
}

if ( ! function_exists( 'get_editable_roles' ) ) {
    function get_editable_roles() {
        return [
            'administrator' => [ 'name' => 'Administrator' ],
            'editor'        => [ 'name' => 'Editor' ],
            'author'        => [ 'name' => 'Author' ],
        ];
    }
}

if ( ! function_exists( 'get_taxonomies' ) ) {
    function get_taxonomies( $args = [], $output = 'names' ) {
        global $visibloc_test_taxonomies;

        $definitions = is_array( $visibloc_test_taxonomies ?? null ) ? $visibloc_test_taxonomies : [];
        $filtered    = [];

        foreach ( $definitions as $slug => $definition ) {
            $definition_args   = isset( $definition['args'] ) && is_array( $definition['args'] ) ? $definition['args'] : [];
            $definition_object = $definition['object'] ?? (object) [];
            $matches           = true;

            foreach ( (array) $args as $key => $value ) {
                if ( ! array_key_exists( $key, $definition_args ) || $definition_args[ $key ] !== $value ) {
                    $matches = false;
                    break;
                }
            }

            if ( ! $matches ) {
                continue;
            }

            if ( 'objects' === $output ) {
                $filtered[ $slug ] = $definition_object;
            } else {
                $filtered[] = $slug;
            }
        }

        return $filtered;
    }
}

if ( ! function_exists( 'taxonomy_exists' ) ) {
    function taxonomy_exists( $taxonomy ) {
        global $visibloc_test_taxonomies;

        $definitions = is_array( $visibloc_test_taxonomies ?? null ) ? $visibloc_test_taxonomies : [];

        return array_key_exists( $taxonomy, $definitions );
    }
}

if ( ! function_exists( 'get_terms' ) ) {
    function get_terms( $args = [] ) {
        global $visibloc_test_terms;

        if ( ! is_array( $args ) ) {
            return [];
        }

        $taxonomy = $args['taxonomy'] ?? '';
        $limit    = isset( $args['number'] ) ? (int) $args['number'] : 0;
        $order    = strtoupper( $args['order'] ?? 'ASC' );
        $orderby  = $args['orderby'] ?? 'name';

        $registered_terms = $visibloc_test_terms[ $taxonomy ] ?? [];
        $items            = [];

        foreach ( $registered_terms as $term ) {
            $items[] = new WP_Term(
                [
                    'term_id' => $term['term_id'] ?? 0,
                    'name'    => $term['name'] ?? '',
                    'slug'    => $term['slug'] ?? '',
                ]
            );
        }

        $comparator = static function ( WP_Term $a, WP_Term $b ) use ( $orderby ) {
            $value_a = $a->{$orderby} ?? '';
            $value_b = $b->{$orderby} ?? '';

            return strcasecmp( (string) $value_a, (string) $value_b );
        };

        usort( $items, $comparator );

        if ( 'DESC' === $order ) {
            $items = array_reverse( $items );
        }

        if ( $limit > 0 ) {
            $items = array_slice( $items, 0, $limit );
        }

        return $items;
    }
}

if ( ! defined( 'VISIBLOC_JLG_DISABLE_EXIT' ) ) {
    define( 'VISIBLOC_JLG_DISABLE_EXIT', true );
}

class AdminSettingsHandlersTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();

        if ( ! function_exists( 'visibloc_jlg_handle_options_save' ) ) {
            require_once dirname( __DIR__, 3 ) . '/includes/admin-settings.php';
        }

        visibloc_test_reset_state();

        $GLOBALS['visibloc_test_state']['current_user'] = new Visibloc_Test_User( 1, [ 'administrator' ] );
        $GLOBALS['visibloc_test_state']['roles']['administrator']->capabilities['manage_options'] = true;

        global $visibloc_test_redirect_state;
        $visibloc_test_redirect_state = [];
    }

    protected function tearDown(): void {
        visibloc_test_reset_state();

        parent::tearDown();
    }

    private function seedCaches(): void {
        $GLOBALS['visibloc_test_transients'] = [
            'visibloc_hidden_posts'         => [ 'value' => [ 1 ], 'expires' => 0 ],
            'visibloc_device_posts'         => [ 'value' => [ 2 ], 'expires' => 0 ],
            'visibloc_scheduled_posts'      => [ 'value' => [ 3 ], 'expires' => 0 ],
            'visibloc_group_block_metadata' => [ 'value' => [ 'meta' ], 'expires' => 0 ],
            'visibloc_device_css_bucket-one' => [ 'value' => 'css', 'expires' => 0 ],
        ];

        $GLOBALS['visibloc_test_options']['visibloc_device_css_transients'] = [ 'bucket-one' ];

        if ( ! isset( $GLOBALS['visibloc_test_object_cache']['visibloc_jlg'] ) ) {
            $GLOBALS['visibloc_test_object_cache']['visibloc_jlg'] = [];
        }

        $GLOBALS['visibloc_test_object_cache']['visibloc_jlg']['visibloc_device_css_cache'] = [
            'value'   => [ 'bucket-one' => 'css-content' ],
            'expires' => 0,
        ];
    }

    private function assertCachesCleared(): void {
        $transients = $GLOBALS['visibloc_test_transients'] ?? [];

        foreach ( [
            'visibloc_hidden_posts',
            'visibloc_device_posts',
            'visibloc_scheduled_posts',
            'visibloc_group_block_metadata',
            'visibloc_device_css_bucket-one',
        ] as $key ) {
            $this->assertArrayNotHasKey( $key, $transients, sprintf( 'Transient "%s" should be cleared.', $key ) );
        }

        $options = $GLOBALS['visibloc_test_options'] ?? [];
        $this->assertArrayNotHasKey(
            'visibloc_device_css_transients',
            $options,
            'Device CSS transient registry should be cleared.'
        );

        $object_cache = $GLOBALS['visibloc_test_object_cache'] ?? [];
        $cache_group  = $object_cache['visibloc_jlg'] ?? [];

        $this->assertArrayNotHasKey(
            'visibloc_device_css_cache',
            $cache_group,
            'Device CSS object cache should be cleared.'
        );
    }

    private function dispatchSettingsRequest( string $nonce_action, array $post_data = [], bool $seed_caches = true ): array {
        global $visibloc_test_redirect_state;

        if ( $seed_caches ) {
            $this->seedCaches();
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST                     = array_merge( $post_data, [ 'visibloc_nonce' => 'nonce-' . $nonce_action ] );
        $visibloc_test_redirect_state = [];

        try {
            visibloc_jlg_handle_options_save();
        } catch ( Visibloc_Test_Redirect_Exception $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        }

        return $visibloc_test_redirect_state;
    }

    public function test_supported_blocks_submission_updates_option_and_redirects(): void {
        $redirect_state = $this->dispatchSettingsRequest(
            'visibloc_save_supported_blocks',
            [
                'visibloc_supported_blocks' => [ 'core/group', 'core/paragraph', 'core/group' ],
            ]
        );

        $saved_blocks = get_option( 'visibloc_supported_blocks', [] );

        $this->assertSame(
            [ 'core/group', 'core/paragraph' ],
            $saved_blocks,
            'Supported blocks should be normalized and saved.'
        );

        $this->assertCachesCleared();

        $this->assertSame(
            'https://example.test/wp-admin/admin.php?page=visi-bloc-jlg-help&status=updated',
            $redirect_state['location'] ?? '',
            'Saving supported blocks should redirect to the success notice.'
        );
    }

    public function test_toggle_debug_submission_switches_option_and_redirects(): void {
        update_option( 'visibloc_debug_mode', 'off' );

        $redirect_state = $this->dispatchSettingsRequest( 'visibloc_toggle_debug' );

        $this->assertSame( 'on', get_option( 'visibloc_debug_mode' ) );

        $this->assertCachesCleared();

        $this->assertSame(
            'https://example.test/wp-admin/admin.php?page=visi-bloc-jlg-help&status=updated',
            $redirect_state['location'] ?? ''
        );
    }

    public function test_breakpoints_submission_updates_values_and_redirects(): void {
        update_option( 'visibloc_breakpoint_mobile', 781 );
        update_option( 'visibloc_breakpoint_tablet', 1024 );

        $redirect_state = $this->dispatchSettingsRequest(
            'visibloc_save_breakpoints',
            [
                'visibloc_breakpoint_mobile' => '900',
                'visibloc_breakpoint_tablet' => '1200',
            ]
        );

        $this->assertSame( 900, get_option( 'visibloc_breakpoint_mobile' ) );
        $this->assertSame( 1200, get_option( 'visibloc_breakpoint_tablet' ) );

        $this->assertCachesCleared();

        $this->assertSame(
            'https://example.test/wp-admin/admin.php?page=visi-bloc-jlg-help&status=updated',
            $redirect_state['location'] ?? ''
        );
    }

    public function test_fallback_submission_updates_settings_and_redirects(): void {
        $redirect_state = $this->dispatchSettingsRequest(
            'visibloc_save_fallback',
            [
                'visibloc_fallback_mode'     => 'text',
                'visibloc_fallback_text'     => '<strong>Fallback</strong>',
                'visibloc_fallback_block_id' => '42',
            ]
        );

        $expected_settings = visibloc_jlg_normalize_fallback_settings(
            [
                'mode'     => 'text',
                'text'     => '<strong>Fallback</strong>',
                'block_id' => 42,
            ]
        );

        $this->assertSame( $expected_settings, get_option( 'visibloc_fallback_settings' ) );

        $this->assertCachesCleared();

        $this->assertSame(
            'https://example.test/wp-admin/admin.php?page=visi-bloc-jlg-help&status=updated',
            $redirect_state['location'] ?? ''
        );
    }

    public function test_permissions_submission_updates_roles_and_redirects(): void {
        $redirect_state = $this->dispatchSettingsRequest(
            'visibloc_save_permissions',
            [
                'visibloc_preview_roles' => [ 'editor', 'author', 'editor', 'ghost' ],
            ]
        );

        $saved_roles = $GLOBALS['visibloc_test_options']['visibloc_preview_roles'] ?? [];

        $this->assertSame(
            [ 'editor', 'author', 'administrator' ],
            $saved_roles,
            'Preview roles should include unique editable roles and always keep administrators.'
        );

        $this->assertCachesCleared();

        $this->assertSame(
            'https://example.test/wp-admin/admin.php?page=visi-bloc-jlg-help&status=updated',
            $redirect_state['location'] ?? ''
        );
    }

    public function test_import_settings_submission_updates_options_and_redirects(): void {
        $payload = wp_json_encode(
            [
                'supported_blocks' => [ 'core/group', 'core/cover' ],
                'breakpoints'      => [ 'mobile' => 800, 'tablet' => 1100 ],
                'preview_roles'    => [ 'editor' ],
                'debug_mode'       => 'on',
                'fallback'         => [
                    'mode'     => 'text',
                    'text'     => 'Hello',
                    'block_id' => 0,
                ],
            ]
        );

        $redirect_state = $this->dispatchSettingsRequest(
            'visibloc_import_settings',
            [ 'visibloc_settings_payload' => $payload ]
        );

        $this->assertSame( [ 'core/group', 'core/cover' ], get_option( 'visibloc_supported_blocks' ) );
        $this->assertSame( 800, get_option( 'visibloc_breakpoint_mobile' ) );
        $this->assertSame( 1100, get_option( 'visibloc_breakpoint_tablet' ) );
        $saved_roles = $GLOBALS['visibloc_test_options']['visibloc_preview_roles'] ?? [];
        $this->assertSame( [ 'editor', 'administrator' ], $saved_roles );
        $this->assertSame( 'on', get_option( 'visibloc_debug_mode' ) );

        $this->assertSame(
            visibloc_jlg_normalize_fallback_settings(
                [ 'mode' => 'text', 'text' => 'Hello', 'block_id' => 0 ]
            ),
            get_option( 'visibloc_fallback_settings' )
        );

        $this->assertCachesCleared();

        $this->assertSame(
            'https://example.test/wp-admin/admin.php?page=visi-bloc-jlg-help&status=settings_imported',
            $redirect_state['location'] ?? ''
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_export_settings_submission_streams_snapshot(): void {
        $this->seedCaches();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST                     = [ 'visibloc_nonce' => 'nonce-visibloc_export_settings' ];

        ob_start();
        visibloc_jlg_handle_options_save();
        $output = ob_get_clean();

        $this->assertNotSame( '', $output, 'Export should produce a JSON payload.' );

        $decoded = json_decode( $output, true );

        $this->assertIsArray( $decoded, 'Exported payload should be valid JSON.' );
        $this->assertArrayHasKey( 'supported_blocks', $decoded );
        $this->assertArrayHasKey( 'breakpoints', $decoded );

        // Export does not change options but should leave caches untouched when exit is disabled.
        $this->assertArrayHasKey( 'visibloc_hidden_posts', $GLOBALS['visibloc_test_transients'] );
    }
}

