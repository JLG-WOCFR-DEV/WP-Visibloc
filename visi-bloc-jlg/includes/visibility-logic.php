<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/fallback.php';

function visibloc_jlg_get_supported_blocks() {
    $default_blocks   = (array) VISIBLOC_JLG_DEFAULT_SUPPORTED_BLOCKS;
    $option_value     = get_option( 'visibloc_supported_blocks', [] );
    $configured_blocks = visibloc_jlg_normalize_block_names( $option_value );
    $merged_blocks     = array_merge( $default_blocks, $configured_blocks );
    $filtered_blocks   = apply_filters( 'visibloc_supported_blocks', $merged_blocks );

    if ( ! is_array( $filtered_blocks ) ) {
        return $default_blocks;
    }

    $sanitized = [];

    foreach ( visibloc_jlg_normalize_block_names( $filtered_blocks ) as $block_name ) {
        $sanitized[ $block_name ] = true;
    }

    if ( empty( $sanitized ) ) {
        return $default_blocks;
    }

    return array_keys( $sanitized );
}

function visibloc_jlg_is_supported_block( $block_name ) {
    if ( ! is_string( $block_name ) || '' === $block_name ) {
        return false;
    }

    return in_array( $block_name, visibloc_jlg_get_supported_blocks(), true );
}

function visibloc_jlg_render_block_visibility_router( $block_content, $block ) {
    $block_name = is_array( $block ) && isset( $block['blockName'] ) ? $block['blockName'] : '';

    if ( ! visibloc_jlg_is_supported_block( $block_name ) ) {
        return $block_content;
    }

    return visibloc_jlg_render_block_filter( $block_content, $block );
}

add_filter( 'render_block', 'visibloc_jlg_render_block_visibility_router', 10, 2 );

function visibloc_jlg_render_block_filter( $block_content, $block ) {
    if ( empty( $block['attrs'] ) ) { return $block_content; }

    $attrs = $block['attrs'];

    $fallback_markup = null;
    $fallback_initialized = false;
    $get_fallback_markup = static function () use ( &$fallback_markup, &$fallback_initialized, $attrs ) {
        if ( ! $fallback_initialized ) {
            $fallback_markup     = visibloc_jlg_get_block_fallback_markup( $attrs );
            $fallback_initialized = true;
        }

        return $fallback_markup;
    };

    $visibility_roles = [];

    if ( array_key_exists( 'visibilityRoles', $attrs ) ) {
        $raw_visibility_roles = $attrs['visibilityRoles'];

        if ( is_array( $raw_visibility_roles ) ) {
            $visibility_roles = $raw_visibility_roles;
        } elseif ( is_string( $raw_visibility_roles ) ) {
            $visibility_roles = '' === trim( $raw_visibility_roles ) ? [] : [ $raw_visibility_roles ];
        } elseif ( is_scalar( $raw_visibility_roles ) ) {
            $visibility_roles = [ $raw_visibility_roles ];
        }

        $visibility_roles = array_values(
            array_filter(
                array_map(
                    static function ( $raw_role ) {
                        if ( ! is_scalar( $raw_role ) ) {
                            return null;
                        }

                        $sanitized_role = sanitize_key( (string) $raw_role );

                        return '' === $sanitized_role ? null : $sanitized_role;
                    },
                    $visibility_roles
                ),
                static function ( $role ) {
                    return null !== $role;
                }
            )
        );
    }

    $advanced_visibility   = visibloc_jlg_normalize_advanced_visibility( $attrs['advancedVisibility'] ?? null );
    $has_advanced_rules    = ! empty( $advanced_visibility['rules'] );
    $has_hidden_flag       = isset( $attrs['isHidden'] ) ? visibloc_jlg_normalize_boolean( $attrs['isHidden'] ) : false;
    $has_schedule_enabled  = isset( $attrs['isSchedulingEnabled'] ) ? visibloc_jlg_normalize_boolean( $attrs['isSchedulingEnabled'] ) : false;

    if ( ! $has_hidden_flag && ! $has_schedule_enabled && empty( $visibility_roles ) && ! $has_advanced_rules ) {
        return $block_content;
    }

    $preview_context = function_exists( 'visibloc_jlg_get_preview_runtime_context' )
        ? visibloc_jlg_get_preview_runtime_context()
        : [
            'can_preview_hidden_blocks'  => false,
            'should_apply_preview_role'  => false,
            'preview_role'               => '',
        ];

    $can_preview_hidden_blocks = ! empty( $preview_context['can_preview_hidden_blocks'] );
    $hidden_preview_markup = null;
    $has_preview_markup = false;

    if ( $has_hidden_flag && $can_preview_hidden_blocks ) {
        $hidden_preview_label = __( 'Hidden block', 'visi-bloc-jlg' );
        $hidden_preview_markup = sprintf(
            '<div class="bloc-cache-apercu vb-label-top">%s%s</div>',
            visibloc_jlg_render_status_badge(
                $hidden_preview_label,
                'hidden',
                __( 'Ce bloc est masqué pour les visiteurs du site.', 'visi-bloc-jlg' )
            ),
            $block_content
        );
        $has_preview_markup = true;
    }

    if ( $has_schedule_enabled ) {
        $current_time = current_datetime()->getTimestamp();

        $start_time = visibloc_jlg_parse_schedule_datetime( $attrs['publishStartDate'] ?? null );
        $end_time   = visibloc_jlg_parse_schedule_datetime( $attrs['publishEndDate'] ?? null );

        if ( null !== $start_time && null !== $end_time && $start_time > $end_time ) {
            if ( $can_preview_hidden_blocks ) {
                $schedule_error_label = __( 'Invalid schedule', 'visi-bloc-jlg' );
                $schedule_error_markup = sprintf(
                    '<div class="bloc-schedule-error vb-label-top">%s%s</div>',
                    visibloc_jlg_render_status_badge(
                        $schedule_error_label,
                        'schedule-error',
                        __( 'La programmation actuelle empêche l’affichage de ce bloc.', 'visi-bloc-jlg' )
                    ),
                    $has_preview_markup && null !== $hidden_preview_markup
                        ? $hidden_preview_markup
                        : $block_content
                );
                $hidden_preview_markup = $schedule_error_markup;
                $has_preview_markup    = true;
            }

            $has_schedule_enabled = false;
        }

        if ( $has_schedule_enabled ) {
            $is_before_start = null !== $start_time && $current_time < $start_time;
            $is_after_end = null !== $end_time && $current_time > $end_time;
            if ( $is_before_start || $is_after_end ) {
                if ( $can_preview_hidden_blocks ) {
                    $datetime_format = visibloc_jlg_get_wp_datetime_format();
                    $start_date_fr = $start_time ? wp_date( $datetime_format, $start_time ) : __( 'N/A', 'visi-bloc-jlg' );
                    $end_date_fr = $end_time ? wp_date( $datetime_format, $end_time ) : __( 'N/A', 'visi-bloc-jlg' );
                    $info = sprintf(
                        /* translators: 1: start date, 2: end date. */
                        __( 'Programmé (Début:%1$s | Fin:%2$s)', 'visi-bloc-jlg' ),
                        $start_date_fr,
                        $end_date_fr
                    );
                    $schedule_preview_markup = '<div class="bloc-schedule-apercu vb-label-top">' .
                        visibloc_jlg_render_status_badge(
                            $info,
                            'schedule',
                            sprintf(
                                /* translators: %s: scheduling information. */
                                __( 'Ce bloc est programmé : %s', 'visi-bloc-jlg' ),
                                $info
                            )
                        ) . (
                            $has_preview_markup && null !== $hidden_preview_markup
                                ? $hidden_preview_markup
                                : $block_content
                        ) .
                    '</div>';

                    return visibloc_jlg_wrap_preview_with_fallback_notice( $schedule_preview_markup, $get_fallback_markup() );
                }
                return $get_fallback_markup();
            }
        }
    }

    if ( $has_advanced_rules ) {
        $advanced_rules_match = visibloc_jlg_evaluate_advanced_visibility( $advanced_visibility );

        if ( ! $advanced_rules_match ) {
            if ( $can_preview_hidden_blocks ) {
                $advanced_label = __( 'Règles avancées actives', 'visi-bloc-jlg' );
                $advanced_markup = sprintf(
                    '<div class="bloc-advanced-apercu vb-label-top">%s%s</div>',
                    visibloc_jlg_render_status_badge(
                        $advanced_label,
                        'advanced',
                        __( 'Des règles avancées masquent ce bloc pour les visiteurs.', 'visi-bloc-jlg' )
                    ),
                    $has_preview_markup && null !== $hidden_preview_markup
                        ? $hidden_preview_markup
                        : $block_content
                );

                $hidden_preview_markup = $advanced_markup;
                $has_preview_markup    = true;
            } else {
                return $get_fallback_markup();
            }
        }
    }

    if ( ! empty( $visibility_roles ) ) {
        $should_apply_preview_role = ! empty( $preview_context['should_apply_preview_role'] );
        $preview_role              = is_string( $preview_context['preview_role'] ?? '' ) ? $preview_context['preview_role'] : '';

        static $cached_user_ref = null;
        static $cached_user_logged_in = null;
        static $cached_user_roles = null;

        $current_user       = wp_get_current_user();
        $current_roles      = (array) $current_user->roles;
        $current_is_logged_in = $current_user->exists();

        if ( ! ( $cached_user_ref instanceof WP_User )
            || $cached_user_ref !== $current_user
            || $cached_user_logged_in !== $current_is_logged_in
            || $cached_user_roles !== $current_roles
        ) {
            $cached_user_ref      = $current_user;
            $cached_user_logged_in = $current_is_logged_in;
            $cached_user_roles     = $current_roles;
        }

        $is_logged_in = $cached_user_logged_in;
        $user_roles   = $cached_user_roles;

        if ( $should_apply_preview_role ) {
            if ( 'guest' === $preview_role ) {
                $is_logged_in = false;
                $user_roles   = [];
            } elseif ( '' !== $preview_role ) {
                static $allowed_preview_roles_cache = null;

                if ( null === $allowed_preview_roles_cache ) {
                    $allowed_preview_roles_cache = function_exists( 'visibloc_jlg_get_allowed_preview_roles' )
                        ? (array) visibloc_jlg_get_allowed_preview_roles()
                        : [];
                }

                static $role_exists_cache = [];

                if ( ! array_key_exists( $preview_role, $role_exists_cache ) ) {
                    $role_exists_cache[ $preview_role ] = (bool) get_role( $preview_role );
                }

                if ( $role_exists_cache[ $preview_role ] ) {
                    if ( ! in_array( $preview_role, $allowed_preview_roles_cache, true ) ) {
                        $can_preview_hidden_blocks = false;
                    }

                    $is_logged_in = true;
                    $user_roles   = [ $preview_role ];
                } else {
                    $should_apply_preview_role = false;
                    $preview_role             = '';
                }
            }
        }

        $is_visible = false;
        // Manual check: without preview access the cookie must not affect visibility.
        if ( in_array( 'logged-out', $visibility_roles, true ) && ! $is_logged_in ) $is_visible = true;
        if ( ! $is_visible && in_array( 'logged-in', $visibility_roles, true ) && $is_logged_in ) $is_visible = true;
        if ( ! $is_visible && ! empty( $user_roles ) && count( array_intersect( $user_roles, $visibility_roles ) ) > 0 ) { $is_visible = true; }
        if ( ! $is_visible ) {
            if ( $has_preview_markup && null !== $hidden_preview_markup ) {
                return visibloc_jlg_wrap_preview_with_fallback_notice( $hidden_preview_markup, $get_fallback_markup() );
            }

            return $get_fallback_markup();
        }
    }

    if ( $has_hidden_flag ) {
        if ( $has_preview_markup && null !== $hidden_preview_markup ) {
            return visibloc_jlg_wrap_preview_with_fallback_notice( $hidden_preview_markup, $get_fallback_markup() );
        }

        return $get_fallback_markup();
    }

    if ( $has_preview_markup && null !== $hidden_preview_markup ) {
        return visibloc_jlg_wrap_preview_with_fallback_notice( $hidden_preview_markup, $get_fallback_markup() );
    }

    return $block_content;
}

function visibloc_jlg_wrap_preview_with_fallback_notice( $preview_markup, $fallback_markup ) {
    if ( '' === $fallback_markup ) {
        return $preview_markup;
    }

    $label = __( 'Contenu de repli actif', 'visi-bloc-jlg' );

    return sprintf(
        '<div class="bloc-fallback-apercu vb-label-top" data-visibloc-fallback="1">%s%s<div class="bloc-fallback-apercu__replacement">%s</div></div>',
        visibloc_jlg_render_status_badge(
            $label,
            'fallback',
            __( 'Le contenu de repli est affiché à la place du bloc original.', 'visi-bloc-jlg' )
        ),
        $preview_markup,
        $fallback_markup
    );
}

function visibloc_jlg_render_status_badge( $label, $variant = '', $screen_reader_text = '' ) {
    $label_value = (string) $label;

    if ( function_exists( 'wp_strip_all_tags' ) ) {
        $label_text = trim( wp_strip_all_tags( $label_value ) );
    } else {
        $label_text = trim( strip_tags( $label_value ) );
    }

    if ( '' === $label_text ) {
        return '';
    }

    $class_names = [ 'visibloc-status-badge' ];

    if ( '' !== $variant ) {
        $normalized_variant = strtolower( preg_replace( '/[^a-z0-9\-]+/', '-', $variant ) );
        $normalized_variant = trim( $normalized_variant, '-' );

        if ( '' !== $normalized_variant ) {
            $class_names[] = 'visibloc-status-badge--' . $normalized_variant;
        }
    }

    $badge  = sprintf( '<span class="%s">', esc_attr( implode( ' ', $class_names ) ) );
    $badge .= esc_html( $label_text );

    $screen_reader_text = trim( (string) $screen_reader_text );

    if ( '' !== $screen_reader_text ) {
        $badge .= sprintf( '<span class="screen-reader-text">%s</span>', esc_html( $screen_reader_text ) );
    }

    $badge .= '</span>';

    return $badge;
}

function visibloc_jlg_normalize_advanced_visibility( $value ) {
    $default = [
        'logic' => 'AND',
        'rules' => [],
    ];

    if ( null === $value ) {
        return $default;
    }

    if ( is_string( $value ) ) {
        $decoded = json_decode( $value, true );

        if ( is_array( $decoded ) ) {
            $value = $decoded;
        } else {
            return $default;
        }
    }

    if ( ! is_array( $value ) ) {
        return $default;
    }

    $logic = isset( $value['logic'] ) && 'OR' === $value['logic'] ? 'OR' : 'AND';
    $rules = [];

    if ( isset( $value['rules'] ) && is_array( $value['rules'] ) ) {
        foreach ( $value['rules'] as $rule ) {
            $normalized_rule = visibloc_jlg_normalize_advanced_rule( $rule );

            if ( null !== $normalized_rule ) {
                $rules[] = $normalized_rule;
            }
        }
    }

    return [
        'logic' => $logic,
        'rules' => $rules,
    ];
}

function visibloc_jlg_normalize_advanced_rule( $rule ) {
    if ( ! is_array( $rule ) ) {
        return null;
    }

    $type = isset( $rule['type'] ) ? $rule['type'] : '';

    if ( ! in_array( $type, [ 'post_type', 'taxonomy', 'template', 'recurring_schedule' ], true ) ) {
        return null;
    }

    $normalized = [
        'type' => $type,
    ];

    switch ( $type ) {
        case 'post_type':
            $normalized['operator'] = isset( $rule['operator'] ) && 'is_not' === $rule['operator'] ? 'is_not' : 'is';
            $normalized['value']    = isset( $rule['value'] ) && is_string( $rule['value'] ) ? $rule['value'] : '';
            break;
        case 'taxonomy':
            $normalized['operator'] = isset( $rule['operator'] ) && 'not_in' === $rule['operator'] ? 'not_in' : 'in';
            $normalized['taxonomy'] = isset( $rule['taxonomy'] ) && is_string( $rule['taxonomy'] ) ? $rule['taxonomy'] : '';
            $normalized['terms']    = [];

            if ( isset( $rule['terms'] ) && is_array( $rule['terms'] ) ) {
                foreach ( $rule['terms'] as $term ) {
                    if ( is_scalar( $term ) ) {
                        $term_value = (string) $term;

                        if ( '' !== $term_value ) {
                            $normalized['terms'][] = $term_value;
                        }
                    }
                }
            }
            break;
        case 'template':
            $normalized['operator'] = isset( $rule['operator'] ) && 'is_not' === $rule['operator'] ? 'is_not' : 'is';
            $normalized['value']    = isset( $rule['value'] ) && is_string( $rule['value'] ) ? $rule['value'] : '';
            break;
        case 'recurring_schedule':
            $normalized['operator']  = 'matches';
            $normalized['frequency'] = isset( $rule['frequency'] ) && 'weekly' === $rule['frequency'] ? 'weekly' : 'daily';
            $normalized['days']      = [];

            if ( isset( $rule['days'] ) && is_array( $rule['days'] ) ) {
                foreach ( $rule['days'] as $day ) {
                    if ( is_string( $day ) && '' !== $day ) {
                        $normalized['days'][] = $day;
                    }
                }
            }

            $normalized['startTime'] = isset( $rule['startTime'] ) && is_string( $rule['startTime'] ) ? $rule['startTime'] : '08:00';
            $normalized['endTime']   = isset( $rule['endTime'] ) && is_string( $rule['endTime'] ) ? $rule['endTime'] : '17:00';
            break;
    }

    return $normalized;
}

function visibloc_jlg_evaluate_advanced_visibility( $advanced_visibility ) {
    if ( empty( $advanced_visibility['rules'] ) ) {
        return true;
    }

    $post_context = visibloc_jlg_get_visibility_post_context();
    $logic        = isset( $advanced_visibility['logic'] ) && 'OR' === $advanced_visibility['logic'] ? 'OR' : 'AND';
    $results      = [];

    foreach ( $advanced_visibility['rules'] as $rule ) {
        $results[] = visibloc_jlg_evaluate_advanced_rule( $rule, $post_context );
    }

    if ( 'OR' === $logic ) {
        foreach ( $results as $result ) {
            if ( true === $result ) {
                return true;
            }
        }

        return false;
    }

    foreach ( $results as $result ) {
        if ( false === $result ) {
            return false;
        }
    }

    return true;
}

function visibloc_jlg_get_visibility_post_context() {
    global $post;

    if ( $post instanceof WP_Post ) {
        return $post;
    }

    $post_id = get_the_ID();

    if ( $post_id ) {
        $maybe_post = get_post( $post_id );

        if ( $maybe_post instanceof WP_Post ) {
            return $maybe_post;
        }
    }

    return null;
}

function visibloc_jlg_evaluate_advanced_rule( $rule, $post ) {
    switch ( $rule['type'] ) {
        case 'post_type':
            return visibloc_jlg_match_post_type_rule( $rule, $post );
        case 'taxonomy':
            return visibloc_jlg_match_taxonomy_rule( $rule, $post );
        case 'template':
            return visibloc_jlg_match_template_rule( $rule, $post );
        case 'recurring_schedule':
            return visibloc_jlg_match_recurring_schedule_rule( $rule );
    }

    return true;
}

function visibloc_jlg_match_post_type_rule( $rule, $post ) {
    if ( ! $post instanceof WP_Post ) {
        return true;
    }

    $post_type = get_post_type( $post );
    $rule_type = isset( $rule['value'] ) ? $rule['value'] : '';

    if ( '' === $rule_type ) {
        return true;
    }

    if ( isset( $rule['operator'] ) && 'is_not' === $rule['operator'] ) {
        return $post_type !== $rule_type;
    }

    return $post_type === $rule_type;
}

function visibloc_jlg_match_taxonomy_rule( $rule, $post ) {
    if ( ! $post instanceof WP_Post ) {
        return true;
    }

    $taxonomy = isset( $rule['taxonomy'] ) ? $rule['taxonomy'] : '';
    $terms    = isset( $rule['terms'] ) && is_array( $rule['terms'] ) ? $rule['terms'] : [];
    $operator = isset( $rule['operator'] ) && 'not_in' === $rule['operator'] ? 'not_in' : 'in';

    if ( '' === $taxonomy ) {
        return true;
    }

    $terms = array_values( array_unique( array_filter( array_map( 'strval', $terms ) ) ) );

    if ( empty( $terms ) ) {
        return 'not_in' === $operator;
    }

    $has_terms = has_term( $terms, $taxonomy, $post );

    return 'not_in' === $operator ? ! $has_terms : $has_terms;
}

function visibloc_jlg_match_template_rule( $rule, $post ) {
    if ( ! $post instanceof WP_Post ) {
        return true;
    }

    $target_template = isset( $rule['value'] ) ? $rule['value'] : '';

    $current_template = '';

    if ( function_exists( 'get_page_template_slug' ) ) {
        $current_template = get_page_template_slug( $post );
    }

    if ( ! is_string( $current_template ) ) {
        $current_template = '';
    }

    if ( isset( $rule['operator'] ) && 'is_not' === $rule['operator'] ) {
        return $current_template !== $target_template;
    }

    return $current_template === $target_template;
}

function visibloc_jlg_match_recurring_schedule_rule( $rule ) {
    $start_minutes = visibloc_jlg_parse_time_to_minutes( $rule['startTime'] ?? '' );
    $end_minutes   = visibloc_jlg_parse_time_to_minutes( $rule['endTime'] ?? '' );

    if ( null === $start_minutes || null === $end_minutes ) {
        return false;
    }

    $current_datetime = current_datetime();

    if ( ! $current_datetime instanceof DateTimeInterface ) {
        return true;
    }

    $current_minutes = ( (int) $current_datetime->format( 'H' ) * 60 ) + (int) $current_datetime->format( 'i' );

    if ( ! visibloc_jlg_is_time_within_range( $current_minutes, $start_minutes, $end_minutes ) ) {
        return false;
    }

    if ( isset( $rule['frequency'] ) && 'weekly' === $rule['frequency'] ) {
        $days = isset( $rule['days'] ) && is_array( $rule['days'] ) ? array_values( array_unique( array_filter( array_map( 'strval', $rule['days'] ) ) ) ) : [];

        if ( empty( $days ) ) {
            return false;
        }

        $current_day = strtolower( $current_datetime->format( 'D' ) );
        $day_map     = [
            'mon' => 'mon',
            'tue' => 'tue',
            'wed' => 'wed',
            'thu' => 'thu',
            'fri' => 'fri',
            'sat' => 'sat',
            'sun' => 'sun',
        ];

        $current_day_slug = isset( $day_map[ $current_day ] ) ? $day_map[ $current_day ] : strtolower( $current_day );

        return in_array( $current_day_slug, $days, true );
    }

    return true;
}

function visibloc_jlg_parse_time_to_minutes( $time ) {
    if ( ! is_string( $time ) || '' === $time ) {
        return null;
    }

    if ( ! preg_match( '/^(2[0-3]|[01]?\d):([0-5]\d)$/', $time, $matches ) ) {
        return null;
    }

    $hours   = (int) $matches[1];
    $minutes = (int) $matches[2];

    return ( $hours * 60 ) + $minutes;
}

function visibloc_jlg_is_time_within_range( $current, $start, $end ) {
    if ( $start === $end ) {
        return false;
    }

    if ( $start < $end ) {
        return $current >= $start && $current <= $end;
    }

    // Overnight range (e.g., 22:00 - 02:00).
    return $current >= $start || $current <= $end;
}
