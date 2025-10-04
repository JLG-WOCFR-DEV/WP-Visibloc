<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'visibloc_jlg_inline_translate_hidden_block' ) ) {
    // Ensure the "Hidden block" string is registered for translations.
    __( 'Hidden block', 'visi-bloc-jlg' );

    /**
     * Provide inline translations for strings that are generated dynamically.
     *
     * @param string $translation The translated text.
     * @param string $text        The original text to translate.
     * @param string $domain      Textdomain for the translated string.
     * @return string The filtered translation.
     */
    function visibloc_jlg_inline_translate_hidden_block( $translation, $text, $domain ) {
        if ( 'visi-bloc-jlg' !== $domain ) {
            return $translation;
        }

        if ( 'Hidden block' === $text ) {
            $locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();

            if ( $locale && 0 === strpos( strtolower( $locale ), 'fr' ) ) {
                return 'Bloc caché';
            }
        }

        return $translation;
    }
}

add_filter( 'gettext_visi-bloc-jlg', 'visibloc_jlg_inline_translate_hidden_block', 10, 3 );
