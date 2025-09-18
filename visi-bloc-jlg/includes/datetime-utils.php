<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Parse a Gutenberg schedule datetime attribute into a site-local timestamp.
 *
 * @param string|null $value Raw attribute value.
 *
 * @return int|null Timestamp on success, null otherwise.
 */
function visibloc_jlg_parse_schedule_datetime( $value ) {
    if ( empty( $value ) || ! is_string( $value ) ) {
        return null;
    }

    $timezone = wp_timezone();
    $datetime = date_create_immutable( $value, $timezone );

    if ( false === $datetime ) {
        return null;
    }

    return $datetime->getTimestamp();
}
