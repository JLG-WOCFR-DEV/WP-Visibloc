<?php
/**
 * Editor preferences helpers and REST endpoints.
 *
 * @package VisiBlocJLG
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/utils.php';

/**
 * Retrieve the user meta key used to persist the editor mode preference.
 *
 * @return string
 */
function visibloc_jlg_get_editor_mode_meta_key() {
    return 'visibloc_editor_mode';
}

/**
 * Retrieve the user meta key used to persist the high visibility preference.
 *
 * @return string
 */
function visibloc_jlg_get_high_visibility_meta_key() {
    return 'visibloc_high_visibility';
}

/**
 * Return the default editor mode.
 *
 * @return string
 */
function visibloc_jlg_get_default_editor_mode() {
    return 'simple';
}

/**
 * Return the default value for the high visibility preference.
 *
 * @return bool
 */
function visibloc_jlg_get_default_high_visibility() {
    return false;
}

/**
 * List the supported editor modes.
 *
 * @return string[]
 */
function visibloc_jlg_get_supported_editor_modes() {
    return [ 'simple', 'expert' ];
}

/**
 * Normalize a high visibility value.
 *
 * @param mixed $value Raw value.
 *
 * @return bool
 */
function visibloc_jlg_normalize_high_visibility( $value ) {
    return visibloc_jlg_normalize_boolean_value( $value );
}

/**
 * Normalize a raw editor mode value.
 *
 * @param mixed $value Raw value.
 *
 * @return string
 */
function visibloc_jlg_normalize_editor_mode( $value ) {
    if ( is_string( $value ) ) {
        $value = strtolower( trim( $value ) );
    } else {
        $value = '';
    }

    return in_array( $value, visibloc_jlg_get_supported_editor_modes(), true )
        ? $value
        : visibloc_jlg_get_default_editor_mode();
}

/**
 * Retrieve the stored editor mode for a given user.
 *
 * @param int $user_id Optional user ID. Defaults to current user.
 *
 * @return string
 */
function visibloc_jlg_get_user_editor_mode( $user_id = 0 ) {
    if ( ! function_exists( 'get_current_user_id' ) ) {
        return visibloc_jlg_get_default_editor_mode();
    }

    $user_id = $user_id > 0 ? (int) $user_id : get_current_user_id();

    if ( $user_id <= 0 ) {
        return visibloc_jlg_get_default_editor_mode();
    }

    if ( ! function_exists( 'get_user_meta' ) ) {
        return visibloc_jlg_get_default_editor_mode();
    }

    $meta_value = get_user_meta( $user_id, visibloc_jlg_get_editor_mode_meta_key(), true );

    return visibloc_jlg_normalize_editor_mode( $meta_value );
}

/**
 * Retrieve the stored high visibility preference for a given user.
 *
 * @param int $user_id Optional user ID. Defaults to current user.
 *
 * @return bool
 */
function visibloc_jlg_get_user_high_visibility( $user_id = 0 ) {
    if ( ! function_exists( 'get_current_user_id' ) ) {
        return visibloc_jlg_get_default_high_visibility();
    }

    $user_id = $user_id > 0 ? (int) $user_id : get_current_user_id();

    if ( $user_id <= 0 ) {
        return visibloc_jlg_get_default_high_visibility();
    }

    if ( ! function_exists( 'get_user_meta' ) ) {
        return visibloc_jlg_get_default_high_visibility();
    }

    $meta_value = get_user_meta( $user_id, visibloc_jlg_get_high_visibility_meta_key(), true );

    if ( '' === $meta_value ) {
        return visibloc_jlg_get_default_high_visibility();
    }

    return visibloc_jlg_normalize_high_visibility( $meta_value );
}

/**
 * Persist the editor mode for a given user.
 *
 * @param int    $user_id User identifier.
 * @param string $mode    Editor mode.
 *
 * @return bool
 */
function visibloc_jlg_set_user_editor_mode( $user_id, $mode ) {
    if ( ! function_exists( 'update_user_meta' ) ) {
        return false;
    }

    $user_id = (int) $user_id;

    if ( $user_id <= 0 ) {
        return false;
    }

    $normalized_mode = visibloc_jlg_normalize_editor_mode( $mode );

    return false !== update_user_meta( $user_id, visibloc_jlg_get_editor_mode_meta_key(), $normalized_mode );
}

/**
 * Persist the high visibility preference for a given user.
 *
 * @param int  $user_id User identifier.
 * @param bool $enabled Whether high visibility should be enabled.
 *
 * @return bool
 */
function visibloc_jlg_set_user_high_visibility( $user_id, $enabled ) {
    if ( ! function_exists( 'update_user_meta' ) ) {
        return false;
    }

    $user_id = (int) $user_id;

    if ( $user_id <= 0 ) {
        return false;
    }

    $normalized_enabled = visibloc_jlg_normalize_high_visibility( $enabled );

    return false !== update_user_meta(
        $user_id,
        visibloc_jlg_get_high_visibility_meta_key(),
        $normalized_enabled ? '1' : '0'
    );
}

/**
 * Determine if the current user can manage editor preferences.
 *
 * @return bool
 */
function visibloc_jlg_can_manage_editor_preferences() {
    return function_exists( 'current_user_can' ) ? current_user_can( 'edit_posts' ) : false;
}

/**
 * Build the payload injected in the editor script.
 *
 * @return array<string, mixed>
 */
function visibloc_jlg_get_editor_preferences_payload() {
    return [
        'mode' => visibloc_jlg_get_user_editor_mode(),
        'highVisibility' => visibloc_jlg_get_user_high_visibility(),
    ];
}

/**
 * Retrieve the REST endpoint URL used to persist preferences.
 *
 * @return string
 */
function visibloc_jlg_get_editor_preferences_rest_url() {
    if ( function_exists( 'rest_url' ) ) {
        return rest_url( 'visibloc-jlg/v1/editor-preferences' );
    }

    return '';
}

/**
 * REST callback returning the current preferences.
 *
 * @param WP_REST_Request $request Incoming request.
 *
 * @return WP_REST_Response|WP_Error
 */
function visibloc_jlg_rest_get_editor_preferences( WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    if ( ! visibloc_jlg_can_manage_editor_preferences() ) {
        return new WP_Error(
            'visibloc_rest_forbidden',
            __( 'Vous n’avez pas la permission de modifier ces préférences.', 'visi-bloc-jlg' ),
            [
                'status' => rest_authorization_required_code(),
            ]
        );
    }

    return rest_ensure_response(
        [
            'mode' => visibloc_jlg_get_user_editor_mode(),
            'highVisibility' => visibloc_jlg_get_user_high_visibility(),
        ]
    );
}

/**
 * Sanitize the incoming editor mode.
 *
 * @param mixed $mode Raw editor mode.
 *
 * @return string
 */
function visibloc_jlg_sanitize_editor_mode( $mode ) {
    if ( ! is_string( $mode ) ) {
        return '';
    }

    return sanitize_key( $mode );
}

/**
 * Sanitize the incoming high visibility preference.
 *
 * @param mixed $enabled Raw value.
 *
 * @return bool
 */
function visibloc_jlg_sanitize_high_visibility( $enabled ) {
    return visibloc_jlg_normalize_high_visibility( $enabled );
}

/**
 * Validate the incoming editor mode.
 *
 * @param string          $mode    Editor mode.
 * @param WP_REST_Request $request Request instance.
 *
 * @return bool
 */
function visibloc_jlg_validate_editor_mode( $mode, WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    return in_array( $mode, visibloc_jlg_get_supported_editor_modes(), true );
}

/**
 * Validate the incoming high visibility preference.
 *
 * @param bool             $enabled  Sanitized value.
 * @param WP_REST_Request  $request  Request instance.
 *
 * @return bool
 */
function visibloc_jlg_validate_high_visibility( $enabled, WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    return is_bool( $enabled );
}

/**
 * REST callback persisting the editor preferences.
 *
 * @param WP_REST_Request $request Incoming request.
 *
 * @return WP_REST_Response|WP_Error
 */
function visibloc_jlg_rest_update_editor_preferences( WP_REST_Request $request ) {
    if ( ! visibloc_jlg_can_manage_editor_preferences() ) {
        return new WP_Error(
            'visibloc_rest_forbidden',
            __( 'Vous n’avez pas la permission de modifier ces préférences.', 'visi-bloc-jlg' ),
            [
                'status' => rest_authorization_required_code(),
            ]
        );
    }

    if ( ! function_exists( 'get_current_user_id' ) ) {
        return new WP_Error(
            'visibloc_rest_unavailable',
            __( 'La préférence ne peut pas être enregistrée pour le moment.', 'visi-bloc-jlg' ),
            [
                'status' => 500,
            ]
        );
    }

    $user_id = (int) get_current_user_id();

    if ( $user_id <= 0 ) {
        return new WP_Error(
            'visibloc_rest_forbidden',
            __( 'Vous devez être connecté pour modifier ce réglage.', 'visi-bloc-jlg' ),
            [
                'status' => rest_authorization_required_code(),
            ]
        );
    }

    $mode = (string) $request->get_param( 'mode' );

    if ( '' === $mode ) {
        $mode = visibloc_jlg_get_default_editor_mode();
    }

    $high_visibility_param = $request->get_param( 'highVisibility' );
    $high_visibility        = null;

    if ( ! visibloc_jlg_set_user_editor_mode( $user_id, $mode ) ) {
        return new WP_Error(
            'visibloc_rest_unavailable',
            __( 'La préférence ne peut pas être enregistrée pour le moment.', 'visi-bloc-jlg' ),
            [
                'status' => 500,
            ]
        );
    }

    if ( null !== $high_visibility_param ) {
        $high_visibility = visibloc_jlg_normalize_high_visibility( $high_visibility_param );

        if ( ! visibloc_jlg_set_user_high_visibility( $user_id, $high_visibility ) ) {
            return new WP_Error(
                'visibloc_rest_unavailable',
                __( 'La préférence ne peut pas être enregistrée pour le moment.', 'visi-bloc-jlg' ),
                [
                    'status' => 500,
                ]
            );
        }
    } else {
        $high_visibility = visibloc_jlg_get_user_high_visibility( $user_id );
    }

    return rest_ensure_response(
        [
            'mode' => visibloc_jlg_get_user_editor_mode( $user_id ),
            'highVisibility' => visibloc_jlg_get_user_high_visibility( $user_id ),
        ]
    );
}

/**
 * Register the editor preferences REST endpoints.
 */
function visibloc_jlg_register_editor_preferences_rest_route() {
    if ( ! function_exists( 'register_rest_route' ) ) {
        return;
    }

    register_rest_route(
        'visibloc-jlg/v1',
        '/editor-preferences',
        [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => 'visibloc_jlg_rest_get_editor_preferences',
                'permission_callback' => 'visibloc_jlg_can_manage_editor_preferences',
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => 'visibloc_jlg_rest_update_editor_preferences',
                'permission_callback' => 'visibloc_jlg_can_manage_editor_preferences',
                'args'                => [
                    'mode' => [
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'visibloc_jlg_sanitize_editor_mode',
                        'validate_callback' => 'visibloc_jlg_validate_editor_mode',
                    ],
                    'highVisibility' => [
                        'type'              => 'boolean',
                        'required'          => false,
                        'sanitize_callback' => 'visibloc_jlg_sanitize_high_visibility',
                        'validate_callback' => 'visibloc_jlg_validate_high_visibility',
                    ],
                ],
            ],
        ]
    );
}

add_action( 'rest_api_init', 'visibloc_jlg_register_editor_preferences_rest_route' );
