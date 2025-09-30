<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'VISIBLOC_JLG_DEFAULT_SUPPORTED_BLOCKS' ) ) {
    define( 'VISIBLOC_JLG_DEFAULT_SUPPORTED_BLOCKS', [ 'core/group' ] );
}

if ( ! function_exists( 'visibloc_jlg_normalize_block_names' ) ) {
    /**
     * Sanitize and normalize a list of block names.
     *
     * @param mixed $block_names Raw block names.
     * @return array List of sanitized block names.
     */
    function visibloc_jlg_normalize_block_names( $block_names ) {
        if ( ! is_array( $block_names ) ) {
            return [];
        }

        $normalized = [];

        foreach ( $block_names as $block_name ) {
            if ( ! is_string( $block_name ) ) {
                continue;
            }

            $parts = explode( '/', $block_name );

            if ( count( $parts ) < 2 ) {
                continue;
            }

            $namespace        = sanitize_key( array_shift( $parts ) );
            $block_slug_parts = array_filter(
                array_map(
                    static function ( $segment ) {
                        return sanitize_key( $segment );
                    },
                    $parts
                )
            );

            if ( '' === $namespace || empty( $block_slug_parts ) ) {
                continue;
            }

            $normalized[ $namespace . '/' . implode( '/', $block_slug_parts ) ] = true;
        }

        return array_keys( $normalized );
    }
}
