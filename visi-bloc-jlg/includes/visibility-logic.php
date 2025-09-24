<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'render_block', 'visibloc_jlg_render_block_filter', 10, 2 );
function visibloc_jlg_render_block_filter( $block_content, $block ) {
    if ( empty( $block['blockName'] ) || 'core/group' !== $block['blockName'] ) { return $block_content; }
    if ( empty( $block['attrs'] ) ) { return $block_content; }
    $attrs = $block['attrs'];
    $is_admin_or_technical_request = function_exists( 'visibloc_jlg_is_admin_or_technical_request' )
        ? visibloc_jlg_is_admin_or_technical_request()
        : false;
    $is_preview_role_neutralized = $is_admin_or_technical_request;
    $effective_user_id = function_exists( 'visibloc_jlg_get_effective_user_id' ) ? visibloc_jlg_get_effective_user_id() : 0;
    $can_impersonate = $effective_user_id && function_exists( 'visibloc_jlg_is_user_allowed_to_impersonate' )
        ? visibloc_jlg_is_user_allowed_to_impersonate( $effective_user_id )
        : false;
    $can_preview_hidden_blocks = $effective_user_id && function_exists( 'visibloc_jlg_is_user_allowed_to_preview' )
        ? visibloc_jlg_is_user_allowed_to_preview( $effective_user_id )
        : false;
    $had_preview_permission = $can_preview_hidden_blocks;

    if ( function_exists( 'visibloc_jlg_get_allowed_preview_roles' ) ) {
        $allowed_preview_roles = visibloc_jlg_get_allowed_preview_roles();
    } else {
        $allowed_preview_roles = (array) get_option( 'visibloc_preview_roles', [ 'administrator' ] );
        $allowed_preview_roles = array_map( 'sanitize_key', $allowed_preview_roles );

        if ( empty( $allowed_preview_roles ) ) {
            $allowed_preview_roles = [ 'administrator' ];
        }
    }

    if ( $is_preview_role_neutralized ) {
        $preview_role = '';
    } elseif ( function_exists( 'visibloc_jlg_get_preview_role_from_cookie' ) ) {
        $preview_role = visibloc_jlg_get_preview_role_from_cookie();
    } else {
        $preview_role = isset( $_COOKIE['visibloc_preview_role'] ) ? sanitize_key( wp_unslash( $_COOKIE['visibloc_preview_role'] ) ) : '';
    }

    $preview_role = is_string( $preview_role ) ? $preview_role : '';

    if ( $can_preview_hidden_blocks && $is_preview_role_neutralized ) {
        $can_preview_hidden_blocks = false;
    }

    $should_apply_preview_role = false;

    if ( ! $is_preview_role_neutralized && '' !== $preview_role ) {
        if ( 'guest' === $preview_role ) {
            $can_preview_hidden_blocks = false;
            $should_apply_preview_role = ( $had_preview_permission || $can_impersonate );
        } else {
            if ( ! in_array( $preview_role, $allowed_preview_roles, true ) ) {
                $can_preview_hidden_blocks = false;
            }

            if ( ! $can_impersonate ) {
                $preview_role = '';
            } elseif ( ! get_role( $preview_role ) ) {
                $preview_role = '';
            } else {
                $should_apply_preview_role = true;
            }
        }
    }

    if ( '' === $preview_role ) {
        $should_apply_preview_role = false;
    }

    if ( ! empty( $attrs['isSchedulingEnabled'] ) ) {
        $current_time = current_time( 'timestamp', true );

        $start_time = visibloc_jlg_parse_schedule_datetime( $attrs['publishStartDate'] ?? null );
        $end_time   = visibloc_jlg_parse_schedule_datetime( $attrs['publishEndDate'] ?? null );

        $is_before_start = null !== $start_time && $current_time < $start_time;
        $is_after_end = null !== $end_time && $current_time > $end_time;
        if ( $is_before_start || $is_after_end ) {
            if ( $can_preview_hidden_blocks ) {
                $start_date_fr = $start_time ? wp_date( 'd/m/Y H:i', $start_time ) : __( 'N/A', 'visi-bloc-jlg' );
                $end_date_fr = $end_time ? wp_date( 'd/m/Y H:i', $end_time ) : __( 'N/A', 'visi-bloc-jlg' );
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
    }

    if ( ! empty( $visibility_roles ) ) {
        $user = wp_get_current_user();
        $is_logged_in = $user->exists();
        $user_roles = (array) $user->roles;

        if ( $should_apply_preview_role ) {
            if ( 'guest' === $preview_role ) {
                $is_logged_in = false;
                $user_roles = [];
            } elseif ( '' !== $preview_role && get_role( $preview_role ) ) {
                $is_logged_in = true;
                $user_roles = [ $preview_role ];
            }
        }

        $is_visible = false;
        // Manual check: without preview access the cookie must not affect visibility.
        if ( in_array( 'logged-out', $visibility_roles, true ) && ! $is_logged_in ) $is_visible = true;
        if ( ! $is_visible && in_array( 'logged-in', $visibility_roles, true ) && $is_logged_in ) $is_visible = true;
        if ( ! $is_visible && ! empty( $user_roles ) && count( array_intersect( $user_roles, $visibility_roles ) ) > 0 ) { $is_visible = true; }
        if ( ! $is_visible ) return '';
    }
    
    if ( isset( $attrs['isHidden'] ) && $attrs['isHidden'] === true ) {
        if ( $can_preview_hidden_blocks ) {
            return sprintf(
                '<div class="bloc-cache-apercu" data-visibloc-label="%s">%s</div>',
                esc_attr__( 'Hidden block', 'visi-bloc-jlg' ),
                $block_content
            );
        }
        return '';
    }

    return $block_content;
}
