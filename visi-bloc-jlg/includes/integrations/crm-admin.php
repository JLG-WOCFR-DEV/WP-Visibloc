<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Format a timestamp using WordPress settings.
 *
 * @param int $timestamp Timestamp to format.
 * @return string
 */
function visibloc_jlg_format_crm_datetime( $timestamp ) {
    $timestamp = absint( $timestamp );

    if ( $timestamp <= 0 ) {
        return '';
    }

    $format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

    if ( function_exists( 'wp_date' ) ) {
        return wp_date( $format, $timestamp );
    }

    return date_i18n( $format, $timestamp );
}

/**
 * Register the CRM integrations submenu inside the existing help menu.
 */
function visibloc_jlg_register_crm_settings_page() {
    add_submenu_page(
        'visi-bloc-jlg-help',
        __( 'Intégrations CRM', 'visi-bloc-jlg' ),
        __( 'Intégrations CRM', 'visi-bloc-jlg' ),
        'manage_options',
        'visi-bloc-jlg-crm',
        'visibloc_jlg_render_crm_settings_page'
    );
}
add_action( 'admin_menu', 'visibloc_jlg_register_crm_settings_page' );

/**
 * Handle the CRM settings form submission.
 */
function visibloc_jlg_handle_crm_settings_submission() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Action non autorisée.', 'visi-bloc-jlg' ) );
    }

    check_admin_referer( 'visibloc_jlg_crm_settings' );

    $connector   = isset( $_POST['visibloc_crm_connector'] ) ? sanitize_key( wp_unslash( $_POST['visibloc_crm_connector'] ) ) : '';
    $credentials = isset( $_POST['visibloc_crm_credentials'] ) && is_array( $_POST['visibloc_crm_credentials'] )
        ? array_map( 'wp_unslash', $_POST['visibloc_crm_credentials'] )
        : [];

    $previous = Visibloc_CRM_Sync::get_settings();
    $cleaned  = Visibloc_CRM_Sync::sanitize_credentials( $connector, $credentials, $previous['credentials'] );

    Visibloc_CRM_Sync::update_settings(
        [
            'connector'   => $connector,
            'credentials' => $cleaned,
        ]
    );

    if ( '' !== $connector ) {
        Visibloc_CRM_Sync::refresh_segments( [ 'source' => 'settings', 'force' => true ] );
    }

    visibloc_jlg_queue_crm_notice( 'success', __( 'Les réglages CRM ont été enregistrés.', 'visi-bloc-jlg' ) );

    wp_safe_redirect( visibloc_jlg_get_crm_settings_page_url( [ 'notice' => 1 ] ) );
    exit;
}
add_action( 'admin_post_visibloc_crm_save_settings', 'visibloc_jlg_handle_crm_settings_submission' );

/**
 * Handle manual synchronization requests coming from the settings page.
 */
function visibloc_jlg_handle_crm_manual_sync() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Action non autorisée.', 'visi-bloc-jlg' ) );
    }

    check_admin_referer( 'visibloc_jlg_crm_manual_sync' );

    $result = Visibloc_CRM_Sync::refresh_segments( [ 'source' => 'manual', 'force' => true ] );

    if ( is_wp_error( $result ) ) {
        visibloc_jlg_queue_crm_notice( 'error', $result->get_error_message() );
    } else {
        visibloc_jlg_queue_crm_notice( 'success', $result['message'] );
    }

    wp_safe_redirect( visibloc_jlg_get_crm_settings_page_url( [ 'notice' => 1 ] ) );
    exit;
}
add_action( 'admin_post_visibloc_crm_manual_sync', 'visibloc_jlg_handle_crm_manual_sync' );

/**
 * Build the admin URL pointing to the CRM settings page.
 *
 * @param array $args Optional query arguments.
 * @return string
 */
function visibloc_jlg_get_crm_settings_page_url( array $args = [] ) {
    $base = admin_url( 'admin.php?page=visi-bloc-jlg-crm' );

    if ( empty( $args ) ) {
        return $base;
    }

    return add_query_arg( array_map( 'sanitize_text_field', $args ), $base );
}

/**
 * Persist a notice message that will be displayed on the CRM settings page.
 *
 * @param string $type    Notice type (success|error|warning|info).
 * @param string $message Message to display.
 * @return void
 */
function visibloc_jlg_queue_crm_notice( $type, $message ) {
    if ( ! function_exists( 'set_transient' ) ) {
        return;
    }

    $user_id = get_current_user_id();
    $key     = 'visibloc_crm_notice_' . ( $user_id ? $user_id : 'global' );

    set_transient(
        $key,
        [
            'type'    => sanitize_key( $type ),
            'message' => wp_kses_post( $message ),
        ],
        MINUTE_IN_SECONDS * 5
    );
}

/**
 * Retrieve and display the latest queued notice for the current user.
 *
 * @return void
 */
function visibloc_jlg_display_crm_notice() {
    if ( ! function_exists( 'get_transient' ) ) {
        return;
    }

    $user_id = get_current_user_id();
    $key     = 'visibloc_crm_notice_' . ( $user_id ? $user_id : 'global' );
    $notice  = get_transient( $key );

    if ( false === $notice ) {
        return;
    }

    delete_transient( $key );

    $type    = isset( $notice['type'] ) ? sanitize_key( $notice['type'] ) : 'info';
    $message = isset( $notice['message'] ) ? wp_kses_post( $notice['message'] ) : '';

    if ( '' === $message ) {
        return;
    }

    $class_map = [
        'success' => 'notice notice-success',
        'error'   => 'notice notice-error',
        'warning' => 'notice notice-warning',
        'info'    => 'notice notice-info',
    ];

    $class = isset( $class_map[ $type ] ) ? $class_map[ $type ] : $class_map['info'];

    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
}

/**
 * Render the CRM settings page content.
 */
function visibloc_jlg_render_crm_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Action non autorisée.', 'visi-bloc-jlg' ) );
    }

    $settings           = Visibloc_CRM_Sync::get_settings();
    $snapshot           = Visibloc_CRM_Sync::get_snapshot();
    $choices            = Visibloc_CRM_Sync::get_connector_choices();
    $selected_connector = $settings['connector'];
    $credentials        = $settings['credentials'];
    $fields             = Visibloc_CRM_Sync::get_connector_fields( $selected_connector );

    $has_connector = '' !== $selected_connector;
    $last_sync     = $snapshot['synced_at'];
    $last_attempt  = $snapshot['attempted_at'];
    $status        = $snapshot['status'];
    $error_message = $snapshot['error_message'];
    $connector_label = Visibloc_CRM_Sync::get_connector_label( $selected_connector );

    if ( function_exists( 'settings_errors' ) ) {
        settings_errors();
    }

    if ( isset( $_GET['notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        visibloc_jlg_display_crm_notice();
    }

    $next_event = function_exists( 'wp_next_scheduled' ) ? wp_next_scheduled( Visibloc_CRM_Sync::CRON_HOOK ) : false;
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Intégrations CRM', 'visi-bloc-jlg' ); ?></h1>

        <p class="description">
            <?php esc_html_e( 'Connectez votre CRM pour exposer automatiquement les segments marketing dans l’éditeur de blocs.', 'visi-bloc-jlg' ); ?>
        </p>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="visibloc-crm-settings-form">
            <?php wp_nonce_field( 'visibloc_jlg_crm_settings' ); ?>
            <input type="hidden" name="action" value="visibloc_crm_save_settings" />

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="visibloc-crm-connector"><?php esc_html_e( 'Connecteur actif', 'visi-bloc-jlg' ); ?></label>
                        </th>
                        <td>
                            <select id="visibloc-crm-connector" name="visibloc_crm_connector" class="regular-text">
                                <option value=""><?php esc_html_e( 'Aucun connecteur', 'visi-bloc-jlg' ); ?></option>
                                <?php foreach ( $choices as $choice ) :
                                    $value = isset( $choice['id'] ) ? $choice['id'] : '';
                                    $label = isset( $choice['label'] ) ? $choice['label'] : $value;
                                    $description = isset( $choice['description'] ) ? $choice['description'] : '';
                                    ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_connector, $value ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ( empty( $choices ) ) : ?>
                                <p class="description">
                                    <?php esc_html_e( 'Aucun connecteur n’est actuellement disponible. Utilisez le filtre « visibloc_jlg_crm_connectors » pour en déclarer.', 'visi-bloc-jlg' ); ?>
                                </p>
                            <?php else :
                                foreach ( $choices as $choice ) :
                                    if ( empty( $choice['description'] ) ) {
                                        continue;
                                    }
                                    $value = isset( $choice['id'] ) ? $choice['id'] : '';
                                    ?>
                                    <p class="description" data-visibloc-crm-connector-description="<?php echo esc_attr( $value ); ?>" style="<?php echo $selected_connector === $value ? '' : 'display:none;'; ?>">
                                        <?php echo esc_html( $choice['description'] ); ?>
                                    </p>
                                <?php endforeach;
                            endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php if ( $has_connector ) : ?>
                <h2><?php esc_html_e( 'Identifiants API', 'visi-bloc-jlg' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <?php foreach ( $fields as $field ) :
                            $field_key   = $field['key'];
                            $field_type  = isset( $field['type'] ) ? $field['type'] : 'text';
                            $field_value = isset( $credentials[ $field_key ] ) ? $credentials[ $field_key ] : '';
                            $placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
                            ?>
                            <tr>
                                <th scope="row">
                                    <label for="visibloc-crm-field-<?php echo esc_attr( $field_key ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
                                </th>
                                <td>
                                    <?php if ( 'textarea' === $field_type ) : ?>
                                        <textarea
                                            class="large-text"
                                            rows="4"
                                            id="visibloc-crm-field-<?php echo esc_attr( $field_key ); ?>"
                                            name="visibloc_crm_credentials[<?php echo esc_attr( $field_key ); ?>]"
                                            placeholder="<?php echo esc_attr( $placeholder ); ?>"
                                        ><?php echo esc_textarea( $field_value ); ?></textarea>
                                    <?php else :
                                        $input_type = in_array( $field_type, [ 'text', 'password', 'url', 'email' ], true ) ? $field_type : 'text';
                                        ?>
                                        <input
                                            type="<?php echo esc_attr( $input_type ); ?>"
                                            class="regular-text"
                                            id="visibloc-crm-field-<?php echo esc_attr( $field_key ); ?>"
                                            name="visibloc_crm_credentials[<?php echo esc_attr( $field_key ); ?>]"
                                            value="<?php echo 'password' === $input_type ? '' : esc_attr( $field_value ); ?>"
                                            placeholder="<?php echo esc_attr( $placeholder ); ?>"
                                        />
                                        <?php if ( 'password' === $input_type && ! empty( $field_value ) ) : ?>
                                            <p class="description"><?php esc_html_e( 'Laisser vide pour conserver la valeur enregistrée.', 'visi-bloc-jlg' ); ?></p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $field['description'] ) ) : ?>
                                        <p class="description"><?php echo esc_html( $field['description'] ); ?></p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php submit_button( __( 'Enregistrer les réglages', 'visi-bloc-jlg' ) ); ?>
        </form>

        <hr />

        <h2><?php esc_html_e( 'Synchronisation des segments', 'visi-bloc-jlg' ); ?></h2>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Dernière synchronisation réussie', 'visi-bloc-jlg' ); ?></th>
                    <td>
                        <?php
                        if ( $last_sync ) {
                            echo esc_html( sprintf( __( 'Le %s', 'visi-bloc-jlg' ), visibloc_jlg_format_crm_datetime( $last_sync ) ) );
                        } else {
                            esc_html_e( 'Aucune synchronisation n’a encore été réalisée.', 'visi-bloc-jlg' );
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Dernier essai', 'visi-bloc-jlg' ); ?></th>
                    <td>
                        <?php
                        if ( $last_attempt ) {
                            echo esc_html( sprintf( __( 'Le %s', 'visi-bloc-jlg' ), visibloc_jlg_format_crm_datetime( $last_attempt ) ) );
                        } else {
                            esc_html_e( 'Aucune tentative enregistrée.', 'visi-bloc-jlg' );
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Statut du connecteur', 'visi-bloc-jlg' ); ?></th>
                    <td>
                        <?php
                        if ( 'error' === $status && $error_message ) {
                            printf( '<span class="visibloc-status visibloc-status--error">%s</span>', esc_html( $error_message ) );
                        } elseif ( $has_connector ) {
                            printf( '<span class="visibloc-status visibloc-status--success">%s</span>', esc_html( sprintf( __( 'Connecté à %s', 'visi-bloc-jlg' ), $connector_label ) ) );
                        } else {
                            esc_html_e( 'Aucun connecteur configuré.', 'visi-bloc-jlg' );
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Prochain rafraîchissement automatique', 'visi-bloc-jlg' ); ?></th>
                    <td>
                        <?php
                        if ( $next_event ) {
                            echo esc_html( sprintf( __( 'Prévu le %s', 'visi-bloc-jlg' ), visibloc_jlg_format_crm_datetime( $next_event ) ) );
                        } else {
                            esc_html_e( 'Aucun rafraîchissement planifié pour le moment.', 'visi-bloc-jlg' );
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'visibloc_jlg_crm_manual_sync' ); ?>
            <input type="hidden" name="action" value="visibloc_crm_manual_sync" />
            <?php submit_button( __( 'Lancer une synchronisation manuelle', 'visi-bloc-jlg' ), 'secondary' ); ?>
        </form>
    </div>
    <script>
        ( function() {
            const select = document.getElementById( 'visibloc-crm-connector' );
            if ( ! select ) {
                return;
            }

            const descriptions = document.querySelectorAll( '[data-visibloc-crm-connector-description]' );

            const toggleDescriptions = () => {
                const value = select.value;
                descriptions.forEach( ( element ) => {
                    const matches = element.getAttribute( 'data-visibloc-crm-connector-description' ) === value;
                    element.style.display = matches ? '' : 'none';
                } );
            };

            select.addEventListener( 'change', toggleDescriptions );
            toggleDescriptions();
        } )();
    </script>
    <?php
}
