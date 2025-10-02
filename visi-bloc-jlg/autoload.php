<?php
spl_autoload_register(
    static function ( $class ) {
        if ( 0 !== strpos( $class, 'VisiBloc\\' ) ) {
            return;
        }

        $relative = substr( $class, strlen( 'VisiBloc\\' ) );
        $path     = __DIR__ . '/src/' . str_replace( '\\', '/', $relative ) . '.php';

        if ( is_readable( $path ) ) {
            require_once $path;
        }
    }
);
