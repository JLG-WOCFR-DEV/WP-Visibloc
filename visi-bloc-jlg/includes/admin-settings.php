<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/block-utils.php';

add_action( 'admin_init', 'visibloc_jlg_handle_options_save' );
function visibloc_jlg_handle_options_save() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
    if ( 'POST' !== $request_method ) return;

    if ( ! isset( $_POST['visibloc_nonce'] ) ) return;

    $nonce = isset( $_POST['visibloc_nonce'] ) ? wp_unslash( $_POST['visibloc_nonce'] ) : '';

    if ( ! is_string( $nonce ) || '' === $nonce ) return;

    $submitted_supported_blocks = [];
    if ( isset( $_POST['visibloc_supported_blocks'] ) ) {
        $submitted_supported_blocks = (array) wp_unslash( $_POST['visibloc_supported_blocks'] );
    }

    $mobile_breakpoint          = null;
    $mobile_breakpoint_invalid  = false;
    if ( isset( $_POST['visibloc_breakpoint_mobile'] ) ) {
        $raw_mobile_breakpoint = trim( wp_unslash( $_POST['visibloc_breakpoint_mobile'] ) );
        if ( '' !== $raw_mobile_breakpoint ) {
            $mobile_breakpoint = absint( $raw_mobile_breakpoint );
            if ( $mobile_breakpoint < 1 ) {
                $mobile_breakpoint_invalid = true;
                $mobile_breakpoint         = null;
            }
        }
    }

    $tablet_breakpoint          = null;
    $tablet_breakpoint_invalid  = false;
    if ( isset( $_POST['visibloc_breakpoint_tablet'] ) ) {
        $raw_tablet_breakpoint = trim( wp_unslash( $_POST['visibloc_breakpoint_tablet'] ) );
        if ( '' !== $raw_tablet_breakpoint ) {
            $tablet_breakpoint = absint( $raw_tablet_breakpoint );
            if ( $tablet_breakpoint < 1 ) {
                $tablet_breakpoint_invalid = true;
                $tablet_breakpoint         = null;
            }
        }
    }

    $submitted_roles = [];
    if ( isset( $_POST['visibloc_preview_roles'] ) ) {
        $submitted_roles = array_map( 'sanitize_key', (array) wp_unslash( $_POST['visibloc_preview_roles'] ) );
    }

    if ( wp_verify_nonce( $nonce, 'visibloc_save_supported_blocks' ) ) {
        $normalized_blocks = visibloc_jlg_normalize_block_names( $submitted_supported_blocks );
        update_option( 'visibloc_supported_blocks', $normalized_blocks );
        visibloc_jlg_clear_caches();
        wp_safe_redirect( admin_url( 'admin.php?page=visi-bloc-jlg-help&status=updated' ) );
        exit;
    }

    if ( wp_verify_nonce( $nonce, 'visibloc_toggle_debug' ) ) {
        $current_status = get_option( 'visibloc_debug_mode', 'off' );
        update_option( 'visibloc_debug_mode', ( $current_status === 'on' ) ? 'off' : 'on' );
        visibloc_jlg_clear_caches();
        wp_safe_redirect( admin_url( 'admin.php?page=visi-bloc-jlg-help&status=updated' ) );
        exit;
    }

    if ( wp_verify_nonce( $nonce, 'visibloc_save_breakpoints' ) ) {
        $current_mobile_bp = get_option( 'visibloc_breakpoint_mobile', 781 );
        $current_tablet_bp = get_option( 'visibloc_breakpoint_tablet', 1024 );

        if ( $mobile_breakpoint_invalid || $tablet_breakpoint_invalid ) {
            $redirect_url = add_query_arg(
                'status',
                'invalid_breakpoints',
                admin_url( 'admin.php?page=visi-bloc-jlg-help' )
            );
            wp_safe_redirect( $redirect_url );
            exit;
        }

        $new_mobile_bp = ( null !== $mobile_breakpoint ) ? $mobile_breakpoint : $current_mobile_bp;
        $new_tablet_bp = ( null !== $tablet_breakpoint ) ? $tablet_breakpoint : $current_tablet_bp;

        if ( $new_mobile_bp < 1 || $new_tablet_bp < 1 || $new_tablet_bp <= $new_mobile_bp ) {
            $redirect_url = add_query_arg(
                'status',
                'invalid_breakpoints',
                admin_url( 'admin.php?page=visi-bloc-jlg-help' )
            );
            wp_safe_redirect( $redirect_url );
            exit;
        }

        if ( null !== $mobile_breakpoint && $mobile_breakpoint !== $current_mobile_bp ) {
            update_option( 'visibloc_breakpoint_mobile', $mobile_breakpoint );
        }

        if ( null !== $tablet_breakpoint && $tablet_breakpoint !== $current_tablet_bp ) {
            update_option( 'visibloc_breakpoint_tablet', $tablet_breakpoint );
        }

        visibloc_jlg_clear_caches();
        wp_safe_redirect( admin_url( 'admin.php?page=visi-bloc-jlg-help&status=updated' ) );
        exit;
    }

    if ( wp_verify_nonce( $nonce, 'visibloc_save_permissions' ) ) {
        if ( ! function_exists( 'get_editable_roles' ) ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        $editable_roles = array_keys( (array) get_editable_roles() );
        $editable_roles = array_map( 'sanitize_key', $editable_roles );

        $sanitized_roles = array_values( array_unique( array_intersect( $editable_roles, $submitted_roles ) ) );

        // On s'assure que l'administrateur est toujours inclus
        if ( ! in_array( 'administrator', $sanitized_roles, true ) ) {
            $sanitized_roles[] = 'administrator';
        }

        update_option( 'visibloc_preview_roles', $sanitized_roles );
        visibloc_jlg_clear_caches();
        wp_safe_redirect( admin_url( 'admin.php?page=visi-bloc-jlg-help&status=updated' ) );
        exit;
    }
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

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Visi-Bloc - JLG - Aide et Réglages', 'visi-bloc-jlg' ); ?></h1>
        <?php if ( 'updated' === $status ) : ?>
            <div id="message" class="updated notice is-dismissible"><p><?php esc_html_e( 'Réglages mis à jour.', 'visi-bloc-jlg' ); ?></p></div>
        <?php elseif ( 'invalid_breakpoints' === $status ) : ?>
            <div id="message" class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Les valeurs de breakpoint doivent être des nombres positifs et la tablette doit être supérieure au mobile. Les réglages n’ont pas été enregistrés.', 'visi-bloc-jlg' ); ?></p></div>
        <?php endif; ?>
        <div id="poststuff">
            <?php
            visibloc_jlg_render_supported_blocks_section( $registered_block_types, $configured_blocks );
            visibloc_jlg_render_permissions_section( $allowed_roles );
            visibloc_jlg_render_hidden_blocks_section( $hidden_posts );
            visibloc_jlg_render_device_visibility_section( $device_posts );
            visibloc_jlg_render_scheduled_blocks_section( $scheduled_posts );
            visibloc_jlg_render_debug_mode_section( $debug_status );
            visibloc_jlg_render_breakpoints_section( $mobile_bp, $tablet_bp );
            ?>
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

    ?>
    <div class="postbox">
        <h2 class="hndle"><span><?php esc_html_e( 'Blocs compatibles', 'visi-bloc-jlg' ); ?></span></h2>
        <div class="inside">
            <form method="POST" action="">
                <p><?php esc_html_e( 'Sélectionnez les blocs Gutenberg pouvant utiliser les contrôles de visibilité Visi-Bloc.', 'visi-bloc-jlg' ); ?></p>
                <?php if ( empty( $registered_block_types ) ) : ?>
                    <p><em><?php esc_html_e( 'Aucun bloc enregistré n’a été détecté.', 'visi-bloc-jlg' ); ?></em></p>
                <?php else : ?>
                    <fieldset>
                        <?php foreach ( $registered_block_types as $block ) :
                            $block_name  = isset( $block['name'] ) && is_string( $block['name'] ) ? $block['name'] : '';
                            $block_label = isset( $block['label'] ) && is_string( $block['label'] ) ? $block['label'] : $block_name;

                            if ( '' === $block_name ) {
                                continue;
                            }

                            $is_default  = in_array( $block_name, $default_blocks, true );
                            $is_checked  = $is_default || in_array( $block_name, $configured_blocks, true );
                            $is_disabled = $is_default;
                            ?>
                            <label style="display: block; margin-bottom: 6px;">
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

    ?>
    <div class="postbox">
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

    ?>
    <div class="postbox">
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

    ?>
    <div class="postbox">
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

    ?>
    <div class="postbox">
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
                                <td data-label="<?php echo esc_attr( $title_column_label ); ?>">
                                    <a href="<?php echo esc_url( $scheduled_block['link'] ); ?>"><?php echo esc_html( $scheduled_block['title'] ); ?></a>
                                </td>
                                <td data-label="<?php echo esc_attr( $start_column_label ); ?>"><?php echo esc_html( $start_display ); ?></td>
                                <td data-label="<?php echo esc_attr( $end_column_label ); ?>"><?php echo esc_html( $end_display ); ?></td>
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
    ?>
    <div class="postbox">
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

function visibloc_jlg_render_breakpoints_section( $mobile_bp, $tablet_bp ) {
    ?>
    <div class="postbox">
        <h2 class="hndle"><span><?php esc_html_e( 'Réglage des points de rupture', 'visi-bloc-jlg' ); ?></span></h2>
        <div class="inside">
            <form method="POST" action="">
                <p><?php esc_html_e( "Alignez les largeurs d'écran avec celles de votre thème.", 'visi-bloc-jlg' ); ?></p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="visibloc_breakpoint_mobile"><?php esc_html_e( 'Largeur max. mobile', 'visi-bloc-jlg' ); ?></label></th>
                        <td><input name="visibloc_breakpoint_mobile" type="number" id="visibloc_breakpoint_mobile" value="<?php echo esc_attr( $mobile_bp ); ?>" class="small-text"> <?php esc_html_e( 'px', 'visi-bloc-jlg' ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="visibloc_breakpoint_tablet"><?php esc_html_e( 'Largeur max. tablette', 'visi-bloc-jlg' ); ?></label></th>
                        <td><input name="visibloc_breakpoint_tablet" type="number" id="visibloc_breakpoint_tablet" value="<?php echo esc_attr( $tablet_bp ); ?>" class="small-text"> <?php esc_html_e( 'px', 'visi-bloc-jlg' ); ?></td>
                    </tr>
                </table>
                <input type="hidden" name="action" value="visibloc_save_breakpoints">
                <?php wp_nonce_field( 'visibloc_save_breakpoints', 'visibloc_nonce' ); ?>
                <?php submit_button( __( 'Enregistrer les breakpoints', 'visi-bloc-jlg' ) ); ?>
            </form>
        </div>
    </div>
    <?php
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
    delete_transient( 'visibloc_hidden_posts' );
    delete_transient( 'visibloc_device_posts' );
    delete_transient( 'visibloc_scheduled_posts' );
    delete_transient( 'visibloc_group_block_metadata' );
    if ( function_exists( 'wp_cache_delete' ) ) {
        wp_cache_delete( 'visibloc_device_css_cache', 'visibloc_jlg' );
    }
}

add_action( 'save_post', 'visibloc_jlg_refresh_group_block_summary_on_save', 20, 3 );
add_action( 'deleted_post', 'visibloc_jlg_remove_group_block_summary_for_post' );
add_action( 'trashed_post', 'visibloc_jlg_remove_group_block_summary_for_post' );
