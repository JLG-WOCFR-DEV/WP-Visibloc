<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Parse a scheduling datetime string into a timestamp based on the site's timezone.
 *
 * @param string|null $value Datetime string to parse.
 *
 * @return int|null Timestamp on success, null otherwise.
 */
function visibloc_jlg_parse_schedule_datetime( $value ) {
    if ( empty( $value ) ) {
        return null;
    }

    $datetime = date_create_immutable( $value, wp_timezone() );

    if ( false === $datetime ) {
        return null;
    }

    return $datetime->getTimestamp();
}
