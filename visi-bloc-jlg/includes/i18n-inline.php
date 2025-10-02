<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function visibloc_jlg_inline_translate_hidden_block( $translation, $text, $domain ) {
    if ( 'Hidden block' === $text ) {
        return 'Bloc caché';
    }

    return $translation;
}

add_filter( 'gettext_visi-bloc-jlg', 'visibloc_jlg_inline_translate_hidden_block', 10, 3 );
