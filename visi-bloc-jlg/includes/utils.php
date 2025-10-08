<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'visibloc_jlg_normalize_boolean_value' ) ) {
    /**
     * Normalize a mixed value into a strict boolean.
     *
     * @param mixed $value Raw value to normalize.
     * @return bool
     */
    function visibloc_jlg_normalize_boolean_value( $value ) {
        if ( is_bool( $value ) ) {
            return $value;
        }

        if ( null === $value ) {
            return false;
        }

        if ( is_array( $value ) || is_object( $value ) ) {
            return false;
        }

        if ( is_int( $value ) || is_float( $value ) ) {
            return 0.0 !== (float) $value;
        }

        if ( is_string( $value ) ) {
            $trimmed = trim( $value );

            if ( '' === $trimmed ) {
                return false;
            }
        }

        $filtered = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

        if ( null !== $filtered ) {
            return $filtered;
        }

        return false;
    }
}

if ( ! function_exists( 'visibloc_jlg_normalize_boolean' ) ) {
    /**
     * Backwards compatible alias for {@see visibloc_jlg_normalize_boolean_value()}.
     *
     * @param mixed $value Raw value to normalize.
     * @return bool
     */
    function visibloc_jlg_normalize_boolean( $value ) {
        return visibloc_jlg_normalize_boolean_value( $value );
    }
}
