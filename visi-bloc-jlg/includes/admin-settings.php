<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_init', 'visibloc_jlg_handle_options_save' );
function visibloc_jlg_handle_options_save() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( ! isset( $_POST['visibloc_nonce'] ) ) return;

    $nonce = sanitize_text_field( wp_unslash( $_POST['visibloc_nonce'] ) );
    if ( '' === $nonce ) return;

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
        $sanitized_roles = $submitted_roles;
        // On s'assure que l'administrateur est toujours inclus
        if ( ! in_array( 'administrator', $sanitized_roles ) ) {
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

    $allowed_roles_option = get_option( 'visibloc_preview_roles', [ 'administrator' ] );
    $allowed_roles        = array_filter( (array) $allowed_roles_option );

    if ( empty( $allowed_roles ) ) {
        $allowed_roles = [ 'administrator' ];
    }
    $scheduled_posts = visibloc_jlg_get_scheduled_posts();
    $hidden_posts    = visibloc_jlg_get_hidden_posts();
    $device_posts    = visibloc_jlg_get_device_specific_posts();
    $status          = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';

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
    ?>
    <div class="postbox">
        <h2 class="hndle"><span><?php esc_html_e( 'Tableau de bord des blocs masqués (via Œil)', 'visi-bloc-jlg' ); ?></span></h2>
        <div class="inside">
            <?php if ( empty( $hidden_posts ) ) : ?>
                <p><?php esc_html_e( "Aucun bloc masqué manuellement n'a été trouvé.", 'visi-bloc-jlg' ); ?></p>
            <?php else : ?>
                <ul style="list-style: disc; padding-left: 20px;">
                    <?php foreach ( $hidden_posts as $post_data ) : ?>
                        <li><a href="<?php echo esc_url( $post_data['link'] ); ?>"><?php echo esc_html( $post_data['title'] ); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function visibloc_jlg_render_device_visibility_section( $device_posts ) {
    ?>
    <div class="postbox">
        <h2 class="hndle"><span><?php esc_html_e( 'Tableau de bord des blocs avec visibilité par appareil', 'visi-bloc-jlg' ); ?></span></h2>
        <div class="inside">
            <?php if ( empty( $device_posts ) ) : ?>
                <p><?php esc_html_e( "Aucun bloc avec une règle de visibilité par appareil n'a été trouvé.", 'visi-bloc-jlg' ); ?></p>
            <?php else : ?>
                <ul style="list-style: disc; padding-left: 20px;">
                    <?php foreach ( $device_posts as $post_data ) : ?>
                        <li><a href="<?php echo esc_url( $post_data['link'] ); ?>"><?php echo esc_html( $post_data['title'] ); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function visibloc_jlg_render_scheduled_blocks_section( $scheduled_posts ) {
    ?>
    <div class="postbox">
        <h2 class="hndle"><span><?php esc_html_e( 'Tableau de bord des blocs programmés', 'visi-bloc-jlg' ); ?></span></h2>
        <div class="inside">
            <?php if ( empty( $scheduled_posts ) ) : ?>
                <p><?php esc_html_e( "Aucun bloc programmé n'a été trouvé sur votre site.", 'visi-bloc-jlg' ); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( "Titre de l'article / Modèle", 'visi-bloc-jlg' ); ?></th>
                            <th><?php esc_html_e( 'Date de début', 'visi-bloc-jlg' ); ?></th>
                            <th><?php esc_html_e( 'Date de fin', 'visi-bloc-jlg' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $scheduled_posts as $scheduled_block ) :
                            $start_datetime = visibloc_jlg_create_schedule_datetime( $scheduled_block['start'] ?? null );
                            $end_datetime   = visibloc_jlg_create_schedule_datetime( $scheduled_block['end'] ?? null );

                            $start_display = null !== $start_datetime ? wp_date( 'd/m/Y H:i', $start_datetime->getTimestamp() ) : '–';
                            $end_display   = null !== $end_datetime ? wp_date( 'd/m/Y H:i', $end_datetime->getTimestamp() ) : '–';
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url( $scheduled_block['link'] ); ?>"><?php echo esc_html( $scheduled_block['title'] ); ?></a>
                                </td>
                                <td><?php echo esc_html( $start_display ); ?></td>
                                <td><?php echo esc_html( $end_display ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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

function visibloc_jlg_find_blocks_recursive( $blocks, $callback ) {
    $found_blocks = [];
    foreach ( $blocks as $block ) {
        if ( $callback( $block ) ) $found_blocks[] = $block;
        if ( ! empty( $block['innerBlocks'] ) ) $found_blocks = array_merge( $found_blocks, visibloc_jlg_find_blocks_recursive( $block['innerBlocks'], $callback ) );
    }
    return $found_blocks;
}

function visibloc_jlg_get_posts_with_condition( $attribute_callback ) {
    $found_posts = [];
    $post_types  = apply_filters( 'visibloc_jlg_scanned_post_types', [ 'post', 'page', 'wp_template' ] );
    $page        = 1;

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
            $post_content = get_post_field( 'post_content', $post_id );

            if ( false === strpos( $post_content, '<!-- wp:' ) ) {
                continue;
            }

            $blocks       = parse_blocks( $post_content );
            $found_blocks = visibloc_jlg_find_blocks_recursive( $blocks, $attribute_callback );

            if ( ! empty( $found_blocks ) ) {
                $post_title = get_the_title( $post_id );
                $post_link  = get_edit_post_link( $post_id );

                foreach ( $found_blocks as $block ) {
                    $found_posts[] = [
                        'id'        => $post_id,
                        'title'     => $post_title,
                        'link'      => $post_link,
                        'blockName' => $block['blockName'] ?? '',
                        'attrs'     => $block['attrs'] ?? [],
                    ];
                }
            }
        }

        $page++;
    }

    return $found_posts;
}

function visibloc_jlg_get_hidden_posts() {
    $cache_key = 'visibloc_hidden_posts';
    $cached    = get_transient( $cache_key );
    if ( false !== $cached ) {
        return $cached;
    }

    $posts = visibloc_jlg_get_posts_with_condition( function( $block ) {
        return ( isset( $block['blockName'] ) && $block['blockName'] === 'core/group' && ! empty( $block['attrs']['isHidden'] ) );
    } );

    set_transient( $cache_key, $posts, HOUR_IN_SECONDS );
    return $posts;
}

function visibloc_jlg_get_device_specific_posts() {
    $cache_key = 'visibloc_device_posts';
    $cached    = get_transient( $cache_key );
    if ( false !== $cached ) {
        return $cached;
    }

    $posts = visibloc_jlg_get_posts_with_condition( function( $block ) {
        return ( isset( $block['blockName'] ) && $block['blockName'] === 'core/group' && ! empty( $block['attrs']['deviceVisibility'] ) && $block['attrs']['deviceVisibility'] !== 'all' );
    } );

    set_transient( $cache_key, $posts, HOUR_IN_SECONDS );
    return $posts;
}

function visibloc_jlg_get_scheduled_posts() {
    $cache_key = 'visibloc_scheduled_posts';
    $cached    = get_transient( $cache_key );
    if ( false === $cached ) {
        $cached = visibloc_jlg_get_posts_with_condition(
            function( $block ) {
                $has_scheduling_window = (
                    ! empty( $block['attrs']['publishStartDate'] )
                    || ! empty( $block['attrs']['publishEndDate'] )
                );

                return (
                    isset( $block['blockName'] )
                    && $block['blockName'] === 'core/group'
                    && ! empty( $block['attrs']['isSchedulingEnabled'] )
                    && $has_scheduling_window
                );
            }
        );
        set_transient( $cache_key, $cached, HOUR_IN_SECONDS );
    }

    $formatted_posts = [];
    foreach ( $cached as $post_block ) {
        $formatted_posts[] = [
            'id'    => $post_block['id'],
            'title' => $post_block['title'],
            'link'  => $post_block['link'],
            'start' => $post_block['attrs']['publishStartDate'] ?? null,
            'end'   => $post_block['attrs']['publishEndDate'] ?? null,
        ];
    }

    return $formatted_posts;
}

function visibloc_jlg_clear_caches( $unused_post_id = null ) {
    delete_transient( 'visibloc_hidden_posts' );
    delete_transient( 'visibloc_device_posts' );
    delete_transient( 'visibloc_scheduled_posts' );
}

add_action( 'save_post', 'visibloc_jlg_clear_caches' );
add_action( 'deleted_post', 'visibloc_jlg_clear_caches' );
add_action( 'trashed_post', 'visibloc_jlg_clear_caches' );
