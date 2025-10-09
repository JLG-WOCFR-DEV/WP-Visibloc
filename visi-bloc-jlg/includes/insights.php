<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'VISIBLOC_JLG_INSIGHTS_OPTION' ) ) {
    define( 'VISIBLOC_JLG_INSIGHTS_OPTION', 'visibloc_insights' );
}

if ( ! defined( 'VISIBLOC_JLG_INSIGHTS_EVENT_LIMIT' ) ) {
    define( 'VISIBLOC_JLG_INSIGHTS_EVENT_LIMIT', 50 );
}

if ( ! function_exists( 'visibloc_jlg_insights_buffer' ) ) {
    /**
     * Runtime buffer storing insight events before persisting them.
     *
     * @return array
     */
    function &visibloc_jlg_insights_buffer() {
        static $buffer = [
            'counters'   => [],
            'reasons'    => [],
            'events'     => [],
            'has_events' => false,
        ];

        return $buffer;
    }
}

if ( ! function_exists( 'visibloc_jlg_reset_insights_buffer' ) ) {
    /**
     * Reset the runtime insight buffer.
     */
    function visibloc_jlg_reset_insights_buffer() {
        $buffer = &visibloc_jlg_insights_buffer();

        $buffer['counters']   = [];
        $buffer['reasons']    = [];
        $buffer['events']     = [];
        $buffer['has_events'] = false;
    }
}

if ( ! function_exists( 'visibloc_jlg_get_insights_buffer_snapshot' ) ) {
    /**
     * Retrieve a copy of the runtime insight buffer.
     *
     * @return array
     */
    function visibloc_jlg_get_insights_buffer_snapshot() {
        $buffer = visibloc_jlg_insights_buffer();

        return [
            'counters'   => $buffer['counters'],
            'reasons'    => $buffer['reasons'],
            'events'     => $buffer['events'],
            'has_events' => ! empty( $buffer['has_events'] ),
        ];
    }
}

if ( ! function_exists( 'visibloc_jlg_sanitize_insight_key' ) ) {
    /**
     * Normalize an insight key to a safe identifier.
     *
     * @param string $value Raw key.
     * @return string
     */
    function visibloc_jlg_sanitize_insight_key( $value ) {
        if ( ! is_string( $value ) ) {
            return '';
        }

        $sanitized = strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', $value ) );

        return is_string( $sanitized ) ? trim( $sanitized ) : '';
    }
}

if ( ! function_exists( 'visibloc_jlg_normalize_insight_count_map' ) ) {
    /**
     * Normalize a map of insight counts.
     *
     * @param mixed $value Raw count map.
     * @return array<string,int>
     */
    function visibloc_jlg_normalize_insight_count_map( $value ) {
        if ( ! is_array( $value ) ) {
            return [];
        }

        $normalized = [];

        foreach ( $value as $key => $count ) {
            $normalized_key = visibloc_jlg_sanitize_insight_key( (string) $key );

            if ( '' === $normalized_key ) {
                continue;
            }

            if ( ! is_numeric( $count ) ) {
                continue;
            }

            $normalized[ $normalized_key ] = max( 0, (int) $count );
        }

        return $normalized;
    }
}

if ( ! function_exists( 'visibloc_jlg_normalize_insight_events' ) ) {
    /**
     * Normalize an array of raw insight events.
     *
     * @param mixed $value Raw events.
     * @return array<int,array<string,mixed>>
     */
    function visibloc_jlg_normalize_insight_events( $value ) {
        if ( ! is_array( $value ) ) {
            return [];
        }

        $limit    = visibloc_jlg_get_insight_event_limit();
        $events   = [];
        $count    = 0;
        $sanitizer = function ( $text ) {
            if ( function_exists( 'sanitize_text_field' ) ) {
                return sanitize_text_field( $text );
            }

            return is_string( $text ) ? trim( strip_tags( $text ) ) : '';
        };

        foreach ( $value as $event ) {
            if ( $count >= $limit ) {
                break;
            }

            if ( ! is_array( $event ) ) {
                continue;
            }

            $event_key  = visibloc_jlg_sanitize_insight_key( $event['event'] ?? '' );
            $reason_key = visibloc_jlg_sanitize_insight_key( $event['reason'] ?? '' );

            if ( '' === $event_key ) {
                continue;
            }

            $events[] = [
                'event'         => $event_key,
                'reason'        => $reason_key,
                'block_name'    => $sanitizer( $event['block_name'] ?? '' ),
                'post_id'       => isset( $event['post_id'] ) ? (int) $event['post_id'] : 0,
                'post_type'     => $sanitizer( $event['post_type'] ?? '' ),
                'timestamp'     => isset( $event['timestamp'] ) ? max( 0, (int) $event['timestamp'] ) : 0,
                'is_preview'    => ! empty( $event['is_preview'] ),
                'uses_fallback' => ! empty( $event['uses_fallback'] ),
            ];

            $count++;
        }

        return $events;
    }
}

if ( ! function_exists( 'visibloc_jlg_get_insight_event_limit' ) ) {
    /**
     * Retrieve the maximum number of events to persist.
     *
     * @return int
     */
    function visibloc_jlg_get_insight_event_limit() {
        $limit = (int) apply_filters( 'visibloc_jlg_insights_event_history_limit', VISIBLOC_JLG_INSIGHTS_EVENT_LIMIT );

        return $limit > 0 ? $limit : VISIBLOC_JLG_INSIGHTS_EVENT_LIMIT;
    }
}

if ( ! function_exists( 'visibloc_jlg_get_insight_option_defaults' ) ) {
    /**
     * Default persisted insight snapshot structure.
     *
     * @return array
     */
    function visibloc_jlg_get_insight_option_defaults() {
        return [
            'counters'   => [],
            'reasons'    => [],
            'events'     => [],
            'updated_at' => 0,
        ];
    }
}

if ( ! function_exists( 'visibloc_jlg_normalize_insight_snapshot' ) ) {
    /**
     * Normalize a persisted insight snapshot.
     *
     * @param mixed $value Raw option value.
     * @return array
     */
    function visibloc_jlg_normalize_insight_snapshot( $value ) {
        $defaults = visibloc_jlg_get_insight_option_defaults();

        if ( ! is_array( $value ) ) {
            $value = [];
        }

        $snapshot = array_merge( $defaults, $value );

        $snapshot['counters']   = visibloc_jlg_normalize_insight_count_map( $snapshot['counters'] );
        $snapshot['reasons']    = visibloc_jlg_normalize_insight_count_map( $snapshot['reasons'] );
        $snapshot['events']     = visibloc_jlg_normalize_insight_events( $snapshot['events'] );
        $snapshot['updated_at'] = max( 0, (int) $snapshot['updated_at'] );

        return $snapshot;
    }
}

if ( ! function_exists( 'visibloc_jlg_is_insight_collection_enabled' ) ) {
    /**
     * Determine if insight collection is enabled.
     *
     * @return bool
     */
    function visibloc_jlg_is_insight_collection_enabled() {
        $enabled = true;

        if ( function_exists( 'apply_filters' ) ) {
            $enabled = (bool) apply_filters( 'visibloc_jlg_enable_insights_collection', $enabled );
        }

        return $enabled;
    }
}

if ( ! function_exists( 'visibloc_jlg_record_insight_event' ) ) {
    /**
     * Record an insight event in the runtime buffer.
     *
     * @param string $event   Event name (visible, hidden, fallback, preview).
     * @param array  $context Additional context.
     */
    function visibloc_jlg_record_insight_event( $event, array $context = [] ) {
        if ( ! visibloc_jlg_is_insight_collection_enabled() ) {
            return;
        }

        $event_key = visibloc_jlg_sanitize_insight_key( $event );

        if ( '' === $event_key ) {
            return;
        }

        $buffer = &visibloc_jlg_insights_buffer();

        if ( ! isset( $buffer['counters'][ $event_key ] ) ) {
            $buffer['counters'][ $event_key ] = 0;
        }

        $buffer['counters'][ $event_key ]++;

        $reason = '';

        if ( isset( $context['reason'] ) ) {
            $reason = visibloc_jlg_sanitize_insight_key( (string) $context['reason'] );

            if ( '' !== $reason ) {
                if ( ! isset( $buffer['reasons'][ $reason ] ) ) {
                    $buffer['reasons'][ $reason ] = 0;
                }

                $buffer['reasons'][ $reason ]++;
            }
        }

        $sanitizer = function ( $value ) {
            if ( function_exists( 'sanitize_text_field' ) ) {
                return sanitize_text_field( $value );
            }

            return is_string( $value ) ? trim( strip_tags( $value ) ) : '';
        };

        $event_entry = [
            'event'         => $event_key,
            'reason'        => $reason,
            'block_name'    => $sanitizer( $context['block_name'] ?? '' ),
            'post_id'       => isset( $context['post_id'] ) ? (int) $context['post_id'] : 0,
            'post_type'     => $sanitizer( $context['post_type'] ?? '' ),
            'timestamp'     => time(),
            'is_preview'    => ! empty( $context['preview'] ),
            'uses_fallback' => ! empty( $context['uses_fallback'] ),
        ];

        $buffer['events'][] = $event_entry;
        $buffer['has_events'] = true;
    }
}

if ( ! function_exists( 'visibloc_jlg_flush_insight_events' ) ) {
    /**
     * Persist buffered insight events to the database.
     */
    function visibloc_jlg_flush_insight_events() {
        $buffer = visibloc_jlg_insights_buffer();

        if ( empty( $buffer['has_events'] ) ) {
            return;
        }

        $stored    = visibloc_jlg_normalize_insight_snapshot( get_option( VISIBLOC_JLG_INSIGHTS_OPTION, [] ) );
        $counters  = $stored['counters'];
        $reasons   = $stored['reasons'];
        $events    = $stored['events'];
        $event_map = $buffer['counters'];

        foreach ( $event_map as $key => $count ) {
            $counters[ $key ] = ( $counters[ $key ] ?? 0 ) + (int) $count;
        }

        foreach ( $buffer['reasons'] as $key => $count ) {
            $reasons[ $key ] = ( $reasons[ $key ] ?? 0 ) + (int) $count;
        }

        $merged_events = array_merge( $buffer['events'], $events );
        $limit         = visibloc_jlg_get_insight_event_limit();

        if ( count( $merged_events ) > $limit ) {
            $merged_events = array_slice( $merged_events, 0, $limit );
        }

        $snapshot = [
            'counters'   => visibloc_jlg_normalize_insight_count_map( $counters ),
            'reasons'    => visibloc_jlg_normalize_insight_count_map( $reasons ),
            'events'     => visibloc_jlg_normalize_insight_events( $merged_events ),
            'updated_at' => time(),
        ];

        update_option( VISIBLOC_JLG_INSIGHTS_OPTION, $snapshot, false );
        visibloc_jlg_reset_insights_buffer();
    }
}

if ( ! function_exists( 'visibloc_jlg_get_insight_snapshot' ) ) {
    /**
     * Retrieve the latest insight snapshot, merging runtime events when required.
     *
     * @param bool $include_runtime Whether to merge runtime buffered events.
     * @return array
     */
    function visibloc_jlg_get_insight_snapshot( $include_runtime = true ) {
        $stored = visibloc_jlg_normalize_insight_snapshot( get_option( VISIBLOC_JLG_INSIGHTS_OPTION, [] ) );

        if ( ! $include_runtime ) {
            return $stored;
        }

        $buffer = visibloc_jlg_insights_buffer();

        if ( ! empty( $buffer['counters'] ) ) {
            foreach ( $buffer['counters'] as $key => $count ) {
                $stored['counters'][ $key ] = ( $stored['counters'][ $key ] ?? 0 ) + (int) $count;
            }
        }

        if ( ! empty( $buffer['reasons'] ) ) {
            foreach ( $buffer['reasons'] as $key => $count ) {
                $stored['reasons'][ $key ] = ( $stored['reasons'][ $key ] ?? 0 ) + (int) $count;
            }
        }

        if ( ! empty( $buffer['events'] ) ) {
            $merged_events = array_merge( $buffer['events'], $stored['events'] );
            $limit         = visibloc_jlg_get_insight_event_limit();

            if ( count( $merged_events ) > $limit ) {
                $merged_events = array_slice( $merged_events, 0, $limit );
            }

            $stored['events'] = visibloc_jlg_normalize_insight_events( $merged_events );
        }

        return $stored;
    }
}

if ( ! function_exists( 'visibloc_jlg_format_insight_number' ) ) {
    /**
     * Format an integer according to the current locale.
     *
     * @param int $value Integer value.
     * @return string
     */
    function visibloc_jlg_format_insight_number( $value ) {
        $value = (int) $value;

        if ( function_exists( 'number_format_i18n' ) ) {
            return number_format_i18n( $value );
        }

        return number_format( $value );
    }
}

if ( ! function_exists( 'visibloc_jlg_format_insight_percentage' ) ) {
    /**
     * Format a percentage with locale-aware decimals.
     *
     * @param float $value Percentage value (0-100).
     * @param int   $precision Number of decimals.
     * @return string
     */
    function visibloc_jlg_format_insight_percentage( $value, $precision = 1 ) {
        $value = max( 0, min( 100, (float) $value ) );

        if ( function_exists( 'number_format_i18n' ) ) {
            $formatted = number_format_i18n( $value, $precision );
        } else {
            $formatted = number_format( $value, $precision );
        }

        $formatted = rtrim( rtrim( $formatted, '0' ), ',.' );

        if ( '' === $formatted ) {
            $formatted = '0';
        }

        return $formatted . ' %';
    }
}

if ( ! function_exists( 'visibloc_jlg_get_insight_event_labels' ) ) {
    /**
     * Map of insight event labels.
     *
     * @return array<string,string>
     */
    function visibloc_jlg_get_insight_event_labels() {
        return [
            'visible'  => __( 'Bloc affiché', 'visi-bloc-jlg' ),
            'fallback' => __( 'Fallback servi', 'visi-bloc-jlg' ),
            'hidden'   => __( 'Bloc masqué', 'visi-bloc-jlg' ),
            'preview'  => __( 'Aperçu éditeur', 'visi-bloc-jlg' ),
        ];
    }
}

if ( ! function_exists( 'visibloc_jlg_get_insight_reason_labels' ) ) {
    /**
     * Map of insight reason labels.
     *
     * @return array<string,string>
     */
    function visibloc_jlg_get_insight_reason_labels() {
        return [
            'schedule-window' => __( 'Fenêtre de programmation', 'visi-bloc-jlg' ),
            'schedule-invalid' => __( 'Programmation invalide', 'visi-bloc-jlg' ),
            'advanced-rules'  => __( 'Règles avancées', 'visi-bloc-jlg' ),
            'roles'           => __( 'Restriction par rôle', 'visi-bloc-jlg' ),
            'manual-flag'     => __( 'Masquage manuel', 'visi-bloc-jlg' ),
        ];
    }
}

if ( ! function_exists( 'visibloc_jlg_format_insight_relative_time' ) ) {
    /**
     * Format a timestamp as a relative label.
     *
     * @param int $timestamp Unix timestamp.
     * @return string
     */
    function visibloc_jlg_format_insight_relative_time( $timestamp ) {
        $timestamp = (int) $timestamp;

        if ( $timestamp <= 0 ) {
            return '';
        }

        $now = time();

        if ( function_exists( 'human_time_diff' ) ) {
            $diff = human_time_diff( $timestamp, $now );

            return sprintf( __( 'il y a %s', 'visi-bloc-jlg' ), $diff );
        }

        $seconds = max( 0, $now - $timestamp );

        if ( $seconds < 60 ) {
            return __( 'il y a quelques secondes', 'visi-bloc-jlg' );
        }

        $minutes = (int) floor( $seconds / 60 );

        if ( $minutes < 60 ) {
            if ( function_exists( '_n' ) ) {
                return sprintf(
                    _n( 'il y a %s minute', 'il y a %s minutes', $minutes, 'visi-bloc-jlg' ),
                    $minutes
                );
            }

            return sprintf( 'il y a %s minutes', $minutes );
        }

        $hours = (int) floor( $minutes / 60 );

        if ( $hours < 24 ) {
            if ( function_exists( '_n' ) ) {
                return sprintf(
                    _n( 'il y a %s heure', 'il y a %s heures', $hours, 'visi-bloc-jlg' ),
                    $hours
                );
            }

            return sprintf( 'il y a %s heures', $hours );
        }

        $days = (int) floor( $hours / 24 );

        if ( function_exists( '_n' ) ) {
            return sprintf(
                _n( 'il y a %s jour', 'il y a %s jours', $days, 'visi-bloc-jlg' ),
                $days
            );
        }

        return sprintf( 'il y a %s jours', $days );
    }
}

if ( ! function_exists( 'visibloc_jlg_format_insight_absolute_time' ) ) {
    /**
     * Format a timestamp using site preferences.
     *
     * @param int $timestamp Unix timestamp.
     * @return string
     */
    function visibloc_jlg_format_insight_absolute_time( $timestamp ) {
        $timestamp = (int) $timestamp;

        if ( $timestamp <= 0 ) {
            return '';
        }

        $date_format = function_exists( 'get_option' ) ? get_option( 'date_format', 'Y-m-d' ) : 'Y-m-d';
        $time_format = function_exists( 'get_option' ) ? get_option( 'time_format', 'H:i' ) : 'H:i';
        $format      = trim( $date_format . ' ' . $time_format );

        if ( function_exists( 'wp_date' ) ) {
            return wp_date( $format, $timestamp );
        }

        return date( $format, $timestamp );
    }
}

if ( ! function_exists( 'visibloc_jlg_get_insight_block_label' ) ) {
    /**
     * Retrieve a human label for a block type.
     *
     * @param string $block_name Block type name.
     * @return string
     */
    function visibloc_jlg_get_insight_block_label( $block_name ) {
        $block_name = (string) $block_name;

        if ( '' === $block_name ) {
            return __( 'Bloc personnalisé', 'visi-bloc-jlg' );
        }

        if ( class_exists( '\\WP_Block_Type_Registry' ) ) {
            $registry = \WP_Block_Type_Registry::get_instance();

            if ( $registry && method_exists( $registry, 'get_registered' ) ) {
                $block_type = $registry->get_registered( $block_name );

                if ( $block_type && ! empty( $block_type->title ) ) {
                    return (string) $block_type->title;
                }
            }
        }

        if ( false !== strpos( $block_name, '/' ) ) {
            $parts = explode( '/', $block_name );
            $label = end( $parts );

            return ucfirst( str_replace( '-', ' ', (string) $label ) );
        }

        return ucfirst( str_replace( '-', ' ', $block_name ) );
    }
}

if ( ! function_exists( 'visibloc_jlg_get_insight_post_context' ) ) {
    /**
     * Retrieve the title and links for a post.
     *
     * @param int $post_id Post identifier.
     * @return array{title:string,link:string}
     */
    function visibloc_jlg_get_insight_post_context( $post_id ) {
        $post_id = (int) $post_id;

        if ( $post_id <= 0 ) {
            return [
                'title' => '',
                'link'  => '',
            ];
        }

        $title = '';

        if ( function_exists( 'get_the_title' ) ) {
            $title = (string) get_the_title( $post_id );
        }

        if ( '' === $title && function_exists( 'get_post' ) ) {
            $post = get_post( $post_id );

            if ( $post && isset( $post->post_title ) ) {
                $title = (string) $post->post_title;
            }
        }

        if ( '' === $title ) {
            $title = sprintf( __( 'Contenu #%d', 'visi-bloc-jlg' ), $post_id );
        }

        $link = '';

        if ( function_exists( 'current_user_can' ) && function_exists( 'get_edit_post_link' ) ) {
            if ( current_user_can( 'edit_post', $post_id ) ) {
                $link = (string) get_edit_post_link( $post_id );
            }
        }

        if ( '' === $link && function_exists( 'get_permalink' ) ) {
            $link = (string) get_permalink( $post_id );
        }

        return [
            'title' => $title,
            'link'  => $link,
        ];
    }
}

if ( ! function_exists( 'visibloc_jlg_get_insight_dashboard_model' ) ) {
    /**
     * Prepare a formatted model for the admin dashboard.
     *
     * @return array
     */
    function visibloc_jlg_get_insight_dashboard_model() {
        $snapshot = visibloc_jlg_get_insight_snapshot();

        $counters = array_merge(
            [
                'visible'  => 0,
                'fallback' => 0,
                'hidden'   => 0,
                'preview'  => 0,
            ],
            $snapshot['counters']
        );

        $exposure_total        = $counters['visible'] + $counters['fallback'] + $counters['hidden'];
        $hidden_fallback_total = $counters['hidden'] + $counters['fallback'];
        $tracked_total         = $exposure_total + $counters['preview'];

        $rates = [
            'fallback' => $exposure_total > 0 ? ( $counters['fallback'] / $exposure_total ) * 100 : 0.0,
            'hidden'   => $exposure_total > 0 ? ( $counters['hidden'] / $exposure_total ) * 100 : 0.0,
            'preview'  => $tracked_total > 0 ? ( $counters['preview'] / $tracked_total ) * 100 : 0.0,
        ];

        $reason_labels = visibloc_jlg_get_insight_reason_labels();
        $reasons       = $snapshot['reasons'];

        arsort( $reasons );

        $formatted_reasons = [];
        $display_total     = max( 1, $hidden_fallback_total );

        foreach ( $reasons as $reason_key => $count ) {
            $label = $reason_labels[ $reason_key ] ?? ucfirst( str_replace( '-', ' ', $reason_key ) );

            $formatted_reasons[] = [
                'key'                => $reason_key,
                'label'              => $label,
                'count'              => (int) $count,
                'count_display'      => visibloc_jlg_format_insight_number( $count ),
                'percentage'         => $count > 0 ? ( $count / $display_total ) * 100 : 0.0,
                'percentage_display' => $count > 0 ? visibloc_jlg_format_insight_percentage( ( $count / $display_total ) * 100 ) : '',
            ];

            if ( count( $formatted_reasons ) >= 5 ) {
                break;
            }
        }

        $event_labels = visibloc_jlg_get_insight_event_labels();
        $events       = [];
        $event_limit  = (int) apply_filters( 'visibloc_jlg_insights_dashboard_event_display_limit', 10 );
        $event_limit  = $event_limit > 0 ? $event_limit : 10;

        foreach ( $snapshot['events'] as $event ) {
            if ( count( $events ) >= $event_limit ) {
                break;
            }

            $event_key  = $event['event'] ?? '';
            $reason_key = $event['reason'] ?? '';
            $block_name = $event['block_name'] ?? '';
            $post_id    = isset( $event['post_id'] ) ? (int) $event['post_id'] : 0;

            $post_context = visibloc_jlg_get_insight_post_context( $post_id );

            $events[] = [
                'event_key'      => $event_key,
                'event_label'    => $event_labels[ $event_key ] ?? ucfirst( $event_key ),
                'reason_key'     => $reason_key,
                'reason_label'   => $reason_labels[ $reason_key ] ?? ( '' === $reason_key ? '—' : ucfirst( str_replace( '-', ' ', $reason_key ) ) ),
                'block_name'     => $block_name,
                'block_label'    => visibloc_jlg_get_insight_block_label( $block_name ),
                'post_id'        => $post_id,
                'post_title'     => $post_context['title'],
                'post_link'      => $post_context['link'],
                'timestamp'      => isset( $event['timestamp'] ) ? (int) $event['timestamp'] : 0,
                'relative_time'  => visibloc_jlg_format_insight_relative_time( $event['timestamp'] ?? 0 ),
                'absolute_time'  => visibloc_jlg_format_insight_absolute_time( $event['timestamp'] ?? 0 ),
                'is_preview'     => ! empty( $event['is_preview'] ),
                'uses_fallback'  => ! empty( $event['uses_fallback'] ),
            ];
        }

        $updated_at = (int) $snapshot['updated_at'];

        return [
            'counters' => $counters,
            'totals'   => [
                'tracked'       => $tracked_total,
                'exposures'     => $exposure_total,
                'hidden_or_fallback' => $hidden_fallback_total,
                'tracked_display'   => visibloc_jlg_format_insight_number( $tracked_total ),
                'exposures_display' => visibloc_jlg_format_insight_number( $exposure_total ),
                'updated_at'        => $updated_at,
                'updated_human'     => $updated_at > 0 ? visibloc_jlg_format_insight_relative_time( $updated_at ) : '',
            ],
            'rates'   => [
                'fallback'         => $rates['fallback'],
                'fallback_display' => visibloc_jlg_format_insight_percentage( $rates['fallback'] ),
                'hidden'           => $rates['hidden'],
                'hidden_display'   => visibloc_jlg_format_insight_percentage( $rates['hidden'] ),
                'preview'          => $rates['preview'],
                'preview_display'  => visibloc_jlg_format_insight_percentage( $rates['preview'] ),
            ],
            'reasons' => $formatted_reasons,
            'events'  => $events,
        ];
    }
}

if ( function_exists( 'add_action' ) ) {
    add_action( 'shutdown', 'visibloc_jlg_flush_insight_events', 1 );
}
