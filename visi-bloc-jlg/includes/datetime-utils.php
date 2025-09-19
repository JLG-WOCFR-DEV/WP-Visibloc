<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Convert a Gutenberg schedule datetime attribute into a DateTimeImmutable instance
 * that respects the site's timezone.
 *
 * @param string|null $value Raw attribute value.
 *
 * @return DateTimeImmutable|null Datetime object on success, null otherwise.
 */
function visibloc_jlg_create_schedule_datetime( $value ) {
    if ( empty( $value ) || ! is_string( $value ) ) {
        return null;
    }

    $timezone = wp_timezone();
    $datetime = date_create_immutable( $value, $timezone );

    if ( false === $datetime ) {
        return null;
    }

    return $datetime;
}

/**
 * Parse a Gutenberg schedule datetime attribute into a site-local timestamp.
 *
 * @param string|null $value Raw attribute value.
 *
 * @return int|null Timestamp on success, null otherwise.
 */
function visibloc_jlg_parse_schedule_datetime( $value ) {
    $datetime = visibloc_jlg_create_schedule_datetime( $value );

    if ( null === $datetime ) {
        return null;
    }

    return $datetime->getTimestamp();
}
