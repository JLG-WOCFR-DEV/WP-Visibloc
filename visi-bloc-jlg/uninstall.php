<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

// Supprime les options de la base de données
delete_option( 'visibloc_debug_mode' );
delete_option( 'visibloc_breakpoint_mobile' );
delete_option( 'visibloc_breakpoint_tablet' );
delete_option( 'visibloc_preview_roles' );

// Supprime les transients de cache du plugin
delete_transient( 'visibloc_hidden_posts' );
delete_transient( 'visibloc_device_posts' );
delete_transient( 'visibloc_scheduled_posts' );
delete_transient( 'visibloc_group_block_metadata' );
