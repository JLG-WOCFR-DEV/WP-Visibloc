<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Manage persisted admin alerts for the notification center.
 */
class Visibloc_JLG_Alerts_Manager {
    const STATUS_OPEN      = 'open';
    const STATUS_RESOLVED  = 'resolved';
    const STATUS_DISMISSED = 'dismissed';

    /**
     * Cached singleton instance.
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Cached table existence flag.
     *
     * @var bool|null
     */
    private $table_exists = null;

    /**
     * Retrieve the manager singleton.
     *
     * @return self
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Retrieve the alerts table name.
     *
     * @return string
     */
    public function get_table_name() {
        global $wpdb;

        $prefix = 'wp_';

        if ( isset( $wpdb ) && isset( $wpdb->prefix ) ) {
            $prefix = $wpdb->prefix;
        }

        return $prefix . 'visibloc_alerts';
    }

    /**
     * Determine whether the alerts table exists.
     *
     * @param bool $reset_cache Optional. Whether to reset the cached flag.
     * @return bool
     */
    public function table_exists( $reset_cache = false ) {
        if ( $reset_cache ) {
            $this->table_exists = null;
        }

        if ( null !== $this->table_exists ) {
            return (bool) $this->table_exists;
        }

        global $wpdb;

        if ( ! isset( $wpdb ) || ! method_exists( $wpdb, 'get_var' ) ) {
            $this->table_exists = false;

            return false;
        }

        $table_name = $this->get_table_name();
        $prepared   = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );
        $result     = $wpdb->get_var( $prepared );

        $this->table_exists = ( $result === $table_name );

        return $this->table_exists;
    }

    /**
     * Create or update the alerts table schema.
     */
    public function install_table() {
        global $wpdb;

        if ( ! isset( $wpdb ) ) {
            return;
        }

        $table_name      = $this->get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $schema = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            type varchar(191) NOT NULL,
            context_hash char(40) NOT NULL,
            level varchar(20) NOT NULL DEFAULT 'info',
            title text NOT NULL,
            description text NULL,
            context_json longtext NULL,
            status varchar(20) NOT NULL DEFAULT 'open',
            occurrence_count bigint(20) unsigned NOT NULL DEFAULT 1,
            last_occurrence_at datetime NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at datetime NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY type_context (type, context_hash),
            KEY status (status),
            KEY level (level),
            KEY last_occurrence (last_occurrence_at)
        ) {$charset_collate};";

        if ( ! function_exists( 'dbDelta' ) ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        dbDelta( $schema );

        $this->table_exists( true );
    }

    /**
     * Schedule the daily cron scan if necessary.
     */
    public function ensure_daily_scan_scheduled() {
        if ( ! function_exists( 'wp_next_scheduled' ) || ! function_exists( 'wp_schedule_event' ) ) {
            return;
        }

        if ( false === wp_next_scheduled( 'visibloc_jlg_daily_alerts_scan' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'visibloc_jlg_daily_alerts_scan' );
        }
    }

    /**
     * Cancel the scheduled daily scan.
     */
    public function clear_daily_scan_schedule() {
        if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
            return;
        }

        wp_clear_scheduled_hook( 'visibloc_jlg_daily_alerts_scan' );
    }

    /**
     * Record or refresh an alert.
     *
     * @param string $type        Alert identifier.
     * @param string $level       Severity level (critical|warning|info).
     * @param string $title       Alert title.
     * @param string $description Alert description.
     * @param array  $context     Supplemental context to persist.
     * @return bool
     */
    public function record_alert( $type, $level, $title, $description, array $context = [] ) {
        global $wpdb;

        if ( ! isset( $wpdb ) || ! method_exists( $wpdb, 'query' ) ) {
            return false;
        }

        if ( ! $this->table_exists() ) {
            return false;
        }

        $normalized_type  = $this->normalize_key( $type );
        $normalized_level = $this->normalize_level( $level );
        $normalized_title = $this->sanitize_text( $title );
        $normalized_description = $this->sanitize_textarea( $description );

        if ( '' === $normalized_type || '' === $normalized_title ) {
            return false;
        }

        $hash_basis   = isset( $context['hash_context'] ) && is_array( $context['hash_context'] )
            ? $context['hash_context']
            : $context;
        $context_hash = $this->get_context_hash( $hash_basis );
        $context_json = $this->encode_json( $context );

        $now = $this->current_datetime();

        $table = $this->get_table_name();

        $sql = $wpdb->prepare(
            "INSERT INTO {$table} (type, context_hash, level, title, description, context_json, status, occurrence_count, last_occurrence_at, created_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %d, %s, %s)
            ON DUPLICATE KEY UPDATE
                level = VALUES(level),
                title = VALUES(title),
                description = VALUES(description),
                context_json = VALUES(context_json),
                status = %s,
                occurrence_count = occurrence_count + 1,
                last_occurrence_at = VALUES(last_occurrence_at),
                resolved_at = NULL",
            $normalized_type,
            $context_hash,
            $normalized_level,
            $normalized_title,
            $normalized_description,
            $context_json,
            self::STATUS_OPEN,
            1,
            $now,
            $now,
            self::STATUS_OPEN
        );

        $result = $wpdb->query( $sql );

        if ( false === $result ) {
            return false;
        }

        if ( $wpdb->insert_id > 0 ) {
            return true;
        }

        // Ensure the row exists when the insert resulted in an update.
        $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE type = %s AND context_hash = %s LIMIT 1",
                $normalized_type,
                $context_hash
            )
        );

        return true;
    }

    /**
     * Resolve an alert.
     *
     * @param int $alert_id Alert identifier.
     * @return bool
     */
    public function resolve_alert( $alert_id ) {
        return $this->update_alert_status( $alert_id, self::STATUS_RESOLVED );
    }

    /**
     * Dismiss an alert (permanent ignore).
     *
     * @param int $alert_id Alert identifier.
     * @return bool
     */
    public function dismiss_alert( $alert_id ) {
        return $this->update_alert_status( $alert_id, self::STATUS_DISMISSED );
    }

    /**
     * Resolve alerts of a given type not part of the provided hashes.
     *
     * @param string $type           Alert identifier.
     * @param array  $context_hashes Hashes that should remain open.
     */
    public function resolve_alerts_not_in( $type, array $context_hashes ) {
        global $wpdb;

        if ( ! isset( $wpdb ) || ! method_exists( $wpdb, 'query' ) ) {
            return;
        }

        if ( ! $this->table_exists() ) {
            return;
        }

        $normalized_type = $this->normalize_key( $type );

        if ( '' === $normalized_type ) {
            return;
        }

        $table = $this->get_table_name();
        $now   = $this->current_datetime();

        $placeholders = [];
        $params       = [ $now, $normalized_type, self::STATUS_OPEN ];

        foreach ( array_unique( $context_hashes ) as $hash ) {
            $hash = is_string( $hash ) ? $hash : '';

            if ( '' === $hash ) {
                continue;
            }

            $placeholders[] = '%s';
            $params[]       = $hash;
        }

        if ( empty( $placeholders ) ) {
            $sql = $wpdb->prepare(
                "UPDATE {$table} SET status = %s, resolved_at = %s WHERE type = %s AND status = %s",
                self::STATUS_RESOLVED,
                $now,
                $normalized_type,
                self::STATUS_OPEN
            );

            $wpdb->query( $sql );

            return;
        }

        $in_clause = implode( ',', $placeholders );

        $sql = $wpdb->prepare(
            "UPDATE {$table} SET status = %s, resolved_at = %s WHERE type = %s AND status = %s AND context_hash NOT IN ({$in_clause})",
            array_merge( [ self::STATUS_RESOLVED, $now, $normalized_type, self::STATUS_OPEN ], array_slice( $params, 3 ) )
        );

        $wpdb->query( $sql );
    }

    /**
     * Retrieve persisted alerts.
     *
     * @param array $args Optional arguments.
     * @return array[]
     */
    public function get_alerts( array $args = [] ) {
        global $wpdb;

        if ( ! isset( $wpdb ) || ! method_exists( $wpdb, 'get_results' ) ) {
            return [];
        }

        if ( ! $this->table_exists() ) {
            return [];
        }

        $table = $this->get_table_name();

        $query = "SELECT id, type, context_hash, level, title, description, context_json, status, occurrence_count, last_occurrence_at, created_at, resolved_at FROM {$table}";
        $where = [];
        $params = [];

        if ( isset( $args['status'] ) ) {
            $statuses = array_filter(
                array_map( [ $this, 'normalize_status' ], (array) $args['status'] )
            );

            if ( ! empty( $statuses ) ) {
                $placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
                $where[]      = "status IN ({$placeholders})";
                $params       = array_merge( $params, $statuses );
            }
        }

        if ( isset( $args['level'] ) ) {
            $levels = array_filter(
                array_map( [ $this, 'normalize_level' ], (array) $args['level'] )
            );

            if ( ! empty( $levels ) ) {
                $placeholders = implode( ',', array_fill( 0, count( $levels ), '%s' ) );
                $where[]      = "level IN ({$placeholders})";
                $params       = array_merge( $params, $levels );
            }
        }

        if ( ! empty( $where ) ) {
            $query .= ' WHERE ' . implode( ' AND ', $where );
        }

        $query .= ' ORDER BY status ASC, last_occurrence_at DESC, created_at DESC';

        if ( ! empty( $params ) ) {
            $prepared = $wpdb->prepare( $query, $params );
        } else {
            $prepared = $query;
        }

        $results = $wpdb->get_results( $prepared, ARRAY_A );

        if ( empty( $results ) ) {
            return [];
        }

        foreach ( $results as &$row ) {
            $row['occurrence_count'] = isset( $row['occurrence_count'] ) ? (int) $row['occurrence_count'] : 0;
            $row['context']          = $this->decode_json( $row['context_json'] ?? null );
        }

        return $results;
    }

    /**
     * Retrieve the hash for a context payload.
     *
     * @param array $context Context payload.
     * @return string
     */
    public function get_context_hash( array $context ) {
        $normalized = $this->normalize_context( $context );
        $encoded    = $this->encode_json( $normalized );

        return '' !== $encoded ? sha1( $encoded ) : sha1( '' );
    }

    /**
     * Normalize a status before storage.
     *
     * @param string $status Raw status.
     * @return string
     */
    public function normalize_status( $status ) {
        $status = is_string( $status ) ? strtolower( $status ) : '';

        if ( in_array( $status, [ self::STATUS_OPEN, self::STATUS_RESOLVED, self::STATUS_DISMISSED ], true ) ) {
            return $status;
        }

        return '';
    }

    /**
     * Update the alert status.
     *
     * @param int    $alert_id Alert identifier.
     * @param string $status   Target status.
     * @return bool
     */
    private function update_alert_status( $alert_id, $status ) {
        global $wpdb;

        if ( ! isset( $wpdb ) || ! method_exists( $wpdb, 'update' ) ) {
            return false;
        }

        if ( ! $this->table_exists() ) {
            return false;
        }

        $alert_id = absint( $alert_id );
        $status   = $this->normalize_status( $status );

        if ( $alert_id <= 0 || '' === $status ) {
            return false;
        }

        $updated = $wpdb->update(
            $this->get_table_name(),
            [
                'status'       => $status,
                'resolved_at'  => $this->current_datetime(),
            ],
            [ 'id' => $alert_id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );

        return false !== $updated;
    }

    /**
     * Normalize a key value (type).
     *
     * @param string $value Raw value.
     * @return string
     */
    private function normalize_key( $value ) {
        $value = is_string( $value ) ? strtolower( $value ) : '';

        if ( '' === $value ) {
            return '';
        }

        $value = preg_replace( '/[^a-z0-9_-]/', '-', $value );

        return substr( $value, 0, 191 );
    }

    /**
     * Normalize the alert level.
     *
     * @param string $level Raw level.
     * @return string
     */
    private function normalize_level( $level ) {
        $level = is_string( $level ) ? strtolower( $level ) : '';

        if ( in_array( $level, [ 'critical', 'warning', 'info' ], true ) ) {
            return $level;
        }

        return 'info';
    }

    /**
     * Sanitize a short text value.
     *
     * @param string $text Raw text.
     * @return string
     */
    private function sanitize_text( $text ) {
        $text = is_string( $text ) ? $text : '';

        if ( function_exists( 'sanitize_text_field' ) ) {
            return sanitize_text_field( $text );
        }

        return trim( strip_tags( $text ) );
    }

    /**
     * Sanitize a long text value.
     *
     * @param string $text Raw text.
     * @return string
     */
    private function sanitize_textarea( $text ) {
        $text = is_string( $text ) ? $text : '';

        if ( function_exists( 'sanitize_textarea_field' ) ) {
            return sanitize_textarea_field( $text );
        }

        return trim( strip_tags( $text ) );
    }

    /**
     * Encode a value as JSON.
     *
     * @param mixed $value Value to encode.
     * @return string
     */
    private function encode_json( $value ) {
        $encoder = function_exists( 'wp_json_encode' ) ? 'wp_json_encode' : 'json_encode';
        $encoded = call_user_func( $encoder, $value );

        return is_string( $encoded ) ? $encoded : '';
    }

    /**
     * Decode a JSON payload.
     *
     * @param string|null $value JSON string.
     * @return array
     */
    private function decode_json( $value ) {
        if ( ! is_string( $value ) || '' === $value ) {
            return [];
        }

        $decoded = json_decode( $value, true );

        return is_array( $decoded ) ? $decoded : [];
    }

    /**
     * Normalize a context payload for hashing.
     *
     * @param mixed $context Raw context.
     * @return array|mixed
     */
    private function normalize_context( $context ) {
        if ( is_array( $context ) ) {
            if ( $this->is_assoc( $context ) ) {
                ksort( $context );
            }

            foreach ( $context as $key => $value ) {
                $context[ $key ] = $this->normalize_context( $value );
            }

            return $context;
        }

        if ( is_scalar( $context ) || null === $context ) {
            return $context;
        }

        return (string) $context;
    }

    /**
     * Determine if the array is associative.
     *
     * @param array $array Array to inspect.
     * @return bool
     */
    private function is_assoc( array $array ) {
        $keys = array_keys( $array );

        return array_keys( $keys ) !== $keys;
    }

    /**
     * Retrieve the current datetime string in the site timezone.
     *
     * @return string
     */
    private function current_datetime() {
        if ( function_exists( 'current_time' ) ) {
            return current_time( 'mysql' );
        }

        return gmdate( 'Y-m-d H:i:s' );
    }
}

/**
 * Retrieve the global alerts manager.
 *
 * @return Visibloc_JLG_Alerts_Manager
 */
function visibloc_jlg_alerts() {
    return Visibloc_JLG_Alerts_Manager::instance();
}

/**
 * Install the alerts infrastructure during activation.
 */
function visibloc_jlg_install_alerts_feature() {
    $manager = visibloc_jlg_alerts();
    $manager->install_table();
    $manager->ensure_daily_scan_scheduled();
}

/**
 * Remove scheduled tasks during deactivation.
 */
function visibloc_jlg_deactivate_alerts_feature() {
    visibloc_jlg_alerts()->clear_daily_scan_schedule();
}

if ( function_exists( 'register_activation_hook' ) && defined( 'VISIBLOC_JLG_PLUGIN_FILE' ) ) {
    register_activation_hook( VISIBLOC_JLG_PLUGIN_FILE, 'visibloc_jlg_install_alerts_feature' );
}

if ( function_exists( 'register_deactivation_hook' ) && defined( 'VISIBLOC_JLG_PLUGIN_FILE' ) ) {
    register_deactivation_hook( VISIBLOC_JLG_PLUGIN_FILE, 'visibloc_jlg_deactivate_alerts_feature' );
}

/**
 * Handle the "mark as resolved" admin action.
 */
function visibloc_jlg_handle_mark_alert_resolved() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Permissions insuffisantes pour modifier cette alerte.', 'visi-bloc-jlg' ) );
    }

    check_admin_referer( 'visibloc_jlg_alert_mark_as_resolved' );

    $alert_id = isset( $_POST['alert_id'] ) ? absint( $_POST['alert_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

    if ( $alert_id > 0 ) {
        visibloc_jlg_alerts()->resolve_alert( $alert_id );
    }

    visibloc_jlg_redirect_after_alert_action( 'resolved' );
}
add_action( 'admin_post_visibloc_jlg_mark_alert_resolved', 'visibloc_jlg_handle_mark_alert_resolved' );

/**
 * Handle the "dismiss" admin action.
 */
function visibloc_jlg_handle_dismiss_alert() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Permissions insuffisantes pour ignorer cette alerte.', 'visi-bloc-jlg' ) );
    }

    check_admin_referer( 'visibloc_jlg_alert_dismiss' );

    $alert_id = isset( $_POST['alert_id'] ) ? absint( $_POST['alert_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

    if ( $alert_id > 0 ) {
        visibloc_jlg_alerts()->dismiss_alert( $alert_id );
    }

    visibloc_jlg_redirect_after_alert_action( 'dismissed' );
}
add_action( 'admin_post_visibloc_jlg_dismiss_alert', 'visibloc_jlg_handle_dismiss_alert' );

/**
 * Redirect the user after performing an alert management action.
 *
 * @param string $flag Notice identifier.
 */
function visibloc_jlg_redirect_after_alert_action( $flag ) {
    $redirect = wp_get_referer();

    if ( ! $redirect ) {
        $redirect = admin_url( 'admin.php?page=visi-bloc-jlg-help' );
    }

    $redirect = add_query_arg( 'visibloc_alert_notice', sanitize_key( $flag ), $redirect );

    wp_safe_redirect( $redirect );
    exit;
}

/**
 * Display admin notices for alert management actions.
 */
function visibloc_jlg_render_alert_admin_notice() {
    if ( ! isset( $_GET['visibloc_alert_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return;
    }

    $notice = sanitize_key( wp_unslash( $_GET['visibloc_alert_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    switch ( $notice ) {
        case 'resolved':
            $message = __( 'L’alerte a été marquée comme résolue.', 'visi-bloc-jlg' );
            $class   = 'updated';
            break;
        case 'dismissed':
            $message = __( 'L’alerte a été ignorée.', 'visi-bloc-jlg' );
            $class   = 'updated';
            break;
        default:
            return;
    }

    printf(
        '<div class="%1$s notice is-dismissible"><p>%2$s</p></div>',
        esc_attr( $class ),
        esc_html( $message )
    );
}
add_action( 'admin_notices', 'visibloc_jlg_render_alert_admin_notice' );

/**
 * Execute the daily alerts scan.
 */
function visibloc_jlg_run_daily_alerts_scan() {
    $manager = visibloc_jlg_alerts();

    if ( ! function_exists( 'visibloc_jlg_generate_runtime_alerts' ) ) {
        require_once __DIR__ . '/admin-settings.php';
    }

    $context = [
        'fallback_settings' => function_exists( 'visibloc_jlg_get_fallback_settings' ) ? visibloc_jlg_get_fallback_settings() : [],
        'hidden_posts'      => function_exists( 'visibloc_jlg_get_hidden_posts' ) ? visibloc_jlg_get_hidden_posts() : [],
        'scheduled_posts'   => function_exists( 'visibloc_jlg_get_scheduled_posts' ) ? visibloc_jlg_get_scheduled_posts() : [],
    ];

    $runtime_alerts = function_exists( 'visibloc_jlg_generate_runtime_alerts' )
        ? visibloc_jlg_generate_runtime_alerts( $context )
        : [];

    $managed_types = [
        'missing-fallback',
        'schedule-expired',
        'schedule-expiring-soon',
        'schedule-conflict',
    ];

    $active_hashes_by_type = [];

    foreach ( $runtime_alerts as $alert ) {
        if ( empty( $alert['persistent'] ) ) {
            continue;
        }

        if ( empty( $alert['type'] ) || ! in_array( $alert['type'], $managed_types, true ) ) {
            continue;
        }

        $hash_context = isset( $alert['hash_context'] ) && is_array( $alert['hash_context'] )
            ? $alert['hash_context']
            : [];

        $payload = [
            'hash_context' => $hash_context,
            'items'        => isset( $alert['items'] ) ? (array) $alert['items'] : [],
            'actions'      => isset( $alert['actions'] ) ? (array) $alert['actions'] : [],
            'meta'         => isset( $alert['meta'] ) ? (array) $alert['meta'] : [],
        ];

        $manager->record_alert(
            $alert['type'],
            $alert['level'] ?? 'info',
            $alert['title'] ?? '',
            $alert['description'] ?? '',
            $payload
        );

        $context_hash = $manager->get_context_hash( $hash_context );

        if ( ! isset( $active_hashes_by_type[ $alert['type'] ] ) ) {
            $active_hashes_by_type[ $alert['type'] ] = [];
        }

        $active_hashes_by_type[ $alert['type'] ][] = $context_hash;
    }

    foreach ( $managed_types as $type ) {
        $hashes = isset( $active_hashes_by_type[ $type ] ) ? $active_hashes_by_type[ $type ] : [];
        $manager->resolve_alerts_not_in( $type, $hashes );
    }
}
add_action( 'visibloc_jlg_daily_alerts_scan', 'visibloc_jlg_run_daily_alerts_scan' );

