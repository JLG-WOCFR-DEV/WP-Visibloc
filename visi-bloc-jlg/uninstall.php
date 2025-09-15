<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

// Supprime les options de la base de données
delete_option( 'visibloc_debug_mode' );
delete_option( 'visibloc_breakpoint_mobile' );
delete_option( 'visibloc_breakpoint_tablet' );
delete_option( 'visibloc_preview_roles' );
