<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function visibloc_jlg_get_supported_blocks() {
    static $supported_blocks = null;

    if ( null !== $supported_blocks ) {
        return $supported_blocks;
    }

    $default_blocks = [ 'core/group', 'core/columns' ];
    $filtered_blocks = apply_filters( 'visibloc_supported_blocks', $default_blocks );

    if ( ! is_array( $filtered_blocks ) ) {
        $supported_blocks = $default_blocks;

        return $supported_blocks;
    }

    $normalized = array_values( array_unique( array_filter( array_map(
        static function ( $block_name ) {
            if ( ! is_string( $block_name ) ) {
                return null;
            }

            $block_name = trim( $block_name );

            return '' === $block_name ? null : $block_name;
        },
        $filtered_blocks
    ) ) ) );

    $supported_blocks = ! empty( $normalized ) ? $normalized : $default_blocks;

    return $supported_blocks;
}

function visibloc_jlg_is_supported_block( $block_name ) {
    if ( ! is_string( $block_name ) || '' === $block_name ) {
        return false;
    }

    return in_array( $block_name, visibloc_jlg_get_supported_blocks(), true );
}

function visibloc_jlg_maybe_filter_rendered_block( $block_content, $block ) {
    $block_name = isset( $block['blockName'] ) ? $block['blockName'] : '';

    if ( ! visibloc_jlg_is_supported_block( $block_name ) ) {
        return $block_content;
    }

    return visibloc_jlg_render_block_filter( $block_content, $block );
}

add_filter( 'render_block', 'visibloc_jlg_maybe_filter_rendered_block', 10, 2 );
function visibloc_jlg_render_block_filter( $block_content, $block ) {
    if ( empty( $block['attrs'] ) ) { return $block_content; }

    $attrs = $block['attrs'];

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

    $has_hidden_flag      = isset( $attrs['isHidden'] ) ? visibloc_jlg_normalize_boolean( $attrs['isHidden'] ) : false;
    $has_schedule_enabled = isset( $attrs['isSchedulingEnabled'] ) ? visibloc_jlg_normalize_boolean( $attrs['isSchedulingEnabled'] ) : false;

    if ( ! $has_hidden_flag && ! $has_schedule_enabled && empty( $visibility_roles ) ) {
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
    $should_show_hidden_preview = $has_hidden_flag && $can_preview_hidden_blocks;
    $hidden_preview_markup = null;

    if ( $should_show_hidden_preview ) {
        $hidden_preview_markup = sprintf(
            '<div class="bloc-cache-apercu" data-visibloc-label="%s">%s</div>',
            esc_attr__( 'Hidden block', 'visi-bloc-jlg' ),
            $block_content
        );
    }

    if ( $has_schedule_enabled ) {
        $current_time = current_time( 'timestamp', true );

        $start_time = visibloc_jlg_parse_schedule_datetime( $attrs['publishStartDate'] ?? null );
        $end_time   = visibloc_jlg_parse_schedule_datetime( $attrs['publishEndDate'] ?? null );

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
                return '<div class="bloc-schedule-apercu" data-schedule-info="' . esc_attr( $info ) . '">' . $block_content . '</div>';
            }
            return '';
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
            return $should_show_hidden_preview ? $hidden_preview_markup : '';
        }
    }

    if ( $has_hidden_flag ) {
        return $should_show_hidden_preview ? $hidden_preview_markup : '';
    }

    return $block_content;
}
