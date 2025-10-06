<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/cache-constants.php';

require_once __DIR__ . '/block-utils.php';
require_once __DIR__ . '/fallback.php';

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

    if ( $has_list_changed ) {
        visibloc_jlg_rebuild_group_block_summary_index();
    }

    return $normalized_blocks;
}

add_action( 'admin_init', 'visibloc_jlg_handle_options_save' );
function visibloc_jlg_handle_options_save() {
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

        visibloc_jlg_finalize_settings_request( $result );
        return;
    }
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
        'exported_at'      => gmdate( 'c' ),
        'version'          => defined( 'VISIBLOC_JLG_VERSION' ) ? VISIBLOC_JLG_VERSION : 'unknown',
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
    $debug_status   = get_option( 'visibloc_debug_mode', 'off' );
    $mobile_bp      = get_option( 'visibloc_breakpoint_mobile', 781 );
    $tablet_bp      = get_option( 'visibloc_breakpoint_tablet', 1024 );
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

    $breakpoints_requirement_message = visibloc_jlg_get_breakpoints_requirement_message();

    $sections = [
        [
            'id'      => 'visibloc-section-blocks',
            'label'   => __( 'Blocs compatibles', 'visi-bloc-jlg' ),
            'render'  => 'visibloc_jlg_render_supported_blocks_section',
            'args'    => [ $registered_block_types, $configured_blocks ],
        ],
        [
            'id'      => 'visibloc-section-permissions',
            'label'   => __( "Permissions d'Aperçu", 'visi-bloc-jlg' ),
            'render'  => 'visibloc_jlg_render_permissions_section',
            'args'    => [ $allowed_roles ],
        ],
        [
            'id'      => 'visibloc-section-hidden',
            'label'   => __( 'Tableau de bord des blocs masqués (via Œil)', 'visi-bloc-jlg' ),
            'render'  => 'visibloc_jlg_render_hidden_blocks_section',
            'args'    => [ $hidden_posts ],
        ],
        [
            'id'      => 'visibloc-section-device',
            'label'   => __( 'Tableau de bord des blocs avec visibilité par appareil', 'visi-bloc-jlg' ),
            'render'  => 'visibloc_jlg_render_device_visibility_section',
            'args'    => [ $device_posts ],
        ],
        [
            'id'      => 'visibloc-section-scheduled',
            'label'   => __( 'Tableau de bord des blocs programmés', 'visi-bloc-jlg' ),
            'render'  => 'visibloc_jlg_render_scheduled_blocks_section',
            'args'    => [ $scheduled_posts ],
        ],
        [
            'id'      => 'visibloc-section-debug',
            'label'   => __( 'Mode de débogage', 'visi-bloc-jlg' ),
            'render'  => 'visibloc_jlg_render_debug_mode_section',
            'args'    => [ $debug_status ],
        ],
        [
            'id'      => 'visibloc-section-breakpoints',
            'label'   => __( 'Réglage des points de rupture', 'visi-bloc-jlg' ),
            'render'  => 'visibloc_jlg_render_breakpoints_section',
            'args'    => [ $mobile_bp, $tablet_bp ],
        ],
        [
            'id'      => 'visibloc-section-fallback',
            'label'   => __( 'Contenu de repli global', 'visi-bloc-jlg' ),
            'render'  => 'visibloc_jlg_render_fallback_section',
            'args'    => [ $fallback_settings, $fallback_blocks ],
        ],
        [
            'id'      => 'visibloc-section-backup',
            'label'   => __( 'Export & sauvegarde', 'visi-bloc-jlg' ),
            'render'  => 'visibloc_jlg_render_settings_backup_section',
            'args'    => [],
        ],
    ];

    $nav_select_id      = 'visibloc-help-nav-picker';
    $nav_description_id = $nav_select_id . '-description';
    $nav_list_id        = 'visibloc-help-nav-list';

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
        <div class="visibloc-help-layout">
            <div class="visibloc-help-layout__sidebar">
                <div class="visibloc-help-nav__mobile" data-visibloc-nav-picker-container>
                    <label class="visibloc-help-nav__mobile-label" for="<?php echo esc_attr( $nav_select_id ); ?>">
                        <?php esc_html_e( 'Aller directement à une section', 'visi-bloc-jlg' ); ?>
                    </label>
                    <p id="<?php echo esc_attr( $nav_description_id ); ?>" class="description visibloc-help-nav__mobile-description">
                        <?php esc_html_e( 'Choisissez une section pour y accéder rapidement depuis la navigation mobile.', 'visi-bloc-jlg' ); ?>
                    </p>
                    <select
                        id="<?php echo esc_attr( $nav_select_id ); ?>"
                        class="visibloc-help-nav__mobile-select regular-text"
                        aria-describedby="<?php echo esc_attr( $nav_description_id ); ?>"
                        data-visibloc-nav-picker
                    >
                        <?php foreach ( $sections as $section ) :
                            if ( empty( $section['id'] ) || empty( $section['label'] ) ) {
                                continue;
                            }

                            $section_id    = sanitize_html_class( $section['id'] );
                            $section_label = $section['label'];
                            ?>
                            <option value="<?php echo esc_attr( $section_id ); ?>">
                                <?php echo esc_html( $section_label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <nav
                    class="visibloc-help-nav"
                    aria-label="<?php echo esc_attr__( 'Navigation des réglages Visi-Bloc', 'visi-bloc-jlg' ); ?>"
                    data-visibloc-nav-container
                >
                    <ul id="<?php echo esc_attr( $nav_list_id ); ?>" class="visibloc-help-nav__list">
                        <?php foreach ( $sections as $section ) :
                            if ( empty( $section['id'] ) || empty( $section['label'] ) ) {
                                continue;
                            }

                            $section_id    = sanitize_html_class( $section['id'] );
                            $section_label = $section['label'];
                            ?>
                            <li class="visibloc-help-nav__item">
                                <a
                                    class="visibloc-help-nav__link"
                                    href="#<?php echo esc_attr( $section_id ); ?>"
                                    data-visibloc-nav-link
                                >
                                    <?php echo esc_html( $section_label ); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </div>
            <div id="poststuff" class="visibloc-help-layout__content">
                <?php foreach ( $sections as $section ) :
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

                            foreach ( $registered_block_types as $block ) :
                                $block_name  = isset( $block['name'] ) && is_string( $block['name'] ) ? $block['name'] : '';
                                $block_label = isset( $block['label'] ) && is_string( $block['label'] ) ? $block['label'] : $block_name;

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
                                    style="display: block; margin-bottom: 6px;"
                                >
                                    <input type="checkbox" name="visibloc_supported_blocks[]" value="<?php echo esc_attr( $block_name ); ?>" <?php checked( $is_checked ); ?> <?php disabled( $is_disabled ); ?> />
                                    <?php echo esc_html( $block_label ); ?>
                                    <span class="description" style="margin-left: 4px;">
                                        (<?php echo esc_html( $block_name ); ?>)
                                        <?php if ( $is_default ) : ?>
                                            — <?php esc_html_e( 'Toujours actif', 'visi-bloc-jlg' ); ?>
                                        <?php endif; ?>
                                    </span>
                                </label>
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
        $version   = defined( 'VISIBLOC_JLG_VERSION' ) ? VISIBLOC_JLG_VERSION : '0.0.0';

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
