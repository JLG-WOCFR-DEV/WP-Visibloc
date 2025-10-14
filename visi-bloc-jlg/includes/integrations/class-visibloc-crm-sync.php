<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/class-visibloc-crm-connector-interface.php';

/**
 * Manage CRM connectors and synchronization routines.
 */
class Visibloc_CRM_Sync {
    const OPTION_SETTINGS = 'visibloc_crm_connector_settings';
    const OPTION_SNAPSHOT = 'visibloc_crm_segments_snapshot';
    const CRON_HOOK       = 'visibloc_crm_refresh_segments';
    const LOCK_TRANSIENT  = 'visibloc_crm_sync_lock';

    /** @var bool */
    private static $is_syncing = false;

    /**
     * Register hooks used by the synchronizer.
     */
    public static function init() {
        add_action( 'init', [ __CLASS__, 'maybe_schedule_refresh' ] );
        add_action( self::CRON_HOOK, [ __CLASS__, 'handle_scheduled_refresh' ] );
    }

    /**
     * Retrieve sanitized connector settings.
     *
     * @return array{connector:string,credentials:array<string,string>}
     */
    public static function get_settings() {
        $stored = get_option( self::OPTION_SETTINGS, [] );

        if ( ! is_array( $stored ) ) {
            $stored = [];
        }

        $connector   = isset( $stored['connector'] ) ? sanitize_key( $stored['connector'] ) : '';
        $credentials = isset( $stored['credentials'] ) && is_array( $stored['credentials'] )
            ? array_filter( array_map( 'strval', $stored['credentials'] ) )
            : [];

        return [
            'connector'   => $connector,
            'credentials' => $credentials,
        ];
    }

    /**
     * Update settings and reconfigure the scheduler accordingly.
     *
     * @param array{connector:string,credentials:array<string,string>} $settings Settings to persist.
     * @return void
     */
    public static function update_settings( array $settings ) {
        $previous = self::get_settings();
        $sanitized = [
            'connector'   => isset( $settings['connector'] ) ? sanitize_key( $settings['connector'] ) : '',
            'credentials' => isset( $settings['credentials'] ) && is_array( $settings['credentials'] )
                ? array_map( 'strval', $settings['credentials'] )
                : [],
        ];

        update_option( self::OPTION_SETTINGS, $sanitized, false );

        if ( $previous['connector'] !== $sanitized['connector'] ) {
            self::reset_snapshot( $sanitized['connector'] );
        }

        self::maybe_schedule_refresh();
    }

    /**
     * Fetch the current snapshot describing CRM segments.
     *
     * @return array{
     *     connector:string,
     *     segments:array<int,array<string,mixed>>,
     *     status:string,
     *     error_message:string,
     *     error_code:string,
     *     attempted_at:int,
     *     synced_at:int
     * }
     */
    public static function get_snapshot() {
        $snapshot = get_option( self::OPTION_SNAPSHOT, [] );

        if ( ! is_array( $snapshot ) ) {
            $snapshot = [];
        }

        $segments = isset( $snapshot['segments'] ) && is_array( $snapshot['segments'] )
            ? array_values( array_filter( array_map( [ __CLASS__, 'sanitize_segment_for_storage' ], $snapshot['segments'] ) ) )
            : [];

        return [
            'connector'     => isset( $snapshot['connector'] ) ? sanitize_key( $snapshot['connector'] ) : '',
            'segments'      => $segments,
            'status'        => isset( $snapshot['status'] ) ? sanitize_key( $snapshot['status'] ) : 'idle',
            'error_message' => isset( $snapshot['error_message'] ) ? self::sanitize_plain_text( $snapshot['error_message'] ) : '',
            'error_code'    => isset( $snapshot['error_code'] ) ? sanitize_key( $snapshot['error_code'] ) : '',
            'attempted_at'  => isset( $snapshot['attempted_at'] ) ? absint( $snapshot['attempted_at'] ) : 0,
            'synced_at'     => isset( $snapshot['synced_at'] ) ? absint( $snapshot['synced_at'] ) : 0,
        ];
    }

    /**
     * Store a sanitized snapshot in the options table.
     *
     * @param array $snapshot Snapshot data.
     * @return void
     */
    public static function update_snapshot( array $snapshot ) {
        $current = self::get_snapshot();

        $prepared = [
            'connector'     => isset( $snapshot['connector'] ) ? sanitize_key( $snapshot['connector'] ) : $current['connector'],
            'segments'      => isset( $snapshot['segments'] ) && is_array( $snapshot['segments'] )
                ? array_values( array_filter( array_map( [ __CLASS__, 'sanitize_segment_for_storage' ], $snapshot['segments'] ) ) )
                : $current['segments'],
            'status'        => isset( $snapshot['status'] ) ? sanitize_key( $snapshot['status'] ) : $current['status'],
            'error_message' => isset( $snapshot['error_message'] )
                ? self::sanitize_plain_text( $snapshot['error_message'] )
                : $current['error_message'],
            'error_code'    => isset( $snapshot['error_code'] ) ? sanitize_key( $snapshot['error_code'] ) : $current['error_code'],
            'attempted_at'  => isset( $snapshot['attempted_at'] ) ? absint( $snapshot['attempted_at'] ) : $current['attempted_at'],
            'synced_at'     => isset( $snapshot['synced_at'] ) ? absint( $snapshot['synced_at'] ) : $current['synced_at'],
        ];

        update_option( self::OPTION_SNAPSHOT, $prepared, false );
    }

    /**
     * Reset the stored snapshot when the connector changes.
     *
     * @param string $connector Connector identifier.
     * @return void
     */
    public static function reset_snapshot( $connector ) {
        $connector = sanitize_key( $connector );

        update_option(
            self::OPTION_SNAPSHOT,
            [
                'connector'     => $connector,
                'segments'      => [],
                'status'        => 'idle',
                'error_message' => '',
                'error_code'    => '',
                'attempted_at'  => 0,
                'synced_at'     => 0,
            ],
            false
        );
    }

    /**
     * Ensure the cron event is scheduled when required.
     *
     * @return void
     */
    public static function maybe_schedule_refresh() {
        if ( ! function_exists( 'wp_next_scheduled' ) || ! function_exists( 'wp_schedule_event' ) ) {
            return;
        }

        $settings  = self::get_settings();
        $connector = $settings['connector'];

        if ( '' === $connector ) {
            self::unschedule_refresh();

            return;
        }

        $next = wp_next_scheduled( self::CRON_HOOK );

        if ( false !== $next ) {
            return;
        }

        $interval = self::get_refresh_interval();

        wp_schedule_event( time() + MINUTE_IN_SECONDS, $interval, self::CRON_HOOK );
    }

    /**
     * Remove scheduled events when the connector is disabled.
     *
     * @return void
     */
    public static function unschedule_refresh() {
        if ( ! function_exists( 'wp_next_scheduled' ) || ! function_exists( 'wp_unschedule_event' ) ) {
            return;
        }

        while ( false !== ( $timestamp = wp_next_scheduled( self::CRON_HOOK ) ) ) {
            wp_unschedule_event( $timestamp, self::CRON_HOOK );
        }
    }

    /**
     * Handle the cron callback used to refresh segments.
     *
     * @return void
     */
    public static function handle_scheduled_refresh() {
        self::refresh_segments( [ 'source' => 'schedule' ] );
    }

    /**
     * Refresh the cached segments by calling the active connector.
     *
     * @param array $args {
     *     @type string $source Arbitrary context identifier.
     *     @type bool   $force  Force a refresh even if a lock is present.
     * }
     * @return array{status:string,message:string}|WP_Error
     */
    public static function refresh_segments( array $args = [] ) {
        $defaults = [
            'source' => 'manual',
            'force'  => false,
        ];
        $args     = wp_parse_args( $args, $defaults );

        if ( self::$is_syncing && ! $args['force'] ) {
            return new WP_Error( 'visibloc_crm_sync_running', __( 'Une synchronisation est déjà en cours.', 'visi-bloc-jlg' ) );
        }

        $lock_key = self::LOCK_TRANSIENT;

        if ( ! $args['force'] && get_transient( $lock_key ) ) {
            return new WP_Error( 'visibloc_crm_sync_locked', __( 'Une synchronisation vient de s’exécuter, veuillez patienter.', 'visi-bloc-jlg' ) );
        }

        $settings  = self::get_settings();
        $connector = $settings['connector'];

        if ( '' === $connector ) {
            return new WP_Error( 'visibloc_crm_connector_missing', __( 'Aucun connecteur CRM n’est sélectionné.', 'visi-bloc-jlg' ) );
        }

        $instance = self::load_connector( $connector, $settings['credentials'] );

        if ( is_wp_error( $instance ) ) {
            self::update_snapshot(
                [
                    'status'        => 'error',
                    'error_code'    => $instance->get_error_code(),
                    'error_message' => $instance->get_error_message(),
                    'attempted_at'  => self::get_current_timestamp(),
                    'connector'     => $connector,
                ]
            );

            return $instance;
        }

        self::$is_syncing = true;
        set_transient( $lock_key, 1, MINUTE_IN_SECONDS * 5 );

        $start     = microtime( true );
        $timestamp = self::get_current_timestamp();
        $snapshot  = self::get_snapshot();

        try {
            $fetched = $instance->fetch_segments();
        } catch ( Exception $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
            $fetched = new WP_Error( 'visibloc_crm_connector_exception', $exception->getMessage() );
        }

        delete_transient( $lock_key );
        self::$is_syncing = false;

        if ( is_wp_error( $fetched ) ) {
            self::update_snapshot(
                [
                    'status'        => 'error',
                    'error_code'    => $fetched->get_error_code(),
                    'error_message' => $fetched->get_error_message(),
                    'attempted_at'  => $timestamp,
                    'connector'     => $connector,
                    'segments'      => $snapshot['segments'],
                    'synced_at'     => $snapshot['synced_at'],
                ]
            );

            return $fetched;
        }

        $normalized = self::normalize_segments( $fetched, $connector );

        self::update_snapshot(
            [
                'status'        => 'success',
                'error_code'    => '',
                'error_message' => '',
                'segments'      => $normalized,
                'attempted_at'  => $timestamp,
                'synced_at'     => $timestamp,
                'connector'     => $connector,
            ]
        );

        $duration = max( 0, microtime( true ) - $start );

        /* translators: %s: Number of segments retrieved. */
        $message = sprintf( __( '%s segments synchronisés avec succès.', 'visi-bloc-jlg' ), count( $normalized ) );

        if ( $duration > 0 ) {
            /* translators: %s: human readable duration. */
            $message .= ' ' . sprintf( __( 'Durée de la synchronisation : %s secondes.', 'visi-bloc-jlg' ), number_format_i18n( $duration, 2 ) );
        }

        return [
            'status'  => 'success',
            'message' => $message,
        ];
    }

    /**
     * Instantiate the connector selected by the administrator.
     *
     * @param string               $connector   Connector identifier.
     * @param array<string,string> $credentials Connector credentials.
     * @return Visibloc_CRM_Connector_Interface|WP_Error
     */
    public static function load_connector( $connector, array $credentials = [] ) {
        $connector = sanitize_key( $connector );

        $resolved = apply_filters( 'visibloc_jlg_crm_connector', null, $connector, $credentials );

        if ( is_callable( $resolved ) ) {
            $resolved = call_user_func( $resolved, $connector, $credentials );
        }

        if ( is_string( $resolved ) && class_exists( $resolved ) ) {
            $resolved = new $resolved( $credentials );
        }

        if ( ! $resolved instanceof Visibloc_CRM_Connector_Interface ) {
            return new WP_Error( 'visibloc_crm_connector_invalid', __( 'Le connecteur CRM sélectionné est introuvable ou invalide.', 'visi-bloc-jlg' ) );
        }

        return $resolved;
    }

    /**
     * Retrieve the list of available connectors exposed through filters.
     *
     * @return array<int,array<string,string>>
     */
    public static function get_connector_choices() {
        $choices = apply_filters( 'visibloc_jlg_crm_connectors', [] );

        if ( ! is_array( $choices ) ) {
            $choices = [];
        }

        $normalized = [];

        foreach ( $choices as $key => $definition ) {
            if ( is_string( $definition ) ) {
                $normalized[] = [
                    'id'          => sanitize_key( is_string( $key ) ? $key : $definition ),
                    'label'       => self::sanitize_plain_text( $definition ),
                    'description' => '',
                ];
                continue;
            }

            if ( ! is_array( $definition ) ) {
                continue;
            }

            $id = '';

            if ( isset( $definition['id'] ) && is_string( $definition['id'] ) ) {
                $id = sanitize_key( $definition['id'] );
            } elseif ( is_string( $key ) ) {
                $id = sanitize_key( $key );
            }

            if ( '' === $id ) {
                continue;
            }

            $label = isset( $definition['label'] ) ? self::sanitize_plain_text( $definition['label'] ) : $id;

            $normalized[] = [
                'id'          => $id,
                'label'       => $label,
                'description' => isset( $definition['description'] )
                    ? self::sanitize_plain_text( $definition['description'] )
                    : '',
            ];
        }

        return $normalized;
    }

    /**
     * Retrieve metadata describing credential fields required by a connector.
     *
     * @param string $connector Connector identifier.
     * @return array<int,array<string,string>>
     */
    public static function get_connector_fields( $connector ) {
        $fields = apply_filters( 'visibloc_jlg_crm_connector_fields', [], $connector );

        if ( ! is_array( $fields ) ) {
            $fields = [];
        }

        $normalized = [];

        foreach ( $fields as $definition ) {
            if ( ! is_array( $definition ) ) {
                continue;
            }

            $key   = isset( $definition['key'] ) ? sanitize_key( $definition['key'] ) : '';
            $label = isset( $definition['label'] ) ? self::sanitize_plain_text( $definition['label'] ) : '';

            if ( '' === $key || '' === $label ) {
                continue;
            }

            $normalized[] = [
                'key'         => $key,
                'label'       => $label,
                'type'        => isset( $definition['type'] ) ? sanitize_key( $definition['type'] ) : 'text',
                'description' => isset( $definition['description'] )
                    ? self::sanitize_plain_text( $definition['description'] )
                    : '',
                'placeholder' => isset( $definition['placeholder'] )
                    ? self::sanitize_plain_text( $definition['placeholder'] )
                    : '',
            ];
        }

        if ( empty( $normalized ) ) {
            $normalized = [
                [
                    'key'         => 'api_key',
                    'label'       => __( 'Clé API', 'visi-bloc-jlg' ),
                    'type'        => 'text',
                    'description' => __( 'Identifiant fourni par votre CRM.', 'visi-bloc-jlg' ),
                    'placeholder' => '',
                ],
                [
                    'key'         => 'api_secret',
                    'label'       => __( 'Secret API', 'visi-bloc-jlg' ),
                    'type'        => 'password',
                    'description' => __( 'Secret associé à la clé API.', 'visi-bloc-jlg' ),
                    'placeholder' => '',
                ],
            ];
        }

        return $normalized;
    }

    /**
     * Sanitize credential values before persisting them.
     *
     * @param string               $connector            Connector identifier.
     * @param array<string,string> $raw_credentials      Submitted credentials.
     * @param array<string,string> $previous_credentials Previously stored credentials.
     * @return array<string,string>
     */
    public static function sanitize_credentials( $connector, array $raw_credentials, array $previous_credentials = [] ) {
        $connector = sanitize_key( $connector );
        $fields    = self::get_connector_fields( $connector );
        $sanitized = [];

        foreach ( $fields as $field ) {
            $key      = $field['key'];
            $type     = isset( $field['type'] ) ? $field['type'] : 'text';
            $provided = isset( $raw_credentials[ $key ] ) ? $raw_credentials[ $key ] : null;

            if ( 'password' === $type && '' === $provided ) {
                if ( isset( $previous_credentials[ $key ] ) ) {
                    $sanitized[ $key ] = $previous_credentials[ $key ];
                }
                continue;
            }

            $sanitized[ $key ] = self::sanitize_credential_value( $provided, $type );
        }

        return array_filter( $sanitized, 'strlen' );
    }

    /**
     * Normalize and sanitize the list of segments returned by a connector.
     *
     * @param mixed  $segments  Raw segment list.
     * @param string $connector Connector identifier.
     * @return array<int,array<string,string>>
     */
    public static function normalize_segments( $segments, $connector ) {
        if ( ! is_array( $segments ) ) {
            return [];
        }

        $normalized = [];

        foreach ( $segments as $segment ) {
            if ( is_object( $segment ) ) {
                $segment = (array) $segment;
            }

            if ( ! is_array( $segment ) ) {
                continue;
            }

            $identifier = '';

            if ( isset( $segment['value'] ) ) {
                $identifier = (string) $segment['value'];
            } elseif ( isset( $segment['id'] ) ) {
                $identifier = (string) $segment['id'];
            }

            $identifier = trim( $identifier );

            if ( '' === $identifier ) {
                continue;
            }

            $label = isset( $segment['label'] ) ? self::sanitize_plain_text( $segment['label'] ) : '';
            $desc  = isset( $segment['description'] ) ? self::sanitize_plain_text( $segment['description'] ) : '';
            $src   = isset( $segment['source'] ) ? self::sanitize_plain_text( $segment['source'] ) : '';

            if ( '' === $label ) {
                $label = $identifier;
            }

            $normalized[ $identifier ] = [
                'value'       => $identifier,
                'label'       => $label,
                'description' => $desc,
                'source'      => $src,
            ];
        }

        /**
         * Filter the normalized segments before they are persisted.
         *
         * @param array<int,array<string,string>> $normalized Normalized segments.
         * @param string                          $connector  Connector identifier.
         */
        $normalized = apply_filters( 'visibloc_jlg_normalized_crm_segments', array_values( $normalized ), $connector );

        if ( ! is_array( $normalized ) ) {
            return [];
        }

        return array_values( array_filter( array_map( [ __CLASS__, 'sanitize_segment_for_storage' ], $normalized ) ) );
    }

    /**
     * Sanitize a single segment entry prior to storage.
     *
     * @param mixed $segment Segment payload.
     * @return array<string,string>|null
     */
    public static function sanitize_segment_for_storage( $segment ) {
        if ( is_object( $segment ) ) {
            $segment = (array) $segment;
        }

        if ( ! is_array( $segment ) ) {
            return null;
        }

        $value = isset( $segment['value'] ) ? (string) $segment['value'] : '';
        $value = trim( $value );

        if ( '' === $value ) {
            return null;
        }

        return [
            'value'       => $value,
            'label'       => isset( $segment['label'] ) ? self::sanitize_plain_text( $segment['label'] ) : $value,
            'description' => isset( $segment['description'] ) ? self::sanitize_plain_text( $segment['description'] ) : '',
            'source'      => isset( $segment['source'] ) ? self::sanitize_plain_text( $segment['source'] ) : '',
        ];
    }

    /**
     * Retrieve the label associated with a connector identifier.
     *
     * @param string $connector Connector identifier.
     * @return string
     */
    public static function get_connector_label( $connector ) {
        $connector = sanitize_key( $connector );

        foreach ( self::get_connector_choices() as $choice ) {
            if ( isset( $choice['id'] ) && $connector === $choice['id'] ) {
                return $choice['label'];
            }
        }

        return $connector;
    }

    /**
     * Determine the WP-Cron schedule used to refresh segments.
     *
     * @return string
     */
    public static function get_refresh_interval() {
        $interval = apply_filters( 'visibloc_jlg_crm_refresh_interval', 'hourly' );

        if ( ! is_string( $interval ) || '' === $interval ) {
            return 'hourly';
        }

        return $interval;
    }

    /**
     * Sanitize a credential value based on the field type.
     *
     * @param mixed  $value Raw value.
     * @param string $type  Field type.
     * @return string
     */
    private static function sanitize_credential_value( $value, $type ) {
        switch ( $type ) {
            case 'textarea':
                return sanitize_textarea_field( (string) $value );
            case 'url':
                return esc_url_raw( (string) $value );
            default:
                return self::sanitize_plain_text( (string) $value );
        }
    }

    /**
     * Sanitize a generic plain text value.
     *
     * @param string $value Raw value.
     * @return string
     */
    private static function sanitize_plain_text( $value ) {
        $value = (string) $value;
        $value = wp_strip_all_tags( $value );

        return trim( $value );
    }

    /**
     * Retrieve the current timestamp using the WP time zone when available.
     *
     * @return int
     */
    private static function get_current_timestamp() {
        if ( function_exists( 'current_time' ) ) {
            return (int) current_time( 'timestamp' );
        }

        return time();
    }
}
