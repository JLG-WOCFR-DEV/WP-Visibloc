<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/datetime-utils.php';
require_once __DIR__ . '/fallback.php';
require_once __DIR__ . '/geolocation.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/plugin-meta.php';
require_once __DIR__ . '/insights.php';
require_once __DIR__ . '/audit-log.php';

visibloc_jlg_define_default_supported_blocks();

if ( ! defined( 'VISIBLOC_JLG_SUPPORTED_BLOCKS_CACHE_KEY' ) ) {
    define( 'VISIBLOC_JLG_SUPPORTED_BLOCKS_CACHE_KEY', 'visibloc_supported_blocks' );
}

if ( ! defined( 'VISIBLOC_JLG_SUPPORTED_BLOCKS_CACHE_GROUP' ) ) {
    define( 'VISIBLOC_JLG_SUPPORTED_BLOCKS_CACHE_GROUP', 'visibloc_jlg' );
}

/**
 * Internal runtime cache accessor for supported block lists.
 *
 * @param array|null $new_value Optional value to prime the cache.
 * @param bool       $reset     Whether to flush the runtime cache.
 * @return array|null
 */
function visibloc_jlg_supported_blocks_runtime_cache( $new_value = null, $reset = false ) {
    static $cache = [
        'initialized' => false,
        'value'       => [],
    ];

    if ( $reset ) {
        $cache['initialized'] = false;
        $cache['value']       = [];

        return null;
    }

    if ( null !== $new_value ) {
        $cache['initialized'] = true;
        $cache['value']       = $new_value;

        return $cache['value'];
    }

    if ( $cache['initialized'] ) {
        return $cache['value'];
    }

    return null;
}

/**
 * Flush the supported blocks caches (runtime + object cache).
 */
function visibloc_jlg_invalidate_supported_blocks_cache() {
    visibloc_jlg_supported_blocks_runtime_cache( null, true );

    if ( function_exists( 'wp_cache_delete' ) ) {
        wp_cache_delete( VISIBLOC_JLG_SUPPORTED_BLOCKS_CACHE_KEY, VISIBLOC_JLG_SUPPORTED_BLOCKS_CACHE_GROUP );
    }

    if ( function_exists( 'do_action' ) ) {
        /**
         * Fires when the supported blocks cache has been invalidated.
         *
         * This allows integrations to rebuild any dependent caches or
         * re-run expensive calculations that rely on the supported
         * blocks list.
         */
        do_action( 'visibloc_jlg_supported_blocks_cache_invalidated' );
    }
}

/**
 * Persist the supported blocks list inside caches for the current request.
 *
 * @param array $supported_blocks Normalized list of supported blocks.
 */
function visibloc_jlg_prime_supported_blocks_cache( array $supported_blocks ) {
    visibloc_jlg_supported_blocks_runtime_cache( $supported_blocks );
}

/**
 * Retrieve the normalized supported blocks configured via the option.
 *
 * Results are cached persistently to avoid repeated option lookups while still
 * allowing dynamic filters to run on every request.
 *
 * @return array
 */
function visibloc_jlg_get_configured_supported_blocks_from_cache() {
    if ( function_exists( 'wp_cache_get' ) ) {
        $cached_value = wp_cache_get( VISIBLOC_JLG_SUPPORTED_BLOCKS_CACHE_KEY, VISIBLOC_JLG_SUPPORTED_BLOCKS_CACHE_GROUP );

        if ( is_array( $cached_value ) ) {
            return $cached_value;
        }
    }

    $option_value      = get_option( 'visibloc_supported_blocks', [] );
    $configured_blocks = visibloc_jlg_normalize_block_names( $option_value );

    if ( function_exists( 'wp_cache_set' ) ) {
        wp_cache_set(
            VISIBLOC_JLG_SUPPORTED_BLOCKS_CACHE_KEY,
            $configured_blocks,
            VISIBLOC_JLG_SUPPORTED_BLOCKS_CACHE_GROUP
        );
    }

    return $configured_blocks;
}

/**
 * Reset caches when the supported blocks option changes.
 *
 * @internal Hooked to option lifecycle events.
 */
function visibloc_jlg_handle_supported_blocks_option_mutation( ...$unused ) {
    visibloc_jlg_invalidate_supported_blocks_cache();
}

if ( function_exists( 'add_action' ) ) {
    add_action( 'update_option_visibloc_supported_blocks', 'visibloc_jlg_handle_supported_blocks_option_mutation', 10, 3 );
    add_action( 'add_option_visibloc_supported_blocks', 'visibloc_jlg_handle_supported_blocks_option_mutation', 10, 2 );
    add_action( 'delete_option_visibloc_supported_blocks', 'visibloc_jlg_handle_supported_blocks_option_mutation', 10, 1 );
}

final class Visibloc_Jlg_Visibility_Decision {
    public $is_visible;

    public $use_fallback;

    /** @var callable|null */
    public $preview_transform;

    public $reason;

    public function __construct( $is_visible, $use_fallback = false, $preview_transform = null, $reason = '' ) {
        $this->is_visible        = (bool) $is_visible;
        $this->use_fallback      = (bool) $use_fallback;
        $this->preview_transform = is_callable( $preview_transform ) ? $preview_transform : null;
        $this->reason            = (string) $reason;
    }

    public function has_preview_transform() {
        return null !== $this->preview_transform;
    }
}

function visibloc_jlg_get_supported_blocks() {
    $cached_blocks = visibloc_jlg_supported_blocks_runtime_cache();

    if ( null !== $cached_blocks ) {
        return $cached_blocks;
    }

    $default_blocks    = (array) VISIBLOC_JLG_DEFAULT_SUPPORTED_BLOCKS;
    $configured_blocks = visibloc_jlg_get_configured_supported_blocks_from_cache();
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

    $supported_blocks = array_keys( $sanitized );

    visibloc_jlg_prime_supported_blocks_cache( $supported_blocks );

    return $supported_blocks;
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

    $rendered_content = visibloc_jlg_render_block_filter( $block_content, $block );

    if ( function_exists( 'visibloc_jlg_record_audit_event' ) ) {
        $post_id = function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0;

        $state = 'visible';

        if ( '' === $rendered_content ) {
            $state = 'hidden';
        } elseif ( $rendered_content !== $block_content ) {
            $state = 'modified';
        }

        $block_label = '' !== $block_name ? $block_name : 'generic';

        if ( function_exists( 'sanitize_text_field' ) ) {
            $block_label = sanitize_text_field( $block_label );
        }

        $message = sprintf(
            function_exists( '__' ) ? __( 'Bloc %s évalué : %s.', 'visi-bloc-jlg' ) : 'Block %s evaluated: %s.',
            $block_label,
            $state
        );

        $context = [
            'block_name' => $block_label,
            'state'      => $state,
        ];

        if ( $post_id > 0 ) {
            $context['post_id'] = $post_id;
        }

        visibloc_jlg_record_audit_event(
            'block_visibility_checked',
            [
                'message' => $message,
                'context' => $context,
                'post_id' => $post_id,
            ]
        );
    }

    return $rendered_content;
}

add_filter( 'render_block', 'visibloc_jlg_render_block_visibility_router', 10, 2 );

/**
 * Apply Visibloc visibility logic to rendered blocks.
 *
 * L’ordre d’évaluation reflète l’expérience attendue côté éditeur :
 * 1. Préparer le fallback sans le rendre tant qu’aucune règle ne le requiert.
 * 2. Évaluer la programmation : une fenêtre invalide annule le fallback mais doit signaler l’erreur en aperçu.
 * 3. Appliquer les règles avancées, qui peuvent activer le fallback.
 * 4. Vérifier les rôles.
 * 5. Lire le drapeau manuel.
 *
 * La première règle qui masque le bloc renvoie immédiatement le contenu d’aperçu ou le fallback.
 * En mode aperçu, chaque couche doit communiquer explicitement la raison de l’état affiché.
 *
 * @param string $block_content Rendered block markup.
 * @param array  $block         Block instance data.
 *
 * @return string
 */
function visibloc_jlg_render_block_filter( $block_content, $block ) {
    if ( empty( $block['attrs'] ) ) {
        return $block_content;
    }

    $attrs = $block['attrs'];

    $block_name = is_array( $block ) && isset( $block['blockName'] ) && is_string( $block['blockName'] )
        ? $block['blockName']
        : '';

    $post_id = 0;

    if ( function_exists( 'get_the_ID' ) ) {
        $post_id = (int) get_the_ID();
    }

    $post_type = '';

    if ( $post_id > 0 && function_exists( 'get_post_type' ) ) {
        $post_type_value = get_post_type( $post_id );

        if ( is_string( $post_type_value ) ) {
            $post_type = $post_type_value;
        }
    }

    $insight_context = [
        'block_name' => $block_name,
        'post_id'    => $post_id,
        'post_type'  => $post_type,
    ];

    $visibility_roles   = visibloc_jlg_normalize_visibility_roles( $attrs['visibilityRoles'] ?? [] );
    $advanced_visibility = visibloc_jlg_normalize_advanced_visibility( $attrs['advancedVisibility'] ?? null );
    $has_advanced_rules = ! empty( $advanced_visibility['rules'] );
    $has_hidden_flag    = isset( $attrs['isHidden'] ) ? visibloc_jlg_normalize_boolean( $attrs['isHidden'] ) : false;
    $has_schedule       = isset( $attrs['isSchedulingEnabled'] ) ? visibloc_jlg_normalize_boolean( $attrs['isSchedulingEnabled'] ) : false;

    if ( ! $has_hidden_flag && ! $has_schedule && empty( $visibility_roles ) && ! $has_advanced_rules ) {
        return $block_content;
    }

    $get_fallback_markup = visibloc_jlg_create_fallback_loader( $attrs );

    $preview_context = function_exists( 'visibloc_jlg_get_preview_runtime_context' )
        ? visibloc_jlg_get_preview_runtime_context()
        : [
            'can_preview_hidden_blocks' => false,
            'should_apply_preview_role' => false,
            'preview_role'              => '',
        ];

    $can_preview_hidden_blocks = ! empty( $preview_context['can_preview_hidden_blocks'] );
    $user_visibility_context   = visibloc_jlg_get_user_visibility_context( $preview_context, $can_preview_hidden_blocks );
    $preview_transforms        = [];
    $preview_reasons           = [];

    $decisions = [];

    $schedule_timezone       = visibloc_jlg_normalize_schedule_timezone( $attrs['publishTimezone'] ?? 'site' );
    $schedule_timezone_label = visibloc_jlg_get_timezone_display_label( $schedule_timezone );

    $decisions[] = visibloc_jlg_should_display_for_schedule(
        [
            'is_enabled' => $has_schedule,
            'start_time' => visibloc_jlg_parse_schedule_datetime( $attrs['publishStartDate'] ?? null, $schedule_timezone ),
            'end_time'   => visibloc_jlg_parse_schedule_datetime( $attrs['publishEndDate'] ?? null, $schedule_timezone ),
        ],
        [
            'current_time'    => current_datetime()->getTimestamp(),
            'can_preview'     => $can_preview_hidden_blocks,
            'datetime_format' => visibloc_jlg_get_wp_datetime_format(),
            'timezone'        => $schedule_timezone,
            'timezone_label'  => $schedule_timezone_label,
        ]
    );

    $decisions[] = visibloc_jlg_should_display_for_advanced_rules( $advanced_visibility, $user_visibility_context, $can_preview_hidden_blocks );
    $decisions[] = visibloc_jlg_should_display_for_roles( $visibility_roles, $user_visibility_context, $can_preview_hidden_blocks );
    $decisions[] = visibloc_jlg_should_display_for_manual_flag( $has_hidden_flag, $can_preview_hidden_blocks );

    foreach ( $decisions as $decision ) {
        $rendered = visibloc_jlg_process_visibility_decision(
            $decision,
            $block_content,
            $get_fallback_markup,
            $preview_transforms,
            $insight_context,
            $preview_reasons
        );

        if ( null !== $rendered ) {
            return $rendered;
        }
    }

    if ( ! empty( $preview_transforms ) ) {
        $preview_context = $insight_context;
        $preview_context['reason']        = $preview_reasons[0] ?? '';
        $preview_context['preview']       = true;
        $preview_context['uses_fallback'] = false;

        visibloc_jlg_record_insight_event( 'preview', $preview_context );

        return visibloc_jlg_apply_preview_transforms( $preview_transforms, $block_content );
    }

    visibloc_jlg_record_insight_event( 'visible', $insight_context );

    return $block_content;
}

function visibloc_jlg_normalize_visibility_roles( $raw_roles ) {
    if ( is_string( $raw_roles ) ) {
        $raw_roles = '' === trim( $raw_roles ) ? [] : [ $raw_roles ];
    } elseif ( is_scalar( $raw_roles ) ) {
        $raw_roles = [ $raw_roles ];
    }

    if ( ! is_array( $raw_roles ) ) {
        return [];
    }

    $normalized = [];

    foreach ( $raw_roles as $role ) {
        if ( ! is_scalar( $role ) ) {
            continue;
        }

        $sanitized = sanitize_key( (string) $role );

        if ( '' !== $sanitized ) {
            $normalized[] = $sanitized;
        }
    }

    return array_values( array_unique( $normalized ) );
}

function visibloc_jlg_create_fallback_loader( array $attrs ) {
    $fallback_initialized = false;
    $fallback_markup      = null;

    return static function () use ( &$fallback_initialized, &$fallback_markup, $attrs ) {
        if ( ! $fallback_initialized ) {
            $fallback_markup      = visibloc_jlg_get_block_fallback_markup( $attrs );
            $fallback_initialized = true;
        }

        return $fallback_markup;
    };
}

function visibloc_jlg_visibility_decision( $is_visible, $use_fallback = false, $preview_transform = null, $reason = '' ) {
    return new Visibloc_Jlg_Visibility_Decision( $is_visible, $use_fallback, $preview_transform, $reason );
}

function visibloc_jlg_should_display_for_schedule( array $schedule, array $context ) {
    if ( empty( $schedule['is_enabled'] ) ) {
        return visibloc_jlg_visibility_decision( true, false, null, 'schedule-disabled' );
    }

    $start_time   = $schedule['start_time'];
    $end_time     = $schedule['end_time'];
    $can_preview  = ! empty( $context['can_preview'] );
    $current_time = isset( $context['current_time'] ) ? (int) $context['current_time'] : time();
    $preview_timezone_label = isset( $context['timezone_label'] ) ? (string) $context['timezone_label'] : '';
    $preview_timezone       = visibloc_jlg_resolve_schedule_timezone( $context['timezone'] ?? null );

    if ( null !== $start_time && null !== $end_time && $start_time > $end_time ) {
        if ( $can_preview ) {
            $transform = static function ( $content ) {
                $schedule_error_label = __( 'Invalid schedule', 'visi-bloc-jlg' );

                return sprintf(
                    '<div class="bloc-schedule-error vb-label-top" data-visibloc-reason="schedule-invalid">%s%s</div>',
                    visibloc_jlg_render_status_badge(
                        $schedule_error_label,
                        'schedule-error',
                        __( 'La programmation actuelle empêche l’affichage de ce bloc.', 'visi-bloc-jlg' )
                    ),
                    $content
                );
            };

            return visibloc_jlg_visibility_decision( true, false, $transform, 'schedule-invalid' );
        }

        return visibloc_jlg_visibility_decision( true, false, null, 'schedule-invalid' );
    }

    $is_before_start = null !== $start_time && $current_time < $start_time;
    $is_after_end    = null !== $end_time && $current_time > $end_time;

    if ( $is_before_start || $is_after_end ) {
        if ( $can_preview ) {
            $datetime_format = isset( $context['datetime_format'] ) ? (string) $context['datetime_format'] : get_option( 'date_format', 'Y-m-d' );
            $start_date_fr   = $start_time ? wp_date( $datetime_format, $start_time, $preview_timezone ) : __( 'N/A', 'visi-bloc-jlg' );
            $end_date_fr     = $end_time ? wp_date( $datetime_format, $end_time, $preview_timezone ) : __( 'N/A', 'visi-bloc-jlg' );
            $info            = sprintf(
                /* translators: 1: start date, 2: end date. */
                __( 'Programmé (Début:%1$s | Fin:%2$s)', 'visi-bloc-jlg' ),
                $start_date_fr,
                $end_date_fr
            );

            if ( '' !== $preview_timezone_label ) {
                $info .= sprintf( ' — %s', $preview_timezone_label );
            }

            $transform = static function ( $content ) use ( $info ) {
                return '<div class="bloc-schedule-apercu vb-label-top" data-visibloc-reason="schedule-window">'
                    . visibloc_jlg_render_status_badge(
                        $info,
                        'schedule',
                        sprintf(
                            /* translators: %s: scheduling information. */
                            __( 'Ce bloc est programmé : %s', 'visi-bloc-jlg' ),
                            $info
                        )
                    )
                    . $content
                    . '</div>';
            };

            return visibloc_jlg_visibility_decision( false, true, $transform, 'schedule-window' );
        }

        return visibloc_jlg_visibility_decision( false, true, null, 'schedule-window' );
    }

    return visibloc_jlg_visibility_decision( true, false, null, 'schedule-window' );
}

function visibloc_jlg_should_display_for_advanced_rules( array $advanced_visibility, array $user_visibility_context, $can_preview_hidden_blocks ) {
    if ( empty( $advanced_visibility['rules'] ) ) {
        return visibloc_jlg_visibility_decision( true, false, null, 'advanced-rules' );
    }

    $runtime_context = [
        'user'        => $user_visibility_context,
        'geolocation' => visibloc_jlg_get_geolocation_context(),
    ];

    $advanced_rules_match = visibloc_jlg_evaluate_advanced_visibility(
        $advanced_visibility,
        $runtime_context
    );

    if ( $advanced_rules_match ) {
        return visibloc_jlg_visibility_decision( true, false, null, 'advanced-rules' );
    }

    if ( $can_preview_hidden_blocks ) {
        $transform = static function ( $content ) {
            $advanced_label = __( 'Règles avancées actives', 'visi-bloc-jlg' );

            return sprintf(
                '<div class="bloc-advanced-apercu vb-label-top" data-visibloc-reason="advanced-rules">%s%s</div>',
                visibloc_jlg_render_status_badge(
                    $advanced_label,
                    'advanced',
                    __( 'Des règles avancées masquent ce bloc pour les visiteurs.', 'visi-bloc-jlg' )
                ),
                $content
            );
        };

        return visibloc_jlg_visibility_decision( false, true, $transform, 'advanced-rules' );
    }

    return visibloc_jlg_visibility_decision( false, true, null, 'advanced-rules' );
}

function visibloc_jlg_should_display_for_roles( array $visibility_roles, array $user_visibility_context, $can_preview_hidden_blocks ) {
    if ( empty( $visibility_roles ) ) {
        return visibloc_jlg_visibility_decision( true, false, null, 'roles' );
    }

    $is_logged_in = ! empty( $user_visibility_context['is_logged_in'] );
    $user_roles   = isset( $user_visibility_context['roles'] ) && is_array( $user_visibility_context['roles'] )
        ? $user_visibility_context['roles']
        : [];

    $is_visible = false;

    if ( in_array( 'logged-out', $visibility_roles, true ) && ! $is_logged_in ) {
        $is_visible = true;
    }

    if ( ! $is_visible && in_array( 'logged-in', $visibility_roles, true ) && $is_logged_in ) {
        $is_visible = true;
    }

    if ( ! $is_visible && ! empty( $user_roles ) && array_intersect( $user_roles, $visibility_roles ) ) {
        $is_visible = true;
    }

    if ( $is_visible ) {
        return visibloc_jlg_visibility_decision( true, false, null, 'roles' );
    }

    if ( $can_preview_hidden_blocks ) {
        $transform = static function ( $content ) {
            $label = __( 'Restriction par rôle', 'visi-bloc-jlg' );

            return sprintf(
                '<div class="bloc-role-apercu vb-label-top" data-visibloc-reason="roles">%s%s</div>',
                visibloc_jlg_render_status_badge(
                    $label,
                    'roles',
                    __( 'Ce bloc est réservé à des rôles spécifiques.', 'visi-bloc-jlg' )
                ),
                $content
            );
        };

        return visibloc_jlg_visibility_decision( false, true, $transform, 'roles' );
    }

    return visibloc_jlg_visibility_decision( false, true, null, 'roles' );
}

function visibloc_jlg_should_display_for_manual_flag( $has_hidden_flag, $can_preview_hidden_blocks ) {
    if ( ! $has_hidden_flag ) {
        return visibloc_jlg_visibility_decision( true, false, null, 'manual-flag' );
    }

    if ( $can_preview_hidden_blocks ) {
        $transform = static function ( $content ) {
            $hidden_preview_label = __( 'Hidden block', 'visi-bloc-jlg' );

            return sprintf(
                '<div class="bloc-cache-apercu vb-label-top" data-visibloc-reason="manual-flag">%s%s</div>',
                visibloc_jlg_render_status_badge(
                    $hidden_preview_label,
                    'hidden',
                    __( 'Ce bloc est masqué pour les visiteurs du site.', 'visi-bloc-jlg' )
                ),
                $content
            );
        };

        return visibloc_jlg_visibility_decision( false, true, $transform, 'manual-flag' );
    }

    return visibloc_jlg_visibility_decision( false, true, null, 'manual-flag' );
}

function visibloc_jlg_process_visibility_decision( Visibloc_Jlg_Visibility_Decision $decision, $block_content, callable $get_fallback_markup, array &$preview_transforms, array $insight_context = [], array &$preview_reasons = [] ) {
    $reason = is_string( $decision->reason ) ? $decision->reason : '';

    if ( $decision->has_preview_transform() ) {
        $preview_transforms[] = $decision->preview_transform;

        if ( '' !== $reason ) {
            $preview_reasons[] = $reason;
        }
    }

    if ( $decision->is_visible ) {
        return null;
    }

    if ( ! empty( $preview_transforms ) ) {
        $preview_markup = visibloc_jlg_apply_preview_transforms( $preview_transforms, $block_content );

        $event_context = $insight_context;
        $event_context['reason']        = $reason;
        $event_context['preview']       = true;
        $event_context['uses_fallback'] = (bool) $decision->use_fallback;

        visibloc_jlg_record_insight_event( 'preview', $event_context );

        if ( $decision->use_fallback ) {
            $preview_markup = visibloc_jlg_wrap_preview_with_fallback_notice( $preview_markup, $get_fallback_markup(), $decision->reason );
        }

        return $preview_markup;
    }

    if ( $decision->use_fallback ) {
        $event_context = $insight_context;
        $event_context['reason']        = $reason;
        $event_context['uses_fallback'] = true;

        visibloc_jlg_record_insight_event( 'fallback', $event_context );

        return $get_fallback_markup();
    }

    $event_context = $insight_context;
    $event_context['reason'] = $reason;

    visibloc_jlg_record_insight_event( 'hidden', $event_context );

    return '';
}

function visibloc_jlg_apply_preview_transforms( array $transforms, $block_content ) {
    $preview_markup = $block_content;

    foreach ( $transforms as $transform ) {
        if ( is_callable( $transform ) ) {
            $preview_markup = $transform( $preview_markup );
        }
    }

    return $preview_markup;
}

function visibloc_jlg_wrap_preview_with_fallback_notice( $preview_markup, $fallback_markup, $reason = '' ) {
    if ( '' === $fallback_markup ) {
        return $preview_markup;
    }

    $label = __( 'Contenu de repli actif', 'visi-bloc-jlg' );
    $reason_attr = '';

    if ( '' !== $reason ) {
        $reason_attr = sprintf(
            ' data-visibloc-reason="%s"',
            esc_attr( $reason )
        );
    }

    return sprintf(
        '<div class="bloc-fallback-apercu vb-label-top" data-visibloc-fallback="1"%s>%s%s<div class="bloc-fallback-apercu__replacement">%s</div></div>',
        $reason_attr,
        visibloc_jlg_render_status_badge(
            $label,
            'fallback',
            __( 'Le contenu de repli est affiché à la place du bloc original.', 'visi-bloc-jlg' )
        ),
        $preview_markup,
        $fallback_markup
    );
}

function visibloc_jlg_get_user_visibility_context( $preview_context, &$can_preview_hidden_blocks, $reset_cache = false ) {
    static $cached_user_ref            = null;
    static $cached_user_logged_in      = null;
    static $cached_user_roles          = null;
    static $allowed_preview_roles_cache = null;
    static $role_exists_cache          = [];

    if ( $reset_cache ) {
        $cached_user_ref             = null;
        $cached_user_logged_in       = null;
        $cached_user_roles           = null;
        $allowed_preview_roles_cache = null;
        $role_exists_cache           = [];

        if ( function_exists( 'visibloc_jlg_get_geolocation_context' ) ) {
            visibloc_jlg_get_geolocation_context( true );
        }
    }

    $current_user = wp_get_current_user();
    $current_roles = (array) $current_user->roles;
    $current_is_logged_in = $current_user->exists();

    if ( ! ( $cached_user_ref instanceof WP_User )
        || $cached_user_ref !== $current_user
        || $cached_user_logged_in !== $current_is_logged_in
        || $cached_user_roles !== $current_roles
    ) {
        $cached_user_ref        = $current_user;
        $cached_user_logged_in  = $current_is_logged_in;
        $cached_user_roles      = $current_roles;
    }

    $is_logged_in = $cached_user_logged_in;
    $user_roles   = $cached_user_roles;

    $should_apply_preview_role = ! empty( $preview_context['should_apply_preview_role'] );
    $preview_role = isset( $preview_context['preview_role'] ) && is_string( $preview_context['preview_role'] )
        ? $preview_context['preview_role']
        : '';
    $applied_preview_role = '';

    if ( $should_apply_preview_role ) {
        if ( 'guest' === $preview_role ) {
            $is_logged_in = false;
            $user_roles   = [];
            $applied_preview_role = 'guest';
        } elseif ( '' !== $preview_role ) {
            if ( null === $allowed_preview_roles_cache ) {
                $allowed_preview_roles_cache = function_exists( 'visibloc_jlg_get_allowed_preview_roles' )
                    ? (array) visibloc_jlg_get_allowed_preview_roles()
                    : [];
            }

            if ( ! array_key_exists( $preview_role, $role_exists_cache ) ) {
                $role_exists_cache[ $preview_role ] = (bool) get_role( $preview_role );
            }

            if ( $role_exists_cache[ $preview_role ] ) {
                if ( ! in_array( $preview_role, $allowed_preview_roles_cache, true ) ) {
                    $can_preview_hidden_blocks = false;
                }

                $is_logged_in         = true;
                $user_roles           = [ $preview_role ];
                $applied_preview_role = $preview_role;
            } else {
                $should_apply_preview_role = false;
                $preview_role             = '';
            }
        }
    }

    return [
        'is_logged_in'             => (bool) $is_logged_in,
        'roles'                    => array_values( array_unique( array_map( 'strval', (array) $user_roles ) ) ),
        'should_apply_preview_role' => $should_apply_preview_role && '' !== $preview_role,
        'preview_role'             => $applied_preview_role,
    ];
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

    $supported_types = [
        'post_type',
        'taxonomy',
        'template',
        'recurring_schedule',
        'logged_in_status',
        'user_role_group',
        'user_segment',
        'woocommerce_cart',
        'query_param',
        'cookie',
        'visit_count',
        'geolocation',
    ];

    if ( ! in_array( $type, $supported_types, true ) ) {
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
        case 'logged_in_status':
            $normalized['operator'] = isset( $rule['operator'] ) && 'is_not' === $rule['operator'] ? 'is_not' : 'is';
            $normalized['value']    = isset( $rule['value'] ) && is_string( $rule['value'] ) ? $rule['value'] : '';
            break;
        case 'user_role_group':
            $normalized['operator'] = isset( $rule['operator'] ) && 'does_not_match' === $rule['operator']
                ? 'does_not_match'
                : 'matches';
            $normalized['group'] = isset( $rule['group'] ) && is_string( $rule['group'] ) ? $rule['group'] : '';
            break;
        case 'user_segment':
            $normalized['operator'] = isset( $rule['operator'] ) && 'does_not_match' === $rule['operator']
                ? 'does_not_match'
                : 'matches';
            $normalized['segment'] = isset( $rule['segment'] ) && is_string( $rule['segment'] ) ? $rule['segment'] : '';
            break;
        case 'woocommerce_cart':
            $normalized['operator'] = isset( $rule['operator'] ) && 'not_contains' === $rule['operator'] ? 'not_contains' : 'contains';
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
        case 'query_param':
            $allowed_operators = [ 'equals', 'not_equals', 'contains', 'not_contains', 'exists', 'not_exists' ];
            $operator = isset( $rule['operator'] ) && in_array( $rule['operator'], $allowed_operators, true )
                ? $rule['operator']
                : 'equals';
            $normalized['operator'] = $operator;
            $normalized['param']    = isset( $rule['param'] ) && is_string( $rule['param'] ) ? $rule['param'] : '';
            $normalized['value']    = isset( $rule['value'] ) && is_string( $rule['value'] ) ? $rule['value'] : '';
            break;
        case 'cookie':
            $allowed_cookie_operators = [ 'equals', 'not_equals', 'contains', 'not_contains', 'exists', 'not_exists' ];
            $operator                 = isset( $rule['operator'] ) && in_array( $rule['operator'], $allowed_cookie_operators, true )
                ? $rule['operator']
                : 'equals';
            $normalized['operator']   = $operator;
            $normalized['name']       = isset( $rule['name'] ) && is_string( $rule['name'] ) ? $rule['name'] : '';
            $normalized['value']      = isset( $rule['value'] ) && is_string( $rule['value'] ) ? $rule['value'] : '';
            break;
        case 'geolocation':
            $operator = isset( $rule['operator'] ) && 'not_in' === $rule['operator'] ? 'not_in' : 'in';
            $normalized['operator']  = $operator;
            $normalized['countries'] = [];

            if ( isset( $rule['countries'] ) && is_array( $rule['countries'] ) ) {
                foreach ( $rule['countries'] as $country ) {
                    $code = visibloc_jlg_normalize_country_code( $country );

                    if ( '' !== $code ) {
                        $normalized['countries'][] = $code;
                    }
                }
            }

            $normalized['countries'] = array_values( array_unique( $normalized['countries'] ) );
            break;
        case 'visit_count':
            $allowed_visit_operators  = [ 'at_least', 'at_most', 'equals', 'not_equals' ];
            $operator                 = isset( $rule['operator'] ) && in_array( $rule['operator'], $allowed_visit_operators, true )
                ? $rule['operator']
                : 'at_least';
            $threshold                = isset( $rule['threshold'] ) ? (int) $rule['threshold'] : 0;
            $normalized['operator']   = $operator;
            $normalized['threshold']  = max( 0, $threshold );
            break;
    }

    return $normalized;
}

function visibloc_jlg_evaluate_advanced_visibility( $advanced_visibility, $runtime_context = [] ) {
    if ( empty( $advanced_visibility['rules'] ) ) {
        return true;
    }

    $post_context = visibloc_jlg_get_visibility_post_context();
    $logic        = isset( $advanced_visibility['logic'] ) && 'OR' === $advanced_visibility['logic'] ? 'OR' : 'AND';
    $results      = [];

    foreach ( $advanced_visibility['rules'] as $rule ) {
        $results[] = visibloc_jlg_evaluate_advanced_rule( $rule, $post_context, $runtime_context );
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

function visibloc_jlg_evaluate_advanced_rule( $rule, $post, $runtime_context = [] ) {
    switch ( $rule['type'] ) {
        case 'post_type':
            return visibloc_jlg_match_post_type_rule( $rule, $post );
        case 'taxonomy':
            return visibloc_jlg_match_taxonomy_rule( $rule, $post );
        case 'template':
            return visibloc_jlg_match_template_rule( $rule, $post );
        case 'recurring_schedule':
            return visibloc_jlg_match_recurring_schedule_rule( $rule );
        case 'logged_in_status':
            return visibloc_jlg_match_logged_in_status_rule( $rule, $runtime_context );
        case 'user_role_group':
            return visibloc_jlg_match_user_role_group_rule( $rule, $runtime_context );
        case 'woocommerce_cart':
            return visibloc_jlg_match_woocommerce_cart_rule( $rule );
        case 'query_param':
            return visibloc_jlg_match_query_param_rule( $rule );
        case 'cookie':
            return visibloc_jlg_match_cookie_rule( $rule );
        case 'user_segment':
            return visibloc_jlg_match_user_segment_rule( $rule, $runtime_context );
        case 'geolocation':
            return visibloc_jlg_match_geolocation_rule( $rule, $runtime_context );
        case 'visit_count':
            return visibloc_jlg_match_visit_count_rule( $rule );
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

function visibloc_jlg_get_role_group_map() {
    static $cache = null;

    if ( null !== $cache ) {
        return $cache;
    }

    $cache = [];
    $groups = function_exists( 'visibloc_jlg_get_role_group_definitions' )
        ? (array) visibloc_jlg_get_role_group_definitions()
        : [];

    foreach ( $groups as $group ) {
        if ( ! is_array( $group ) ) {
            continue;
        }

        $value = isset( $group['value'] ) ? (string) $group['value'] : '';

        if ( '' === $value ) {
            continue;
        }

        $roles = [];

        if ( isset( $group['roles'] ) && is_array( $group['roles'] ) ) {
            foreach ( $group['roles'] as $role_slug ) {
                if ( is_string( $role_slug ) && '' !== $role_slug ) {
                    $roles[] = $role_slug;
                }
            }
        }

        $cache[ $value ] = array_values( array_unique( $roles ) );
    }

    return $cache;
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

function visibloc_jlg_match_logged_in_status_rule( $rule, $runtime_context ) {
    $operator = isset( $rule['operator'] ) && 'is_not' === $rule['operator'] ? 'is_not' : 'is';
    $target   = isset( $rule['value'] ) ? (string) $rule['value'] : '';

    if ( '' === $target ) {
        return true;
    }

    $user_context = isset( $runtime_context['user'] ) && is_array( $runtime_context['user'] )
        ? $runtime_context['user']
        : [];
    $is_logged_in = ! empty( $user_context['is_logged_in'] );

    $matches = false;

    if ( 'logged_in' === $target ) {
        $matches = $is_logged_in;
    } elseif ( 'logged_out' === $target ) {
        $matches = ! $is_logged_in;
    }

    return 'is_not' === $operator ? ! $matches : $matches;
}

function visibloc_jlg_match_user_role_group_rule( $rule, $runtime_context ) {
    $group    = isset( $rule['group'] ) ? (string) $rule['group'] : '';
    $operator = isset( $rule['operator'] ) && 'does_not_match' === $rule['operator'] ? 'does_not_match' : 'matches';

    if ( '' === $group ) {
        return true;
    }

    $user_context = isset( $runtime_context['user'] ) && is_array( $runtime_context['user'] )
        ? $runtime_context['user']
        : [];
    $is_logged_in = ! empty( $user_context['is_logged_in'] );
    $user_roles = isset( $user_context['roles'] ) && is_array( $user_context['roles'] )
        ? array_values( array_unique( array_map( 'strval', $user_context['roles'] ) ) )
        : [];

    $group_map = visibloc_jlg_get_role_group_map();
    $group_roles = isset( $group_map[ $group ] ) ? $group_map[ $group ] : [];

    if ( empty( $group_roles ) ) {
        $matches = false;
    } else {
        if ( ! $is_logged_in ) {
            $matches = false;
        } else {
            $matches = ! empty( array_intersect( $group_roles, $user_roles ) );
        }
    }

    return 'does_not_match' === $operator ? ! $matches : $matches;
}

function visibloc_jlg_match_user_segment_rule( $rule, $runtime_context ) {
    $segment  = isset( $rule['segment'] ) ? (string) $rule['segment'] : '';
    $operator = isset( $rule['operator'] ) && 'does_not_match' === $rule['operator'] ? 'does_not_match' : 'matches';

    if ( '' === $segment ) {
        return true;
    }

    $user_context = isset( $runtime_context['user'] ) && is_array( $runtime_context['user'] )
        ? $runtime_context['user']
        : [];

    $matched = false;

    if ( function_exists( 'apply_filters' ) ) {
        $matched = (bool) apply_filters(
            'visibloc_jlg_user_segment_matches',
            $matched,
            [
                'segment' => $segment,
                'user'    => $user_context,
                'rule'    => $rule,
            ]
        );
    }

    return 'does_not_match' === $operator ? ! $matched : $matched;
}

function visibloc_jlg_match_geolocation_rule( $rule, $runtime_context ) {
    $operator  = isset( $rule['operator'] ) && 'not_in' === $rule['operator'] ? 'not_in' : 'in';
    $countries = isset( $rule['countries'] ) && is_array( $rule['countries'] )
        ? array_values( array_filter( array_map( 'visibloc_jlg_normalize_country_code', $rule['countries'] ) ) )
        : [];

    if ( empty( $countries ) ) {
        return true;
    }

    $geolocation = isset( $runtime_context['geolocation'] ) && is_array( $runtime_context['geolocation'] )
        ? $runtime_context['geolocation']
        : visibloc_jlg_get_geolocation_context();

    $country_code = isset( $geolocation['country_code'] )
        ? visibloc_jlg_normalize_country_code( $geolocation['country_code'] )
        : '';

    if ( '' === $country_code ) {
        $default_visibility = ( 'not_in' === $operator );

        return (bool) apply_filters(
            'visibloc_jlg_geolocation_default_match',
            $default_visibility,
            $rule,
            $runtime_context,
            $geolocation
        );
    }

    $is_match = in_array( $country_code, $countries, true );

    return 'not_in' === $operator ? ! $is_match : $is_match;
}

function visibloc_jlg_match_woocommerce_cart_rule( $rule ) {
    $operator = isset( $rule['operator'] ) && 'not_contains' === $rule['operator'] ? 'not_contains' : 'contains';
    $taxonomy = isset( $rule['taxonomy'] ) ? (string) $rule['taxonomy'] : '';
    $terms    = isset( $rule['terms'] ) && is_array( $rule['terms'] ) ? array_values( array_unique( array_map( 'strval', $rule['terms'] ) ) ) : [];

    if ( '' === $taxonomy ) {
        return 'not_contains' === $operator;
    }

    if ( 'contains' === $operator && empty( $terms ) ) {
        return false;
    }

    if ( ! function_exists( 'WC' ) ) {
        return 'not_contains' === $operator;
    }

    $wc = WC();

    if ( ! $wc || ! isset( $wc->cart ) || ! $wc->cart ) {
        return 'not_contains' === $operator;
    }

    $cart = $wc->cart;

    if ( ! method_exists( $cart, 'get_cart' ) ) {
        return 'not_contains' === $operator;
    }

    $cart_items = $cart->get_cart();

    if ( empty( $cart_items ) ) {
        return 'not_contains' === $operator;
    }

    $product_ids = [];

    foreach ( $cart_items as $item ) {
        if ( ! is_array( $item ) ) {
            continue;
        }

        if ( isset( $item['product_id'] ) ) {
            $product_ids[] = (int) $item['product_id'];
        }

        if ( isset( $item['variation_id'] ) ) {
            $product_ids[] = (int) $item['variation_id'];
        }
    }

    $product_ids = array_values( array_unique( array_filter( $product_ids ) ) );

    if ( empty( $product_ids ) ) {
        return 'not_contains' === $operator;
    }

    $matched = false;

    foreach ( $product_ids as $product_id ) {
        $term_slugs = wp_get_object_terms( $product_id, $taxonomy, [ 'fields' => 'slugs' ] );

        if ( ! is_wp_error( $term_slugs ) ) {
            foreach ( $term_slugs as $slug ) {
                if ( in_array( (string) $slug, $terms, true ) ) {
                    $matched = true;
                    break 2;
                }
            }
        }

        $term_ids = wp_get_object_terms( $product_id, $taxonomy, [ 'fields' => 'ids' ] );

        if ( is_wp_error( $term_ids ) ) {
            continue;
        }

        foreach ( $term_ids as $term_id ) {
            if ( in_array( (string) $term_id, $terms, true ) ) {
                $matched = true;
                break 2;
            }
        }
    }

    return 'not_contains' === $operator ? ! $matched : $matched;
}

function visibloc_jlg_match_query_param_rule( $rule ) {
    $operator = isset( $rule['operator'] ) ? (string) $rule['operator'] : 'equals';
    $param    = isset( $rule['param'] ) ? (string) $rule['param'] : '';
    $value    = isset( $rule['value'] ) ? (string) $rule['value'] : '';

    if ( '' === $param ) {
        return true;
    }

    $raw = isset( $_GET[ $param ] ) ? wp_unslash( $_GET[ $param ] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    $values = [];

    if ( is_array( $raw ) ) {
        foreach ( $raw as $item ) {
            if ( is_scalar( $item ) ) {
                $values[] = (string) $item;
            }
        }
    } elseif ( null !== $raw ) {
        $values[] = (string) $raw;
    }

    switch ( $operator ) {
        case 'exists':
            return ! empty( $values );
        case 'not_exists':
            return empty( $values );
        case 'equals':
            if ( empty( $values ) ) {
                return false;
            }

            return in_array( $value, $values, true );
        case 'not_equals':
            if ( empty( $values ) ) {
                return true;
            }

            return ! in_array( $value, $values, true );
        case 'contains':
            if ( '' === $value ) {
                return ! empty( $values );
            }

            foreach ( $values as $current_value ) {
                if ( false !== strpos( $current_value, $value ) ) {
                    return true;
                }
            }

            return false;
        case 'not_contains':
            if ( '' === $value ) {
                return empty( $values );
            }

            foreach ( $values as $current_value ) {
                if ( false !== strpos( $current_value, $value ) ) {
                    return false;
                }
            }

            return true;
    }

    return true;
}

function visibloc_jlg_match_cookie_rule( $rule ) {
    $operator = isset( $rule['operator'] ) ? (string) $rule['operator'] : 'equals';
    $name     = isset( $rule['name'] ) ? (string) $rule['name'] : '';
    $value    = isset( $rule['value'] ) ? (string) $rule['value'] : '';

    if ( '' === $name ) {
        return true;
    }

    $raw = isset( $_COOKIE[ $name ] ) ? wp_unslash( $_COOKIE[ $name ] ) : null;

    $values = [];

    if ( is_array( $raw ) ) {
        foreach ( $raw as $item ) {
            if ( is_scalar( $item ) ) {
                $values[] = (string) $item;
            }
        }
    } elseif ( null !== $raw ) {
        $values[] = (string) $raw;
    }

    switch ( $operator ) {
        case 'exists':
            return ! empty( $values );
        case 'not_exists':
            return empty( $values );
        case 'equals':
            if ( empty( $values ) ) {
                return false;
            }

            return in_array( $value, $values, true );
        case 'not_equals':
            if ( empty( $values ) ) {
                return true;
            }

            return ! in_array( $value, $values, true );
        case 'contains':
            if ( '' === $value ) {
                return ! empty( $values );
            }

            foreach ( $values as $current_value ) {
                if ( false !== strpos( $current_value, $value ) ) {
                    return true;
                }
            }

            return false;
        case 'not_contains':
            if ( '' === $value ) {
                return empty( $values );
            }

            foreach ( $values as $current_value ) {
                if ( false !== strpos( $current_value, $value ) ) {
                    return false;
                }
            }

            return true;
    }

    return true;
}

function visibloc_jlg_match_visit_count_rule( $rule ) {
    $allowed_operators = [ 'at_least', 'at_most', 'equals', 'not_equals' ];
    $operator          = isset( $rule['operator'] ) && in_array( $rule['operator'], $allowed_operators, true )
        ? $rule['operator']
        : 'at_least';

    $threshold = isset( $rule['threshold'] ) ? (int) $rule['threshold'] : 0;

    if ( $threshold < 0 ) {
        $threshold = 0;
    }

    $visit_count = visibloc_jlg_get_visit_count();

    switch ( $operator ) {
        case 'equals':
            return $visit_count === $threshold;
        case 'not_equals':
            return $visit_count !== $threshold;
        case 'at_most':
            return $visit_count <= $threshold;
        case 'at_least':
        default:
            return $visit_count >= $threshold;
    }
}

function visibloc_jlg_get_visit_count_cookie_name() {
    $default_name = 'visibloc_visit_count';

    $cookie_name = function_exists( 'apply_filters' )
        ? apply_filters( 'visibloc_jlg_visit_count_cookie_name', $default_name )
        : $default_name;

    if ( ! is_string( $cookie_name ) ) {
        return '';
    }

    $sanitized = preg_replace( '/[^A-Za-z0-9_\-]/', '', $cookie_name );

    return is_string( $sanitized ) ? trim( $sanitized ) : '';
}

function visibloc_jlg_get_visit_count_cookie_lifetime() {
    $default_lifetime = defined( 'YEAR_IN_SECONDS' ) ? YEAR_IN_SECONDS : 31536000;

    $lifetime = function_exists( 'apply_filters' )
        ? apply_filters( 'visibloc_jlg_visit_count_cookie_lifetime', $default_lifetime )
        : $default_lifetime;

    if ( ! is_numeric( $lifetime ) ) {
        return $default_lifetime;
    }

    $lifetime = (int) $lifetime;

    return $lifetime > 0 ? $lifetime : $default_lifetime;
}

function visibloc_jlg_should_track_visit_count( array $context = [] ) {
    $defaults = [
        'is_admin'       => function_exists( 'is_admin' ) ? is_admin() : false,
        'doing_ajax'     => function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : false,
        'doing_cron'     => function_exists( 'wp_doing_cron' ) ? wp_doing_cron() : false,
        'is_rest_request' => defined( 'REST_REQUEST' ) && REST_REQUEST,
        'php_sapi'       => function_exists( 'php_sapi_name' ) ? php_sapi_name() : '',
    ];

    $context = array_merge( $defaults, $context );

    $should_track = ! $context['doing_ajax'] && ! $context['doing_cron'] && ! $context['is_rest_request'];

    if ( $context['is_admin'] ) {
        $should_track = false;
    }

    if ( 'cli' === $context['php_sapi'] ) {
        $should_track = false;
    }

    if ( function_exists( 'apply_filters' ) ) {
        $should_track = (bool) apply_filters( 'visibloc_jlg_should_track_visit_count', $should_track, $context );
    }

    return $should_track;
}

function visibloc_jlg_track_visit_count() {
    $context = [
        'is_admin'        => function_exists( 'is_admin' ) ? is_admin() : false,
        'doing_ajax'      => function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : false,
        'doing_cron'      => function_exists( 'wp_doing_cron' ) ? wp_doing_cron() : false,
        'is_rest_request' => defined( 'REST_REQUEST' ) && REST_REQUEST,
        'php_sapi'        => function_exists( 'php_sapi_name' ) ? php_sapi_name() : '',
    ];

    if ( ! visibloc_jlg_should_track_visit_count( $context ) ) {
        return;
    }

    $cookie_name = visibloc_jlg_get_visit_count_cookie_name();

    if ( '' === $cookie_name ) {
        return;
    }

    $current_value = visibloc_jlg_get_visit_count( $cookie_name );
    $next_value    = min( $current_value + 1, PHP_INT_MAX );

    if ( 'cli' === $context['php_sapi'] || headers_sent() ) {
        $_COOKIE[ $cookie_name ] = (string) $next_value;

        return;
    }

    $lifetime = visibloc_jlg_get_visit_count_cookie_lifetime();
    $expires  = time() + $lifetime;
    $path     = defined( 'COOKIEPATH' ) ? COOKIEPATH : '/';
    $domain   = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';
    $secure   = function_exists( 'is_ssl' ) ? is_ssl() : false;

    setcookie( $cookie_name, (string) $next_value, $expires, $path, $domain, $secure, true );

    $_COOKIE[ $cookie_name ] = (string) $next_value;
}

function visibloc_jlg_get_visit_count( $cookie_name = '' ) {
    $name = '' !== $cookie_name ? $cookie_name : visibloc_jlg_get_visit_count_cookie_name();

    if ( '' === $name ) {
        return 0;
    }

    $raw_value = isset( $_COOKIE[ $name ] ) ? $_COOKIE[ $name ] : null;

    if ( is_array( $raw_value ) ) {
        $raw_value = reset( $raw_value );
    }

    if ( null === $raw_value ) {
        return 0;
    }

    if ( function_exists( 'wp_unslash' ) ) {
        $raw_value = wp_unslash( $raw_value );
    }

    if ( ! is_scalar( $raw_value ) ) {
        return 0;
    }

    $count = filter_var( $raw_value, FILTER_VALIDATE_INT );

    if ( false === $count ) {
        return 0;
    }

    $count = (int) $count;

    return $count < 0 ? 0 : $count;
}

if ( function_exists( 'add_action' ) ) {
    add_action( 'init', 'visibloc_jlg_track_visit_count', 1 );
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
