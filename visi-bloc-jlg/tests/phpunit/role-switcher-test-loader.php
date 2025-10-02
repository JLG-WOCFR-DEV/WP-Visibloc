<?php

use Visibloc\Tests\Support\TestServices;

if ( ! class_exists( TestServices::class, false ) ) {
    $autoloader = dirname( __DIR__, 3 ) . '/vendor/autoload.php';

    if ( file_exists( $autoloader ) ) {
        require_once $autoloader;
    }
}

TestServices::bootstrap();

if ( isset( $GLOBALS['visibloc_role_switcher_loaded'] ) && $GLOBALS['visibloc_role_switcher_loaded'] ) {
    return;
}

$role_switcher_path = dirname( __DIR__, 2 ) . '/includes/role-switcher.php';
$role_switcher_code = file_get_contents( $role_switcher_path );

if ( false === $role_switcher_code ) {
    throw new RuntimeException( 'Unable to read role switcher source.' );
}

$role_switcher_code = preg_replace(
    '/function\s+visibloc_jlg_set_preview_cookie\s*\(/',
    'function visibloc_jlg_set_preview_cookie_original(',
    $role_switcher_code,
    1,
    $replacement_count
);

if ( 1 !== $replacement_count ) {
    throw new RuntimeException( 'Failed to prepare role switcher source for testing.' );
}

eval( '?>' . $role_switcher_code );

if ( ! isset( $GLOBALS['visibloc_test_cookie_log'] ) ) {
    $GLOBALS['visibloc_test_cookie_log'] = [];
}

if ( ! function_exists( 'visibloc_jlg_set_preview_cookie' ) ) {
    function visibloc_jlg_set_preview_cookie( $value, $expires ) {
        global $visibloc_test_cookie_log;

        $visibloc_test_cookie_log[] = [
            'value'   => $value,
            'expires' => $expires,
        ];

        return true;
    }
}

$GLOBALS['visibloc_role_switcher_loaded'] = true;
