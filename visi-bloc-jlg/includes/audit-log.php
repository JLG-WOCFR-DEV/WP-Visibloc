<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve the fully-qualified audit log table name.
 *
 * @return string
 */
function visibloc_jlg_get_audit_log_table_name() {
    global $wpdb;

    $prefix = 'wp_';

    if ( isset( $wpdb ) && isset( $wpdb->prefix ) ) {
        $prefix = $wpdb->prefix;
    }

    return $prefix . 'visibloc_audit';
}

/**
 * Determine if the audit log table exists.
 *
 * @param bool $reset_cache Whether to force a new lookup.
 * @return bool
 */
function visibloc_jlg_audit_log_table_exists( $reset_cache = false ) {
    static $exists = null;

    if ( $reset_cache ) {
        $exists = null;
    }

    if ( null !== $exists ) {
        return $exists;
    }

    global $wpdb;

    if ( ! isset( $wpdb ) || ! method_exists( $wpdb, 'get_var' ) ) {
        $exists = false;

        return $exists;
    }

    $table_name = visibloc_jlg_get_audit_log_table_name();
    $prepared   = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );
    $result     = $wpdb->get_var( $prepared );

    $exists = ( $result === $table_name );

    return $exists;
}

/**
 * Create or update the audit log table structure.
 */
function visibloc_jlg_install_audit_log_table() {
    global $wpdb;

    if ( ! isset( $wpdb ) ) {
        return;
    }

    $table_name      = visibloc_jlg_get_audit_log_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    $schema = "CREATE TABLE {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        event_type varchar(191) NOT NULL,
        message text NULL,
        context longtext NULL,
        user_id bigint(20) unsigned NULL,
        post_id bigint(20) unsigned NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY event_type (event_type),
        KEY post_id (post_id),
        KEY created_at (created_at)
    ) {$charset_collate};";

    if ( ! function_exists( 'dbDelta' ) ) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }

    dbDelta( $schema );

    visibloc_jlg_audit_log_table_exists( true );
}

if ( function_exists( 'register_activation_hook' ) && defined( 'VISIBLOC_JLG_PLUGIN_FILE' ) ) {
    register_activation_hook( VISIBLOC_JLG_PLUGIN_FILE, 'visibloc_jlg_install_audit_log_table' );
}

/**
 * Normalize the event identifier before storage.
 *
 * @param string $event_type Raw event identifier.
 * @return string
 */
function visibloc_jlg_normalize_audit_event_type( $event_type ) {
    $event_type = is_string( $event_type ) ? $event_type : '';

    if ( '' === $event_type ) {
        return '';
    }

    if ( function_exists( 'sanitize_text_field' ) ) {
        $event_type = sanitize_text_field( $event_type );
    } else {
        $event_type = trim( strip_tags( $event_type ) );
    }

    $event_type = str_replace( ' ', '_', $event_type );

    return substr( $event_type, 0, 191 );
}

/**
 * Record an event inside the audit log table.
 *
 * @param string $event_type Event identifier.
 * @param array  $payload    Optional contextual payload.
 * @return bool True when the event was stored.
 */
function visibloc_jlg_record_audit_event( $event_type, array $payload = [] ) {
    global $wpdb;

    $normalized_event = visibloc_jlg_normalize_audit_event_type( $event_type );

    if ( '' === $normalized_event ) {
        return false;
    }

    if ( ! isset( $wpdb ) || ! method_exists( $wpdb, 'insert' ) ) {
        return false;
    }

    if ( ! visibloc_jlg_audit_log_table_exists() ) {
        return false;
    }

    $message = isset( $payload['message'] ) ? (string) $payload['message'] : '';

    if ( function_exists( 'sanitize_textarea_field' ) ) {
        $message = sanitize_textarea_field( $message );
    } else {
        $message = trim( strip_tags( $message ) );
    }

    $context = [];

    if ( isset( $payload['context'] ) && is_array( $payload['context'] ) ) {
        $context = $payload['context'];
    } else {
        $context = array_diff_key(
            $payload,
            [
                'message' => true,
                'user_id' => true,
                'post_id' => true,
            ]
        );
    }

    $json_encoder = function_exists( 'wp_json_encode' ) ? 'wp_json_encode' : 'json_encode';
    $context_json = ! empty( $context ) ? call_user_func( $json_encoder, $context ) : null;

    $user_id = 0;
    if ( isset( $payload['user_id'] ) ) {
        $user_id = (int) $payload['user_id'];
    } elseif ( function_exists( 'get_current_user_id' ) ) {
        $user_id = (int) get_current_user_id();
    }

    $post_id = isset( $payload['post_id'] ) ? (int) $payload['post_id'] : 0;

    $created_at = function_exists( 'current_time' ) ? current_time( 'mysql' ) : gmdate( 'Y-m-d H:i:s' );

    $data = [
        'event_type' => $normalized_event,
        'message'    => $message,
        'created_at' => $created_at,
    ];

    $formats = [
        '%s',
        '%s',
        '%s',
    ];

    if ( null !== $context_json ) {
        $data['context'] = $context_json;
        $formats[]       = '%s';
    }

    if ( $user_id > 0 ) {
        $data['user_id'] = $user_id;
        $formats[]       = '%d';
    }

    if ( $post_id > 0 ) {
        $data['post_id'] = $post_id;
        $formats[]       = '%d';
    }

    $inserted = $wpdb->insert(
        visibloc_jlg_get_audit_log_table_name(),
        $data,
        $formats
    );

    return false !== $inserted;
}
