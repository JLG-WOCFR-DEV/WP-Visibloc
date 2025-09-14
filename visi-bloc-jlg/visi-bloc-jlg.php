<?php
/**
 * Plugin Name:       Visi-Bloc - JLG
 * Description:       Ajoute des options avancées pour cacher/afficher des blocs sur le site public.
 * Version:           1.1
 * Author:            Jérôme Le Gousse
 */

if ( ! defined( 'WPINC' ) ) { exit; }

// Charge les différents modules du plugin
require_once __DIR__ . '/includes/admin-settings.php';
require_once __DIR__ . '/includes/assets.php';
require_once __DIR__ . '/includes/visibility-logic.php';
require_once __DIR__ . '/includes/role-switcher.php';

/**
 * Vérifie si l'utilisateur actuellement connecté a un rôle autorisé à voir les aperçus.
 *
 * @return bool True si l'utilisateur peut voir les aperçus, sinon false.
 */
function visibloc_jlg_can_user_preview() {
    if ( ! is_user_logged_in() ) {
        return false;
    }

    // Récupère les rôles autorisés depuis les options, avec 'administrator' comme valeur par défaut sécurisée.
    $allowed_roles = get_option( 'visibloc_preview_roles', ['administrator'] );
    
    // Si pour une raison quelconque l'option est vide, on sécurise en autorisant uniquement l'admin.
    if ( empty( $allowed_roles ) ) {
        $allowed_roles = ['administrator'];
    }

    $user = wp_get_current_user();
    $user_roles = (array) $user->roles;

    // Vérifie s'il y a une intersection entre les rôles de l'utilisateur et les rôles autorisés.
    if ( count( array_intersect( $user_roles, $allowed_roles ) ) > 0 ) {
        return true;
    }

    return false;
}