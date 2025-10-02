<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter(
    'gettext_visi-bloc-jlg',
    static function ( $translation, $text, $domain ) {
        if ( 'visi-bloc-jlg' !== $domain ) {
            return $translation;
        }

        if ( 'Hidden block' === $text ) {
            return 'Bloc caché';
        }

        return $translation;
    },
    10,
    3
);
