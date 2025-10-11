<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/cache-constants.php';

require_once __DIR__ . '/block-utils.php';
require_once __DIR__ . '/audit-log.php';
require_once __DIR__ . '/fallback.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/plugin-meta.php';
require_once __DIR__ . '/insights.php';

visibloc_jlg_define_default_supported_blocks();

/**
 * Build the onboarding checklist items displayed on the help page.
 *
 * @param array $context Contextual data (supported blocks, roles, breakpoints…).
 * @return array[]
 */
function visibloc_jlg_build_onboarding_checklist_items( array $context ) {
    $supported_blocks   = isset( $context['supported_blocks'] ) ? (array) $context['supported_blocks'] : [];
    $allowed_roles      = isset( $context['preview_roles'] ) ? (array) $context['preview_roles'] : [];
    $fallback_settings  = isset( $context['fallback'] ) ? (array) $context['fallback'] : [];
    $mobile_breakpoint  = isset( $context['mobile_breakpoint'] ) ? absint( $context['mobile_breakpoint'] ) : 0;
    $tablet_breakpoint  = isset( $context['tablet_breakpoint'] ) ? absint( $context['tablet_breakpoint'] ) : 0;
    $default_mobile_bp  = 781;
    $default_tablet_bp  = 1024;
    $has_custom_device  = $mobile_breakpoint > 0 && $tablet_breakpoint > 0
        ? ( $mobile_breakpoint !== $default_mobile_bp ) || ( $tablet_breakpoint !== $default_tablet_bp )
        : false;
    $has_additional_role = count( array_diff( $allowed_roles, [ 'administrator' ] ) ) > 0;
    $has_fallback        = visibloc_jlg_fallback_has_content( $fallback_settings );

    return [
        [
            'key'         => 'supported-blocks',
            'complete'    => ! empty( $supported_blocks ),
            'title'       => __( 'Sélectionnez les blocs à contrôler', 'visi-bloc-jlg' ),
            'description' => __( 'Activez la visibilité avancée uniquement sur les blocs qui doivent être pilotés.', 'visi-bloc-jlg' ),
            'action'      => [
                'label' => __( 'Configurer les blocs compatibles', 'visi-bloc-jlg' ),
                'url'   => '#visibloc-section-blocks',
            ],
        ],
        [
            'key'         => 'preview-roles',
            'complete'    => $has_additional_role,
            'title'       => __( "Autorisez les rôles à prévisualiser", 'visi-bloc-jlg' ),
            'description' => __( "Ajoutez les rôles marketing ou éditoriaux qui doivent tester les parcours personnalisés sans privilèges administrateur.", 'visi-bloc-jlg' ),
            'action'      => [
                'label' => __( "Définir les rôles d’aperçu", 'visi-bloc-jlg' ),
                'url'   => '#visibloc-section-permissions',
            ],
        ],
        [
            'key'         => 'fallback',
            'complete'    => $has_fallback,
            'title'       => __( 'Préparez un contenu de repli', 'visi-bloc-jlg' ),
            'description' => __( 'Sélectionnez un message ou un bloc de remplacement pour les visiteurs qui ne remplissent pas les conditions.', 'visi-bloc-jlg' ),
            'action'      => [
                'label' => __( 'Configurer le fallback global', 'visi-bloc-jlg' ),
                'url'   => '#visibloc-section-fallback',
            ],
        ],
        [
            'key'         => 'breakpoints',
            'complete'    => $has_custom_device,
            'title'       => __( 'Personnalisez les points de rupture', 'visi-bloc-jlg' ),
            'description' => __( 'Adaptez les seuils mobile et tablette à votre grille responsive pour fiabiliser les règles par appareil.', 'visi-bloc-jlg' ),
            'action'      => [
                'label' => __( 'Mettre à jour les breakpoints', 'visi-bloc-jlg' ),
                'url'   => '#visibloc-section-breakpoints',
            ],
        ],
    ];
}

/**
 * Calculate progress details for the onboarding checklist.
 *
 * @param array $items Checklist items.
 * @return array{
 *     total:int,
 *     completed:int,
 *     percent:int
 * }
 */
function visibloc_jlg_calculate_onboarding_progress( array $items ) {
    $total = count( $items );

    if ( 0 === $total ) {
        return [
            'total'     => 0,
            'completed' => 0,
            'percent'   => 0,
        ];
    }

    $completed = 0;

    foreach ( $items as $item ) {
        if ( ! empty( $item['complete'] ) ) {
            $completed++;
        }
    }

    $percent = (int) round( ( $completed / $total ) * 100 );

    return [
        'total'     => $total,
        'completed' => $completed,
        'percent'   => $percent,
    ];
}

/**
 * Normalize a schedule timestamp coming from block attributes.
 *
 * @param mixed $value Raw timestamp or date string.
 * @return int|null
 */
function visibloc_jlg_normalize_schedule_timestamp( $value ) {
    if ( null === $value || '' === $value ) {
        return null;
    }

    if ( is_numeric( $value ) ) {
        $timestamp = (int) $value;

        return $timestamp > 0 ? $timestamp : null;
    }

    $timestamp = strtotime( (string) $value );

    return false !== $timestamp ? $timestamp : null;
}

/**
 * Collect proactive admin notifications from various data sources.
 *
 * @param array $context Optional contextual data to avoid duplicate queries.
 * @return array<int,array<string,mixed>>
 */
function visibloc_jlg_collect_admin_notifications( array $context = [] ) {
    $notifications = [];

    $fallback_settings = isset( $context['fallback_settings'] )
        ? (array) $context['fallback_settings']
        : visibloc_jlg_get_fallback_settings();
    $hidden_posts      = isset( $context['hidden_posts'] )
        ? (array) $context['hidden_posts']
        : visibloc_jlg_get_hidden_posts();
    $scheduled_posts   = isset( $context['scheduled_posts'] )
        ? (array) $context['scheduled_posts']
        : visibloc_jlg_get_scheduled_posts();

    $has_fallback_content = visibloc_jlg_fallback_has_content( $fallback_settings );
    $hidden_block_total   = 0;

    foreach ( $hidden_posts as $post_data ) {
        if ( isset( $post_data['block_count'] ) ) {
            $hidden_block_total += max( 0, (int) $post_data['block_count'] );
        }
    }

    if ( ! $has_fallback_content ) {
        $description = __( 'Aucun fallback global n’est défini. Configurez un message ou un bloc de repli pour éviter les zones vides lorsque du contenu est masqué.', 'visi-bloc-jlg' );

        if ( $hidden_block_total > 0 ) {
            /* translators: %d: Number of hidden blocks. */
            $description = sprintf( __( '%d blocs sont masqués sans fallback global. Configurez un message ou un bloc de repli pour sécuriser le parcours visiteur.', 'visi-bloc-jlg' ), $hidden_block_total );
        }

        $notifications[] = [
            'id'          => 'missing-fallback',
            'level'       => 'critical',
            'title'       => __( 'Fallback global absent', 'visi-bloc-jlg' ),
            'description' => $description,
            'actions'     => [
                [
                    'label'   => __( 'Configurer le fallback', 'visi-bloc-jlg' ),
                    'url'     => admin_url( 'admin.php?page=visi-bloc-jlg-help#visibloc-section-fallback' ),
                    'variant' => 'primary',
                ],
            ],
        ];
    }

    $now = function_exists( 'current_time' ) ? (int) current_time( 'timestamp' ) : time();

    $expired_map  = [];
    $expiring_map = [];
    $expiring_threshold = DAY_IN_SECONDS * 2;

    foreach ( $scheduled_posts as $entry ) {
        $post_id = isset( $entry['id'] ) ? (int) $entry['id'] : 0;

        if ( $post_id <= 0 ) {
            continue;
        }

        $end_timestamp = visibloc_jlg_normalize_schedule_timestamp( $entry['end'] ?? null );
        $start_timestamp = visibloc_jlg_normalize_schedule_timestamp( $entry['start'] ?? null );

        if ( null === $end_timestamp && null === $start_timestamp ) {
            continue;
        }

        $title = isset( $entry['title'] ) && is_string( $entry['title'] )
            ? $entry['title']
            : '';
        $link = isset( $entry['link'] ) && is_string( $entry['link'] )
            ? $entry['link']
            : '';

        if ( null !== $end_timestamp && $end_timestamp < $now ) {
            if ( ! isset( $expired_map[ $post_id ] ) ) {
                $expired_map[ $post_id ] = [
                    'id'          => $post_id,
                    'title'       => $title,
                    'link'        => $link,
                    'occurrences' => 0,
                    'closest'     => null,
                ];
            }

            $expired_map[ $post_id ]['occurrences']++;

            if ( null === $expired_map[ $post_id ]['closest'] || $end_timestamp < $expired_map[ $post_id ]['closest'] ) {
                $expired_map[ $post_id ]['closest'] = $end_timestamp;
            }

            continue;
        }

        if ( null !== $end_timestamp && ( $end_timestamp - $now ) <= $expiring_threshold ) {
            if ( ! isset( $expiring_map[ $post_id ] ) ) {
                $expiring_map[ $post_id ] = [
                    'id'          => $post_id,
                    'title'       => $title,
                    'link'        => $link,
                    'occurrences' => 0,
                    'closest'     => null,
                ];
            }

            $expiring_map[ $post_id ]['occurrences']++;

            if ( null === $expiring_map[ $post_id ]['closest'] || $end_timestamp < $expiring_map[ $post_id ]['closest'] ) {
                $expiring_map[ $post_id ]['closest'] = $end_timestamp;
            }
        }
    }

    $datetime_format = visibloc_jlg_get_wp_datetime_format();

    if ( ! empty( $expired_map ) ) {
        $total_expired = 0;
        $expired_items = array_values( $expired_map );

        usort(
            $expired_items,
            static function ( $a, $b ) {
                return ( $a['closest'] ?? PHP_INT_MAX ) <=> ( $b['closest'] ?? PHP_INT_MAX );
            }
        );

        $rendered_items = [];

        foreach ( array_slice( $expired_items, 0, 5 ) as $item ) {
            $total_expired += isset( $item['occurrences'] ) ? (int) $item['occurrences'] : 0;
            $label = $item['title'];

            if ( $item['occurrences'] > 1 ) {
                /* translators: %s: Post title. %d: Number of rules. */
                $label = sprintf( __( '%1$s – %2$d programmations', 'visi-bloc-jlg' ), $label, (int) $item['occurrences'] );
            }

            if ( isset( $item['closest'] ) && null !== $item['closest'] ) {
                $label .= ' · ' . date_i18n( $datetime_format, (int) $item['closest'] );
            }

            $rendered_items[] = [
                'label' => $label,
                'url'   => $item['link'],
            ];
        }

        if ( $total_expired <= 0 ) {
            $total_expired = array_sum( wp_list_pluck( $expired_items, 'occurrences' ) );
        }

        $notifications[] = [
            'id'          => 'schedule-expired',
            'level'       => 'critical',
            'title'       => __( 'Programmations expirées', 'visi-bloc-jlg' ),
            'description' => sprintf(
                _n(
                    'Une programmation est arrivée à expiration mais masque toujours un bloc.',
                    '%d programmations sont arrivées à expiration et masquent toujours des blocs.',
                    $total_expired,
                    'visi-bloc-jlg'
                ),
                $total_expired
            ),
            'items'       => $rendered_items,
            'actions'     => [
                [
                    'label' => __( 'Ouvrir le tableau des blocs programmés', 'visi-bloc-jlg' ),
                    'url'   => admin_url( 'admin.php?page=visi-bloc-jlg-help#visibloc-section-scheduled' ),
                ],
            ],
        ];
    }

    if ( ! empty( $expiring_map ) ) {
        $total_expiring = 0;
        $expiring_items = array_values( $expiring_map );

        usort(
            $expiring_items,
            static function ( $a, $b ) {
                return ( $a['closest'] ?? PHP_INT_MAX ) <=> ( $b['closest'] ?? PHP_INT_MAX );
            }
        );

        $rendered_items = [];

        foreach ( array_slice( $expiring_items, 0, 5 ) as $item ) {
            $total_expiring += isset( $item['occurrences'] ) ? (int) $item['occurrences'] : 0;
            $label = $item['title'];

            if ( $item['occurrences'] > 1 ) {
                /* translators: %s: Post title. %d: Number of rules. */
                $label = sprintf( __( '%1$s – %2$d programmations', 'visi-bloc-jlg' ), $label, (int) $item['occurrences'] );
            }

            if ( isset( $item['closest'] ) && null !== $item['closest'] ) {
                $label .= ' · ' . date_i18n( $datetime_format, (int) $item['closest'] );
            }

            $rendered_items[] = [
                'label' => $label,
                'url'   => $item['link'],
            ];
        }

        if ( $total_expiring <= 0 ) {
            $total_expiring = array_sum( wp_list_pluck( $expiring_items, 'occurrences' ) );
        }

        $notifications[] = [
            'id'          => 'schedule-expiring-soon',
            'level'       => 'warning',
            'title'       => __( 'Programmations qui s’achèvent bientôt', 'visi-bloc-jlg' ),
            'description' => sprintf(
                _n(
                    'Une programmation se termine dans les prochaines 48 heures.',
                    '%d programmations se terminent dans les prochaines 48 heures.',
                    $total_expiring,
                    'visi-bloc-jlg'
                ),
                $total_expiring
            ),
            'items'       => $rendered_items,
            'actions'     => [
                [
                    'label' => __( 'Vérifier les fenêtres temporelles', 'visi-bloc-jlg' ),
                    'url'   => admin_url( 'admin.php?page=visi-bloc-jlg-help#visibloc-section-scheduled' ),
                ],
            ],
        ];
    }

    if ( function_exists( 'visibloc_jlg_get_insight_dashboard_model' ) ) {
        $insights = visibloc_jlg_get_insight_dashboard_model();

        $exposures     = isset( $insights['totals']['exposures'] ) ? (int) $insights['totals']['exposures'] : 0;
        $fallback_hits = isset( $insights['counters']['fallback'] ) ? (int) $insights['counters']['fallback'] : 0;
        $fallback_rate = isset( $insights['rates']['fallback'] ) ? (float) $insights['rates']['fallback'] : 0.0;

        if ( $exposures > 0 && $fallback_hits > 0 && $fallback_rate >= 25.0 ) {
            $level = $fallback_rate >= 40.0 ? 'critical' : 'warning';

            $notifications[] = [
                'id'          => 'fallback-usage-alert',
                'level'       => $level,
                'title'       => __( 'Beaucoup de visiteurs voient le fallback', 'visi-bloc-jlg' ),
                'description' => sprintf(
                    /* translators: 1: Fallback rate. 2: Number of fallback impressions. */
                    __( 'Le fallback a été servi dans %1$s des affichages suivis (%2$d occurrences). Vérifiez les règles pour éviter une perte de conversion.', 'visi-bloc-jlg' ),
                    visibloc_jlg_format_insight_percentage( $fallback_rate ),
                    $fallback_hits
                ),
                'actions'     => [
                    [
                        'label' => __( 'Analyser les règles actives', 'visi-bloc-jlg' ),
                        'url'   => admin_url( 'admin.php?page=visi-bloc-jlg-help#visibloc-section-insights' ),
                    ],
                ],
            ];
        }
    }

    return $notifications;
}

function visibloc_jlg_get_onboarding_mode() {
    $stored = get_option( 'visibloc_onboarding_mode', 'simple' );

    if ( ! is_string( $stored ) ) {
        return 'simple';
    }

    return 'expert' === strtolower( $stored ) ? 'expert' : 'simple';
}

function visibloc_jlg_update_onboarding_mode( $mode ) {
    $normalized = 'expert' === strtolower( (string) $mode ) ? 'expert' : 'simple';

    update_option( 'visibloc_onboarding_mode', $normalized );
}

function visibloc_jlg_sanitize_onboarding_text( $value ) {
    $value = (string) $value;

    if ( function_exists( 'sanitize_textarea_field' ) ) {
        return sanitize_textarea_field( $value );
    }

    $value = strip_tags( $value );

    return trim( preg_replace( '/\s+/', ' ', $value ) );
}

function visibloc_jlg_sanitize_onboarding_recipe_value( $value ) {
    if ( is_array( $value ) ) {
        $sanitized = [];

        foreach ( $value as $key => $child ) {
            if ( is_int( $key ) ) {
                $sanitized[] = visibloc_jlg_sanitize_onboarding_recipe_value( $child );

                continue;
            }

            $normalized_key = is_string( $key ) ? preg_replace( '/[^a-zA-Z0-9_-]/', '_', $key ) : '';

            if ( '' === $normalized_key ) {
                continue;
            }

            $sanitized[ $normalized_key ] = visibloc_jlg_sanitize_onboarding_recipe_value( $child );
        }

        return $sanitized;
    }

    if ( is_scalar( $value ) ) {
        return visibloc_jlg_sanitize_onboarding_text( $value );
    }

    return '';
}

function visibloc_jlg_normalize_onboarding_recipe_section( $section ) {
    if ( ! is_array( $section ) ) {
        return [];
    }

    $normalized = [];

    foreach ( $section as $key => $value ) {
        $normalized_key = is_string( $key ) ? preg_replace( '/[^a-zA-Z0-9_-]/', '_', $key ) : '';

        if ( '' === $normalized_key ) {
            continue;
        }

        $normalized[ $normalized_key ] = visibloc_jlg_sanitize_onboarding_recipe_value( $value );
    }

    return $normalized;
}

function visibloc_jlg_read_onboarding_recipe_file( $file_path ) {
    if ( ! is_string( $file_path ) || '' === $file_path || ! is_readable( $file_path ) ) {
        return null;
    }

    $contents = file_get_contents( $file_path );

    if ( false === $contents ) {
        return null;
    }

    $decoded = json_decode( $contents, true );

    if ( null === $decoded || ! is_array( $decoded ) ) {
        return null;
    }

    $recipe_id = '';

    if ( isset( $decoded['id'] ) && is_string( $decoded['id'] ) && '' !== $decoded['id'] ) {
        $recipe_id = sanitize_key( $decoded['id'] );
    }

    if ( '' === $recipe_id ) {
        $recipe_id = sanitize_key( basename( $file_path, '.json' ) );
    }

    if ( '' === $recipe_id ) {
        return null;
    }

    $recipe = [
        'id'        => $recipe_id,
        'title'     => isset( $decoded['title'] ) && is_string( $decoded['title'] )
            ? sanitize_text_field( $decoded['title'] )
            : $recipe_id,
        'summary'   => isset( $decoded['summary'] ) && is_string( $decoded['summary'] )
            ? sanitize_textarea_field( $decoded['summary'] )
            : '',
        'objective' => visibloc_jlg_normalize_onboarding_recipe_section( $decoded['objective'] ?? [] ),
        'audience'  => visibloc_jlg_normalize_onboarding_recipe_section( $decoded['audience'] ?? [] ),
        'timing'    => visibloc_jlg_normalize_onboarding_recipe_section( $decoded['timing'] ?? [] ),
        'content'   => visibloc_jlg_normalize_onboarding_recipe_section( $decoded['content'] ?? [] ),
    ];

    return $recipe;
}

function visibloc_jlg_get_onboarding_recipes_library() {
    $base_dir = visibloc_jlg_get_plugin_dir_path();
    $recipes_dir = trailingslashit( $base_dir ) . 'resources/recipes';

    if ( ! is_dir( $recipes_dir ) ) {
        return [];
    }

    $files = glob( trailingslashit( $recipes_dir ) . '*.json' );

    if ( ! is_array( $files ) ) {
        $files = [];
    }

    $recipes = [];

    foreach ( $files as $file_path ) {
        $recipe = visibloc_jlg_read_onboarding_recipe_file( $file_path );

        if ( null === $recipe ) {
            continue;
        }

        $recipes[] = $recipe;
    }

    /**
     * Filter the onboarding recipes exposed in the admin and editor.
     *
     * @param array $recipes Recipes definitions.
     */
    return apply_filters( 'visibloc_jlg_onboarding_recipes', $recipes );
}

/**
 * Provide a curated block template for a guided recipe.
 *
 * @param string $slug Recipe identifier.
 * @return string
 */
function visibloc_jlg_get_recipe_template_markup( $slug ) {
    switch ( $slug ) {
        case 'welcome-series':
            return sprintf(
                '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"48px","bottom":"48px","left":"24px","right":"24px"},"blockGap":"24px"},"color":{"background":"#111827","text":"#f8fafc"},"border":{"radius":"18px"}}} -->
<div class="wp-block-group alignfull has-text-color has-background" style="color:#f8fafc;background-color:#111827;border-radius:18px;padding-top:48px;padding-right:24px;padding-bottom:48px;padding-left:24px"><div class="wp-block-group__inner-container"><!-- wp:heading {"textAlign":"center","level":2,"style":{"typography":{"fontSize":"38px"}}} -->
<h2 class="has-text-align-center" style="font-size:38px">%1$s</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">%2$s</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center","flexWrap":"wrap"},"style":{"spacing":{"blockGap":"16px"}}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"primary","textColor":"background","style":{"border":{"radius":"999px"},"typography":{"fontSize":"18px","fontWeight":"600"}}} -->
<div class="wp-block-button has-custom-font-size" style="font-size:18px;font-weight:600"><a class="wp-block-button__link has-background-color has-primary-background-color has-text-color" href="#">%3$s</a></div>
<!-- /wp:button -->

<!-- wp:button {"backgroundColor":"background","textColor":"primary","style":{"border":{"radius":"999px","width":"2px"},"color":{"text":"#e5e7eb"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-background-background-color has-text-color has-background" href="#" style="border-radius:999px;border-width:2px;color:#e5e7eb">%4$s</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:list {"style":{"typography":{"fontSize":"18px"}},"className":"visibloc-welcome-checklist"} -->
<ul class="visibloc-welcome-checklist" style="font-size:18px"><li>%5$s</li><li>%6$s</li><li>%7$s</li></ul>
<!-- /wp:list --></div></div>
<!-- /wp:group -->',
                esc_html__( 'Bienvenue dans votre espace personnalisé 🎉', 'visi-bloc-jlg' ),
                esc_html__( 'Nous avons préparé un parcours de démarrage pour vous guider vers la prochaine étape utile.', 'visi-bloc-jlg' ),
                esc_html__( 'Compléter mon profil', 'visi-bloc-jlg' ),
                esc_html__( 'Découvrir la bibliothèque de ressources', 'visi-bloc-jlg' ),
                esc_html__( 'Suivez le check-list pour activer votre compte en moins de 5 minutes.', 'visi-bloc-jlg' ),
                esc_html__( 'Ajoutez vos centres d’intérêt pour recevoir des recommandations ciblées.', 'visi-bloc-jlg' ),
                esc_html__( 'Planifiez un rappel pour revenir à ce guide quand vous le souhaitez.', 'visi-bloc-jlg' )
            );
        case 'woocommerce-cart-recovery':
            return sprintf(
                '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"48px","bottom":"48px"},"margin":{"top":"0","bottom":"0"},"blockGap":"28px"},"color":{"background":"#111827"}}} -->
<div class="wp-block-group alignfull has-background" style="background-color:#111827;padding-top:48px;padding-bottom:48px"><div class="wp-block-group__inner-container"><!-- wp:group {"style":{"spacing":{"blockGap":"18px"},"border":{"radius":"18px"},"color":{"background":"#0f172a"},"padding":{"top":"32px","right":"32px","bottom":"32px","left":"32px"}},"layout":{"contentSize":"760px"}} -->
<div class="wp-block-group" style="border-radius:18px;background-color:#0f172a;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:paragraph {"align":"center","style":{"typography":{"letterSpacing":"0.4em","textTransform":"uppercase","fontSize":"13px"}},"textColor":"cyan-bluish-gray"} -->
<p class="has-text-align-center has-cyan-bluish-gray-color has-text-color" style="font-size:13px;text-transform:uppercase;letter-spacing:0.4em">%1$s</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","textColor":"background","fontSize":"huge"} -->
<h2 class="has-text-align-center has-background-color has-text-color has-huge-font-size">%2$s</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"background","fontSize":"large"} -->
<p class="has-text-align-center has-background-color has-text-color has-large-font-size">%3$s</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center","flexWrap":"wrap"},"style":{"spacing":{"blockGap":"16px"}}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"luminous-vivid-orange","textColor":"background","style":{"border":{"radius":"999px"},"typography":{"fontWeight":"700"}}} -->
<div class="wp-block-button" style="font-weight:700"><a class="wp-block-button__link has-background-color has-luminous-vivid-orange-background-color has-text-color" href="#">%4$s</a></div>
<!-- /wp:button -->

<!-- wp:button {"backgroundColor":"background","textColor":"luminous-vivid-orange","style":{"border":{"radius":"999px","width":"2px"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-luminous-vivid-orange-color has-background-background-color has-text-color has-background" href="#" style="border-radius:999px;border-width:2px">%5$s</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:paragraph {"align":"center","textColor":"background","fontSize":"medium"} -->
<p class="has-text-align-center has-background-color has-text-color has-medium-font-size">%6$s</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div></div>
<!-- /wp:group -->',
                esc_html__( 'Panier sauvegardé', 'visi-bloc-jlg' ),
                esc_html__( 'Il reste encore votre sélection en boutique ✨', 'visi-bloc-jlg' ),
                esc_html__( 'Finalisez votre commande avant ce soir pour profiter de la livraison offerte.', 'visi-bloc-jlg' ),
                esc_html__( 'Reprendre ma commande', 'visi-bloc-jlg' ),
                esc_html__( 'Contacter le support', 'visi-bloc-jlg' ),
                esc_html__( 'Votre panier est automatiquement sauvegardé pendant 72 heures.', 'visi-bloc-jlg' )
            );
        case 'b2b-lead-nurturing':
            return sprintf(
                '<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"32px","left":"32px"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"60%","style":{"spacing":{"blockGap":"20px"},"border":{"radius":"16px"},"color":{"background":"#f1f5f9"},"padding":{"top":"32px","right":"32px","bottom":"32px","left":"32px"}}} -->
<div class="wp-block-column" style="flex-basis:60%;background-color:#f1f5f9;border-radius:16px;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:heading {"fontSize":"x-large"} -->
<h2 class="has-x-large-font-size">%1$s</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"medium"} -->
<p class="has-medium-font-size">%2$s</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul><li>%3$s</li><li>%4$s</li><li>%5$s</li></ul>
<!-- /wp:list -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"flex-start","flexWrap":"wrap"},"style":{"spacing":{"blockGap":"16px"}}} -->
<div class="wp-block-buttons"><!-- wp:button {"style":{"border":{"radius":"999px"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link" href="#">%6$s</a></div>
<!-- /wp:button -->

<!-- wp:button {"backgroundColor":"foreground","textColor":"background","style":{"border":{"radius":"999px"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-background-color has-foreground-background-color has-text-color" href="#">%7$s</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"40%","style":{"spacing":{"blockGap":"20px"}}} -->
<div class="wp-block-column" style="flex-basis:40%"><!-- wp:group {"style":{"spacing":{"blockGap":"12px"},"border":{"radius":"16px"},"color":{"background":"#0f172a","text":"#f8fafc"},"padding":{"top":"24px","right":"24px","bottom":"24px","left":"24px"}}} -->
<div class="wp-block-group has-text-color has-background" style="color:#f8fafc;background-color:#0f172a;border-radius:16px;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><!-- wp:heading {"level":3} -->
<h3>%8$s</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>%9$s</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>%10$s</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:quote {"style":{"border":{"radius":"12px"},"color":{"background":"#ffffff"},"spacing":{"padding":{"top":"24px","right":"24px","bottom":"24px","left":"24px"}}}} -->
<blockquote class="wp-block-quote" style="border-radius:12px;background-color:#ffffff;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px"><p>%11$s</p><cite>%12$s</cite></blockquote>
<!-- /wp:quote --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->',
                esc_html__( 'Votre prochain palier d’expertise est à portée de clic', 'visi-bloc-jlg' ),
                esc_html__( 'Téléchargez notre guide pour structurer votre démarche d’automatisation marketing.', 'visi-bloc-jlg' ),
                esc_html__( 'Évaluez votre maturité en 10 questions.', 'visi-bloc-jlg' ),
                esc_html__( 'Recevez la matrice de priorisation prête à l’emploi.', 'visi-bloc-jlg' ),
                esc_html__( 'Découvrez le plan d’action sur 30 jours proposé par nos experts.', 'visi-bloc-jlg' ),
                esc_html__( 'Télécharger le guide', 'visi-bloc-jlg' ),
                esc_html__( 'Planifier une démonstration', 'visi-bloc-jlg' ),
                esc_html__( 'Ressource incluse', 'visi-bloc-jlg' ),
                esc_html__( 'Un framework détaillé pour prioriser vos campagnes et orchestrer vos leads.', 'visi-bloc-jlg' ),
                esc_html__( 'Accès instantané, sans carte bancaire.', 'visi-bloc-jlg' ),
                esc_html__( '« Grâce à ce plan, nous avons doublé nos prises de rendez-vous en un trimestre. »', 'visi-bloc-jlg' ),
                esc_html__( 'Claire, Responsable Marketing B2B', 'visi-bloc-jlg' )
            );
    }

    return '';
}

/**
 * Return the curated guided recipes displayed in the onboarding wizard.
 *
 * @return array[]
 */
function visibloc_jlg_get_guided_recipes() {
    return [
        [
            'id'             => 'welcome-series',
            'title'          => __( 'Série de bienvenue personnalisée', 'visi-bloc-jlg' ),
            'description'    => __( 'Affichez un message de bienvenue dynamique aux nouveaux inscrits pour accélérer leur activation.', 'visi-bloc-jlg' ),
            'theme'          => 'onboarding',
            'theme_label'    => __( 'Onboarding', 'visi-bloc-jlg' ),
            'estimated_time' => __( '5 minutes', 'visi-bloc-jlg' ),
            'audience'       => __( 'Visiteurs authentifiés avec un cookie d’inscription récent ou un rôle « Nouvel abonné ».', 'visi-bloc-jlg' ),
            'goal'           => __( 'Accueillir chaleureusement chaque nouvel abonné et l’orienter vers l’action clé.', 'visi-bloc-jlg' ),
            'kpi'            => __( 'Taux de clic sur le call-to-action de bienvenue.', 'visi-bloc-jlg' ),
            'block_template' => visibloc_jlg_get_recipe_template_markup( 'welcome-series' ),
            'blocks'         => [
                __( 'Bloc Bannière / Couverture', 'visi-bloc-jlg' ),
                __( 'Bloc Bouton', 'visi-bloc-jlg' ),
                __( 'Bloc Liste de contrôle', 'visi-bloc-jlg' ),
            ],
            'steps'          => [
                [
                    'title'    => __( 'Objectif', 'visi-bloc-jlg' ),
                    'summary'  => __( 'Clarifiez ce que doit accomplir cette séquence de bienvenue pour vos nouveaux inscrits.', 'visi-bloc-jlg' ),
                    'actions'  => [
                        __( 'Identifiez l’action principale attendue (compléter un profil, télécharger une ressource, rejoindre une communauté).', 'visi-bloc-jlg' ),
                        __( 'Ajoutez une note interne dans le bloc pour rappeler l’objectif à l’équipe éditoriale.', 'visi-bloc-jlg' ),
                        __( 'Définissez le KPI associé dans votre outil d’analytics (événement de clic ou conversion).', 'visi-bloc-jlg' ),
                    ],
                    'notes'    => [
                        __( 'Assurez-vous que la formulation respecte les règles de lisibilité (WCAG 2.2 – critère 3.1.5).', 'visi-bloc-jlg' ),
                    ],
                ],
                [
                    'title'    => __( 'Audience', 'visi-bloc-jlg' ),
                    'summary'  => __( 'Ciblez uniquement les visiteurs fraîchement inscrits ou ceux disposant d’un rôle dédié.', 'visi-bloc-jlg' ),
                    'actions'  => [
                        __( 'Activez la condition « Statut de connexion » et sélectionnez les rôles marketing pertinents.', 'visi-bloc-jlg' ),
                        __( 'Ajoutez un déclencheur « Segment marketing » si votre CRM expose un segment « Nouveau client ».', 'visi-bloc-jlg' ),
                        __( 'Enregistrez un cookie `visibloc_welcome_shown` pour limiter l’affichage à la première visite.', 'visi-bloc-jlg' ),
                    ],
                    'notes'    => [
                        __( 'Testez le parcours avec le commutateur de rôle pour vérifier les annonces de focus et la navigation clavier.', 'visi-bloc-jlg' ),
                    ],
                ],
                [
                    'title'    => __( 'Timing', 'visi-bloc-jlg' ),
                    'summary'  => __( 'Planifiez la durée d’affichage afin d’éviter la surexposition du message.', 'visi-bloc-jlg' ),
                    'actions'  => [
                        __( 'Activez la planification et définissez une date de fin 7 jours après l’inscription.', 'visi-bloc-jlg' ),
                        __( 'Combinez avec une règle récurrente (9h-21h) pour ne pas gêner les visiteurs nocturnes.', 'visi-bloc-jlg' ),
                        __( 'Documentez la durée dans la description du bloc pour les relecteurs.', 'visi-bloc-jlg' ),
                    ],
                    'notes'    => [
                        __( 'Vérifiez l’accessibilité du composant lors de l’activation/désactivation (critère 2.2.1).', 'visi-bloc-jlg' ),
                    ],
                ],
                [
                    'title'    => __( 'Contenu & fallback', 'visi-bloc-jlg' ),
                    'summary'  => __( 'Préparez une alternative accessible pour les visiteurs qui ne remplissent plus les conditions.', 'visi-bloc-jlg' ),
                    'actions'  => [
                        __( 'Rédigez un message court expliquant pourquoi le contenu n’est plus affiché.', 'visi-bloc-jlg' ),
                        __( 'Sélectionnez un bloc réutilisable de repli dans les réglages globaux.', 'visi-bloc-jlg' ),
                        __( 'Ajoutez une classe `vb-desktop-only` si le message doit être limité aux écrans larges.', 'visi-bloc-jlg' ),
                    ],
                    'notes'    => [
                        __( 'Contrôlez le contraste des boutons (> 4.5:1) et la taille minimale des cibles tactiles (critère 2.5.8).', 'visi-bloc-jlg' ),
                    ],
                    'resources' => [
                        [
                            'label' => __( 'Checklist accessibilité WordPress', 'visi-bloc-jlg' ),
                            'url'   => 'https://make.wordpress.org/accessibility/handbook/best-practices/',
                        ],
                    ],
                ],
            ],
        ],
        [
            'id'             => 'woocommerce-cart-recovery',
            'title'          => __( 'Relance panier WooCommerce', 'visi-bloc-jlg' ),
            'description'    => __( 'Affichez une bannière personnalisée aux clients ayant un panier abandonné pour finaliser leur commande.', 'visi-bloc-jlg' ),
            'theme'          => 'conversion',
            'theme_label'    => __( 'Conversion', 'visi-bloc-jlg' ),
            'estimated_time' => __( '8 minutes', 'visi-bloc-jlg' ),
            'audience'       => __( 'Clients connectés avec des articles dans le panier WooCommerce et sans commande validée.', 'visi-bloc-jlg' ),
            'goal'           => __( 'Relancer les paniers abandonnés avec une incitation contextualisée.', 'visi-bloc-jlg' ),
            'kpi'            => __( 'Taux de récupération des paniers sur 7 jours.', 'visi-bloc-jlg' ),
            'block_template' => visibloc_jlg_get_recipe_template_markup( 'woocommerce-cart-recovery' ),
            'blocks'         => [
                __( 'Bloc Bannière / Notice', 'visi-bloc-jlg' ),
                __( 'Bloc Boutons', 'visi-bloc-jlg' ),
                __( 'Bloc Liste de produits', 'visi-bloc-jlg' ),
            ],
            'steps'          => [
                [
                    'title'   => __( 'Objectif', 'visi-bloc-jlg' ),
                    'summary' => __( 'Cadrez la valeur ajoutée de votre relance (code promo, livraison offerte, assistance).', 'visi-bloc-jlg' ),
                    'actions' => [
                        __( 'Choisissez le bénéfice le plus pertinent au regard de vos marges.', 'visi-bloc-jlg' ),
                        __( 'Définissez le message principal et un CTA clair (« Finaliser ma commande »).', 'visi-bloc-jlg' ),
                        __( 'Synchronisez l’objectif avec vos campagnes email/SMS pour éviter les doublons.', 'visi-bloc-jlg' ),
                    ],
                ],
                [
                    'title'   => __( 'Audience', 'visi-bloc-jlg' ),
                    'summary' => __( 'Filtrez les visiteurs ayant un panier actif mais aucune commande récente.', 'visi-bloc-jlg' ),
                    'actions' => [
                        __( 'Ajoutez la condition WooCommerce « Panier non vide ».', 'visi-bloc-jlg' ),
                        __( 'Excluez les segments VIP si une campagne dédiée existe déjà.', 'visi-bloc-jlg' ),
                        __( 'Limitez l’affichage aux rôles clients pour éviter d’exposer l’offre en navigation anonyme.', 'visi-bloc-jlg' ),
                    ],
                    'notes' => [
                        __( 'Vérifiez que la navigation clavier permet d’ajouter le produit au panier sans piège (critère 2.1.2).', 'visi-bloc-jlg' ),
                    ],
                ],
                [
                    'title'   => __( 'Timing', 'visi-bloc-jlg' ),
                    'summary' => __( 'Définissez quand déclencher la relance et combien de temps la conserver.', 'visi-bloc-jlg' ),
                    'actions' => [
                        __( 'Utilisez un cookie de suivi (`visibloc_cart_seen`) pour ne pas ré-afficher le message plus de 3 fois par jour.', 'visi-bloc-jlg' ),
                        __( 'Planifiez l’affichage pendant 72 heures maximum après l’abandon du panier.', 'visi-bloc-jlg' ),
                        __( 'Combinez avec un créneau horaire (8h-22h) pour correspondre aux disponibilités de votre support.', 'visi-bloc-jlg' ),
                    ],
                ],
                [
                    'title'   => __( 'Contenu & fallback', 'visi-bloc-jlg' ),
                    'summary' => __( 'Proposez une alternative utile si le panier a déjà été validé ou expiré.', 'visi-bloc-jlg' ),
                    'actions' => [
                        __( 'Préparez un fallback avec des liens vers les catégories populaires.', 'visi-bloc-jlg' ),
                        __( 'Ajoutez un bouton secondaire vers le support client ou le chat en direct.', 'visi-bloc-jlg' ),
                        __( 'Assurez-vous que les codes promo sont annoncés avec un texte accessible, sans s’appuyer uniquement sur la couleur.', 'visi-bloc-jlg' ),
                    ],
                    'resources' => [
                        [
                            'label' => __( 'Bonnes pratiques WooCommerce', 'visi-bloc-jlg' ),
                            'url'   => 'https://woocommerce.com/posts/abandoned-cart-best-practices/',
                        ],
                    ],
                ],
            ],
        ],
        [
            'id'             => 'b2b-lead-nurturing',
            'title'          => __( 'Parcours lead nurturing B2B', 'visi-bloc-jlg' ),
            'description'    => __( 'Présentez une ressource premium lorsque le visiteur atteint un score d’engagement défini.', 'visi-bloc-jlg' ),
            'theme'          => 'engagement',
            'theme_label'    => __( 'Engagement', 'visi-bloc-jlg' ),
            'estimated_time' => __( '10 minutes', 'visi-bloc-jlg' ),
            'audience'       => __( 'Contacts identifiés par votre CRM (segment « MQL ») naviguant sur des pages produits clés.', 'visi-bloc-jlg' ),
            'goal'           => __( 'Convertir les visiteurs engagés en prospects qualifiés grâce à un contenu approfondi.', 'visi-bloc-jlg' ),
            'kpi'            => __( 'Taux de téléchargement du livre blanc ou d’inscription au webinar.', 'visi-bloc-jlg' ),
            'block_template' => visibloc_jlg_get_recipe_template_markup( 'b2b-lead-nurturing' ),
            'blocks'         => [
                __( 'Bloc Colonnes avec visuels', 'visi-bloc-jlg' ),
                __( 'Bloc Formulaire (intégration Gravity Forms / WPForms)', 'visi-bloc-jlg' ),
                __( 'Bloc Témoignage', 'visi-bloc-jlg' ),
            ],
            'steps'          => [
                [
                    'title'   => __( 'Objectif', 'visi-bloc-jlg' ),
                    'summary' => __( 'Définissez le rôle du contenu premium dans votre funnel.', 'visi-bloc-jlg' ),
                    'actions' => [
                        __( 'Choisissez le contenu téléchargeable le plus pertinent (livre blanc, étude de cas).', 'visi-bloc-jlg' ),
                        __( 'Formulez une promesse claire dans l’accroche et les métadonnées du bloc.', 'visi-bloc-jlg' ),
                        __( 'Préparez un UTM spécifique pour mesurer l’origine des conversions.', 'visi-bloc-jlg' ),
                    ],
                ],
                [
                    'title'   => __( 'Audience', 'visi-bloc-jlg' ),
                    'summary' => __( 'Ciblez les visiteurs identifiés comme prospects chauds par votre CRM.', 'visi-bloc-jlg' ),
                    'actions' => [
                        __( 'Exploitez le segment marketing `crm_mql` fourni par le filtre `visibloc_jlg_user_segments`.', 'visi-bloc-jlg' ),
                        __( 'Ajoutez une règle basée sur la taxonomie (catégorie « Solutions » ou « Tarifs »).', 'visi-bloc-jlg' ),
                        __( 'Excluez les rôles internes pour éviter les biais statistiques.', 'visi-bloc-jlg' ),
                    ],
                    'notes' => [
                        __( 'Vérifiez que le focus revient sur le formulaire après validation (critère 3.2.2).', 'visi-bloc-jlg' ),
                    ],
                ],
                [
                    'title'   => __( 'Timing', 'visi-bloc-jlg' ),
                    'summary' => __( 'Coordonnez l’affichage avec vos autres campagnes nurture.', 'visi-bloc-jlg' ),
                    'actions' => [
                        __( 'Définissez une fenêtre d’affichage alignée sur la campagne email (par exemple 14 jours).', 'visi-bloc-jlg' ),
                        __( 'Ajoutez une règle de fréquence via un cookie (`visibloc_nurture_limit`) pour limiter à 1 affichage par visite.', 'visi-bloc-jlg' ),
                        __( 'Préparez un rappel interne dans l’onglet « Notes » pour synchroniser les commerciaux.', 'visi-bloc-jlg' ),
                    ],
                ],
                [
                    'title'   => __( 'Contenu & fallback', 'visi-bloc-jlg' ),
                    'summary' => __( 'Offrez un contenu alternatif ou un point de contact humain.', 'visi-bloc-jlg' ),
                    'actions' => [
                        __( 'Préparez un message secondaire orienté vers la prise de rendez-vous.', 'visi-bloc-jlg' ),
                        __( 'Ajoutez un témoignage accessible (texte et audio avec transcription).', 'visi-bloc-jlg' ),
                        __( 'Contrôlez la compatibilité du formulaire avec les lecteurs d’écran (libellés explicites, message d’erreur clair).', 'visi-bloc-jlg' ),
                    ],
                    'notes'    => [
                        __( 'Documentez l’impact dans votre tableau de bord analytics dès la première semaine.', 'visi-bloc-jlg' ),
                    ],
                    'resources' => [
                        [
                            'label' => __( 'Guide WCAG 2.2 (W3C)', 'visi-bloc-jlg' ),
                            'url'   => 'https://www.w3.org/TR/WCAG22/',
                        ],
                    ],
                ],
            ],
        ],
    ];
}

function visibloc_jlg_update_supported_blocks( $block_names ) {
    $normalized_blocks    = visibloc_jlg_normalize_block_names( $block_names );
    $current_blocks_raw   = get_option( 'visibloc_supported_blocks', [] );
    $current_blocks       = visibloc_jlg_normalize_block_names( $current_blocks_raw );
    $current_without_new  = array_diff( $current_blocks, $normalized_blocks );
    $new_without_current  = array_diff( $normalized_blocks, $current_blocks );
    $has_list_changed     = count( $current_blocks ) !== count( $normalized_blocks )
        || ! empty( $current_without_new )
        || ! empty( $new_without_current );

    update_option( 'visibloc_supported_blocks', $normalized_blocks );

    if ( function_exists( 'visibloc_jlg_invalidate_supported_blocks_cache' ) ) {
        visibloc_jlg_invalidate_supported_blocks_cache();
    }

    if ( $has_list_changed ) {
        visibloc_jlg_rebuild_group_block_summary_index();
    }

    return $normalized_blocks;
}

add_action( 'admin_init', 'visibloc_jlg_save_settings' );
function visibloc_jlg_save_settings() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
    if ( 'POST' !== $request_method ) return;

    if ( ! isset( $_POST['visibloc_nonce'] ) ) return;

    $nonce = isset( $_POST['visibloc_nonce'] ) ? wp_unslash( $_POST['visibloc_nonce'] ) : '';

    if ( ! is_string( $nonce ) || '' === $nonce ) return;

    $handlers = visibloc_jlg_get_settings_request_handlers();

    foreach ( $handlers as $action => $handler ) {
        if ( ! is_callable( $handler ) ) {
            continue;
        }

        if ( ! wp_verify_nonce( $nonce, $action ) ) {
            continue;
        }

        $data   = visibloc_jlg_prepare_settings_request_data( $action );
        $result = call_user_func( $handler, $data );

        if ( function_exists( 'visibloc_jlg_record_audit_event' ) ) {
            $status = '';

            if ( is_array( $result ) && isset( $result['status'] ) && is_string( $result['status'] ) ) {
                $status = function_exists( 'sanitize_key' ) ? sanitize_key( $result['status'] ) : $result['status'];
            }

            $redirect_to = '';

            if ( is_array( $result ) && isset( $result['redirect_to'] ) && is_string( $result['redirect_to'] ) ) {
                $redirect_to = function_exists( 'esc_url_raw' ) ? esc_url_raw( $result['redirect_to'] ) : $result['redirect_to'];
            }

            $message = sprintf(
                function_exists( '__' ) ? __( 'Paramètres mis à jour via l’action %s.', 'visi-bloc-jlg' ) : 'Settings updated via action %s.',
                $action
            );

            $context = [
                'action'      => function_exists( 'sanitize_key' ) ? sanitize_key( $action ) : $action,
                'status'      => $status,
            ];

            if ( '' !== $redirect_to ) {
                $context['redirect_to'] = $redirect_to;
            }

            $user_id = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;

            visibloc_jlg_record_audit_event(
                'settings_updated',
                [
                    'message' => $message,
                    'context' => $context,
                    'user_id' => $user_id,
                ]
            );
        }

        visibloc_jlg_finalize_settings_request( $result );
        return;
    }
}

function visibloc_jlg_handle_options_save() {
    visibloc_jlg_save_settings();
}

function visibloc_jlg_get_settings_request_handlers() {
    return [
        'visibloc_save_supported_blocks' => 'visibloc_jlg_handle_supported_blocks_request',
        'visibloc_export_settings'       => 'visibloc_jlg_handle_export_settings_request',
        'visibloc_import_settings'       => 'visibloc_jlg_handle_import_settings_request',
        'visibloc_toggle_debug'          => 'visibloc_jlg_handle_toggle_debug_request',
        'visibloc_save_breakpoints'      => 'visibloc_jlg_handle_breakpoints_request',
        'visibloc_save_fallback'         => 'visibloc_jlg_handle_fallback_request',
        'visibloc_save_permissions'      => 'visibloc_jlg_handle_permissions_request',
        'visibloc_save_onboarding_mode'  => 'visibloc_jlg_handle_onboarding_mode_request',
    ];
}

function visibloc_jlg_prepare_settings_request_data( $action ) {
    switch ( $action ) {
        case 'visibloc_save_supported_blocks':
            $submitted_supported_blocks = [];
            if ( isset( $_POST['visibloc_supported_blocks'] ) ) {
                $submitted_supported_blocks = (array) wp_unslash( $_POST['visibloc_supported_blocks'] );
            }

            return [
                'supported_blocks' => visibloc_jlg_normalize_block_names( $submitted_supported_blocks ),
            ];

        case 'visibloc_import_settings':
            return [
                'payload' => isset( $_POST['visibloc_settings_payload'] )
                    ? wp_unslash( $_POST['visibloc_settings_payload'] )
                    : '',
            ];

        case 'visibloc_save_breakpoints':
            $mobile_invalid = false;
            $tablet_invalid = false;

            return [
                'mobile_breakpoint' => visibloc_jlg_normalize_breakpoint_from_request( 'visibloc_breakpoint_mobile', $mobile_invalid ),
                'tablet_breakpoint' => visibloc_jlg_normalize_breakpoint_from_request( 'visibloc_breakpoint_tablet', $tablet_invalid ),
                'mobile_invalid'    => $mobile_invalid,
                'tablet_invalid'    => $tablet_invalid,
            ];

        case 'visibloc_save_fallback':
            $raw_settings = [
                'mode'     => isset( $_POST['visibloc_fallback_mode'] )
                    ? wp_unslash( $_POST['visibloc_fallback_mode'] )
                    : 'none',
                'text'     => isset( $_POST['visibloc_fallback_text'] )
                    ? wp_unslash( $_POST['visibloc_fallback_text'] )
                    : '',
                'block_id' => isset( $_POST['visibloc_fallback_block_id'] )
                    ? wp_unslash( $_POST['visibloc_fallback_block_id'] )
                    : 0,
            ];

            return [
                'settings' => visibloc_jlg_normalize_fallback_settings( $raw_settings ),
            ];

        case 'visibloc_save_permissions':
            $submitted_roles = [];
            if ( isset( $_POST['visibloc_preview_roles'] ) ) {
                $submitted_roles = array_map( 'sanitize_key', (array) wp_unslash( $_POST['visibloc_preview_roles'] ) );
            }

            return [
                'roles' => array_values( array_unique( $submitted_roles ) ),
            ];

        case 'visibloc_save_onboarding_mode':
            $raw_mode = isset( $_POST['visibloc_onboarding_mode'] )
                ? sanitize_text_field( wp_unslash( $_POST['visibloc_onboarding_mode'] ) )
                : 'simple';

            return [
                'mode' => 'expert' === strtolower( $raw_mode ) ? 'expert' : 'simple',
            ];
    }

    return [];
}

function visibloc_jlg_finalize_settings_request( $result ) {
    if ( ! is_array( $result ) ) {
        return;
    }

    $should_exit = ! ( defined( 'VISIBLOC_JLG_DISABLE_EXIT' ) && VISIBLOC_JLG_DISABLE_EXIT );

    if ( ! empty( $result['redirect_to'] ) && is_string( $result['redirect_to'] ) ) {
        wp_safe_redirect( $result['redirect_to'] );

        if ( $should_exit ) {
            exit;
        }

        return;
    }

    if ( ! empty( $result['should_exit'] ) ) {
        if ( $should_exit ) {
            exit;
        }

        return;
    }
}

function visibloc_jlg_handle_supported_blocks_request( array $data ) {
    $supported_blocks = isset( $data['supported_blocks'] ) ? (array) $data['supported_blocks'] : [];

    visibloc_jlg_update_supported_blocks( $supported_blocks );
    visibloc_jlg_clear_caches();

    return visibloc_jlg_create_settings_redirect_result( 'updated' );
}

function visibloc_jlg_handle_export_settings_request( array $data ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    visibloc_jlg_export_settings_snapshot();

    return [
        'should_exit' => true,
    ];
}

function visibloc_jlg_handle_import_settings_request( array $data ) {
    $payload = isset( $data['payload'] ) ? $data['payload'] : '';

    $import_result = visibloc_jlg_import_settings_snapshot( $payload );
    $status        = is_wp_error( $import_result ) ? 'settings_import_failed' : 'settings_imported';

    $redirect_args = [];

    if ( is_wp_error( $import_result ) ) {
        $error_code = $import_result->get_error_code();

        if ( is_string( $error_code ) && '' !== $error_code ) {
            $redirect_args['error_code'] = rawurlencode( $error_code );
        }
    }

    return visibloc_jlg_create_settings_redirect_result( $status, $redirect_args );
}

function visibloc_jlg_handle_toggle_debug_request( array $data ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    $current_status = get_option( 'visibloc_debug_mode', 'off' );
    update_option( 'visibloc_debug_mode', ( $current_status === 'on' ) ? 'off' : 'on' );
    visibloc_jlg_clear_caches();

    return visibloc_jlg_create_settings_redirect_result( 'updated' );
}

function visibloc_jlg_handle_breakpoints_request( array $data ) {
    $mobile_breakpoint = isset( $data['mobile_breakpoint'] ) ? $data['mobile_breakpoint'] : null;
    $tablet_breakpoint = isset( $data['tablet_breakpoint'] ) ? $data['tablet_breakpoint'] : null;
    $mobile_invalid    = ! empty( $data['mobile_invalid'] );
    $tablet_invalid    = ! empty( $data['tablet_invalid'] );

    if ( $mobile_invalid || $tablet_invalid ) {
        return visibloc_jlg_create_settings_redirect_result( 'invalid_breakpoints' );
    }

    $current_mobile_bp = get_option( 'visibloc_breakpoint_mobile', 781 );
    $current_tablet_bp = get_option( 'visibloc_breakpoint_tablet', 1024 );

    $new_mobile_bp = ( null !== $mobile_breakpoint ) ? $mobile_breakpoint : $current_mobile_bp;
    $new_tablet_bp = ( null !== $tablet_breakpoint ) ? $tablet_breakpoint : $current_tablet_bp;

    if ( $new_mobile_bp < 1 || $new_tablet_bp < 1 || $new_tablet_bp <= $new_mobile_bp ) {
        return visibloc_jlg_create_settings_redirect_result( 'invalid_breakpoints' );
    }

    if ( null !== $mobile_breakpoint && $mobile_breakpoint !== $current_mobile_bp ) {
        update_option( 'visibloc_breakpoint_mobile', $mobile_breakpoint );
    }

    if ( null !== $tablet_breakpoint && $tablet_breakpoint !== $current_tablet_bp ) {
        update_option( 'visibloc_breakpoint_tablet', $tablet_breakpoint );
    }

    visibloc_jlg_clear_caches();

    return visibloc_jlg_create_settings_redirect_result( 'updated' );
}

function visibloc_jlg_handle_fallback_request( array $data ) {
    $settings = isset( $data['settings'] ) && is_array( $data['settings'] ) ? $data['settings'] : [];

    update_option( 'visibloc_fallback_settings', $settings );
    visibloc_jlg_clear_caches();

    return visibloc_jlg_create_settings_redirect_result( 'updated' );
}

function visibloc_jlg_handle_permissions_request( array $data ) {
    if ( ! function_exists( 'get_editable_roles' ) ) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
    }

    $roles           = isset( $data['roles'] ) ? (array) $data['roles'] : [];
    $editable_roles  = array_keys( (array) get_editable_roles() );
    $editable_roles  = array_map( 'sanitize_key', $editable_roles );
    $sanitized_roles = array_values( array_unique( array_intersect( $editable_roles, $roles ) ) );

    if ( ! in_array( 'administrator', $sanitized_roles, true ) ) {
        $sanitized_roles[] = 'administrator';
    }

    update_option( 'visibloc_preview_roles', $sanitized_roles );
    visibloc_jlg_clear_caches();

    return visibloc_jlg_create_settings_redirect_result( 'updated' );
}

function visibloc_jlg_handle_onboarding_mode_request( array $data ) {
    $mode = isset( $data['mode'] ) ? $data['mode'] : 'simple';

    visibloc_jlg_update_onboarding_mode( $mode );

    return visibloc_jlg_create_settings_redirect_result( 'updated' );
}

function visibloc_jlg_create_settings_redirect_result( $status = null, array $query_args = [] ) {
    $base_url = admin_url( 'admin.php?page=visi-bloc-jlg-help' );

    if ( null !== $status && '' !== $status ) {
        $query_args['status'] = $status;
    }

    if ( empty( $query_args ) ) {
        return [
            'redirect_to' => $base_url,
        ];
    }

    return [
        'redirect_to' => add_query_arg( $query_args, $base_url ),
    ];
}

function visibloc_jlg_normalize_breakpoint_from_request( $field_name, &$invalid_flag ) {
    $invalid_flag = false;

    if ( ! isset( $_POST[ $field_name ] ) ) {
        return null;
    }

    $raw_value = trim( wp_unslash( $_POST[ $field_name ] ) );

    if ( '' === $raw_value ) {
        return null;
    }

    $normalized = absint( $raw_value );

    if ( $normalized < 1 ) {
        $invalid_flag = true;

        return null;
    }

    return $normalized;
}

function visibloc_jlg_get_settings_snapshot() {
    $supported_blocks = visibloc_jlg_normalize_block_names( get_option( 'visibloc_supported_blocks', [] ) );
    $mobile_bp        = (int) get_option( 'visibloc_breakpoint_mobile', 781 );
    $tablet_bp        = (int) get_option( 'visibloc_breakpoint_tablet', 1024 );
    $preview_roles    = get_option( 'visibloc_preview_roles', [ 'administrator' ] );
    $debug_mode       = get_option( 'visibloc_debug_mode', 'off' );

    $preview_roles = array_values( array_unique( array_map( 'sanitize_key', (array) $preview_roles ) ) );

    if ( empty( $preview_roles ) || ! in_array( 'administrator', $preview_roles, true ) ) {
        $preview_roles[] = 'administrator';
    }

    return [
        'supported_blocks' => $supported_blocks,
        'breakpoints'      => [
            'mobile' => $mobile_bp,
            'tablet' => $tablet_bp,
        ],
        'preview_roles'    => $preview_roles,
        'debug_mode'       => ( 'on' === $debug_mode ) ? 'on' : 'off',
        'fallback'         => visibloc_jlg_get_fallback_settings(),
        'onboarding_mode'  => visibloc_jlg_get_onboarding_mode(),
        'exported_at'      => gmdate( 'c' ),
        'version'          => visibloc_jlg_get_plugin_version(),
    ];
}

function visibloc_jlg_export_settings_snapshot() {
    $snapshot = visibloc_jlg_get_settings_snapshot();
    $json     = wp_json_encode( $snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

    if ( false === $json ) {
        $json = '{}';
    }

    if ( function_exists( 'nocache_headers' ) ) {
        nocache_headers();
    }

    $filename = sprintf( 'visibloc-settings-%s.json', gmdate( 'Ymd-His' ) );

    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=' . $filename );

    echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function visibloc_jlg_import_settings_snapshot( $payload ) {
    if ( ! is_string( $payload ) || '' === trim( $payload ) ) {
        return new WP_Error( 'visibloc_empty_payload', __( 'Aucune donnée fournie pour l’import.', 'visi-bloc-jlg' ) );
    }

    $decoded = json_decode( $payload, true );

    if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) {
        return new WP_Error( 'visibloc_invalid_json', __( 'Le fichier fourni n’est pas un JSON valide.', 'visi-bloc-jlg' ) );
    }

    if ( ! is_array( $decoded ) ) {
        return new WP_Error( 'visibloc_invalid_payload', __( 'Les données importées sont invalides.', 'visi-bloc-jlg' ) );
    }

    $sanitized = visibloc_jlg_sanitize_import_settings( $decoded );

    if ( is_wp_error( $sanitized ) ) {
        return $sanitized;
    }

    if ( isset( $sanitized['supported_blocks'] ) ) {
        visibloc_jlg_update_supported_blocks( $sanitized['supported_blocks'] );
    }

    if ( isset( $sanitized['breakpoints'] ) ) {
        update_option( 'visibloc_breakpoint_mobile', $sanitized['breakpoints']['mobile'] );
        update_option( 'visibloc_breakpoint_tablet', $sanitized['breakpoints']['tablet'] );
    }

    if ( isset( $sanitized['preview_roles'] ) ) {
        update_option( 'visibloc_preview_roles', $sanitized['preview_roles'] );
    }

    if ( isset( $sanitized['debug_mode'] ) ) {
        update_option( 'visibloc_debug_mode', $sanitized['debug_mode'] );
    }

    if ( isset( $sanitized['fallback'] ) ) {
        update_option( 'visibloc_fallback_settings', $sanitized['fallback'] );
    }

    if ( isset( $sanitized['onboarding_mode'] ) ) {
        visibloc_jlg_update_onboarding_mode( $sanitized['onboarding_mode'] );
    }

    visibloc_jlg_clear_caches();

    return true;
}

function visibloc_jlg_sanitize_import_settings( $data ) {
    if ( ! is_array( $data ) ) {
        return new WP_Error( 'visibloc_invalid_payload', __( 'Les données importées sont invalides.', 'visi-bloc-jlg' ) );
    }

    $sanitized = [];

    if ( array_key_exists( 'supported_blocks', $data ) ) {
        $sanitized['supported_blocks'] = visibloc_jlg_normalize_block_names( $data['supported_blocks'] );
    }

    if ( isset( $data['breakpoints'] ) && is_array( $data['breakpoints'] ) ) {
        $mobile = isset( $data['breakpoints']['mobile'] ) ? absint( $data['breakpoints']['mobile'] ) : null;
        $tablet = isset( $data['breakpoints']['tablet'] ) ? absint( $data['breakpoints']['tablet'] ) : null;

        if ( null === $mobile || null === $tablet || $mobile < 1 || $tablet < 1 || $tablet <= $mobile ) {
            return new WP_Error( 'visibloc_invalid_breakpoints', visibloc_jlg_get_breakpoints_requirement_message() );
        }

        $sanitized['breakpoints'] = [
            'mobile' => $mobile,
            'tablet' => $tablet,
        ];
    }

    if ( array_key_exists( 'preview_roles', $data ) ) {
        if ( ! function_exists( 'get_editable_roles' ) ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        $editable_roles = array_keys( (array) get_editable_roles() );
        $editable_roles = array_map( 'sanitize_key', $editable_roles );

        $roles = array_map( 'sanitize_key', (array) $data['preview_roles'] );
        $roles = array_values( array_unique( array_intersect( $roles, $editable_roles ) ) );

        if ( empty( $roles ) || ! in_array( 'administrator', $roles, true ) ) {
            $roles[] = 'administrator';
        }

        $sanitized['preview_roles'] = $roles;
    }

    if ( array_key_exists( 'debug_mode', $data ) ) {
        $debug_mode = ( 'on' === $data['debug_mode'] ) ? 'on' : 'off';
        $sanitized['debug_mode'] = $debug_mode;
    }

    if ( array_key_exists( 'fallback', $data ) ) {
        if ( ! is_array( $data['fallback'] ) ) {
            return new WP_Error( 'visibloc_invalid_fallback_settings', __( 'Les réglages de repli sont invalides.', 'visi-bloc-jlg' ) );
        }

        $sanitized['fallback'] = visibloc_jlg_normalize_fallback_settings( $data['fallback'] );
    }

    if ( array_key_exists( 'onboarding_mode', $data ) ) {
        $sanitized['onboarding_mode'] = 'expert' === strtolower( (string) $data['onboarding_mode'] ) ? 'expert' : 'simple';
    }

    return $sanitized;
}

function visibloc_jlg_get_import_error_message( $code ) {
    switch ( $code ) {
        case 'visibloc_invalid_json':
            return __( 'Le fichier fourni n’est pas un JSON valide.', 'visi-bloc-jlg' );
        case 'visibloc_invalid_payload':
            return __( 'Les données importées sont invalides.', 'visi-bloc-jlg' );
        case 'visibloc_invalid_breakpoints':
            return visibloc_jlg_get_breakpoints_requirement_message();
        case 'visibloc_invalid_fallback_settings':
            return __( 'Les réglages de repli sont invalides.', 'visi-bloc-jlg' );
        case 'visibloc_empty_payload':
            return __( 'Aucune donnée fournie pour l’import.', 'visi-bloc-jlg' );
    }

    return '';
}

add_action( 'admin_menu', 'visibloc_jlg_add_admin_menu' );
function visibloc_jlg_add_admin_menu() {
    add_menu_page(
        __( 'Aide & Réglages Visi-Bloc - JLG', 'visi-bloc-jlg' ),
        __( 'Visi-Bloc - JLG', 'visi-bloc-jlg' ),
        'manage_options',
        'visi-bloc-jlg-help',
        'visibloc_jlg_render_help_page_content',
        'dashicons-visibility',
        25
    );
}

function visibloc_jlg_render_help_page_content() {
    $debug_status      = get_option( 'visibloc_debug_mode', 'off' );
    $mobile_bp         = get_option( 'visibloc_breakpoint_mobile', 781 );
    $tablet_bp         = get_option( 'visibloc_breakpoint_tablet', 1024 );
    $fallback_settings = visibloc_jlg_get_fallback_settings();
    $fallback_blocks   = visibloc_jlg_get_available_fallback_blocks();

    $allowed_roles_option   = get_option( 'visibloc_preview_roles', [ 'administrator' ] );
    $allowed_roles          = array_filter( (array) $allowed_roles_option );
    $configured_blocks_raw  = get_option( 'visibloc_supported_blocks', [] );
    $configured_blocks      = visibloc_jlg_normalize_block_names( $configured_blocks_raw );
    $registered_block_types = [];

    if ( class_exists( 'WP_Block_Type_Registry' ) ) {
        $registry = WP_Block_Type_Registry::get_instance();
        $all_blocks = is_object( $registry ) && method_exists( $registry, 'get_all_registered' )
            ? $registry->get_all_registered()
            : [];

        if ( is_array( $all_blocks ) ) {
            foreach ( $all_blocks as $name => $block_type ) {
                if ( ! is_string( $name ) ) {
                    continue;
                }

                $label = $name;

                if ( is_object( $block_type ) && isset( $block_type->title ) && is_string( $block_type->title ) && '' !== $block_type->title ) {
                    $label = $block_type->title;
                }

                $registered_block_types[] = [
                    'name'  => $name,
                    'label' => $label,
                ];
            }

            usort(
                $registered_block_types,
                static function ( $a, $b ) {
                    return strcmp( strtolower( $a['label'] ), strtolower( $b['label'] ) );
                }
            );
        }
    }

    if ( empty( $allowed_roles ) ) {
        $allowed_roles = [ 'administrator' ];
    }

    $scheduled_posts = visibloc_jlg_get_scheduled_posts();
    $hidden_posts    = visibloc_jlg_get_hidden_posts();
    $device_posts    = visibloc_jlg_get_device_specific_posts();
    $status          = visibloc_jlg_get_sanitized_query_arg( 'status' );

    $guided_recipes = visibloc_jlg_get_guided_recipes();

    $notifications = visibloc_jlg_collect_admin_notifications(
        [
            'fallback_settings' => $fallback_settings,
            'hidden_posts'      => $hidden_posts,
            'scheduled_posts'   => $scheduled_posts,
        ]
    );

    $breakpoints_requirement_message = visibloc_jlg_get_breakpoints_requirement_message();

    $onboarding_items = visibloc_jlg_build_onboarding_checklist_items(
        [
            'supported_blocks'  => $configured_blocks,
            'preview_roles'     => $allowed_roles,
            'fallback'          => $fallback_settings,
            'mobile_breakpoint' => $mobile_bp,
            'tablet_breakpoint' => $tablet_bp,
        ]
    );
    $onboarding_progress = visibloc_jlg_calculate_onboarding_progress( $onboarding_items );
    $onboarding_mode     = visibloc_jlg_get_onboarding_mode();
    $onboarding_recipes  = visibloc_jlg_get_onboarding_recipes_library();

    $section_map = [];

    if ( ! empty( $onboarding_items ) ) {
        $section_map['visibloc-section-onboarding'] = [
            'id'      => 'visibloc-section-onboarding',
            'label'   => __( 'Checklist de mise en route', 'visi-bloc-jlg' ),
            'render'  => 'visibloc_jlg_render_onboarding_section',
            'args'    => [ $onboarding_items, $onboarding_progress, $onboarding_mode, $onboarding_recipes ],
        ];
    }

    if ( ! empty( $guided_recipes ) ) {
        $section_map['visibloc-section-guided-recipes'] = [
            'id'      => 'visibloc-section-guided-recipes',
            'label'   => __( 'Recettes guidées', 'visi-bloc-jlg' ),
            'render'  => 'visibloc_jlg_render_guided_recipes_section',
            'args'    => [ $guided_recipes ],
        ];
    }

    $section_map['visibloc-section-notifications'] = [
        'id'     => 'visibloc-section-notifications',
        'label'  => __( 'Centre de notifications', 'visi-bloc-jlg' ),
        'render' => 'visibloc_jlg_render_notifications_section',
        'args'   => [ $notifications ],
    ];

    $section_map['visibloc-section-insights'] = [
        'id'     => 'visibloc-section-insights',
        'label'  => __( 'Insights d’affichage', 'visi-bloc-jlg' ),
        'render' => 'visibloc_jlg_render_insights_dashboard_section',
        'args'   => [],
    ];

    $section_map['visibloc-section-blocks'] = [
        'id'     => 'visibloc-section-blocks',
        'label'  => __( 'Blocs compatibles', 'visi-bloc-jlg' ),
        'render' => 'visibloc_jlg_render_supported_blocks_section',
        'args'   => [ $registered_block_types, $configured_blocks ],
    ];
    $section_map['visibloc-section-permissions'] = [
        'id'     => 'visibloc-section-permissions',
        'label'  => __( "Permissions d'Aperçu", 'visi-bloc-jlg' ),
        'render' => 'visibloc_jlg_render_permissions_section',
        'args'   => [ $allowed_roles ],
    ];
    $section_map['visibloc-section-hidden'] = [
        'id'     => 'visibloc-section-hidden',
        'label'  => __( 'Tableau de bord des blocs masqués (via Œil)', 'visi-bloc-jlg' ),
        'render' => 'visibloc_jlg_render_hidden_blocks_section',
        'args'   => [ $hidden_posts ],
    ];
    $section_map['visibloc-section-device'] = [
        'id'     => 'visibloc-section-device',
        'label'  => __( 'Tableau de bord des blocs avec visibilité par appareil', 'visi-bloc-jlg' ),
        'render' => 'visibloc_jlg_render_device_visibility_section',
        'args'   => [ $device_posts ],
    ];
    $section_map['visibloc-section-scheduled'] = [
        'id'     => 'visibloc-section-scheduled',
        'label'  => __( 'Tableau de bord des blocs programmés', 'visi-bloc-jlg' ),
        'render' => 'visibloc_jlg_render_scheduled_blocks_section',
        'args'   => [ $scheduled_posts ],
    ];
    $section_map['visibloc-section-breakpoints'] = [
        'id'     => 'visibloc-section-breakpoints',
        'label'  => __( 'Réglage des points de rupture', 'visi-bloc-jlg' ),
        'render' => 'visibloc_jlg_render_breakpoints_section',
        'args'   => [ $mobile_bp, $tablet_bp ],
    ];
    $section_map['visibloc-section-fallback'] = [
        'id'     => 'visibloc-section-fallback',
        'label'  => __( 'Contenu de repli global', 'visi-bloc-jlg' ),
        'render' => 'visibloc_jlg_render_fallback_section',
        'args'   => [ $fallback_settings, $fallback_blocks ],
    ];
    $section_map['visibloc-section-debug'] = [
        'id'     => 'visibloc-section-debug',
        'label'  => __( 'Mode de débogage', 'visi-bloc-jlg' ),
        'render' => 'visibloc_jlg_render_debug_mode_section',
        'args'   => [ $debug_status ],
    ];
    $section_map['visibloc-section-backup'] = [
        'id'     => 'visibloc-section-backup',
        'label'  => __( 'Export & sauvegarde', 'visi-bloc-jlg' ),
        'render' => 'visibloc_jlg_render_settings_backup_section',
        'args'   => [],
    ];

    $group_definitions = [
        [
            'id'          => 'visibloc-group-discover',
            'label'       => __( 'Découverte', 'visi-bloc-jlg' ),
            'description' => __( 'Lancez un parcours guidé et vérifiez en un coup d’œil les étapes fondamentales de configuration.', 'visi-bloc-jlg' ),
            'section_ids' => [ 'visibloc-section-onboarding', 'visibloc-section-guided-recipes' ],
        ],
        [
            'id'          => 'visibloc-group-dashboards',
            'label'       => __( 'Tableaux de bord', 'visi-bloc-jlg' ),
            'description' => __( 'Surveillez les blocs masqués, programmés ou ciblés par appareil pour détecter les anomalies en production.', 'visi-bloc-jlg' ),
            'section_ids' => [ 'visibloc-section-notifications', 'visibloc-section-insights', 'visibloc-section-hidden', 'visibloc-section-device', 'visibloc-section-scheduled' ],
        ],
        [
            'id'          => 'visibloc-group-configuration',
            'label'       => __( 'Configuration avancée', 'visi-bloc-jlg' ),
            'description' => __( 'Affinez les blocs compatibles, les permissions et les points de rupture afin d’harmoniser l’expérience éditeur.', 'visi-bloc-jlg' ),
            'section_ids' => [ 'visibloc-section-blocks', 'visibloc-section-permissions', 'visibloc-section-breakpoints', 'visibloc-section-fallback', 'visibloc-section-debug' ],
        ],
        [
            'id'          => 'visibloc-group-continuity',
            'label'       => __( 'Sauvegarde & continuité', 'visi-bloc-jlg' ),
            'description' => __( 'Exportez vos réglages et automatisez la reprise en cas d’incident grâce aux outils intégrés.', 'visi-bloc-jlg' ),
            'section_ids' => [ 'visibloc-section-backup' ],
        ],
    ];

    $groups = [];

    foreach ( $group_definitions as $group ) {
        $sections = [];

        foreach ( (array) $group['section_ids'] as $section_id ) {
            if ( isset( $section_map[ $section_id ] ) ) {
                $sections[] = $section_map[ $section_id ];
            }
        }

        if ( empty( $sections ) ) {
            continue;
        }

        $groups[] = [
            'id'          => $group['id'],
            'label'       => $group['label'],
            'description' => $group['description'],
            'sections'    => $sections,
        ];
    }

    $group_select_id      = 'visibloc-help-group-picker';
    $group_description_id = $group_select_id . '-description';

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Visi-Bloc - JLG - Aide et Réglages', 'visi-bloc-jlg' ); ?></h1>
        <?php if ( 'updated' === $status ) : ?>
            <div id="message" class="updated notice is-dismissible"><p><?php esc_html_e( 'Réglages mis à jour.', 'visi-bloc-jlg' ); ?></p></div>
        <?php elseif ( 'invalid_breakpoints' === $status ) : ?>
            <div id="message" class="notice notice-error is-dismissible"><p><?php echo esc_html( $breakpoints_requirement_message ); ?> <?php esc_html_e( 'Les réglages n’ont pas été enregistrés.', 'visi-bloc-jlg' ); ?></p></div>
        <?php elseif ( 'settings_imported' === $status ) : ?>
            <div id="message" class="updated notice is-dismissible"><p><?php esc_html_e( 'Les réglages ont été importés avec succès.', 'visi-bloc-jlg' ); ?></p></div>
        <?php elseif ( 'settings_import_failed' === $status ) : ?>
            <?php
            $error_code     = visibloc_jlg_get_sanitized_query_arg( 'error_code' );
            $error_message  = visibloc_jlg_get_import_error_message( $error_code );
            $fallback_error = __( 'L’import a échoué. Vérifiez le contenu du fichier et réessayez.', 'visi-bloc-jlg' );
            ?>
            <div id="message" class="notice notice-error is-dismissible"><p><?php echo esc_html( $error_message ?: $fallback_error ); ?></p></div>
        <?php endif; ?>
        <div class="visibloc-help-groups" data-visibloc-groups>
            <div class="visibloc-help-groups__nav">
                <div class="visibloc-help-nav__mobile" data-visibloc-group-picker-container>
                    <label class="visibloc-help-nav__mobile-label" for="<?php echo esc_attr( $group_select_id ); ?>">
                        <?php esc_html_e( 'Choisir une vue', 'visi-bloc-jlg' ); ?>
                    </label>
                    <p id="<?php echo esc_attr( $group_description_id ); ?>" class="description visibloc-help-nav__mobile-description">
                        <?php esc_html_e( 'Sélectionnez la thématique qui correspond à votre besoin : découverte, tableaux de bord, configuration ou sauvegarde.', 'visi-bloc-jlg' ); ?>
                    </p>
                    <select
                        id="<?php echo esc_attr( $group_select_id ); ?>"
                        class="visibloc-help-nav__mobile-select regular-text"
                        aria-describedby="<?php echo esc_attr( $group_description_id ); ?>"
                        data-visibloc-group-picker
                    >
                        <?php foreach ( $groups as $group_index => $group ) : ?>
                            <option value="<?php echo esc_attr( $group['id'] ); ?>" <?php selected( 0, $group_index ); ?>>
                                <?php echo esc_html( $group['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="visibloc-help-groups__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Vues thématiques Visi-Bloc', 'visi-bloc-jlg' ); ?>">
                    <?php foreach ( $groups as $index => $group ) :
                        $is_active = ( 0 === $index );
                        $tab_id    = $group['id'] . '-tab';
                        ?>
                        <button
                            type="button"
                            class="visibloc-help-groups__tab<?php echo $is_active ? ' is-active' : ''; ?>"
                            id="<?php echo esc_attr( $tab_id ); ?>"
                            role="tab"
                            aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                            aria-controls="<?php echo esc_attr( $group['id'] ); ?>"
                            data-visibloc-group-tab="<?php echo esc_attr( $group['id'] ); ?>"
                            <?php echo $is_active ? '' : 'tabindex="-1"'; ?>
                        >
                            <span class="visibloc-help-groups__tab-label"><?php echo esc_html( $group['label'] ); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="visibloc-help-groups__panels">
                <?php foreach ( $groups as $index => $group ) :
                    $is_active = ( 0 === $index );
                    $tab_id    = $group['id'] . '-tab';
                    ?>
                    <section
                        id="<?php echo esc_attr( $group['id'] ); ?>"
                        class="visibloc-help-groups__panel<?php echo $is_active ? ' is-active' : ''; ?>"
                        role="tabpanel"
                        aria-labelledby="<?php echo esc_attr( $tab_id ); ?>"
                        data-visibloc-group-panel="<?php echo esc_attr( $group['id'] ); ?>"
                        <?php echo $is_active ? '' : 'hidden'; ?>
                    >
                        <header class="visibloc-help-groups__panel-header">
                            <h2 class="visibloc-help-groups__panel-title"><?php echo esc_html( $group['label'] ); ?></h2>
                            <?php if ( ! empty( $group['description'] ) ) : ?>
                                <p class="visibloc-help-groups__panel-description"><?php echo esc_html( $group['description'] ); ?></p>
                            <?php endif; ?>
                        </header>
                        <div class="visibloc-help-groups__panel-content">
                            <?php foreach ( $group['sections'] as $section ) :
                                $callback = $section['render'] ?? null;

                                if ( empty( $callback ) || ! is_callable( $callback ) ) {
                                    continue;
                                }

                                $args = isset( $section['args'] ) && is_array( $section['args'] )
                                    ? $section['args']
                                    : [];

                                call_user_func_array( $callback, $args );
                            endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}

function visibloc_jlg_render_onboarding_section( $items, $progress, $mode = 'simple', $recipes = [] ) {
    $items    = is_array( $items ) ? array_values( array_filter( $items ) ) : [];
    $progress = is_array( $progress ) ? $progress : [ 'completed' => 0, 'total' => 0, 'percent' => 0 ];
    $mode     = 'expert' === strtolower( (string) $mode ) ? 'expert' : 'simple';
    $recipes  = is_array( $recipes ) ? array_values( array_filter( $recipes ) ) : [];

    if ( empty( $items ) ) {
        return;
    }

    $title_id      = 'visibloc-onboarding-title';
    $list_id       = 'visibloc-onboarding-list';
    $completed     = isset( $progress['completed'] ) ? (int) $progress['completed'] : 0;
    $total         = isset( $progress['total'] ) ? (int) $progress['total'] : count( $items );
    $percent       = isset( $progress['percent'] ) ? (int) $progress['percent'] : 0;
    $percent_value = max( 0, min( 100, $percent ) );

    ?>
    <section
        id="visibloc-section-onboarding"
        class="visibloc-onboarding"
        aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
        data-visibloc-section="visibloc-section-onboarding"
    >
        <div class="visibloc-onboarding__header">
            <div class="visibloc-onboarding__intro">
                <h3 id="<?php echo esc_attr( $title_id ); ?>" class="visibloc-onboarding__title">
                    <?php esc_html_e( 'Assistant de prise en main', 'visi-bloc-jlg' ); ?>
                </h3>
                <p class="visibloc-onboarding__subtitle">
                    <?php esc_html_e( 'Suivez les étapes clés pour sécuriser l’activation des règles de visibilité.', 'visi-bloc-jlg' ); ?>
                </p>
            </div>
            <div class="visibloc-onboarding__progress" role="group" aria-label="<?php esc_attr_e( 'Progression de la checklist', 'visi-bloc-jlg' ); ?>">
                <div class="visibloc-onboarding__progress-count">
                    <span class="visibloc-onboarding__progress-value"><?php echo esc_html( $completed ); ?> / <?php echo esc_html( $total ); ?></span>
                    <span class="visibloc-onboarding__progress-label"><?php esc_html_e( 'étapes terminées', 'visi-bloc-jlg' ); ?></span>
                </div>
                <div class="visibloc-onboarding__progress-bar" role="presentation">
                    <span class="visibloc-onboarding__progress-bar-fill" style="width: <?php echo esc_attr( $percent_value ); ?>%;"></span>
                </div>
            </div>
            <div class="visibloc-onboarding__mode">
                <form method="post" class="visibloc-onboarding__mode-form">
                    <?php wp_nonce_field( 'visibloc_save_onboarding_mode', 'visibloc_nonce' ); ?>
                    <label class="screen-reader-text" for="visibloc-onboarding-mode">
                        <?php esc_html_e( 'Mode par défaut de l’assistant', 'visi-bloc-jlg' ); ?>
                    </label>
                    <select name="visibloc_onboarding_mode" id="visibloc-onboarding-mode">
                        <option value="simple" <?php selected( 'simple', $mode ); ?>>
                            <?php esc_html_e( 'Simple', 'visi-bloc-jlg' ); ?>
                        </option>
                        <option value="expert" <?php selected( 'expert', $mode ); ?>>
                            <?php esc_html_e( 'Expert', 'visi-bloc-jlg' ); ?>
                        </option>
                    </select>
                    <button type="submit" class="button button-secondary">
                        <?php esc_html_e( 'Enregistrer', 'visi-bloc-jlg' ); ?>
                    </button>
                </form>
            </div>
        </div>
        <details class="visibloc-contextual-guide" data-visibloc-guide>
            <summary class="visibloc-contextual-guide__summary">
                <?php esc_html_e( 'Guide express : comment valider chaque étape ?', 'visi-bloc-jlg' ); ?>
            </summary>
            <div class="visibloc-contextual-guide__content">
                <p><?php esc_html_e( 'Chaque carte renvoie vers la section correspondante de la page. Pensez à valider l’accessibilité (labels, contrastes) avant de marquer l’étape comme terminée.', 'visi-bloc-jlg' ); ?></p>
            </div>
        </details>
        <?php if ( ! empty( $recipes ) ) : ?>
            <div class="visibloc-onboarding__recipes">
                <h4 class="visibloc-onboarding__recipes-title"><?php esc_html_e( 'Recettes disponibles', 'visi-bloc-jlg' ); ?></h4>
                <ul class="visibloc-onboarding__recipes-list">
                    <?php foreach ( $recipes as $recipe ) :
                        $recipe_title   = isset( $recipe['title'] ) ? (string) $recipe['title'] : '';
                        $recipe_summary = isset( $recipe['summary'] ) ? (string) $recipe['summary'] : '';
                        ?>
                        <li class="visibloc-onboarding__recipes-item">
                            <strong><?php echo esc_html( $recipe_title ); ?></strong>
                            <?php if ( '' !== $recipe_summary ) : ?>
                                <span class="visibloc-onboarding__recipes-summary"><?php echo esc_html( $recipe_summary ); ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <ul class="visibloc-onboarding__checklist" id="<?php echo esc_attr( $list_id ); ?>" role="list">
            <?php foreach ( $items as $item ) :
                $is_complete  = ! empty( $item['complete'] );
                $status_class = $is_complete ? 'is-complete' : 'is-pending';
                $title        = isset( $item['title'] ) ? (string) $item['title'] : '';
                $description  = isset( $item['description'] ) ? (string) $item['description'] : '';
                $action       = isset( $item['action'] ) && is_array( $item['action'] ) ? $item['action'] : [];
                $action_label = isset( $action['label'] ) ? (string) $action['label'] : '';
                $action_url   = isset( $action['url'] ) ? (string) $action['url'] : '';
                ?>
                <li class="visibloc-onboarding__item <?php echo esc_attr( $status_class ); ?>">
                    <div class="visibloc-onboarding__status">
                        <?php if ( $is_complete ) : ?>
                            <span aria-hidden="true" class="visibloc-onboarding__status-icon visibloc-onboarding__status-icon--complete">
                                <svg viewBox="0 0 24 24" focusable="false" role="img" aria-hidden="true"><path d="M9.6 16.2a1 1 0 0 1-.74-.33l-3.1-3.4a1 1 0 1 1 1.48-1.34l2.28 2.5 6-6.6a1 1 0 0 1 1.48 1.34l-6.74 7.4a1 1 0 0 1-.74.33z" /></svg>
                            </span>
                            <span class="screen-reader-text"><?php esc_html_e( 'Étape terminée', 'visi-bloc-jlg' ); ?></span>
                        <?php else : ?>
                            <span aria-hidden="true" class="visibloc-onboarding__status-icon visibloc-onboarding__status-icon--pending">
                                <svg viewBox="0 0 24 24" focusable="false" role="img" aria-hidden="true"><path d="M12 4a1 1 0 0 1 1 1v6.08l3.36 2.16a1 1 0 1 1-1.07 1.7l-3.85-2.48A1 1 0 0 1 11 11V5a1 1 0 0 1 1-1z" /></svg>
                            </span>
                            <span class="screen-reader-text"><?php esc_html_e( 'Étape à compléter', 'visi-bloc-jlg' ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="visibloc-onboarding__details">
                        <h4 class="visibloc-onboarding__item-title"><?php echo esc_html( $title ); ?></h4>
                        <p class="visibloc-onboarding__item-description"><?php echo esc_html( $description ); ?></p>
                        <?php if ( '' !== $action_label && '' !== $action_url ) : ?>
                            <a class="visibloc-onboarding__action button button-secondary" href="<?php echo esc_url( $action_url ); ?>">
                                <?php echo esc_html( $action_label ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php
}

function visibloc_jlg_render_guided_recipes_section( $recipes ) {
    $recipes = is_array( $recipes ) ? array_values( array_filter( $recipes ) ) : [];
    $section_id = 'visibloc-section-guided-recipes';

    $filter_select_id      = $section_id . '-filter';
    $empty_message_id      = $section_id . '-empty';
    $live_region_id        = $section_id . '-live';
    $dialog_title_id       = $section_id . '-dialog-title';
    $dialog_description_id = $section_id . '-dialog-description';

    $themes = [];

    foreach ( $recipes as $recipe ) {
        $theme_slug = isset( $recipe['theme'] ) ? sanitize_html_class( $recipe['theme'] ) : '';

        if ( '' === $theme_slug ) {
            $theme_slug = 'general';
        }

        $theme_label = isset( $recipe['theme_label'] ) ? (string) $recipe['theme_label'] : '';

        if ( '' === $theme_label ) {
            $theme_label = ucfirst( str_replace( '-', ' ', $theme_slug ) );
        }

        $themes[ $theme_slug ] = $theme_label;
    }

    if ( ! empty( $themes ) ) {
        ksort( $themes );
    }

    ?>
    <div
        id="<?php echo esc_attr( $section_id ); ?>"
        class="postbox visibloc-guided-recipes-box"
        data-visibloc-section="<?php echo esc_attr( $section_id ); ?>"
    >
        <h2 class="hndle"><span><?php esc_html_e( 'Recettes guidées', 'visi-bloc-jlg' ); ?></span></h2>
        <div class="inside">
            <section class="visibloc-guided-recipes" data-visibloc-recipes>
                <header class="visibloc-guided-recipes__intro">
                    <div class="visibloc-guided-recipes__text">
                        <h3 class="visibloc-guided-recipes__title">
                            <?php esc_html_e( 'Accélérez vos scénarios avec un assistant pas-à-pas', 'visi-bloc-jlg' ); ?>
                        </h3>
                        <p class="visibloc-guided-recipes__subtitle">
                            <?php esc_html_e( 'Choisissez une recette pour lancer l’assistant en quatre étapes. Chaque étape rappelle les exigences WCAG 2.2 et les réglages clés à valider avant publication.', 'visi-bloc-jlg' ); ?>
                        </p>
                    </div>
                    <?php if ( ! empty( $themes ) ) : ?>
                        <div class="visibloc-guided-recipes__filters">
                            <label class="visibloc-guided-recipes__filter-label" for="<?php echo esc_attr( $filter_select_id ); ?>">
                                <?php esc_html_e( 'Filtrer par thématique', 'visi-bloc-jlg' ); ?>
                            </label>
                            <select
                                id="<?php echo esc_attr( $filter_select_id ); ?>"
                                class="visibloc-guided-recipes__filter-select"
                                data-visibloc-recipes-filter
                                aria-controls="<?php echo esc_attr( $section_id ); ?>-list"
                            >
                                <option value="">
                                    <?php esc_html_e( 'Toutes les thématiques', 'visi-bloc-jlg' ); ?>
                                </option>
                                <?php foreach ( $themes as $theme_slug => $theme_label ) : ?>
                                    <option value="<?php echo esc_attr( $theme_slug ); ?>">
                                        <?php echo esc_html( $theme_label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </header>
                <?php if ( empty( $recipes ) ) : ?>
                    <p><em><?php esc_html_e( 'Aucune recette n’est disponible pour le moment.', 'visi-bloc-jlg' ); ?></em></p>
                <?php else : ?>
                    <div class="screen-reader-text" id="<?php echo esc_attr( $live_region_id ); ?>" aria-live="polite" data-visibloc-recipes-live></div>
                    <ul class="visibloc-guided-recipes__list" id="<?php echo esc_attr( $section_id ); ?>-list" role="list">
                        <?php foreach ( $recipes as $index => $recipe ) :
                            $recipe_id = isset( $recipe['id'] ) ? sanitize_key( $recipe['id'] ) : '';

                            if ( '' === $recipe_id ) {
                                $recipe_id = 'recipe-' . $index;
                            }

                            $card_id     = 'visibloc-recipe-card-' . $recipe_id;
                            $template_id = 'visibloc-recipe-template-' . $recipe_id;

                            $theme_slug = isset( $recipe['theme'] ) ? sanitize_html_class( $recipe['theme'] ) : '';

                            if ( '' === $theme_slug ) {
                                $theme_slug = 'general';
                            }

                            $theme_label = isset( $recipe['theme_label'] ) ? (string) $recipe['theme_label'] : '';
                            $title       = isset( $recipe['title'] ) ? (string) $recipe['title'] : '';
                            $description = isset( $recipe['description'] ) ? (string) $recipe['description'] : '';
                            $estimated   = isset( $recipe['estimated_time'] ) ? (string) $recipe['estimated_time'] : '';
                            $audience    = isset( $recipe['audience'] ) ? (string) $recipe['audience'] : '';
                            $goal        = isset( $recipe['goal'] ) ? (string) $recipe['goal'] : '';
                            $kpi         = isset( $recipe['kpi'] ) ? (string) $recipe['kpi'] : '';

                            $blocks = isset( $recipe['blocks'] ) && is_array( $recipe['blocks'] )
                                ? array_values( array_filter( array_map( 'strval', $recipe['blocks'] ) ) )
                                : [];
                            $steps = isset( $recipe['steps'] ) && is_array( $recipe['steps'] )
                                ? array_values( array_filter( $recipe['steps'] ) )
                                : [];
                            $block_template      = isset( $recipe['block_template'] ) ? (string) $recipe['block_template'] : '';
                            $block_template_json = '' !== $block_template ? wp_json_encode( $block_template ) : '';

                            if ( false === $block_template_json ) {
                                $block_template_json = '';
                            }

                            $step_count      = count( $steps );
                            $step_count_text = sprintf( _n( '%d étape', '%d étapes', $step_count, 'visi-bloc-jlg' ), $step_count );
                            $blocks_json     = ! empty( $blocks ) ? wp_json_encode( $blocks ) : '[]';

                            if ( false === $blocks_json ) {
                                $blocks_json = '[]';
                            }
                            ?>
                            <li
                                class="visibloc-guided-recipes__item"
                                data-visibloc-recipe-card
                                data-theme="<?php echo esc_attr( $theme_slug ); ?>"
                                data-recipe-id="<?php echo esc_attr( $recipe_id ); ?>"
                                data-recipe-title="<?php echo esc_attr( $title ); ?>"
                                data-recipe-description="<?php echo esc_attr( $description ); ?>"
                                data-recipe-goal="<?php echo esc_attr( $goal ); ?>"
                                data-recipe-audience="<?php echo esc_attr( $audience ); ?>"
                                data-recipe-kpi="<?php echo esc_attr( $kpi ); ?>"
                                data-recipe-time="<?php echo esc_attr( $estimated ); ?>"
                                data-recipe-theme-label="<?php echo esc_attr( $theme_label ); ?>"
                                data-recipe-step-count="<?php echo esc_attr( $step_count ); ?>"
                                data-recipe-blocks="<?php echo esc_attr( $blocks_json ); ?>"
                                data-recipe-has-template="<?php echo esc_attr( '' !== $block_template_json ? 'true' : 'false' ); ?>"
                            >
                                <article class="visibloc-recipe-card" aria-labelledby="<?php echo esc_attr( $card_id ); ?>-title">
                                    <header class="visibloc-recipe-card__header">
                                        <?php if ( '' !== $theme_label ) : ?>
                                            <span class="visibloc-recipe-card__tag"><?php echo esc_html( $theme_label ); ?></span>
                                        <?php endif; ?>
                                        <h3 id="<?php echo esc_attr( $card_id ); ?>-title" class="visibloc-recipe-card__title">
                                            <?php echo esc_html( $title ); ?>
                                        </h3>
                                        <?php if ( '' !== $description ) : ?>
                                            <p class="visibloc-recipe-card__description"><?php echo esc_html( $description ); ?></p>
                                        <?php endif; ?>
                                    </header>
                                    <?php if ( ! empty( $blocks ) ) : ?>
                                        <ul class="visibloc-recipe-card__blocks" aria-label="<?php esc_attr_e( 'Blocs recommandés', 'visi-bloc-jlg' ); ?>">
                                            <?php foreach ( $blocks as $block_label ) : ?>
                                                <li><?php echo esc_html( $block_label ); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <dl class="visibloc-recipe-card__meta">
                                        <div class="visibloc-recipe-card__meta-item">
                                            <dt><?php esc_html_e( 'Durée estimée', 'visi-bloc-jlg' ); ?></dt>
                                            <dd><?php echo esc_html( '' !== $estimated ? $estimated : __( 'Quelques minutes', 'visi-bloc-jlg' ) ); ?></dd>
                                        </div>
                                        <div class="visibloc-recipe-card__meta-item">
                                            <dt><?php esc_html_e( 'Objectif', 'visi-bloc-jlg' ); ?></dt>
                                            <dd><?php echo esc_html( $goal ); ?></dd>
                                        </div>
                                        <div class="visibloc-recipe-card__meta-item">
                                            <dt><?php esc_html_e( 'Audience', 'visi-bloc-jlg' ); ?></dt>
                                            <dd><?php echo esc_html( $audience ); ?></dd>
                                        </div>
                                    </dl>
                                    <div class="visibloc-recipe-card__footer">
                                        <p class="visibloc-recipe-card__kpi">
                                            <strong><?php esc_html_e( 'Indicateur clé', 'visi-bloc-jlg' ); ?>:</strong>
                                            <span><?php echo esc_html( $kpi ); ?></span>
                                        </p>
                                        <p class="visibloc-recipe-card__steps" aria-label="<?php esc_attr_e( 'Nombre d’étapes de l’assistant', 'visi-bloc-jlg' ); ?>">
                                            <?php echo esc_html( $step_count_text ); ?>
                                        </p>
                                        <?php if ( '' !== $block_template_json ) : ?>
                                            <p class="visibloc-recipe-card__template">
                                                <?php esc_html_e( 'Modèle de blocs prêt à l’emploi inclus.', 'visi-bloc-jlg' ); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="visibloc-recipe-card__actions">
                                        <button
                                            type="button"
                                            class="button button-primary visibloc-recipe-card__button"
                                            data-visibloc-recipe-start
                                            data-recipe-template="<?php echo esc_attr( $template_id ); ?>"
                                        >
                                            <?php esc_html_e( 'Lancer l’assistant', 'visi-bloc-jlg' ); ?>
                                        </button>
                                    </div>
                                </article>
                                <template id="<?php echo esc_attr( $template_id ); ?>" data-visibloc-recipe-template>
                                    <?php foreach ( $steps as $step ) :
                                        $step_title     = isset( $step['title'] ) ? (string) $step['title'] : '';
                                        $step_summary   = isset( $step['summary'] ) ? (string) $step['summary'] : '';
                                        $step_actions   = isset( $step['actions'] ) && is_array( $step['actions'] ) ? array_values( array_filter( $step['actions'] ) ) : [];
                                        $step_notes     = isset( $step['notes'] ) && is_array( $step['notes'] ) ? array_values( array_filter( $step['notes'] ) ) : [];
                                        $step_resources = isset( $step['resources'] ) && is_array( $step['resources'] ) ? array_values( array_filter( $step['resources'] ) ) : [];
                                        ?>
                                        <div
                                            class="visibloc-recipe-step"
                                            data-visibloc-recipe-step
                                            data-step-title="<?php echo esc_attr( $step_title ); ?>"
                                            data-step-summary="<?php echo esc_attr( $step_summary ); ?>"
                                        >
                                            <?php if ( '' !== $step_summary ) : ?>
                                                <p class="visibloc-recipe-step__summary"><?php echo esc_html( $step_summary ); ?></p>
                                            <?php endif; ?>
                                            <?php if ( ! empty( $step_actions ) ) : ?>
                                                <ul class="visibloc-recipe-step__list">
                                                    <?php foreach ( $step_actions as $action ) : ?>
                                                        <li><?php echo esc_html( $action ); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                            <?php if ( ! empty( $step_notes ) ) : ?>
                                                <div class="visibloc-recipe-step__notes" role="note">
                                                    <strong class="visibloc-recipe-step__notes-title"><?php esc_html_e( 'Points de vigilance', 'visi-bloc-jlg' ); ?></strong>
                                                    <ul>
                                                        <?php foreach ( $step_notes as $note ) : ?>
                                                            <li><?php echo esc_html( $note ); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ( ! empty( $step_resources ) ) : ?>
                                                <p class="visibloc-recipe-step__resources">
                                                    <span class="visibloc-recipe-step__resources-label"><?php esc_html_e( 'Ressources utiles :', 'visi-bloc-jlg' ); ?></span>
                                                    <?php
                                                    $resource_index = 0;
                                                    foreach ( $step_resources as $resource ) :
                                                        $resource_label = isset( $resource['label'] ) ? (string) $resource['label'] : '';
                                                        $resource_url   = isset( $resource['url'] ) ? (string) $resource['url'] : '';

                                                        if ( '' === $resource_label || '' === $resource_url ) {
                                                            continue;
                                                        }

                                                        if ( $resource_index > 0 ) {
                                                            echo '<span class="visibloc-recipe-step__resources-separator"> · </span>';
                                                        }

                                                        printf(
                                                            '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
                                                            esc_url( $resource_url ),
                                                            esc_html( $resource_label )
                                                        );

                                                        $resource_index++;
                                                    endforeach;
                                                    ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ( '' !== $block_template_json ) : ?>
                                        <script type="application/json" data-visibloc-recipe-template-blocks><?php echo $block_template_json; ?></script>
                                    <?php endif; ?>
                                </template>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p
                        id="<?php echo esc_attr( $empty_message_id ); ?>"
                        class="visibloc-guided-recipes__empty"
                        data-visibloc-recipes-empty
                        hidden
                    >
                        <?php esc_html_e( 'Aucune recette ne correspond au filtre sélectionné.', 'visi-bloc-jlg' ); ?>
                    </p>
                    <div class="visibloc-guided-recipes__dialog" data-visibloc-recipe-dialog hidden>
                        <div class="visibloc-guided-recipes__dialog-backdrop" data-visibloc-recipe-close></div>
                        <div
                            class="visibloc-guided-recipes__dialog-window"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="<?php echo esc_attr( $dialog_title_id ); ?>"
                            aria-describedby="<?php echo esc_attr( $dialog_description_id ); ?>"
                            data-visibloc-recipe-dialog-window
                        >
                            <header class="visibloc-guided-recipes__dialog-header">
                                <div class="visibloc-guided-recipes__dialog-heading">
                                    <h3 id="<?php echo esc_attr( $dialog_title_id ); ?>" class="visibloc-guided-recipes__dialog-title" data-visibloc-recipe-dialog-title></h3>
                                    <p id="<?php echo esc_attr( $dialog_description_id ); ?>" class="visibloc-guided-recipes__dialog-description" data-visibloc-recipe-dialog-description></p>
                                </div>
                                <button type="button" class="visibloc-guided-recipes__dialog-close" data-visibloc-recipe-close>
                                    <span aria-hidden="true">&times;</span>
                                    <span class="screen-reader-text"><?php esc_html_e( 'Fermer l’assistant', 'visi-bloc-jlg' ); ?></span>
                                </button>
                            </header>
                            <div class="visibloc-guided-recipes__dialog-meta" data-visibloc-recipe-dialog-meta>
                                <dl class="visibloc-guided-recipes__dialog-meta-grid">
                                    <div class="visibloc-guided-recipes__dialog-meta-item">
                                        <dt><?php esc_html_e( 'Objectif', 'visi-bloc-jlg' ); ?></dt>
                                        <dd data-visibloc-recipe-meta="goal"></dd>
                                    </div>
                                    <div class="visibloc-guided-recipes__dialog-meta-item">
                                        <dt><?php esc_html_e( 'Audience cible', 'visi-bloc-jlg' ); ?></dt>
                                        <dd data-visibloc-recipe-meta="audience"></dd>
                                    </div>
                                    <div class="visibloc-guided-recipes__dialog-meta-item">
                                        <dt><?php esc_html_e( 'Indicateur clé', 'visi-bloc-jlg' ); ?></dt>
                                        <dd data-visibloc-recipe-meta="kpi"></dd>
                                    </div>
                                    <div class="visibloc-guided-recipes__dialog-meta-item">
                                        <dt><?php esc_html_e( 'Durée estimée', 'visi-bloc-jlg' ); ?></dt>
                                        <dd data-visibloc-recipe-meta="time"></dd>
                                    </div>
                                </dl>
                                <div class="visibloc-guided-recipes__dialog-blocks" data-visibloc-recipe-dialog-blocks hidden>
                                    <h4 class="visibloc-guided-recipes__dialog-blocks-title"><?php esc_html_e( 'Blocs recommandés', 'visi-bloc-jlg' ); ?></h4>
                                    <ul class="visibloc-guided-recipes__dialog-blocks-list" data-visibloc-recipe-dialog-blocks-list></ul>
                                </div>
                            </div>
                            <div class="visibloc-guided-recipes__dialog-progress" role="group" aria-label="<?php esc_attr_e( 'Progression de l’assistant', 'visi-bloc-jlg' ); ?>">
                                <progress value="0" max="4" class="visibloc-guided-recipes__progress" data-visibloc-recipe-progress>
                                    <?php esc_html_e( 'Progression de l’assistant', 'visi-bloc-jlg' ); ?>
                                </progress>
                                <span class="visibloc-guided-recipes__progress-label" data-visibloc-recipe-progress-label data-visibloc-progress-template="<?php echo esc_attr__( 'Étape %1$s sur %2$s', 'visi-bloc-jlg' ); ?>"></span>
                            </div>
                            <div class="visibloc-guided-recipes__dialog-body">
                                <div class="visibloc-guided-recipes__stepper" data-visibloc-recipe-stepper>
                                    <div class="visibloc-guided-recipes__stepper-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Étapes de la recette', 'visi-bloc-jlg' ); ?>" data-visibloc-recipe-tabs></div>
                                    <div class="visibloc-guided-recipes__stepper-panels" data-visibloc-recipe-panels></div>
                                </div>
                            </div>
                            <div class="screen-reader-text" aria-live="polite" data-visibloc-recipe-step-live></div>
                            <footer class="visibloc-guided-recipes__dialog-footer">
                                <div class="visibloc-guided-recipes__dialog-tools">
                                    <button type="button" class="button button-secondary" data-visibloc-recipe-copy>
                                        <?php esc_html_e( 'Copier le modèle de blocs', 'visi-bloc-jlg' ); ?>
                                    </button>
                                    <span class="visibloc-guided-recipes__dialog-feedback" data-visibloc-recipe-feedback role="status" aria-live="polite" hidden></span>
                                </div>
                                <div class="visibloc-guided-recipes__dialog-navigation">
                                    <button type="button" class="button button-secondary" data-visibloc-recipe-prev>
                                        <?php esc_html_e( 'Étape précédente', 'visi-bloc-jlg' ); ?>
                                    </button>
                                    <button
                                        type="button"
                                        class="button button-primary visibloc-guided-recipes__dialog-next"
                                        data-visibloc-recipe-next
                                        data-visibloc-label-next="<?php echo esc_attr__( 'Étape suivante', 'visi-bloc-jlg' ); ?>"
                                        data-visibloc-label-finish="<?php echo esc_attr__( 'Terminer', 'visi-bloc-jlg' ); ?>"
                                    >
                                        <?php esc_html_e( 'Étape suivante', 'visi-bloc-jlg' ); ?>
                                    </button>
                                </div>
                            </footer>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
    <?php
}

function visibloc_jlg_render_supported_blocks_section( $registered_block_types, $configured_blocks ) {
    $registered_block_types = is_array( $registered_block_types ) ? $registered_block_types : [];
    $configured_blocks      = is_array( $configured_blocks ) ? $configured_blocks : [];
    $default_blocks         = defined( 'VISIBLOC_JLG_DEFAULT_SUPPORTED_BLOCKS' )
        ? (array) VISIBLOC_JLG_DEFAULT_SUPPORTED_BLOCKS
        : [ 'core/group' ];

    $section_id = 'visibloc-section-blocks';

    $groups = [];

    foreach ( $registered_block_types as $block ) {
        $block_name  = isset( $block['name'] ) && is_string( $block['name'] ) ? $block['name'] : '';
        $block_label = isset( $block['label'] ) && is_string( $block['label'] ) ? $block['label'] : $block_name;

        if ( '' === $block_name ) {
            continue;
        }

        $group_key = strpos( $block_name, '/' ) ? substr( $block_name, 0, strpos( $block_name, '/' ) ) : 'core';
        $group_key = $group_key ? sanitize_key( $group_key ) : 'core';

        if ( ! isset( $groups[ $group_key ] ) ) {
            $groups[ $group_key ] = [];
        }

        $groups[ $group_key ][] = [
            'name'  => $block_name,
            'label' => $block_label,
        ];
    }

    ksort( $groups );

    $group_labels = [
        'core'        => __( 'Blocs WordPress natifs', 'visi-bloc-jlg' ),
        'woocommerce' => __( 'WooCommerce', 'visi-bloc-jlg' ),
        'jetpack'     => __( 'Jetpack & partenaires', 'visi-bloc-jlg' ),
    ];

    $presets = [
        'blog' => [
            'label'       => __( 'Pack blog éditorial', 'visi-bloc-jlg' ),
            'description' => __( 'Active les blocs essentiels pour des articles riches (titres, médias, citations, boutons).', 'visi-bloc-jlg' ),
            'blocks'      => [ 'core/heading', 'core/paragraph', 'core/image', 'core/gallery', 'core/quote', 'core/list', 'core/buttons' ],
        ],
        'commerce' => [
            'label'       => __( 'Pack e-commerce', 'visi-bloc-jlg' ),
            'description' => __( 'Cible les blocs produits, paniers et mises en avant pour piloter les parcours d’achat.', 'visi-bloc-jlg' ),
            'blocks'      => [ 'woocommerce/product-image', 'woocommerce/featured-category', 'woocommerce/product-button', 'woocommerce/product-price', 'core/cover', 'core/columns' ],
        ],
        'landing' => [
            'label'       => __( 'Pack landing page', 'visi-bloc-jlg' ),
            'description' => __( 'Préconfigure les blocs de mise en avant (colonnes, témoignages, boutons) pour des pages ciblées.', 'visi-bloc-jlg' ),
            'blocks'      => [ 'core/cover', 'core/columns', 'core/heading', 'core/buttons', 'core/testimonial', 'core/group' ],
        ],
    ];

    ?>
    <div
        id="<?php echo esc_attr( $section_id ); ?>"
        class="postbox"
        data-visibloc-section="<?php echo esc_attr( $section_id ); ?>"
    >
        <h2 class="hndle"><span><?php esc_html_e( 'Blocs compatibles', 'visi-bloc-jlg' ); ?></span></h2>
        <div class="inside">
            <form method="POST" action="">
                <p><?php esc_html_e( 'Sélectionnez les blocs Gutenberg pouvant utiliser les contrôles de visibilité Visi-Bloc.', 'visi-bloc-jlg' ); ?></p>
                <details class="visibloc-contextual-guide" data-visibloc-guide>
                    <summary class="visibloc-contextual-guide__summary">
                        <?php esc_html_e( 'Conseils de configuration', 'visi-bloc-jlg' ); ?>
                    </summary>
                    <div class="visibloc-contextual-guide__content">
                        <p><?php esc_html_e( 'Regroupez les blocs par usage (éditorial, conversion, tunnel) et activez uniquement ceux qui nécessitent des règles de visibilité dynamiques.', 'visi-bloc-jlg' ); ?></p>
                        <p><?php esc_html_e( 'Les packs ci-dessous appliquent une sélection instantanée et restent modifiables bloc par bloc.', 'visi-bloc-jlg' ); ?></p>
                    </div>
                </details>
                <?php if ( empty( $registered_block_types ) ) : ?>
                    <p><em><?php esc_html_e( 'Aucun bloc enregistré n’a été détecté.', 'visi-bloc-jlg' ); ?></em></p>
                <?php else : ?>
                    <fieldset class="visibloc-supported-blocks-fieldset">
                        <legend class="visibloc-supported-blocks-legend">
                            <?php esc_html_e( 'Blocs compatibles', 'visi-bloc-jlg' ); ?>
                        </legend>
                        <div class="visibloc-supported-blocks-search" style="margin-bottom: 12px;">
                            <?php
                            $search_input_id = 'visibloc-supported-blocks-search-' . uniqid();
                            $search_description_id = $search_input_id . '-description';
                            ?>
                            <label for="<?php echo esc_attr( $search_input_id ); ?>" class="screen-reader-text">
                                <?php esc_html_e( 'Rechercher un bloc', 'visi-bloc-jlg' ); ?>
                            </label>
                            <input
                                type="search"
                                id="<?php echo esc_attr( $search_input_id ); ?>"
                                class="regular-text"
                                placeholder="<?php echo esc_attr__( 'Rechercher un bloc…', 'visi-bloc-jlg' ); ?>"
                                autocomplete="off"
                                aria-describedby="<?php echo esc_attr( $search_description_id ); ?>"
                                data-visibloc-blocks-search
                                data-visibloc-blocks-target="visibloc-supported-blocks-list"
                                aria-controls="visibloc-supported-blocks-list"
                            />
                            <div class="visibloc-supported-blocks-actions" style="margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap;">
                                <button
                                    type="button"
                                    class="button button-secondary"
                                    data-visibloc-select-all
                                    data-visibloc-blocks-target="visibloc-supported-blocks-list"
                                >
                                    <?php esc_html_e( 'Tout sélectionner', 'visi-bloc-jlg' ); ?>
                                </button>
                                <button
                                    type="button"
                                    class="button button-secondary"
                                    data-visibloc-select-none
                                    data-visibloc-blocks-target="visibloc-supported-blocks-list"
                                >
                                    <?php esc_html_e( 'Tout désélectionner', 'visi-bloc-jlg' ); ?>
                                </button>
                            </div>
                            <?php if ( ! empty( $presets ) ) : ?>
                                <div class="visibloc-supported-blocks-presets" role="group" aria-label="<?php esc_attr_e( 'Packs de sélection rapide', 'visi-bloc-jlg' ); ?>">
                                    <?php foreach ( $presets as $preset_key => $preset ) :
                                        $blocks_for_preset = array_filter(
                                            array_map( 'strval', $preset['blocks'] ),
                                            static function ( $block_name ) use ( $registered_block_types ) {
                                                foreach ( $registered_block_types as $registered ) {
                                                    if ( isset( $registered['name'] ) && $registered['name'] === $block_name ) {
                                                        return true;
                                                    }
                                                }

                                                return false;
                                            }
                                        );

                                        if ( empty( $blocks_for_preset ) ) {
                                            continue;
                                        }

                                        $preset_json = wp_json_encode( array_values( $blocks_for_preset ) );

                                        if ( false === $preset_json ) {
                                            continue;
                                        }
                                        ?>
                                        <div class="visibloc-supported-blocks-preset">
                                            <button
                                                type="button"
                                                class="button button-secondary"
                                                data-visibloc-block-preset="<?php echo esc_attr( $preset_json ); ?>"
                                                data-visibloc-blocks-target="visibloc-supported-blocks-list"
                                            >
                                                <?php echo esc_html( $preset['label'] ); ?>
                                            </button>
                                            <?php if ( ! empty( $preset['description'] ) ) : ?>
                                                <p class="description visibloc-supported-blocks-preset__description">
                                                    <?php echo esc_html( $preset['description'] ); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <p id="<?php echo esc_attr( $search_description_id ); ?>" class="description" style="margin-top: 4px;">
                                <?php esc_html_e( 'Saisissez un terme pour filtrer la liste des blocs disponibles.', 'visi-bloc-jlg' ); ?>
                            </p>
                        </div>
                        <div
                            id="visibloc-supported-blocks-list"
                            class="visibloc-supported-blocks-list"
                            data-visibloc-blocks-container
                        >
                            <?php
                            $selected_blocks = 0;

                            foreach ( $groups as $group_key => $group_blocks ) :
                                if ( empty( $group_blocks ) ) {
                                    continue;
                                }

                                $group_label = isset( $group_labels[ $group_key ] )
                                    ? $group_labels[ $group_key ]
                                    : ucwords( str_replace( '-', ' ', $group_key ) );
                                ?>
                                <section class="visibloc-supported-blocks-group" data-visibloc-block-group>
                                    <h3 class="visibloc-supported-blocks-group__title"><?php echo esc_html( $group_label ); ?></h3>
                                    <div class="visibloc-supported-blocks-group__list">
                                        <?php foreach ( $group_blocks as $block ) :
                                            $block_name  = isset( $block['name'] ) ? (string) $block['name'] : '';
                                            $block_label = isset( $block['label'] ) ? (string) $block['label'] : $block_name;

                                            if ( '' === $block_name ) {
                                                continue;
                                            }

                                            $is_default  = in_array( $block_name, $default_blocks, true );
                                            $is_checked  = $is_default || in_array( $block_name, $configured_blocks, true );
                                            $is_disabled = $is_default;
                                            $search_text = wp_strip_all_tags( $block_label . ' ' . $block_name );
                                            $search_value = function_exists( 'remove_accents' )
                                                ? remove_accents( $search_text )
                                                : $search_text;

                                            if ( $is_checked ) {
                                                $selected_blocks++;
                                            }

                                            $search_value = function_exists( 'mb_strtolower' )
                                                ? mb_strtolower( $search_value, 'UTF-8' )
                                                : strtolower( $search_value );
                                            ?>
                                            <label
                                                class="visibloc-supported-blocks-item"
                                                data-visibloc-block
                                                data-visibloc-search-value="<?php echo esc_attr( $search_value ); ?>"
                                            >
                                                <input type="checkbox" name="visibloc_supported_blocks[]" value="<?php echo esc_attr( $block_name ); ?>" <?php checked( $is_checked ); ?> <?php disabled( $is_disabled ); ?> />
                                                <span class="visibloc-supported-blocks-item__label"><?php echo esc_html( $block_label ); ?></span>
                                                <span class="visibloc-supported-blocks-item__slug">(<?php echo esc_html( $block_name ); ?>)</span>
                                                <?php if ( $is_default ) : ?>
                                                    <span class="visibloc-supported-blocks-item__badge"><?php esc_html_e( 'Toujours actif', 'visi-bloc-jlg' ); ?></span>
                                                <?php endif; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            <?php endforeach; ?>
                            <?php
                            $count_template        = __( 'Blocs visibles : %1$d — Sélectionnés : %2$d', 'visi-bloc-jlg' );
                            $count_template_attr   = esc_attr( $count_template );
                            $total_blocks          = count( $registered_block_types );
                            $count_template_output = sprintf( $count_template, (int) $total_blocks, (int) $selected_blocks );
                            ?>
                            <p
                                class="visibloc-supported-blocks-count"
                                data-visibloc-blocks-count
                                data-visibloc-count-template="<?php echo $count_template_attr; ?>"
                                aria-live="polite"
                                role="status"
                            >
                                <?php echo esc_html( $count_template_output ); ?>
                            </p>
                            <p class="visibloc-supported-blocks-empty" data-visibloc-blocks-empty hidden>
                                <?php esc_html_e( 'Aucun bloc ne correspond à votre recherche.', 'visi-bloc-jlg' ); ?>
                            </p>
                        </div>
                    </fieldset>
                <?php endif; ?>
                <?php wp_nonce_field( 'visibloc_save_supported_blocks', 'visibloc_nonce' ); ?>
                <?php submit_button( __( 'Enregistrer les blocs compatibles', 'visi-bloc-jlg' ) ); ?>
            </form>
        </div>
    </div>
    <?php
}

function visibloc_jlg_render_permissions_section( $allowed_roles ) {
    if ( ! is_array( $allowed_roles ) ) {
        return;
    }

    $section_id = 'visibloc-section-permissions';

    ?>
    <div
        id="<?php echo esc_attr( $section_id ); ?>"
        class="postbox"
        data-visibloc-section="<?php echo esc_attr( $section_id ); ?>"
    >
        <h2 class="hndle"><span><?php esc_html_e( "Permissions d'Aperçu", 'visi-bloc-jlg' ); ?></span></h2>
        <div class="inside">
            <form method="POST" action="">
                <p><?php esc_html_e( 'Cochez les rôles qui peuvent voir les blocs cachés/programmés sur le site public.', 'visi-bloc-jlg' ); ?></p>
                <?php
                $editable_roles = get_editable_roles();
                foreach ( $editable_roles as $slug => $details ) :
                    $is_disabled = ( 'administrator' === $slug );
                    $is_checked  = ( in_array( $slug, $allowed_roles, true ) || $is_disabled );
                    ?>
                    <label style="display: block; margin-bottom: 5px;">
                        <input type="checkbox" name="visibloc_preview_roles[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $is_checked ); ?> <?php disabled( $is_disabled ); ?> />
                        <?php echo esc_html( $details['name'] ); ?>
                        <?php if ( $is_disabled ) { printf( ' %s', esc_html__( '(toujours activé)', 'visi-bloc-jlg' ) ); } ?>
                    </label>
                <?php endforeach; ?>
                <?php wp_nonce_field( 'visibloc_save_permissions', 'visibloc_nonce' ); ?>
                <?php submit_button( __( 'Enregistrer les Permissions', 'visi-bloc-jlg' ) ); ?>
            </form>
        </div>
    </div>
    <?php
}

function visibloc_jlg_render_notifications_section( $notifications ) {
    $section_id = 'visibloc-section-notifications';
    $level_labels = [
        'critical' => __( 'Critique', 'visi-bloc-jlg' ),
        'warning'  => __( 'Alerte', 'visi-bloc-jlg' ),
        'info'     => __( 'Information', 'visi-bloc-jlg' ),
    ];

    ?>
    <div
        id="<?php echo esc_attr( $section_id ); ?>"
        class="postbox"
        data-visibloc-section="<?php echo esc_attr( $section_id ); ?>"
    >
        <h2 class="hndle"><span><?php esc_html_e( 'Centre de notifications', 'visi-bloc-jlg' ); ?></span></h2>
        <div class="inside">
            <div class="visibloc-notifications">
                <?php if ( empty( $notifications ) ) : ?>
                    <p class="visibloc-notifications__empty">
                        <?php esc_html_e( 'Aucune alerte critique détectée pour le moment.', 'visi-bloc-jlg' ); ?>
                    </p>
                <?php else : ?>
                    <ul class="visibloc-notifications__list">
                        <?php foreach ( $notifications as $notification ) :
                            $level = isset( $notification['level'] ) && is_string( $notification['level'] )
                                ? strtolower( $notification['level'] )
                                : 'info';

                            if ( ! isset( $level_labels[ $level ] ) ) {
                                $level = 'info';
                            }

                            $items   = isset( $notification['items'] ) && is_array( $notification['items'] )
                                ? $notification['items']
                                : [];
                            $actions = isset( $notification['actions'] ) && is_array( $notification['actions'] )
                                ? $notification['actions']
                                : [];
                            ?>
                            <li class="visibloc-notification visibloc-notification--<?php echo esc_attr( $level ); ?>">
                                <div class="visibloc-notification__header">
                                    <span class="visibloc-notification__badge">
                                        <?php echo esc_html( $level_labels[ $level ] ); ?>
                                    </span>
                                    <h3 class="visibloc-notification__title">
                                        <?php echo esc_html( $notification['title'] ?? '' ); ?>
                                    </h3>
                                </div>
                                <?php if ( ! empty( $notification['description'] ) ) : ?>
                                    <p class="visibloc-notification__description">
                                        <?php echo esc_html( $notification['description'] ); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if ( ! empty( $items ) ) : ?>
                                    <ul class="visibloc-notification__items">
                                        <?php foreach ( array_slice( $items, 0, 5 ) as $item ) :
                                            $item_label = isset( $item['label'] ) ? (string) $item['label'] : '';
                                            $item_url   = isset( $item['url'] ) ? (string) $item['url'] : '';
                                            ?>
                                            <li>
                                                <?php if ( '' !== $item_url ) : ?>
                                                    <a href="<?php echo esc_url( $item_url ); ?>">
                                                        <?php echo esc_html( $item_label ); ?>
                                                    </a>
                                                <?php else : ?>
                                                    <span><?php echo esc_html( $item_label ); ?></span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <?php if ( ! empty( $actions ) ) : ?>
                                    <div class="visibloc-notification__actions">
                                        <?php foreach ( array_slice( $actions, 0, 2 ) as $action ) :
                                            $variant = isset( $action['variant'] ) && 'primary' === $action['variant']
                                                ? 'button-primary'
                                                : 'button-secondary';
                                            ?>
                                            <a
                                                class="button <?php echo esc_attr( $variant ); ?>"
                                                href="<?php echo esc_url( $action['url'] ?? '' ); ?>"
                                            >
                                                <?php echo esc_html( $action['label'] ?? '' ); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

function visibloc_jlg_render_insights_dashboard_section() {
    $section_id = 'visibloc-section-insights';
    $model      = visibloc_jlg_get_insight_dashboard_model();
    $tracked    = isset( $model['totals']['tracked'] ) ? (int) $model['totals']['tracked'] : 0;

    $metrics = [
        [
            'label'       => __( 'Rendus suivis', 'visi-bloc-jlg' ),
            'value'       => $model['totals']['tracked_display'] ?? '0',
            'description' => ! empty( $model['totals']['updated_human'] )
                ? sprintf( __( 'Dernière mise à jour %s.', 'visi-bloc-jlg' ), $model['totals']['updated_human'] )
                : __( 'Les données seront affichées après les prochaines visites.', 'visi-bloc-jlg' ),
        ],
        [
            'label'       => __( 'Fallback servi', 'visi-bloc-jlg' ),
            'value'       => visibloc_jlg_format_insight_number( $model['counters']['fallback'] ?? 0 ),
            'description' => sprintf( __( '%s des expositions conditionnelles', 'visi-bloc-jlg' ), $model['rates']['fallback_display'] ?? '0 %' ),
        ],
        [
            'label'       => __( 'Bloc masqué', 'visi-bloc-jlg' ),
            'value'       => visibloc_jlg_format_insight_number( $model['counters']['hidden'] ?? 0 ),
            'description' => sprintf( __( '%s des expositions conditionnelles', 'visi-bloc-jlg' ), $model['rates']['hidden_display'] ?? '0 %' ),
        ],
        [
            'label'       => __( 'Aperçus éditeur', 'visi-bloc-jlg' ),
            'value'       => visibloc_jlg_format_insight_number( $model['counters']['preview'] ?? 0 ),
            'description' => sprintf( __( '%s des événements enregistrés', 'visi-bloc-jlg' ), $model['rates']['preview_display'] ?? '0 %' ),
        ],
    ];

    ?>
    <div
        id="<?php echo esc_attr( $section_id ); ?>"
        class="postbox"
        data-visibloc-section="<?php echo esc_attr( $section_id ); ?>"
    >
        <h2 class="hndle"><span><?php esc_html_e( 'Insights d’affichage', 'visi-bloc-jlg' ); ?></span></h2>
        <div class="inside">
            <?php if ( $tracked <= 0 ) : ?>
                <p><?php esc_html_e( 'Aucune donnée n’a encore été collectée. Les insights apparaîtront dès qu’un bloc conditionnel sera affiché sur le site public.', 'visi-bloc-jlg' ); ?></p>
            <?php else : ?>
                <div class="visibloc-insights-metrics">
                    <?php foreach ( $metrics as $metric ) : ?>
                        <div class="visibloc-insights-metric">
                            <span class="visibloc-insights-metric__label"><?php echo esc_html( $metric['label'] ); ?></span>
                            <span class="visibloc-insights-metric__value"><?php echo esc_html( $metric['value'] ); ?></span>
                            <?php if ( ! empty( $metric['description'] ) ) : ?>
                                <span class="visibloc-insights-metric__description"><?php echo esc_html( $metric['description'] ); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ( ! empty( $model['reasons'] ) ) : ?>
                    <h3 class="visibloc-insights-subtitle"><?php esc_html_e( 'Principales raisons de masquage', 'visi-bloc-jlg' ); ?></h3>
                    <ul class="visibloc-insights-reasons">
                        <?php foreach ( $model['reasons'] as $reason ) : ?>
                            <li class="visibloc-insights-reasons__item">
                                <span class="visibloc-insights-reasons__label"><?php echo esc_html( $reason['label'] ); ?></span>
                                <span class="visibloc-insights-reasons__count"><?php echo esc_html( $reason['count_display'] ); ?></span>
                                <?php if ( ! empty( $reason['percentage_display'] ) ) : ?>
                                    <span class="visibloc-insights-reasons__percentage"><?php echo esc_html( $reason['percentage_display'] ); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <h3 class="visibloc-insights-subtitle"><?php esc_html_e( 'Événements récents', 'visi-bloc-jlg' ); ?></h3>
                <?php if ( empty( $model['events'] ) ) : ?>
                    <p><?php esc_html_e( 'Aucun événement récent à afficher pour le moment.', 'visi-bloc-jlg' ); ?></p>
                <?php else : ?>
                    <div class="visibloc-admin-table-wrapper">
                        <table class="wp-list-table widefat striped visibloc-insights-table">
                            <thead>
                                <tr>
                                    <th scope="col"><?php esc_html_e( 'Événement', 'visi-bloc-jlg' ); ?></th>
                                    <th scope="col"><?php esc_html_e( 'Raison', 'visi-bloc-jlg' ); ?></th>
                                    <th scope="col"><?php esc_html_e( 'Cible', 'visi-bloc-jlg' ); ?></th>
                                    <th scope="col"><?php esc_html_e( 'Horodatage', 'visi-bloc-jlg' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $model['events'] as $event ) : ?>
                                    <tr>
                                        <td>
                                            <span class="visibloc-insights-event-label"><?php echo esc_html( $event['event_label'] ); ?></span>
                                            <?php if ( ! empty( $event['is_preview'] ) ) : ?>
                                                <span class="visibloc-insights-badge visibloc-insights-badge--preview"><?php esc_html_e( 'Aperçu', 'visi-bloc-jlg' ); ?></span>
                                            <?php endif; ?>
                                            <?php if ( ! empty( $event['uses_fallback'] ) ) : ?>
                                                <span class="visibloc-insights-badge visibloc-insights-badge--fallback"><?php esc_html_e( 'Fallback', 'visi-bloc-jlg' ); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="visibloc-insights-reason-label"><?php echo esc_html( $event['reason_label'] ); ?></span>
                                        </td>
                                        <td>
                                            <span class="visibloc-insights-target-block"><?php echo esc_html( $event['block_label'] ); ?></span>
                                            <?php if ( '' !== $event['post_title'] ) : ?>
                                                <span class="visibloc-insights-target-post">
                                                    <?php if ( '' !== $event['post_link'] ) : ?>
                                                        <a href="<?php echo esc_url( $event['post_link'] ); ?>"><?php echo esc_html( $event['post_title'] ); ?></a>
                                                    <?php else : ?>
                                                        <?php echo esc_html( $event['post_title'] ); ?>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="visibloc-insights-time-primary"><?php echo esc_html( $event['relative_time'] ?: '—' ); ?></span>
                                            <?php if ( ! empty( $event['absolute_time'] ) ) : ?>
                                                <span class="visibloc-insights-time-secondary"><?php echo esc_html( $event['absolute_time'] ); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function visibloc_jlg_render_hidden_blocks_section( $hidden_posts ) {
    $grouped_hidden_posts = visibloc_jlg_group_posts_by_id( $hidden_posts );

    $section_id = 'visibloc-section-hidden';

    ?>
    <div
        id="<?php echo esc_attr( $section_id ); ?>"
        class="postbox"
        data-visibloc-section="<?php echo esc_attr( $section_id ); ?>"
    >
        <h2 class="hndle"><span><?php esc_html_e( 'Tableau de bord des blocs masqués (via Œil)', 'visi-bloc-jlg' ); ?></span></h2>
        <div class="inside">
            <?php if ( empty( $grouped_hidden_posts ) ) : ?>
                <p><?php esc_html_e( "Aucun bloc masqué manuellement n'a été trouvé.", 'visi-bloc-jlg' ); ?></p>
            <?php else : ?>
                <ul class="visibloc-admin-post-list">
                    <?php foreach ( $grouped_hidden_posts as $post_data ) :
                        $block_count = isset( $post_data['block_count'] ) ? (int) $post_data['block_count'] : 0;
                        $label       = $post_data['title'] ?? '';

                        if ( $block_count > 1 ) {
                            /* translators: 1: Post title. 2: Number of blocks. */
                            $label = sprintf( __( '%1$s (%2$d blocs)', 'visi-bloc-jlg' ), $label, $block_count );
                        }
                        ?>
                        <li><a href="<?php echo esc_url( $post_data['link'] ?? '' ); ?>"><?php echo esc_html( $label ); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function visibloc_jlg_render_device_visibility_section( $device_posts ) {
    $grouped_device_posts = visibloc_jlg_group_posts_by_id( $device_posts );

    $section_id = 'visibloc-section-device';

    ?>
    <div
        id="<?php echo esc_attr( $section_id ); ?>"
        class="postbox"
        data-visibloc-section="<?php echo esc_attr( $section_id ); ?>"
    >
        <h2 class="hndle"><span><?php esc_html_e( 'Tableau de bord des blocs avec visibilité par appareil', 'visi-bloc-jlg' ); ?></span></h2>
        <div class="inside">
            <?php if ( empty( $grouped_device_posts ) ) : ?>
                <p><?php esc_html_e( "Aucun bloc avec une règle de visibilité par appareil n'a été trouvé.", 'visi-bloc-jlg' ); ?></p>
            <?php else : ?>
                <ul class="visibloc-admin-post-list">
                    <?php foreach ( $grouped_device_posts as $post_data ) :
                        $block_count = isset( $post_data['block_count'] ) ? (int) $post_data['block_count'] : 0;
                        $label       = $post_data['title'] ?? '';

                        if ( $block_count > 1 ) {
                            /* translators: 1: Post title. 2: Number of blocks. */
                            $label = sprintf( __( '%1$s (%2$d blocs)', 'visi-bloc-jlg' ), $label, $block_count );
                        }
                        ?>
                        <li><a href="<?php echo esc_url( $post_data['link'] ?? '' ); ?>"><?php echo esc_html( $label ); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function visibloc_jlg_render_scheduled_blocks_section( $scheduled_posts ) {
    $datetime_format = visibloc_jlg_get_wp_datetime_format();

    $title_column_label = __( "Titre de l'article / Modèle", 'visi-bloc-jlg' );
    $start_column_label = __( 'Date de début', 'visi-bloc-jlg' );
    $end_column_label   = __( 'Date de fin', 'visi-bloc-jlg' );

    $section_id = 'visibloc-section-scheduled';

    ?>
    <div
        id="<?php echo esc_attr( $section_id ); ?>"
        class="postbox"
        data-visibloc-section="<?php echo esc_attr( $section_id ); ?>"
    >
        <h2 class="hndle"><span><?php esc_html_e( 'Tableau de bord des blocs programmés', 'visi-bloc-jlg' ); ?></span></h2>
        <div class="inside">
            <?php if ( empty( $scheduled_posts ) ) : ?>
                <p><?php esc_html_e( "Aucun bloc programmé n'a été trouvé sur votre site.", 'visi-bloc-jlg' ); ?></p>
            <?php else : ?>
                <div class="visibloc-admin-table-wrapper">
                    <table class="wp-list-table widefat striped visibloc-admin-scheduled-table">
                        <thead>
                            <tr>
                                <th scope="col"><?php echo esc_html( $title_column_label ); ?></th>
                                <th scope="col"><?php echo esc_html( $start_column_label ); ?></th>
                                <th scope="col"><?php echo esc_html( $end_column_label ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ( $scheduled_posts as $scheduled_block ) :
                            $start_datetime = visibloc_jlg_create_schedule_datetime( $scheduled_block['start'] ?? null );
                            $end_datetime   = visibloc_jlg_create_schedule_datetime( $scheduled_block['end'] ?? null );

                            $start_display = null !== $start_datetime ? wp_date( $datetime_format, $start_datetime->getTimestamp() ) : '–';
                            $end_display   = null !== $end_datetime ? wp_date( $datetime_format, $end_datetime->getTimestamp() ) : '–';
                            ?>
                            <tr>
                                <td>
                                    <span class="visibloc-table-label"><?php echo esc_html( $title_column_label ); ?></span>
                                    <a href="<?php echo esc_url( $scheduled_block['link'] ); ?>"><?php echo esc_html( $scheduled_block['title'] ); ?></a>
                                </td>
                                <td>
                                    <span class="visibloc-table-label"><?php echo esc_html( $start_column_label ); ?></span>
                                    <?php echo esc_html( $start_display ); ?>
                                </td>
                                <td>
                                    <span class="visibloc-table-label"><?php echo esc_html( $end_column_label ); ?></span>
                                    <?php echo esc_html( $end_display ); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function visibloc_jlg_render_debug_mode_section( $debug_status ) {
    $section_id = 'visibloc-section-debug';

    ?>
    <div
        id="<?php echo esc_attr( $section_id ); ?>"
        class="postbox"
        data-visibloc-section="<?php echo esc_attr( $section_id ); ?>"
    >
        <h2 class="hndle"><span><?php esc_html_e( 'Mode de débogage', 'visi-bloc-jlg' ); ?></span></h2>
        <div class="inside">
            <form method="POST" action="">
                <p>
                    <?php esc_html_e( 'Statut actuel :', 'visi-bloc-jlg' ); ?>
                    <strong><?php echo ( 'on' === $debug_status ) ? esc_html__( 'ACTIVÉ', 'visi-bloc-jlg' ) : esc_html__( 'DÉSACTIVÉ', 'visi-bloc-jlg' ); ?></strong>
                </p>
                <input type="hidden" name="action" value="visibloc_toggle_debug">
                <?php wp_nonce_field( 'visibloc_toggle_debug', 'visibloc_nonce' ); ?>
                <button type="submit" class="button button-primary"><?php echo ( 'on' === $debug_status ) ? esc_html__( 'Désactiver', 'visi-bloc-jlg' ) : esc_html__( 'Activer', 'visi-bloc-jlg' ); ?></button>
            </form>
        </div>
    </div>
    <?php
}

function visibloc_jlg_render_settings_backup_section() {
    $section_id = 'visibloc-section-backup';

    ?>
    <div
        id="<?php echo esc_attr( $section_id ); ?>"
        class="postbox"
        data-visibloc-section="<?php echo esc_attr( $section_id ); ?>"
    >
        <h2 class="hndle"><span><?php esc_html_e( 'Export & sauvegarde', 'visi-bloc-jlg' ); ?></span></h2>
        <div class="inside">
            <p><?php esc_html_e( 'Exportez vos réglages pour les sauvegarder ou les transférer vers un autre site.', 'visi-bloc-jlg' ); ?></p>
            <form method="POST" action="" style="margin-bottom: 16px;">
                <input type="hidden" name="action" value="visibloc_export_settings">
                <?php wp_nonce_field( 'visibloc_export_settings', 'visibloc_nonce' ); ?>
                <?php submit_button( __( 'Exporter les réglages', 'visi-bloc-jlg' ), 'secondary', 'submit', false ); ?>
            </form>
            <hr />
            <p><?php esc_html_e( 'Collez ci-dessous un export JSON précédemment généré pour restaurer vos réglages globaux.', 'visi-bloc-jlg' ); ?></p>
            <form method="POST" action="">
                <textarea name="visibloc_settings_payload" rows="7" class="large-text code" required aria-describedby="visibloc_settings_import_help"></textarea>
                <p id="visibloc_settings_import_help" class="description">
                    <?php esc_html_e( 'Le contenu doit correspondre au fichier JSON exporté depuis Visi-Bloc.', 'visi-bloc-jlg' ); ?>
                </p>
                <input type="hidden" name="action" value="visibloc_import_settings">
                <?php wp_nonce_field( 'visibloc_import_settings', 'visibloc_nonce' ); ?>
                <?php submit_button( __( 'Importer les réglages', 'visi-bloc-jlg' ) ); ?>
            </form>
        </div>
    </div>
    <?php
}

function visibloc_jlg_render_breakpoints_section( $mobile_bp, $tablet_bp ) {
    $breakpoints_requirement_message = visibloc_jlg_get_breakpoints_requirement_message();
    $breakpoints_help_id             = 'visibloc_breakpoints_help';

    $section_id = 'visibloc-section-breakpoints';

    ?>
    <div
        id="<?php echo esc_attr( $section_id ); ?>"
        class="postbox"
        data-visibloc-section="<?php echo esc_attr( $section_id ); ?>"
    >
        <h2 class="hndle"><span><?php esc_html_e( 'Réglage des points de rupture', 'visi-bloc-jlg' ); ?></span></h2>
        <div class="inside">
            <form method="POST" action="">
                <p><?php esc_html_e( "Alignez les largeurs d'écran avec celles de votre thème.", 'visi-bloc-jlg' ); ?></p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="visibloc_breakpoint_mobile"><?php esc_html_e( 'Largeur max. mobile', 'visi-bloc-jlg' ); ?></label></th>
                        <td><input name="visibloc_breakpoint_mobile" type="number" id="visibloc_breakpoint_mobile" value="<?php echo esc_attr( $mobile_bp ); ?>" class="small-text" min="1" step="1" inputmode="numeric" aria-describedby="<?php echo esc_attr( $breakpoints_help_id ); ?>"> <?php esc_html_e( 'px', 'visi-bloc-jlg' ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="visibloc_breakpoint_tablet"><?php esc_html_e( 'Largeur max. tablette', 'visi-bloc-jlg' ); ?></label></th>
                        <td><input name="visibloc_breakpoint_tablet" type="number" id="visibloc_breakpoint_tablet" value="<?php echo esc_attr( $tablet_bp ); ?>" class="small-text" min="1" step="1" inputmode="numeric" aria-describedby="<?php echo esc_attr( $breakpoints_help_id ); ?>"> <?php esc_html_e( 'px', 'visi-bloc-jlg' ); ?></td>
                    </tr>
                </table>
                <p id="<?php echo esc_attr( $breakpoints_help_id ); ?>" class="description"><?php echo esc_html( $breakpoints_requirement_message ); ?></p>
                <input type="hidden" name="action" value="visibloc_save_breakpoints">
                <?php wp_nonce_field( 'visibloc_save_breakpoints', 'visibloc_nonce' ); ?>
                <?php submit_button( __( 'Enregistrer les breakpoints', 'visi-bloc-jlg' ) ); ?>
            </form>
        </div>
    </div>
    <?php
}

function visibloc_jlg_render_fallback_section( $fallback_settings, $fallback_blocks ) {
    $fallback_settings = visibloc_jlg_normalize_fallback_settings( $fallback_settings );
    $fallback_mode     = $fallback_settings['mode'];
    $fallback_text     = $fallback_settings['text'];
    $fallback_block_id = $fallback_settings['block_id'];
    $has_blocks        = ! empty( $fallback_blocks );
    $fallback_mode_help_id  = 'visibloc_fallback_mode_help';
    $fallback_text_help_id  = 'visibloc_fallback_text_help';
    $fallback_block_help_id = 'visibloc_fallback_block_help';

    $section_id = 'visibloc-section-fallback';

    ?>
    <div
        id="<?php echo esc_attr( $section_id ); ?>"
        class="postbox"
        data-visibloc-section="<?php echo esc_attr( $section_id ); ?>"
    >
        <h2 class="hndle"><span><?php esc_html_e( 'Contenu de repli global', 'visi-bloc-jlg' ); ?></span></h2>
        <div class="inside">
            <form method="POST" action="">
                <p><?php esc_html_e( 'Définissez le contenu affiché aux visiteurs lorsque l’accès à un bloc est restreint.', 'visi-bloc-jlg' ); ?></p>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="visibloc_fallback_mode"><?php esc_html_e( 'Type de repli', 'visi-bloc-jlg' ); ?></label>
                        </th>
                        <td>
                            <select
                                name="visibloc_fallback_mode"
                                id="visibloc_fallback_mode"
                                aria-describedby="<?php echo esc_attr( $fallback_mode_help_id ); ?>"
                            >
                                <option value="none" <?php selected( 'none', $fallback_mode ); ?>><?php esc_html_e( 'Aucun', 'visi-bloc-jlg' ); ?></option>
                                <option value="text" <?php selected( 'text', $fallback_mode ); ?>><?php esc_html_e( 'Texte personnalisé', 'visi-bloc-jlg' ); ?></option>
                                <option value="block" <?php selected( 'block', $fallback_mode ); ?>><?php esc_html_e( 'Bloc réutilisable', 'visi-bloc-jlg' ); ?></option>
                            </select>
                            <p id="<?php echo esc_attr( $fallback_mode_help_id ); ?>" class="description"><?php esc_html_e( 'Ce paramètre peut être surchargé bloc par bloc dans l’éditeur.', 'visi-bloc-jlg' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="visibloc_fallback_text"><?php esc_html_e( 'Texte de repli', 'visi-bloc-jlg' ); ?></label>
                        </th>
                        <td>
                            <textarea
                                name="visibloc_fallback_text"
                                id="visibloc_fallback_text"
                                rows="5"
                                class="large-text"
                                aria-describedby="<?php echo esc_attr( $fallback_text_help_id ); ?>"
                            ><?php echo esc_textarea( $fallback_text ); ?></textarea>
                            <p id="<?php echo esc_attr( $fallback_text_help_id ); ?>" class="description"><?php esc_html_e( 'Ce contenu est utilisé lorsque le type « Texte personnalisé » est sélectionné.', 'visi-bloc-jlg' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="visibloc_fallback_block_id"><?php esc_html_e( 'Bloc de substitution', 'visi-bloc-jlg' ); ?></label>
                        </th>
                        <td>
                            <select
                                name="visibloc_fallback_block_id"
                                id="visibloc_fallback_block_id"
                                class="regular-text"
                                aria-describedby="<?php echo esc_attr( $fallback_block_help_id ); ?>"
                            >
                                <option value="0" <?php selected( 0, $fallback_block_id ); ?>><?php esc_html_e( '— Sélectionnez un bloc —', 'visi-bloc-jlg' ); ?></option>
                                <?php foreach ( $fallback_blocks as $block ) :
                                    $value = isset( $block['value'] ) ? (int) $block['value'] : 0;
                                    $label = isset( $block['label'] ) ? $block['label'] : '';

                                    if ( 0 === $value ) {
                                        continue;
                                    }
                                    ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $fallback_block_id ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ( ! $has_blocks ) : ?>
                                <p id="<?php echo esc_attr( $fallback_block_help_id ); ?>" class="description"><?php esc_html_e( 'Aucun bloc réutilisable publié n’a été trouvé.', 'visi-bloc-jlg' ); ?></p>
                            <?php else : ?>
                                <p id="<?php echo esc_attr( $fallback_block_help_id ); ?>" class="description"><?php esc_html_e( 'Utilisé lorsque le type « Bloc réutilisable » est sélectionné.', 'visi-bloc-jlg' ); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <input type="hidden" name="action" value="visibloc_save_fallback">
                <?php wp_nonce_field( 'visibloc_save_fallback', 'visibloc_nonce' ); ?>
                <?php submit_button( __( 'Enregistrer le repli', 'visi-bloc-jlg' ) ); ?>
            </form>
        </div>
    </div>
    <?php
}

function visibloc_jlg_get_breakpoints_requirement_message() {
    return __( 'Les valeurs de breakpoint doivent être des nombres positifs et la tablette doit être supérieure au mobile.', 'visi-bloc-jlg' );
}

function visibloc_jlg_group_posts_by_id( $posts ) {
    if ( ! is_array( $posts ) ) {
        return [];
    }

    $grouped_posts = [];

    foreach ( $posts as $post_data ) {
        if ( ! is_array( $post_data ) ) {
            continue;
        }

        $post_id = isset( $post_data['id'] ) ? absint( $post_data['id'] ) : 0;

        if ( 0 === $post_id ) {
            continue;
        }

        if ( ! isset( $grouped_posts[ $post_id ] ) ) {
            $grouped_posts[ $post_id ] = [
                'id'          => $post_id,
                'title'       => $post_data['title'] ?? '',
                'link'        => $post_data['link'] ?? '',
                'block_count' => 0,
            ];
        }

        $increment = isset( $post_data['block_count'] ) ? (int) $post_data['block_count'] : 1;
        if ( $increment < 1 ) {
            $increment = 1;
        }

        $grouped_posts[ $post_id ]['block_count'] += $increment;
    }

    return array_values( $grouped_posts );
}

function visibloc_jlg_find_blocks_recursive( $blocks, $callback, &$found_blocks = null ) {
    if ( null === $found_blocks ) {
        $found_blocks = [];
    }

    if ( empty( $blocks ) || ! is_array( $blocks ) ) {
        return $found_blocks;
    }

    foreach ( $blocks as $block ) {
        if ( $callback( $block ) ) {
            $found_blocks[] = $block;
        }

        if ( ! empty( $block['innerBlocks'] ) ) {
            visibloc_jlg_find_blocks_recursive( $block['innerBlocks'], $callback, $found_blocks );
        }
    }

    return $found_blocks;
}

function visibloc_jlg_generate_group_block_summary_from_content( $post_id, $post_content = null, $block_matcher = null ) {
    if ( null === $post_content ) {
        $post_content = get_post_field( 'post_content', $post_id );
    }

    if ( ! is_string( $post_content ) || '' === $post_content || false === strpos( $post_content, '<!-- wp:' ) ) {
        return [
            'hidden'    => 0,
            'device'    => 0,
            'scheduled' => [],
        ];
    }

    $blocks = parse_blocks( $post_content );

    if ( ! is_callable( $block_matcher ) ) {
        $supported_blocks = [];

        if ( function_exists( 'visibloc_jlg_get_supported_blocks' ) ) {
            $maybe_supported_blocks = visibloc_jlg_get_supported_blocks();

            if ( is_array( $maybe_supported_blocks ) ) {
                $supported_blocks = $maybe_supported_blocks;
            }
        }

        $supported_lookup = [];

        foreach ( $supported_blocks as $block_name ) {
            if ( ! is_string( $block_name ) ) {
                continue;
            }

            $normalized_name = trim( $block_name );

            if ( '' === $normalized_name ) {
                continue;
            }

            $supported_lookup[ $normalized_name ] = true;
        }

        if ( empty( $supported_lookup ) ) {
            return [
                'hidden'    => 0,
                'device'    => 0,
                'scheduled' => [],
            ];
        }

        $block_matcher = static function( $block ) use ( $supported_lookup ) {
            if ( ! is_array( $block ) ) {
                return false;
            }

            $block_name = $block['blockName'] ?? '';

            if ( ! is_string( $block_name ) || '' === $block_name ) {
                return false;
            }

            return isset( $supported_lookup[ $block_name ] );
        };
    }

    $found = visibloc_jlg_find_blocks_recursive( $blocks, $block_matcher );

    if ( empty( $found ) ) {
        return [
            'hidden'    => 0,
            'device'    => 0,
            'scheduled' => [],
        ];
    }

    $hidden_count = 0;
    $device_count = 0;
    $scheduled    = [];

    foreach ( $found as $block ) {
        $attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : [];

        $is_hidden = isset( $attrs['isHidden'] )
            ? visibloc_jlg_normalize_boolean( $attrs['isHidden'] )
            : false;

        if ( $is_hidden ) {
            $hidden_count++;
        }

        $device_visibility = '';

        if ( array_key_exists( 'deviceVisibility', $attrs ) && is_scalar( $attrs['deviceVisibility'] ) ) {
            $device_visibility = trim( (string) $attrs['deviceVisibility'] );
        }

        if ( '' !== $device_visibility && 'all' !== $device_visibility ) {
            $device_count++;
        }

        $schedule_start = null;
        $schedule_end   = null;

        if ( array_key_exists( 'publishStartDate', $attrs ) && is_scalar( $attrs['publishStartDate'] ) ) {
            $schedule_start = (string) $attrs['publishStartDate'];
        }

        if ( array_key_exists( 'publishEndDate', $attrs ) && is_scalar( $attrs['publishEndDate'] ) ) {
            $schedule_end = (string) $attrs['publishEndDate'];
        }

        $has_scheduling_window = ( null !== $schedule_start || null !== $schedule_end );

        $has_scheduling_enabled = isset( $attrs['isSchedulingEnabled'] )
            ? visibloc_jlg_normalize_boolean( $attrs['isSchedulingEnabled'] )
            : false;

        if ( $has_scheduling_enabled && $has_scheduling_window ) {
            $scheduled[] = [
                'start' => $schedule_start,
                'end'   => $schedule_end,
            ];
        }
    }

    return [
        'hidden'    => $hidden_count,
        'device'    => $device_count,
        'scheduled' => $scheduled,
    ];
}

function visibloc_jlg_group_block_summary_has_data( $summary ) {
    if ( ! is_array( $summary ) ) {
        return false;
    }

    if ( ! empty( $summary['hidden'] ) ) {
        return true;
    }

    if ( ! empty( $summary['device'] ) ) {
        return true;
    }

    if ( ! empty( $summary['scheduled'] ) && is_array( $summary['scheduled'] ) ) {
        return ! empty( $summary['scheduled'] );
    }

    return false;
}

function visibloc_jlg_get_group_block_summary_index() {
    $stored = get_option( 'visibloc_group_block_summary', [] );

    if ( ! is_array( $stored ) ) {
        return [];
    }

    return $stored;
}

function visibloc_jlg_store_group_block_summary_index( $index ) {
    if ( ! is_array( $index ) ) {
        $index = [];
    }

    update_option( 'visibloc_group_block_summary', $index, false );
}

function visibloc_jlg_rebuild_group_block_summary_index( &$scanned_posts = null ) {
    $post_types = apply_filters( 'visibloc_jlg_scanned_post_types', [ 'post', 'page', 'wp_template', 'wp_template_part' ] );
    $page       = 1;
    $summaries  = [];
    $scanned    = 0;

    while ( true ) {
        $query = new WP_Query( [
            'post_type'              => $post_types,
            'post_status'            => [ 'publish', 'future', 'draft', 'pending', 'private' ],
            'posts_per_page'         => 100,
            'paged'                  => $page,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ] );

        if ( empty( $query->posts ) ) {
            break;
        }

        foreach ( $query->posts as $post_id ) {
            $scanned++;
            $summary = visibloc_jlg_generate_group_block_summary_from_content( $post_id );

            if ( visibloc_jlg_group_block_summary_has_data( $summary ) ) {
                $summaries[ $post_id ] = $summary;
            }
        }

        $page++;
    }

    visibloc_jlg_store_group_block_summary_index( $summaries );

    if ( func_num_args() > 0 ) {
        $scanned_posts = $scanned;
    }

    return $summaries;
}

function visibloc_jlg_refresh_group_block_summary_on_save( $post_id, $post, $update ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    $post_types = apply_filters( 'visibloc_jlg_scanned_post_types', [ 'post', 'page', 'wp_template', 'wp_template_part' ] );

    $post_object = $post instanceof WP_Post ? $post : get_post( $post_id );

    if ( ! $post_object || ! in_array( $post_object->post_type, $post_types, true ) ) {
        visibloc_jlg_remove_group_block_summary_for_post( $post_id );
        return;
    }

    $summary = visibloc_jlg_generate_group_block_summary_from_content( $post_id, $post_object->post_content );
    $index   = visibloc_jlg_get_group_block_summary_index();

    if ( visibloc_jlg_group_block_summary_has_data( $summary ) ) {
        $index[ $post_id ] = $summary;
    } else {
        unset( $index[ $post_id ] );
    }

    visibloc_jlg_store_group_block_summary_index( $index );
    visibloc_jlg_clear_caches();
}

function visibloc_jlg_remove_group_block_summary_for_post( $post_id ) {
    $post_id = absint( $post_id );

    if ( $post_id <= 0 ) {
        return;
    }

    $index = visibloc_jlg_get_group_block_summary_index();

    if ( isset( $index[ $post_id ] ) ) {
        unset( $index[ $post_id ] );
        visibloc_jlg_store_group_block_summary_index( $index );
        visibloc_jlg_clear_caches();
    }
}

function visibloc_jlg_collect_group_block_metadata() {
    $cache_key = 'visibloc_group_block_metadata';
    $cached    = get_transient( $cache_key );
    if ( false !== $cached && is_array( $cached ) ) {
        return $cached;
    }

    $collected = [
        'hidden'    => [],
        'device'    => [],
        'scheduled' => [],
    ];

    $summaries = visibloc_jlg_get_group_block_summary_index();

    if ( empty( $summaries ) ) {
        $summaries = visibloc_jlg_rebuild_group_block_summary_index();
    }

    static $post_title_cache = [];
    static $post_link_cache  = [];

    foreach ( $summaries as $post_id => $summary ) {
        $post_id = absint( $post_id );

        if ( $post_id <= 0 ) {
            continue;
        }

        if ( ! array_key_exists( $post_id, $post_title_cache ) ) {
            $post_title_cache[ $post_id ] = get_the_title( $post_id );
        }

        if ( ! array_key_exists( $post_id, $post_link_cache ) ) {
            $post_link_cache[ $post_id ] = get_edit_post_link( $post_id );
        }

        $post_title = $post_title_cache[ $post_id ];
        $post_link  = $post_link_cache[ $post_id ];

        if ( ! empty( $summary['hidden'] ) ) {
            $collected['hidden'][] = [
                'id'          => $post_id,
                'title'       => $post_title,
                'link'        => $post_link,
                'block_count' => (int) $summary['hidden'],
            ];
        }

        if ( ! empty( $summary['device'] ) ) {
            $collected['device'][] = [
                'id'          => $post_id,
                'title'       => $post_title,
                'link'        => $post_link,
                'block_count' => (int) $summary['device'],
            ];
        }

        if ( ! empty( $summary['scheduled'] ) && is_array( $summary['scheduled'] ) ) {
            foreach ( $summary['scheduled'] as $schedule ) {
                if ( ! is_array( $schedule ) ) {
                    continue;
                }

                $collected['scheduled'][] = [
                    'id'    => $post_id,
                    'title' => $post_title,
                    'link'  => $post_link,
                    'start' => $schedule['start'] ?? null,
                    'end'   => $schedule['end'] ?? null,
                ];
            }
        }
    }

    if ( ! empty( $collected['scheduled'] ) ) {
        usort(
            $collected['scheduled'],
            static function ( $a, $b ) {
                $normalize_timestamp = static function ( $value ) {
                    if ( null === $value || '' === $value ) {
                        return null;
                    }

                    if ( is_numeric( $value ) ) {
                        return (int) $value;
                    }

                    $timestamp = strtotime( (string) $value );

                    return false === $timestamp ? null : $timestamp;
                };

                $a_start = $normalize_timestamp( $a['start'] ?? null );
                $b_start = $normalize_timestamp( $b['start'] ?? null );

                if ( null !== $a_start && null === $b_start ) {
                    return -1;
                }

                if ( null === $a_start && null !== $b_start ) {
                    return 1;
                }

                if ( $a_start !== $b_start ) {
                    return $a_start <=> $b_start;
                }

                $a_end = $normalize_timestamp( $a['end'] ?? null );
                $b_end = $normalize_timestamp( $b['end'] ?? null );

                if ( null !== $a_end && null === $b_end ) {
                    return -1;
                }

                if ( null === $a_end && null !== $b_end ) {
                    return 1;
                }

                return $a_end <=> $b_end;
            }
        );
    }

    set_transient( $cache_key, $collected, HOUR_IN_SECONDS );

    return $collected;
}

function visibloc_jlg_get_hidden_posts() {
    $collected = visibloc_jlg_collect_group_block_metadata();

    return isset( $collected['hidden'] ) ? $collected['hidden'] : [];
}

function visibloc_jlg_get_device_specific_posts() {
    $collected = visibloc_jlg_collect_group_block_metadata();

    return isset( $collected['device'] ) ? $collected['device'] : [];
}

function visibloc_jlg_get_scheduled_posts() {
    $collected = visibloc_jlg_collect_group_block_metadata();

    return isset( $collected['scheduled'] ) ? $collected['scheduled'] : [];
}

function visibloc_jlg_clear_caches( $unused_post_id = null ) {
    if ( function_exists( 'visibloc_jlg_invalidate_fallback_blocks_cache' ) ) {
        visibloc_jlg_invalidate_fallback_blocks_cache();
    }

    if ( function_exists( 'visibloc_jlg_clear_editor_data_cache' ) ) {
        visibloc_jlg_clear_editor_data_cache();
    }

    if ( function_exists( 'visibloc_jlg_invalidate_supported_blocks_cache' ) ) {
        visibloc_jlg_invalidate_supported_blocks_cache();
    }

    delete_transient( 'visibloc_hidden_posts' );
    delete_transient( 'visibloc_device_posts' );
    delete_transient( 'visibloc_scheduled_posts' );
    delete_transient( 'visibloc_group_block_metadata' );

    $bucket_keys_to_clear = [];

    if ( function_exists( 'get_option' ) ) {
        $registered_buckets = get_option( VISIBLOC_JLG_DEVICE_CSS_BUCKET_OPTION, [] );

        if ( is_array( $registered_buckets ) ) {
            $bucket_keys_to_clear = array_merge( $bucket_keys_to_clear, $registered_buckets );
        }
    }

    if ( function_exists( 'wp_cache_get' ) ) {
        $cached_css = wp_cache_get( VISIBLOC_JLG_DEVICE_CSS_CACHE_KEY, VISIBLOC_JLG_DEVICE_CSS_CACHE_GROUP );

        if ( is_array( $cached_css ) ) {
            $bucket_keys_to_clear = array_merge( $bucket_keys_to_clear, array_keys( $cached_css ) );
        }
    }

    if ( empty( $bucket_keys_to_clear ) ) {
        $default_mobile_bp = 781;
        $default_tablet_bp = 1024;
        $mobile_bp         = $default_mobile_bp;
        $tablet_bp         = $default_tablet_bp;

        if ( function_exists( 'get_option' ) ) {
            $mobile_bp = absint( get_option( 'visibloc_breakpoint_mobile', $default_mobile_bp ) );
            $tablet_bp = absint( get_option( 'visibloc_breakpoint_tablet', $default_tablet_bp ) );
        }

        $mobile_bp = $mobile_bp > 0 ? $mobile_bp : $default_mobile_bp;
        $tablet_bp = $tablet_bp > 0 ? $tablet_bp : $default_tablet_bp;
        $version   = visibloc_jlg_get_plugin_version();

        $bucket_keys_to_clear = [
            sprintf( '%s:%d:%d:%d', $version, 0, (int) $mobile_bp, (int) $tablet_bp ),
            sprintf( '%s:%d:%d:%d', $version, 1, (int) $mobile_bp, (int) $tablet_bp ),
        ];
    }

    if ( function_exists( 'delete_transient' ) ) {
        foreach ( array_unique( $bucket_keys_to_clear ) as $bucket_key ) {
            delete_transient( VISIBLOC_JLG_DEVICE_CSS_TRANSIENT_PREFIX . $bucket_key );
        }
    }

    if ( function_exists( 'delete_option' ) ) {
        delete_option( VISIBLOC_JLG_DEVICE_CSS_BUCKET_OPTION );
    }

    if ( function_exists( 'wp_cache_delete' ) ) {
        wp_cache_delete( VISIBLOC_JLG_DEVICE_CSS_CACHE_KEY, VISIBLOC_JLG_DEVICE_CSS_CACHE_GROUP );
    }
}

add_action( 'save_post', 'visibloc_jlg_refresh_group_block_summary_on_save', 20, 3 );
add_action( 'deleted_post', 'visibloc_jlg_remove_group_block_summary_for_post' );
add_action( 'trashed_post', 'visibloc_jlg_remove_group_block_summary_for_post' );
