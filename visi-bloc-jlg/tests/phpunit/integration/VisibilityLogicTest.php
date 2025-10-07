<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../includes/assets.php';

class VisibilityLogicTest extends TestCase {
    protected function setUp(): void {
        visibloc_test_reset_state();
        remove_all_filters( 'visibloc_supported_blocks' );
        if ( isset( $GLOBALS['visibloc_test_options']['visibloc_supported_blocks'] ) ) {
            unset( $GLOBALS['visibloc_test_options']['visibloc_supported_blocks'] );
        }
    }

    public function test_blocks_without_visibility_rules_do_not_initialize_fallback(): void {
        $filter_calls = 0;
        $hook = 'pre_option_visibloc_fallback_settings';

        $previous_filters = $GLOBALS['visibloc_test_filters'][ $hook ] ?? null;

        $callback = static function ( $pre_value ) use ( &$filter_calls ) {
            $filter_calls++;

            return $pre_value;
        };

        add_filter( $hook, $callback );

        try {
            $block = [
                'blockName' => 'core/group',
                'attrs'     => [],
            ];

            $content = '<p>Visible block</p>';

            $this->assertSame(
                $content,
                visibloc_jlg_render_block_filter( $content, $block ),
                'Blocks without visibility settings should render as-is.'
            );

            $this->assertSame(
                0,
                $filter_calls,
                'Fallback settings should not be loaded when no visibility rules are present.'
            );
        } finally {
            if ( null === $previous_filters ) {
                unset( $GLOBALS['visibloc_test_filters'][ $hook ] );
            } else {
                $GLOBALS['visibloc_test_filters'][ $hook ] = $previous_filters;
            }
        }
    }

    public function test_blocks_with_irrelevant_attributes_do_not_initialize_fallback(): void {
        $filter_calls = 0;
        $hook = 'pre_option_visibloc_fallback_settings';

        $previous_filters = $GLOBALS['visibloc_test_filters'][ $hook ] ?? null;

        $callback = static function ( $pre_value ) use ( &$filter_calls ) {
            $filter_calls++;

            return $pre_value;
        };

        add_filter( $hook, $callback );

        try {
            $block = [
                'blockName' => 'core/group',
                'attrs'     => [
                    'customAttribute'     => 'value',
                    'fallbackBehavior'    => 'text',
                    'fallbackEnabled'     => true,
                    'irrelevantAttribute' => [ 'nested' => 'data' ],
                ],
            ];

            $content = '<p>Visible block</p>';

            $this->assertSame(
                $content,
                visibloc_jlg_render_block_filter( $content, $block ),
                'Blocks without visibility rules should render as-is even when unrelated attributes are present.'
            );

            $this->assertSame(
                0,
                $filter_calls,
                'Fallback settings should remain untouched when no visibility logic applies.'
            );
        } finally {
            if ( null === $previous_filters ) {
                unset( $GLOBALS['visibloc_test_filters'][ $hook ] );
            } else {
                $GLOBALS['visibloc_test_filters'][ $hook ] = $previous_filters;
            }
        }
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

    public function test_hidden_block_placeholder_visible_for_authorized_previewers_with_role_mismatch(): void {
        global $visibloc_test_state;

        $visibloc_test_state['effective_user_id']       = 42;
        $visibloc_test_state['current_user']            = new Visibloc_Test_User( 42, [ 'administrator' ] );
        $visibloc_test_state['can_preview_users'][42]   = true;
        $visibloc_test_state['can_impersonate_users'][42] = true;
        $visibloc_test_state['allowed_preview_roles']   = [ 'administrator', 'editor' ];
        $visibloc_test_state['preview_role']            = 'editor';

        $block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'isHidden'         => true,
                'visibilityRoles'  => [ 'administrator' ],
            ],
        ];

        $output = visibloc_jlg_render_block_filter( '<p>Hidden admin content</p>', $block );

        $this->assertStringContainsString( 'bloc-role-apercu', $output );
        $this->assertStringContainsString( 'Restriction par rôle', $output );
        $this->assertStringContainsString( '<p>Hidden admin content</p>', $output );
    }

    public function test_advanced_rule_combined_with_fallback_displays_custom_markup(): void {
        global $post;

        $previous_post = isset( $post ) ? $post : null;
        $post          = new WP_Post(
            [
                'ID'          => 123,
                'post_type'   => 'page',
                'post_status' => 'publish',
            ]
        );

        try {
            $block = [
                'blockName' => 'core/group',
                'attrs'     => [
                    'advancedVisibility' => [
                        'logic' => 'AND',
                        'rules' => [
                            [
                                'type'     => 'logged_in_status',
                                'operator' => 'is',
                                'value'    => 'logged_in',
                            ],
                        ],
                    ],
                    'fallbackEnabled'     => true,
                    'fallbackBehavior'    => 'text',
                    'fallbackCustomText'  => 'Fallback pour règles avancées',
                ],
            ];

            $output = visibloc_jlg_render_block_filter( '<p>Contenu avancé</p>', $block );

            $this->assertStringContainsString( 'Fallback pour règles avancées', $output );
            $this->assertStringNotContainsString( 'Contenu avancé', $output );
        } finally {
            $post = $previous_post;
        }
    }

    public function test_inverted_schedule_shows_preview_notice_without_fallback(): void {
        global $visibloc_test_state;

        $visibloc_test_state['effective_user_id']             = 7;
        $visibloc_test_state['current_user']                  = new Visibloc_Test_User( 7, [ 'administrator' ] );
        $visibloc_test_state['can_preview_users'][7]          = true;
        $visibloc_test_state['can_impersonate_users'][7]      = true;
        $visibloc_test_state['allowed_preview_roles']         = [ 'administrator' ];
        $visibloc_test_state['preview_role']                  = '';

        $block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'isSchedulingEnabled' => true,
                'publishStartDate'    => '2024-05-01T10:00:00',
                'publishEndDate'      => '2024-04-01T10:00:00',
            ],
        ];

        $output = visibloc_jlg_render_block_filter( '<p>Programmation inversée</p>', $block );

        $this->assertStringContainsString( 'bloc-schedule-error', $output );
        $this->assertStringContainsString( 'Invalid schedule', $output );
        $this->assertStringContainsString( '<p>Programmation inversée</p>', $output );
        $this->assertStringNotContainsString( 'bloc-fallback-apercu', $output );
    }

    public function test_role_restriction_with_global_fallback_displays_replacement(): void {
        $previous_settings = $GLOBALS['visibloc_test_options']['visibloc_fallback_settings'] ?? null;

        try {
            $GLOBALS['visibloc_test_options']['visibloc_fallback_settings'] = [
                'mode' => 'text',
                'text' => 'Fallback global actif',
            ];
            visibloc_jlg_get_fallback_settings( true );
            visibloc_jlg_get_global_fallback_markup( true );

            $block = [
                'blockName' => 'core/group',
                'attrs'     => [
                    'visibilityRoles' => [ 'subscriber' ],
                ],
            ];

            $output = visibloc_jlg_render_block_filter( '<p>Contenu restreint</p>', $block );

            $this->assertStringContainsString( 'Fallback global actif', $output );
            $this->assertStringNotContainsString( 'Contenu restreint', $output );
        } finally {
            if ( null === $previous_settings ) {
                unset( $GLOBALS['visibloc_test_options']['visibloc_fallback_settings'] );
            } else {
                $GLOBALS['visibloc_test_options']['visibloc_fallback_settings'] = $previous_settings;
            }

            visibloc_jlg_get_fallback_settings( true );
            visibloc_jlg_get_global_fallback_markup( true );
        }
    }

    public function test_preview_exposes_reason_for_advanced_rule_with_fallback(): void {
        global $post, $visibloc_test_state;

        $previous_post     = isset( $post ) ? $post : null;
        $previous_state    = $visibloc_test_state;
        $visibloc_test_state['effective_user_id']           = 9;
        $visibloc_test_state['current_user']                = new Visibloc_Test_User( 9, [ 'administrator' ] );
        $visibloc_test_state['can_preview_users'][9]        = true;
        $visibloc_test_state['can_impersonate_users'][9]    = true;
        $visibloc_test_state['allowed_preview_roles']       = [ 'administrator' ];
        $visibloc_test_state['preview_role']                = '';

        $post = new WP_Post(
            [
                'ID'          => 456,
                'post_type'   => 'page',
                'post_status' => 'publish',
            ]
        );

        try {
            $block = [
                'blockName' => 'core/group',
                'attrs'     => [
                    'advancedVisibility' => [
                        'logic' => 'AND',
                        'rules' => [
                            [
                                'type'     => 'logged_in_status',
                                'operator' => 'is',
                                'value'    => 'logged_out',
                            ],
                        ],
                    ],
                    'fallbackEnabled'    => true,
                    'fallbackBehavior'   => 'text',
                    'fallbackCustomText' => 'Aperçu de repli',
                ],
            ];

            $output = visibloc_jlg_render_block_filter( '<p>Contenu avancé</p>', $block );

            $this->assertStringContainsString( 'bloc-advanced-apercu', $output );
            $this->assertStringContainsString( 'bloc-fallback-apercu', $output );
            $this->assertStringContainsString( 'data-visibloc-reason="advanced-rules"', $output );
            $this->assertStringContainsString( 'Aperçu de repli', $output );
        } finally {
            $post                = $previous_post;
            $visibloc_test_state = $previous_state;
            $can_preview         = false;
            visibloc_jlg_get_user_visibility_context( [], $can_preview, true );
        }
    }

    public function test_cookie_rule_matches_expected_value(): void {
        $previous_cookies = $_COOKIE;

        try {
            $_COOKIE = [ 'marketing_flag' => 'vip-customer' ];

            $this->assertTrue(
                visibloc_jlg_match_cookie_rule(
                    [
                        'operator' => 'equals',
                        'name'     => 'marketing_flag',
                        'value'    => 'vip-customer',
                    ]
                ),
                'Cookie rule should match when the value is identical.'
            );

            $this->assertFalse(
                visibloc_jlg_match_cookie_rule(
                    [
                        'operator' => 'not_equals',
                        'name'     => 'marketing_flag',
                        'value'    => 'vip-customer',
                    ]
                ),
                'Negated comparison should fail when cookie matches the given value.'
            );

            $this->assertTrue(
                visibloc_jlg_match_cookie_rule(
                    [
                        'operator' => 'contains',
                        'name'     => 'marketing_flag',
                        'value'    => 'vip',
                    ]
                ),
                'Substring comparisons should detect values within the cookie.'
            );
        } finally {
            $_COOKIE = $previous_cookies;
        }
    }

    public function test_cookie_rule_handles_missing_cookie_for_exists_operator(): void {
        $previous_cookies = $_COOKIE;

        try {
            $_COOKIE = [];

            $this->assertFalse(
                visibloc_jlg_match_cookie_rule(
                    [
                        'operator' => 'exists',
                        'name'     => 'tracking_id',
                    ]
                ),
                'Exists operator should fail when cookie is missing.'
            );

            $this->assertTrue(
                visibloc_jlg_match_cookie_rule(
                    [
                        'operator' => 'not_exists',
                        'name'     => 'tracking_id',
                    ]
                ),
                'Not exists operator should succeed when cookie is absent.'
            );
        } finally {
            $_COOKIE = $previous_cookies;
        }
    }

    public function test_visit_count_rule_comparisons(): void {
        $previous_cookies = $_COOKIE;

        try {
            $_COOKIE = [];
            $cookie_name = visibloc_jlg_get_visit_count_cookie_name();
            $_COOKIE[ $cookie_name ] = '5';

            $this->assertTrue(
                visibloc_jlg_match_visit_count_rule(
                    [
                        'operator'  => 'at_least',
                        'threshold' => 3,
                    ]
                ),
                'at_least should match when visit count is greater than the threshold.'
            );

            $this->assertTrue(
                visibloc_jlg_match_visit_count_rule(
                    [
                        'operator'  => 'at_most',
                        'threshold' => 5,
                    ]
                ),
                'at_most should match when visit count equals the threshold.'
            );

            $this->assertTrue(
                visibloc_jlg_match_visit_count_rule(
                    [
                        'operator'  => 'equals',
                        'threshold' => 5,
                    ]
                ),
                'equals should match when visit count equals the threshold.'
            );

            $this->assertFalse(
                visibloc_jlg_match_visit_count_rule(
                    [
                        'operator'  => 'not_equals',
                        'threshold' => 5,
                    ]
                ),
                'not_equals should fail when visit count equals the threshold.'
            );

            $this->assertTrue(
                visibloc_jlg_match_visit_count_rule(
                    [
                        'operator'  => 'not_equals',
                        'threshold' => 2,
                    ]
                ),
                'not_equals should succeed when visit count differs from the threshold.'
            );
        } finally {
            $_COOKIE = $previous_cookies;
        }
    }

    public function test_visit_count_rule_handles_missing_cookie(): void {
        $previous_cookies = $_COOKIE;

        try {
            $_COOKIE = [];

            $this->assertFalse(
                visibloc_jlg_match_visit_count_rule(
                    [
                        'operator'  => 'at_least',
                        'threshold' => 1,
                    ]
                ),
                'Without a cookie the at_least operator should fail for positive thresholds.'
            );

            $this->assertTrue(
                visibloc_jlg_match_visit_count_rule(
                    [
                        'operator'  => 'at_most',
                        'threshold' => 0,
                    ]
                ),
                'The visit counter defaults to zero when no cookie is set.'
            );
        } finally {
            $_COOKIE = $previous_cookies;
        }
    }

    public function test_visit_count_tracker_increments_cookie(): void {
        $previous_cookies = $_COOKIE;
        $filter           = static function () {
            return true;
        };

        try {
            $_COOKIE = [];
            add_filter( 'visibloc_jlg_should_track_visit_count', $filter, 10, 2 );

            visibloc_jlg_track_visit_count();

            $this->assertSame(
                '1',
                $_COOKIE[ visibloc_jlg_get_visit_count_cookie_name() ] ?? '',
                'First tracking call should initialize the visit counter to 1.'
            );

            visibloc_jlg_track_visit_count();

            $this->assertSame(
                '2',
                $_COOKIE[ visibloc_jlg_get_visit_count_cookie_name() ] ?? '',
                'Second tracking call should increment the counter.'
            );
        } finally {
            remove_filter( 'visibloc_jlg_should_track_visit_count', $filter, 10 );
            $_COOKIE = $previous_cookies;
        }
    }

    public function test_user_segment_rule_uses_filter_callback(): void {
        $previous_filters = $GLOBALS['visibloc_test_filters']['visibloc_jlg_user_segment_matches'] ?? null;

        $callback = static function ( $matched, $context ) {
            if ( isset( $context['segment'] ) && 'crm_vip' === $context['segment'] ) {
                return true;
            }

            return $matched;
        };

        add_filter( 'visibloc_jlg_user_segment_matches', $callback, 10, 2 );

        try {
            $this->assertTrue(
                visibloc_jlg_match_user_segment_rule(
                    [
                        'type'     => 'user_segment',
                        'operator' => 'matches',
                        'segment'  => 'crm_vip',
                    ],
                    [ 'user' => [] ]
                ),
                'Segment filter should mark crm_vip as matching.'
            );

            $this->assertFalse(
                visibloc_jlg_match_user_segment_rule(
                    [
                        'type'     => 'user_segment',
                        'operator' => 'matches',
                        'segment'  => 'crm_basic',
                    ],
                    [ 'user' => [] ]
                ),
                'Segments without a positive filter response should not match.'
            );

            $this->assertTrue(
                visibloc_jlg_match_user_segment_rule(
                    [
                        'type'     => 'user_segment',
                        'operator' => 'does_not_match',
                        'segment'  => 'crm_basic',
                    ],
                    [ 'user' => [] ]
                ),
                'Negated segment should succeed when no filter matches.'
            );
        } finally {
            remove_filter( 'visibloc_jlg_user_segment_matches', $callback, 10 );

            if ( null === $previous_filters ) {
                unset( $GLOBALS['visibloc_test_filters']['visibloc_jlg_user_segment_matches'] );
            } else {
                $GLOBALS['visibloc_test_filters']['visibloc_jlg_user_segment_matches'] = $previous_filters;
            }
        }
    }

    public function test_preview_exposes_reason_for_inverted_schedule(): void {
        global $visibloc_test_state;

        $previous_state = $visibloc_test_state;
        $visibloc_test_state['effective_user_id']             = 7;
        $visibloc_test_state['current_user']                  = new Visibloc_Test_User( 7, [ 'administrator' ] );
        $visibloc_test_state['can_preview_users'][7]          = true;
        $visibloc_test_state['can_impersonate_users'][7]      = true;
        $visibloc_test_state['allowed_preview_roles']         = [ 'administrator' ];
        $visibloc_test_state['preview_role']                  = '';

        try {
            $block = [
                'blockName' => 'core/group',
                'attrs'     => [
                    'isSchedulingEnabled' => true,
                    'publishStartDate'    => '2024-05-01T10:00:00',
                    'publishEndDate'      => '2024-04-01T10:00:00',
                ],
            ];

            $output = visibloc_jlg_render_block_filter( '<p>Programmation inversée</p>', $block );

            $this->assertStringContainsString( 'bloc-schedule-error', $output );
            $this->assertStringContainsString( 'data-visibloc-reason="schedule-invalid"', $output );
        } finally {
            $visibloc_test_state = $previous_state;
            $can_preview         = false;
            visibloc_jlg_get_user_visibility_context( [], $can_preview, true );
        }
    }

    public function test_preview_exposes_reason_for_role_restriction_with_global_fallback(): void {
        global $visibloc_test_state;

        $previous_state     = $visibloc_test_state;
        $previous_settings  = $GLOBALS['visibloc_test_options']['visibloc_fallback_settings'] ?? null;

        $visibloc_test_state['effective_user_id']             = 5;
        $visibloc_test_state['current_user']                  = new Visibloc_Test_User( 5, [ 'administrator' ] );
        $visibloc_test_state['can_preview_users'][5]          = true;
        $visibloc_test_state['can_impersonate_users'][5]      = true;
        $visibloc_test_state['allowed_preview_roles']         = [ 'administrator' ];
        $visibloc_test_state['preview_role']                  = '';

        try {
            $GLOBALS['visibloc_test_options']['visibloc_fallback_settings'] = [
                'mode' => 'text',
                'text' => 'Fallback global aperçu',
            ];
            visibloc_jlg_get_fallback_settings( true );
            visibloc_jlg_get_global_fallback_markup( true );

            $block = [
                'blockName' => 'core/group',
                'attrs'     => [
                    'visibilityRoles' => [ 'subscriber' ],
                ],
            ];

            $output = visibloc_jlg_render_block_filter( '<p>Contenu restreint</p>', $block );

            $this->assertStringContainsString( 'bloc-role-apercu', $output );
            $this->assertStringContainsString( 'bloc-fallback-apercu', $output );
            $this->assertStringContainsString( 'data-visibloc-reason="roles"', $output );
            $this->assertStringContainsString( 'Fallback global aperçu', $output );
        } finally {
            if ( null === $previous_settings ) {
                unset( $GLOBALS['visibloc_test_options']['visibloc_fallback_settings'] );
            } else {
                $GLOBALS['visibloc_test_options']['visibloc_fallback_settings'] = $previous_settings;
            }

            $visibloc_test_state = $previous_state;
            visibloc_jlg_get_fallback_settings( true );
            visibloc_jlg_get_global_fallback_markup( true );
            $can_preview         = false;
            visibloc_jlg_get_user_visibility_context( [], $can_preview, true );
        }
    }

    /**
     * @dataProvider visibloc_falsey_attribute_provider
     */
    public function test_hidden_flag_falsey_strings_do_not_hide_block( $raw_value ): void {
        $block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'isHidden' => $raw_value,
            ],
        ];

        $this->assertSame(
            '<p>Visible content</p>',
            visibloc_jlg_render_block_filter( '<p>Visible content</p>', $block ),
            'False-like attribute values should not hide the block.'
        );
    }

    /**
     * @dataProvider visibloc_falsey_attribute_provider
     */
    public function test_scheduling_flag_falsey_strings_do_not_enable_window( $raw_value ): void {
        $block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'isSchedulingEnabled' => $raw_value,
                'publishStartDate'    => '2099-01-01 00:00:00',
                'publishEndDate'      => '2099-01-02 00:00:00',
            ],
        ];

        $this->assertSame(
            '<p>Future content</p>',
            visibloc_jlg_render_block_filter( '<p>Future content</p>', $block ),
            'Scheduling should be skipped when the flag is stored as a false-like value.'
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

    public function test_visibility_roles_ignore_nested_values(): void {
        $block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'visibilityRoles' => [
                    [ 'nested' => [ 'array' ] ],
                    (object) [ 'role' => 'editor' ],
                ],
            ],
        ];

        $this->assertSame(
            '<p>Visible content</p>',
            visibloc_jlg_render_block_filter( '<p>Visible content</p>', $block ),
            'Non-scalar visibility role values should be ignored without affecting rendering.'
        );
    }

    public function test_editor_taxonomy_term_limit_can_be_filtered(): void {
        global $visibloc_test_taxonomies, $visibloc_test_terms;

        $previous_taxonomies = $visibloc_test_taxonomies;
        $previous_terms      = $visibloc_test_terms;
        $hook                = 'visibloc_jlg_editor_terms_query_args';
        $previous_filters    = $GLOBALS['visibloc_test_filters'][ $hook ] ?? null;

        $visibloc_test_taxonomies = [
            'genre' => [
                'args'   => [ 'public' => true ],
                'object' => (object) [
                    'label'  => 'Genres',
                    'labels' => (object) [ 'singular_name' => 'Genre' ],
                ],
            ],
        ];

        $visibloc_test_terms = [ 'genre' => [] ];

        for ( $index = 1; $index <= 201; $index++ ) {
            $visibloc_test_terms['genre'][] = [
                'term_id' => $index,
                'name'    => sprintf( 'Term %03d', $index ),
                'slug'    => sprintf( 'term-%03d', $index ),
            ];
        }

        try {
            $unfiltered = visibloc_jlg_get_editor_taxonomies();

            $this->assertCount( 1, $unfiltered, 'Expected a single registered taxonomy.' );

            $unfiltered_terms = array_values( $unfiltered[0]['terms'] );

            $this->assertCount( 200, $unfiltered_terms, 'The default term query should limit results to 200 entries.' );
            $this->assertSame( 'term-200', $unfiltered_terms[199]['value'] );

            $filter = function ( $args, $taxonomy_slug ) {
                $this->assertSame( 'genre', $taxonomy_slug, 'The filter should receive the current taxonomy slug.' );
                $args['number']  = 250;
                $args['orderby'] = 'slug';
                $args['order']   = 'DESC';

                return $args;
            };

            add_filter( $hook, $filter, 10, 2 );

            $filtered = visibloc_jlg_get_editor_taxonomies();

            $this->assertCount( 1, $filtered, 'The filtered request should still reference the expected taxonomy.' );

            $filtered_terms = array_values( $filtered[0]['terms'] );

            $this->assertCount( 201, $filtered_terms, 'Raising the term limit should expose the additional taxonomy entry.' );
            $this->assertSame( 'Term 001', $filtered_terms[0]['label'], 'Sanitized terms should remain alphabetically sorted.' );
            $this->assertSame( 'term-201', $filtered_terms[200]['value'] );
        } finally {
            if ( null === $previous_filters ) {
                unset( $GLOBALS['visibloc_test_filters'][ $hook ] );
            } else {
                $GLOBALS['visibloc_test_filters'][ $hook ] = $previous_filters;
            }

            $visibloc_test_taxonomies = $previous_taxonomies;
            $visibloc_test_terms      = $previous_terms;
        }
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

    public function test_render_block_filter_supports_additional_block_names(): void {
        add_filter(
            'visibloc_supported_blocks',
            static function ( $blocks ) {
                $blocks[] = 'core/columns';

                return $blocks;
            }
        );

        $hidden_columns_block = [
            'blockName' => 'core/columns',
            'attrs'     => [
                'isHidden' => true,
            ],
        ];

        $this->assertSame(
            '',
            apply_filters( 'render_block', '<p>Columns hidden</p>', $hidden_columns_block ),
            'Hidden columns blocks should be filtered once registered as supported.',
        );

        $visible_columns_block = [
            'blockName' => 'core/columns',
            'attrs'     => [
                'isHidden' => false,
            ],
        ];

        $this->assertSame(
            '<p>Columns visible</p>',
            apply_filters( 'render_block', '<p>Columns visible</p>', $visible_columns_block ),
            'Columns blocks should render when not hidden.',
        );
    }

    public function test_supported_blocks_merge_option_with_defaults(): void {
        update_option(
            'visibloc_supported_blocks',
            [
                'core/columns',
                'custom/accordion',
                'invalid-block',
            ]
        );

        $supported = visibloc_jlg_get_supported_blocks();

        $this->assertContains( 'core/group', $supported );
        $this->assertContains( 'core/columns', $supported );
        $this->assertContains( 'custom/accordion', $supported );
        $this->assertNotContains( 'invalid-block', $supported );
    }

    public function visibloc_falsey_attribute_provider(): array {
        return [
            'string-false'    => [ 'false' ],
            'string-zero'     => [ '0' ],
            'null-value'      => [ null ],
            'empty-array'     => [ [] ],
            'stdclass-object' => [ (object) [] ],
        ];
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
        $this->assertStringContainsString( 'vb-label-top', $output );
        $this->assertStringContainsString( 'Programmé (Début:', $output );
        $this->assertStringContainsString( '<p>Scheduled content</p>', $output );
    }

    public function test_invalid_schedule_returns_content_for_regular_users(): void {
        $block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'isSchedulingEnabled' => true,
                'publishStartDate'    => '2099-01-02 00:00:00',
                'publishEndDate'      => '2099-01-01 00:00:00',
            ],
        ];

        $this->assertSame(
            '<p>Scheduled content</p>',
            visibloc_jlg_render_block_filter( '<p>Scheduled content</p>', $block ),
            'Invalid scheduling windows must not hide content for regular visitors.'
        );
    }

    public function test_invalid_schedule_shows_error_badge_for_authorized_previewers(): void {
        global $visibloc_test_state;

        $visibloc_test_state['effective_user_id']       = 11;
        $visibloc_test_state['current_user']            = new Visibloc_Test_User( 11, [ 'administrator' ] );
        $visibloc_test_state['can_preview_users'][11]   = true;
        $visibloc_test_state['allowed_preview_roles']   = [ 'administrator' ];
        $visibloc_test_state['preview_role']            = '';

        $block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'isSchedulingEnabled' => true,
                'publishStartDate'    => '2099-01-02 00:00:00',
                'publishEndDate'      => '2099-01-01 00:00:00',
            ],
        ];

        $output = visibloc_jlg_render_block_filter( '<p>Scheduled content</p>', $block );

        $this->assertStringContainsString( 'bloc-schedule-error', $output );
        $this->assertStringContainsString( 'vb-label-top', $output );
        $this->assertStringContainsString( 'Invalid schedule', $output );
        $this->assertStringContainsString( '<p>Scheduled content</p>', $output );
    }

    public function test_scheduled_block_remains_hidden_before_window_with_local_timezone(): void {
        visibloc_test_set_timezone( 'Europe/Paris' );

        $block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'isSchedulingEnabled' => true,
                'publishStartDate'    => '2024-07-10 10:00:00',
                'publishEndDate'      => '2024-07-10 18:00:00',
            ],
        ];

        $this->setCurrentTimeForTimezone( '2024-07-10 09:30:00' );

        $this->assertSame(
            '',
            visibloc_jlg_render_block_filter( '<p>Scheduled content</p>', $block ),
            'Blocks should remain hidden before the scheduled start when the site timezone is ahead of UTC.'
        );
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

        $this->setCurrentTimeForTimezone( '2024-07-10 09:30:00' );

        $this->assertSame(
            '',
            visibloc_jlg_render_block_filter( '<p>Scheduled content</p>', $block ),
            'Blocks should remain hidden before the scheduled start in the configured site timezone.'
        );

        $this->setCurrentTimeForTimezone( '2024-07-10 11:00:00' );

        $this->assertSame(
            '<p>Scheduled content</p>',
            visibloc_jlg_render_block_filter( '<p>Scheduled content</p>', $block ),
            'Blocks should become visible once the local start time has passed.'
        );
    }

    public function test_scheduled_block_handles_timezone_behind_utc(): void {
        visibloc_test_set_timezone( 'America/New_York' );

        $block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'isSchedulingEnabled' => true,
                'publishStartDate'    => '2024-07-10 08:00:00',
                'publishEndDate'      => '2024-07-10 17:00:00',
            ],
        ];

        $this->setCurrentTimeForTimezone( '2024-07-10 07:30:00' );

        $this->assertSame(
            '',
            visibloc_jlg_render_block_filter( '<p>Scheduled content</p>', $block ),
            'Blocks should remain hidden before the start window in a timezone west of UTC.'
        );

        $this->setCurrentTimeForTimezone( '2024-07-10 09:30:00' );

        $this->assertSame(
            '<p>Scheduled content</p>',
            visibloc_jlg_render_block_filter( '<p>Scheduled content</p>', $block ),
            'Blocks should become visible once the local start time has passed in a timezone west of UTC.'
        );
    }

    public function test_scheduled_block_respects_custom_timezone_override(): void {
        visibloc_test_set_timezone( 'UTC' );

        $block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'isSchedulingEnabled' => true,
                'publishStartDate'    => '2024-07-10 08:00:00',
                'publishEndDate'      => '2024-07-10 12:00:00',
                'publishTimezone'     => 'America/Los_Angeles',
            ],
        ];

        $this->setCurrentTimeForTimezone( '2024-07-10 14:30:00' );

        $this->assertSame(
            '',
            visibloc_jlg_render_block_filter( '<p>Scheduled content</p>', $block ),
            'Blocks should remain hidden before the window in the selected custom timezone.'
        );

        $this->setCurrentTimeForTimezone( '2024-07-10 15:05:00' );

        $this->assertSame(
            '<p>Scheduled content</p>',
            visibloc_jlg_render_block_filter( '<p>Scheduled content</p>', $block ),
            'Blocks should become visible once the custom timezone window opens.'
        );
    }

    public function test_invalid_schedule_timezone_defaults_to_site_timezone(): void {
        visibloc_test_set_timezone( 'Europe/Paris' );

        $block = [
            'blockName' => 'core/group',
            'attrs'     => [
                'isSchedulingEnabled' => true,
                'publishStartDate'    => '2024-07-10 10:00:00',
                'publishEndDate'      => '2024-07-10 18:00:00',
                'publishTimezone'     => 'Invalid/Timezone',
            ],
        ];

        $this->setCurrentTimeForTimezone( '2024-07-10 09:30:00' );

        $this->assertSame(
            '',
            visibloc_jlg_render_block_filter( '<p>Scheduled content</p>', $block ),
            'Invalid timezone identifiers should fall back to the site timezone before the window opens.'
        );

        $this->setCurrentTimeForTimezone( '2024-07-10 10:30:00' );

        $this->assertSame(
            '<p>Scheduled content</p>',
            visibloc_jlg_render_block_filter( '<p>Scheduled content</p>', $block ),
            'Invalid timezone identifiers should allow visibility after the site timezone window opens.'
        );
    }

    private function setCurrentTimeForTimezone( string $datetime, string $timezone = 'site' ): void {
        $timestamp = visibloc_jlg_parse_schedule_datetime( $datetime, $timezone );

        if ( null === $timestamp ) {
            $this->fail( sprintf( 'Failed to parse datetime string "%s" for test setup.', $datetime ) );
        }

        visibloc_test_set_current_time( $timestamp );
    }

    public function test_generate_device_visibility_css_respects_preview_context(): void {
        $css_without_preview = visibloc_jlg_generate_device_visibility_css( false, 781, 1024 );
        $expected_default_css = <<<CSS
@media (max-width: 781px) {
    .vb-hide-on-mobile,
    .vb-tablet-only,
    .vb-desktop-only {
        display: none !important;
    }
}
@media (min-width: 782px) and (max-width: 1024px) {
    .vb-hide-on-tablet,
    .vb-mobile-only,
    .vb-desktop-only {
        display: none !important;
    }
}
@media (min-width: 1025px) {
    .vb-hide-on-desktop,
    .vb-mobile-only,
    .vb-tablet-only {
        display: none !important;
    }
}
@media (orientation: portrait) {
    .vb-hide-on-portrait,
    .vb-landscape-only {
        display: none !important;
    }
}
@media (orientation: landscape) {
    .vb-hide-on-landscape,
    .vb-portrait-only {
        display: none !important;
    }
}
CSS;
        $this->assertSame( $expected_default_css, trim( $css_without_preview ) );

        $css_with_custom_breakpoints = visibloc_jlg_generate_device_visibility_css( false, 700, 900 );
        $this->assertStringContainsString( '@media (max-width: 700px)', $css_with_custom_breakpoints );
        $this->assertStringContainsString( '@media (min-width: 901px)', $css_with_custom_breakpoints );
        $this->assertStringNotContainsString( 'outline: 2px dashed', $css_with_custom_breakpoints );

        $css_with_preview = visibloc_jlg_generate_device_visibility_css( true, 781, 1024 );
        $this->assertStringContainsString( 'outline: 2px dashed #0073aa', $css_with_preview );
        $this->assertStringContainsString( '.visibloc-status-badge {', $css_with_preview );
    }
}
