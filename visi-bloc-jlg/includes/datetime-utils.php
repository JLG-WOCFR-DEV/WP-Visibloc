<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Normalize a timezone identifier coming from block attributes or options.
 *
 * @param mixed $value Raw timezone value.
 *
 * @return string Normalized timezone identifier or "site" when invalid.
 */
function visibloc_jlg_normalize_schedule_timezone( $value ) {
    if ( $value instanceof DateTimeZone ) {
        return $value->getName();
    }

    if ( ! is_string( $value ) ) {
        return 'site';
    }

    $trimmed = trim( $value );

    if ( '' === $trimmed ) {
        return 'site';
    }

    if ( 0 === strcasecmp( 'site', $trimmed ) ) {
        return 'site';
    }

    try {
        new DateTimeZone( $trimmed );

        return $trimmed;
    } catch ( Exception $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        // Fall back to the site timezone when an invalid identifier is provided.
    }

    return 'site';
}

/**
 * Resolve a timezone identifier into a DateTimeZone instance.
 *
 * @param mixed $timezone Timezone identifier or instance.
 *
 * @return DateTimeZone
 */
function visibloc_jlg_resolve_schedule_timezone( $timezone = null ) {
    if ( $timezone instanceof DateTimeZone ) {
        return $timezone;
    }

    if ( is_string( $timezone ) ) {
        $normalized = visibloc_jlg_normalize_schedule_timezone( $timezone );

        if ( 'site' !== $normalized ) {
            try {
                return new DateTimeZone( $normalized );
            } catch ( Exception $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
                // Fall back to the site timezone when an invalid identifier is provided.
            }
        }
    }

    return wp_timezone();
}

/**
 * Format a timezone offset using the canonical ±HH:MM representation.
 *
 * @param DateTimeZone $timezone Target timezone.
 *
 * @return string
 */
function visibloc_jlg_format_timezone_offset_label( DateTimeZone $timezone ) {
    $reference = new DateTimeImmutable( 'now', $timezone );
    $offset    = (int) $timezone->getOffset( $reference );

    $sign        = ( $offset < 0 ) ? '-' : '+';
    $absolute    = abs( $offset );
    $hours       = floor( $absolute / 3600 );
    $minutes     = floor( ( $absolute % 3600 ) / 60 );
    $paddedHours = str_pad( (string) $hours, 2, '0', STR_PAD_LEFT );
    $paddedMins  = str_pad( (string) $minutes, 2, '0', STR_PAD_LEFT );

    return sprintf( '%s%s:%s', $sign, $paddedHours, $paddedMins );
}

/**
 * Retrieve a human-friendly label for a timezone.
 *
 * @param mixed $timezone Timezone identifier or instance.
 *
 * @return string
 */
function visibloc_jlg_get_timezone_display_label( $timezone ) {
    $normalized = visibloc_jlg_normalize_schedule_timezone( $timezone );
    $resolved   = visibloc_jlg_resolve_schedule_timezone( $normalized );

    if ( 'site' === $normalized ) {
        $site_timezone_string = get_option( 'timezone_string' );

        if ( is_string( $site_timezone_string ) && '' !== $site_timezone_string ) {
            $base_label = $site_timezone_string;
        } else {
            $base_label = $resolved->getName();
        }
    } else {
        $base_label = $normalized;
    }

    $offset_label = visibloc_jlg_format_timezone_offset_label( $resolved );

    return sprintf( '%s (UTC%s)', $base_label, $offset_label );
}

/**
 * Produce a sorted list of timezone options suitable for editor UIs.
 *
 * @return array[]
 */
function visibloc_jlg_get_timezone_options() {
    $identifiers = timezone_identifiers_list();

    if ( ! is_array( $identifiers ) || empty( $identifiers ) ) {
        return [];
    }

    $options = [];

    foreach ( $identifiers as $identifier ) {
        if ( ! is_string( $identifier ) || '' === $identifier ) {
            continue;
        }

        $options[] = [
            'value' => $identifier,
            'label' => visibloc_jlg_get_timezone_display_label( $identifier ),
        ];
    }

    usort(
        $options,
        static function ( $first, $second ) {
            return strcmp( (string) ( $first['label'] ?? '' ), (string) ( $second['label'] ?? '' ) );
        }
    );

    return $options;
}

/**
 * Convert a Gutenberg schedule datetime attribute into a DateTimeImmutable instance
 * that respects a chosen timezone.
 *
 * @param string|null $value Raw attribute value.
 * @param mixed       $timezone Optional timezone identifier.
 *
 * @return DateTimeImmutable|null Datetime object on success, null otherwise.
 */
function visibloc_jlg_create_schedule_datetime( $value, $timezone = null ) {
    if ( empty( $value ) || ! is_string( $value ) ) {
        return null;
    }

    $resolved_timezone = visibloc_jlg_resolve_schedule_timezone( $timezone );
    $datetime          = date_create_immutable( $value, $resolved_timezone );

    if ( false === $datetime ) {
        return null;
    }

    return $datetime;
}

/**
 * Parse a Gutenberg schedule datetime attribute into a timestamp for comparisons.
 *
 * @param string|null $value Raw attribute value.
 * @param mixed       $timezone Optional timezone identifier.
 *
 * @return int|null Timestamp on success, null otherwise.
 */
function visibloc_jlg_parse_schedule_datetime( $value, $timezone = null ) {
    $datetime = visibloc_jlg_create_schedule_datetime( $value, $timezone );

    if ( null === $datetime ) {
        return null;
    }

    return $datetime->getTimestamp();
}

/**
 * Retrieve the WordPress datetime format combining date and time options.
 *
 * When either option is empty, a sane default is used to avoid returning an
 * incomplete format string.
 *
 * @return string Datetime format string.
 */
function visibloc_jlg_get_wp_datetime_format() {
    $date_format = get_option( 'date_format', 'F j, Y' );
    $time_format = get_option( 'time_format', 'H:i' );

    if ( ! is_string( $date_format ) || '' === trim( $date_format ) ) {
        $date_format = 'F j, Y';
    }

    if ( ! is_string( $time_format ) || '' === trim( $time_format ) ) {
        $time_format = 'H:i';
    }

    return trim( $date_format . ' ' . $time_format );
}
