<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_REST_Controller' ) ) {
    class WP_REST_Controller {}
}

if ( ! class_exists( 'WP_REST_Server' ) ) {
    class WP_REST_Server {
        const READABLE = 'GET';
        const CREATABLE = 'POST';
    }
}

if ( ! function_exists( 'rest_ensure_response' ) ) {
    class Visibloc_Test_REST_Response {
        private $data;

        public function __construct( $data ) {
            $this->data = $data;
        }

        public function get_data() {
            return $this->data;
        }
    }

    function rest_ensure_response( $response ) {
        return new Visibloc_Test_REST_Response( $response );
    }
}

class Visibloc_Onboarding_Controller extends WP_REST_Controller {
    protected $namespace = 'visibloc-jlg/v1';
    protected $rest_base = 'onboarding';

    public function __construct() {}

    public function register_routes() {
        if ( ! function_exists( 'register_rest_route' ) ) {
            return;
        }

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_item' ],
                    'permission_callback' => [ $this, 'permissions_check' ],
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'update_item' ],
                    'permission_callback' => [ $this, 'permissions_check' ],
                    'args'                => [
                        'recipeId' => [
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_key',
                        ],
                    ],
                ],
            ]
        );
    }

    public function permissions_check() {
        return function_exists( 'current_user_can' ) ? current_user_can( 'edit_posts' ) : false;
    }

    public function get_item( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
        $user_id = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;
        $entry   = visibloc_jlg_get_onboarding_draft_for_user( $user_id );

        $response = [
            'draft'     => isset( $entry['data'] ) ? $entry['data'] : null,
            'updatedAt' => isset( $entry['updated_at'] ) ? (int) $entry['updated_at'] : null,
        ];

        return rest_ensure_response( $response );
    }

    public function update_item( $request ) {
        $params = $request->get_json_params();

        if ( empty( $params ) ) {
            $params = $request->get_body_params();
        }

        if ( ! is_array( $params ) ) {
            return new WP_Error( 'visibloc_rest_invalid_payload', __( 'Données de brouillon invalides.', 'visi-bloc-jlg' ), [
                'status' => 400,
            ] );
        }

        $sanitized = $this->sanitize_draft( $params );
        $user_id   = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;
        $timestamp = function_exists( 'current_time' ) ? (int) current_time( 'timestamp' ) : time();

        visibloc_jlg_store_onboarding_draft_for_user(
            $user_id,
            [
                'data'       => $sanitized,
                'updated_at' => $timestamp,
            ]
        );

        return rest_ensure_response(
            [
                'draft'     => $sanitized,
                'updatedAt' => $timestamp,
            ]
        );
    }

    protected function sanitize_draft( array $data ) {
        $steps = isset( $data['steps'] ) && is_array( $data['steps'] ) ? $data['steps'] : [];
        $recipe_id = '';

        if ( isset( $data['recipeId'] ) && is_string( $data['recipeId'] ) ) {
            $raw = $data['recipeId'];
            $parts = preg_split( '/[\s<>"]+/', $raw );
            $candidate = is_array( $parts ) && isset( $parts[0] ) ? $parts[0] : $raw;
            $recipe_id = sanitize_key( $candidate );
        }

        return [
            'recipeId' => $recipe_id,
            'mode'     => isset( $data['mode'] ) && 'expert' === strtolower( (string) $data['mode'] ) ? 'expert' : 'simple',
            'steps'    => [
                'objective' => $this->sanitize_step_section( $steps['objective'] ?? [] ),
                'audience'  => $this->sanitize_step_section( $steps['audience'] ?? [] ),
                'timing'    => $this->sanitize_step_section( $steps['timing'] ?? [] ),
                'content'   => $this->sanitize_step_section( $steps['content'] ?? [] ),
            ],
        ];
    }

    protected function sanitize_step_section( $section ) {
        if ( ! is_array( $section ) ) {
            return [];
        }

        $normalized = [];

        foreach ( $section as $key => $value ) {
            $normalized_key = is_string( $key ) ? preg_replace( '/[^a-zA-Z0-9_-]/', '_', $key ) : '';

            if ( '' === $normalized_key ) {
                continue;
            }

            if ( is_array( $value ) ) {
                $normalized[ $normalized_key ] = $this->sanitize_step_section( $value );

                continue;
            }

            if ( function_exists( 'visibloc_jlg_sanitize_onboarding_text' ) ) {
                $normalized[ $normalized_key ] = visibloc_jlg_sanitize_onboarding_text( $value );
            } elseif ( function_exists( 'sanitize_textarea_field' ) ) {
                $normalized[ $normalized_key ] = sanitize_textarea_field( (string) $value );
            } else {
                $normalized[ $normalized_key ] = trim( strip_tags( (string) $value ) );
            }
        }

        return $normalized;
    }
}

function visibloc_jlg_get_onboarding_rest_url() {
    if ( function_exists( 'rest_url' ) ) {
        return rest_url( 'visibloc-jlg/v1/onboarding' );
    }

    return '';
}

function visibloc_jlg_get_onboarding_draft_for_user( $user_id ) {
    $user_key = $user_id > 0 ? (string) $user_id : 'guest';
    $drafts   = get_option( 'visibloc_onboarding_drafts', [] );

    if ( isset( $drafts[ $user_key ] ) && is_array( $drafts[ $user_key ] ) ) {
        return $drafts[ $user_key ];
    }

    return [
        'data'       => null,
        'updated_at' => null,
    ];
}

function visibloc_jlg_store_onboarding_draft_for_user( $user_id, array $entry ) {
    $user_key = $user_id > 0 ? (string) $user_id : 'guest';
    $drafts   = get_option( 'visibloc_onboarding_drafts', [] );

    if ( ! is_array( $drafts ) ) {
        $drafts = [];
    }

    $drafts[ $user_key ] = [
        'data'       => isset( $entry['data'] ) && is_array( $entry['data'] ) ? $entry['data'] : null,
        'updated_at' => isset( $entry['updated_at'] ) ? (int) $entry['updated_at'] : null,
    ];

    update_option( 'visibloc_onboarding_drafts', $drafts );
}

function visibloc_jlg_register_onboarding_rest_controller() {
    $controller = new Visibloc_Onboarding_Controller();
    $controller->register_routes();
}

add_action( 'rest_api_init', 'visibloc_jlg_register_onboarding_rest_controller' );
