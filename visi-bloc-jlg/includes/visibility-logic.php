<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'render_block', 'visibloc_jlg_render_block_filter', 10, 2 );
function visibloc_jlg_render_block_filter( $block_content, $block ) {
    if ( empty( $block['blockName'] ) || 'core/group' !== $block['blockName'] ) { return $block_content; }
    if ( empty( $block['attrs'] ) ) { return $block_content; }
    $attrs = $block['attrs'];
    $effective_user_id = function_exists( 'visibloc_jlg_get_effective_user_id' ) ? visibloc_jlg_get_effective_user_id() : 0;
    $is_legit_preview_requester = $effective_user_id && function_exists( 'visibloc_jlg_is_user_allowed_to_preview' )
        ? visibloc_jlg_is_user_allowed_to_preview( $effective_user_id )
        : false;

    if ( ! empty( $attrs['isSchedulingEnabled'] ) ) {
        $current_time = current_time( 'timestamp', true );

        $start_time = visibloc_jlg_parse_schedule_datetime( $attrs['publishStartDate'] ?? null );
        $end_time   = visibloc_jlg_parse_schedule_datetime( $attrs['publishEndDate'] ?? null );

        $is_before_start = null !== $start_time && $current_time < $start_time;
        $is_after_end = null !== $end_time && $current_time > $end_time;
        if ( $is_before_start || $is_after_end ) {
            if ( $is_legit_preview_requester ) {
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

    if ( ! empty( $attrs['visibilityRoles'] ) ) {
        $user = wp_get_current_user();
        $is_logged_in = $user->exists();
        $user_roles = (array) $user->roles;

        if ( $is_legit_preview_requester && isset( $_COOKIE['visibloc_preview_role'] ) ) {
            $preview_role = sanitize_key( wp_unslash( $_COOKIE['visibloc_preview_role'] ) );

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
        if ( in_array( 'logged-out', $attrs['visibilityRoles'] ) && ! $is_logged_in ) $is_visible = true;
        if ( ! $is_visible && in_array( 'logged-in', $attrs['visibilityRoles'] ) && $is_logged_in ) $is_visible = true;
        if ( ! $is_visible && ! empty( $user_roles ) && count( array_intersect( $user_roles, $attrs['visibilityRoles'] ) ) > 0 ) { $is_visible = true; }
        if ( ! $is_visible ) return '';
    }
    
    if ( isset( $attrs['isHidden'] ) && $attrs['isHidden'] === true ) {
        if ( $is_legit_preview_requester ) {
            return '<div class="bloc-cache-apercu">' . $block_content . '</div>';
        }
        return '';
    }

    return $block_content;
}
