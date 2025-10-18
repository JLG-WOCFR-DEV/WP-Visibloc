<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'a11ytb_get_current_user_context' ) ) {
    /**
     * Provide a safe fallback for the a11y Toolbox Pro helper used on the front-end.
     *
     * @return array<string, mixed>
     */
    function a11ytb_get_current_user_context() {
        $locale        = function_exists( 'determine_locale' ) ? determine_locale() : ( function_exists( 'get_locale' ) ? get_locale() : '' );
        $anonymous_ctx = [
            'id'            => 0,
            'isLoggedIn'    => false,
            'is_logged_in'  => false,
            'roles'         => [],
            'caps'          => [],
            'capabilities'  => [],
            'locale'        => $locale,
        ];

        if ( ! function_exists( 'wp_get_current_user' ) ) {
            return function_exists( 'apply_filters' ) ? apply_filters( 'a11ytb_current_user_context', $anonymous_ctx ) : $anonymous_ctx;
        }

        $user = wp_get_current_user();

        if ( ! ( $user instanceof \WP_User ) || ! $user->exists() ) {
            return function_exists( 'apply_filters' ) ? apply_filters( 'a11ytb_current_user_context', $anonymous_ctx ) : $anonymous_ctx;
        }

        $roles = array_values( array_filter( (array) $user->roles, static function ( $role ) {
            return is_string( $role ) && '' !== $role;
        } ) );

        $caps = array_keys( array_filter( (array) $user->allcaps ) );

        $context = [
            'id'            => (int) $user->ID,
            'isLoggedIn'    => true,
            'is_logged_in'  => true,
            'roles'         => $roles,
            'caps'          => $caps,
            'capabilities'  => $caps,
            'locale'        => $locale,
        ];

        if ( function_exists( 'apply_filters' ) ) {
            return apply_filters( 'a11ytb_current_user_context', $context );
        }

        return $context;
    }
}
