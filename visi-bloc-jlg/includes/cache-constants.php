<?php
if ( ! defined( 'VISIBLOC_JLG_DEVICE_CSS_CACHE_GROUP' ) ) {
    define( 'VISIBLOC_JLG_DEVICE_CSS_CACHE_GROUP', 'visibloc_jlg' );
}

if ( ! defined( 'VISIBLOC_JLG_DEVICE_CSS_CACHE_KEY' ) ) {
    define( 'VISIBLOC_JLG_DEVICE_CSS_CACHE_KEY', 'visibloc_device_css_cache' );
}

if ( ! defined( 'VISIBLOC_JLG_DEVICE_CSS_TRANSIENT_PREFIX' ) ) {
    define( 'VISIBLOC_JLG_DEVICE_CSS_TRANSIENT_PREFIX', 'visibloc_device_css_' );
}

if ( ! defined( 'VISIBLOC_JLG_DEVICE_CSS_BUCKET_OPTION' ) ) {
    define( 'VISIBLOC_JLG_DEVICE_CSS_BUCKET_OPTION', 'visibloc_device_css_transients' );
}

if ( ! defined( 'VISIBLOC_JLG_DEVICE_CSS_TRANSIENT_EXPIRATION' ) ) {
    define(
        'VISIBLOC_JLG_DEVICE_CSS_TRANSIENT_EXPIRATION',
        defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400
    );
}
