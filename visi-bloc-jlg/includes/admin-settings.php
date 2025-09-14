<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_init', 'visibloc_jlg_handle_options_save' );
function visibloc_jlg_handle_options_save() {
    if ( ! isset( $_POST['visibloc_nonce'] ) || ! current_user_can( 'manage_options' ) ) return;
    
    if ( wp_verify_nonce( $_POST['visibloc_nonce'], 'visibloc_toggle_debug' ) ) {
        $current_status = get_option( 'visibloc_debug_mode', 'off' );
        update_option( 'visibloc_debug_mode', ( $current_status === 'on' ) ? 'off' : 'on' );
        wp_redirect( admin_url( 'admin.php?page=visi-bloc-jlg-help&status=updated' ) );
        exit;
    }
    
    if ( wp_verify_nonce( $_POST['visibloc_nonce'], 'visibloc_save_breakpoints' ) ) {
        if ( isset( $_POST['visibloc_breakpoint_mobile'] ) ) update_option( 'visibloc_breakpoint_mobile', intval( $_POST['visibloc_breakpoint_mobile'] ) );
        if ( isset( $_POST['visibloc_breakpoint_tablet'] ) ) update_option( 'visibloc_breakpoint_tablet', intval( $_POST['visibloc_breakpoint_tablet'] ) );
        wp_redirect( admin_url( 'admin.php?page=visi-bloc-jlg-help&status=updated' ) );
        exit;
    }

    if ( wp_verify_nonce( $_POST['visibloc_nonce'], 'visibloc_save_permissions' ) ) {
        $submitted_roles = $_POST['visibloc_preview_roles'] ?? [];
        $sanitized_roles = array_map( 'sanitize_key', (array) $submitted_roles );
        // On s'assure que l'administrateur est toujours inclus
        if ( ! in_array( 'administrator', $sanitized_roles ) ) {
            $sanitized_roles[] = 'administrator';
        }
        update_option( 'visibloc_preview_roles', $sanitized_roles );
        wp_redirect( admin_url( 'admin.php?page=visi-bloc-jlg-help&status=updated' ) );
        exit;
    }
}

add_action( 'admin_menu', 'visibloc_jlg_add_admin_menu' );
function visibloc_jlg_add_admin_menu() {
    add_menu_page( 'Aide & Réglages Visi-Bloc - JLG', 'Visi-Bloc - JLG', 'manage_options', 'visi-bloc-jlg-help', 'visibloc_jlg_render_help_page_content', 'dashicons-visibility', 25 );
}

function visibloc_jlg_render_help_page_content() {
    $debug_status = get_option( 'visibloc_debug_mode', 'off' );
    $mobile_bp = get_option( 'visibloc_breakpoint_mobile', 781 );
    $tablet_bp = get_option( 'visibloc_breakpoint_tablet', 1024 );
    $allowed_roles = get_option( 'visibloc_preview_roles', ['administrator'] );
    
    $scheduled_posts = visibloc_jlg_get_scheduled_posts();
    $hidden_posts = visibloc_jlg_get_hidden_posts();
    $device_posts = visibloc_jlg_get_device_specific_posts();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Visi-Bloc - JLG - Aide et Réglages', 'visi-bloc-jlg' ); ?></h1>
        <?php if ( isset( $_GET['status'] ) && $_GET['status'] === 'updated' ) : ?>
            <div id="message" class="updated notice is-dismissible"><p>Réglages mis à jour.</p></div>
        <?php endif; ?>
        <div id="poststuff">
            <div class="postbox">
                <h2 class="hndle"><span>Permissions d'Aperçu</span></h2>
                <div class="inside">
                    <form method="POST" action="">
                        <p>Cochez les rôles qui peuvent voir les blocs cachés/programmés sur le site public.</p>
                        <?php
                        $editable_roles = get_editable_roles();
                        foreach ( $editable_roles as $slug => $details ) :
                            $is_disabled = ( $slug === 'administrator' );
                            $is_checked = ( in_array( $slug, $allowed_roles, true ) || $is_disabled );
                        ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="visibloc_preview_roles[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $is_checked ); ?> <?php disabled( $is_disabled ); ?> />
                                <?php echo esc_html( $details['name'] ); ?>
                                <?php if($is_disabled) echo " (toujours activé)"; ?>
                            </label>
                        <?php endforeach; ?>
                        <?php wp_nonce_field( 'visibloc_save_permissions', 'visibloc_nonce' ); ?>
                        <?php submit_button('Enregistrer les Permissions'); ?>
                    </form>
                </div>
            </div>
            <div class="postbox">
                <h2 class="hndle"><span>Tableau de Bord des Blocs Masqués (via Œil)</span></h2>
                <div class="inside">
                     <?php if ( empty( $hidden_posts ) ) : ?><p>Aucun bloc masqué manuellement n'a été trouvé.</p><?php else : ?><ul style="list-style: disc; padding-left: 20px;"><?php foreach ( $hidden_posts as $post_data ) : ?><li><a href="<?php echo esc_url( $post_data['link'] ); ?>"><?php echo esc_html( $post_data['title'] ); ?></a></li><?php endforeach; ?></ul><?php endif; ?>
                </div>
            </div>
            <div class="postbox">
                <h2 class="hndle"><span>Tableau de Bord des Blocs avec Visibilité par Appareil</span></h2>
                <div class="inside">
                    <?php if ( empty( $device_posts ) ) : ?><p>Aucun bloc avec une règle de visibilité par appareil n'a été trouvé.</p><?php else : ?><ul style="list-style: disc; padding-left: 20px;"><?php foreach ( $device_posts as $post_data ) : ?><li><a href="<?php echo esc_url( $post_data['link'] ); ?>"><?php echo esc_html( $post_data['title'] ); ?></a></li><?php endforeach; ?></ul><?php endif; ?>
                </div>
            </div>
            <div class="postbox">
                <h2 class="hndle"><span>Tableau de Bord des Blocs Programmés</span></h2>
                <div class="inside">
                    <?php if ( empty( $scheduled_posts ) ) : ?><p>Aucun bloc programmé n'a été trouvé sur votre site.</p><?php else : ?><table class="wp-list-table widefat striped"><thead><tr><th>Titre de l'Article / Modèle</th><th>Date de Début</th><th>Date de Fin</th></tr></thead><tbody><?php foreach ( $scheduled_posts as $post_data ) : ?><tr><td><a href="<?php echo esc_url( $post_data['link'] ); ?>"><?php echo esc_html( $post_data['title'] ); ?></a></td><td><?php echo $post_data['start'] ? esc_html( wp_date( 'd/m/Y H:i', strtotime($post_data['start']) ) ) : '–'; ?></td><td><?php echo $post_data['end'] ? esc_html( wp_date( 'd/m/Y H:i', strtotime($post_data['end']) ) ) : '–'; ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?>
                </div>
            </div>
             <div class="postbox">
                <h2 class="hndle"><span>Mode de Débogage</span></h2>
                <div class="inside"><form method="POST" action=""><p>Statut actuel : <strong><?php echo $debug_status === 'on' ? 'ACTIVÉ' : 'DÉSACTIVÉ'; ?></strong></p><input type="hidden" name="action" value="visibloc_toggle_debug"><?php wp_nonce_field( 'visibloc_toggle_debug', 'visibloc_nonce' ); ?><button type="submit" class="button button-primary"><?php echo $debug_status === 'on' ? 'Désactiver' : 'Activer'; ?></button></form></div>
            </div>
            <div class="postbox">
                <h2 class="hndle"><span>Réglage des Points de Rupture</span></h2>
                <div class="inside"><form method="POST" action=""><p>Alignez les largeurs d'écran avec celles de votre thème.</p><table class="form-table"><tr><th scope="row"><label for="visibloc_breakpoint_mobile">Largeur max. Mobile</label></th><td><input name="visibloc_breakpoint_mobile" type="number" id="visibloc_breakpoint_mobile" value="<?php echo esc_attr( $mobile_bp ); ?>" class="small-text"> px</td></tr><tr><th scope="row"><label for="visibloc_breakpoint_tablet">Largeur max. Tablette</label></th><td><input name="visibloc_breakpoint_tablet" type="number" id="visibloc_breakpoint_tablet" value="<?php echo esc_attr( $tablet_bp ); ?>" class="small-text"> px</td></tr></table><input type="hidden" name="action" value="visibloc_save_breakpoints"><?php wp_nonce_field( 'visibloc_save_breakpoints', 'visibloc_nonce' ); ?><?php submit_button('Enregistrer les breakpoints'); ?></form></div>
            </div>
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
    $query_args = [
        'post_type' => ['post', 'page', 'wp_template'], 'post_status' => ['publish', 'future', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
    ];
    $query = new WP_Query( $query_args );
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            if ( has_blocks( get_the_content() ) ) {
                $blocks = parse_blocks( get_the_content() );
                $found_blocks = visibloc_jlg_find_blocks_recursive( $blocks, $attribute_callback );
                if ( ! empty( $found_blocks ) ) {
                    $found_posts[] = [
                        'id' => get_the_ID(), 'title' => get_the_title(), 'link' => get_edit_post_link(),
                        'attrs' => $found_blocks[0]['attrs'] ?? [],
                    ];
                }
            }
        }
    }
    wp_reset_postdata();
    return $found_posts;
}

function visibloc_jlg_get_hidden_posts() {
    return visibloc_jlg_get_posts_with_condition( function( $block ) {
        return ( isset($block['blockName']) && $block['blockName'] === 'core/group' && ! empty( $block['attrs']['isHidden'] ) );
    });
}

function visibloc_jlg_get_device_specific_posts() {
    return visibloc_jlg_get_posts_with_condition( function( $block ) {
        return ( isset($block['blockName']) && $block['blockName'] === 'core/group' && ! empty( $block['attrs']['deviceVisibility'] ) && $block['attrs']['deviceVisibility'] !== 'all' );
    });
}

function visibloc_jlg_get_scheduled_posts() {
    $posts = visibloc_jlg_get_posts_with_condition( function( $block ) {
        return ( isset($block['blockName']) && $block['blockName'] === 'core/group' && ! empty( $block['attrs']['isSchedulingEnabled'] ) && ( ! empty( $block['attrs']['publishStartDate'] ) || ! empty( $attrs['publishEndDate'] ) ) );
    });
    $formatted_posts = [];
    foreach($posts as $post) {
        $formatted_posts[] = [
            'id' => $post['id'], 'title' => $post['title'], 'link' => $post['link'],
            'start' => $post['attrs']['publishStartDate'] ?? null, 'end' => $post['attrs']['publishEndDate'] ?? null,
        ];
    }
    return $formatted_posts;
}