<?php
/**
 * Geolocation helpers and provider abstractions.
 *
 * @package VisiBlocJLG
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! interface_exists( 'Visibloc_Jlg_Geolocation_Provider_Interface' ) ) {
    /**
     * Basic contract for geolocation providers.
     */
    interface Visibloc_Jlg_Geolocation_Provider_Interface {
        /**
         * Locate an IP address and return metadata.
         *
         * @param string $ip_address IPv4 or IPv6 address.
         * @return array<string, mixed>
         */
        public function locate( $ip_address );
    }
}

if ( ! class_exists( 'Visibloc_Jlg_Request_Header_Geolocation_Provider' ) ) {
    /**
     * Provider that relies on reverse proxy headers populated by the host.
     */
    class Visibloc_Jlg_Request_Header_Geolocation_Provider implements Visibloc_Jlg_Geolocation_Provider_Interface {
        /**
         * {@inheritdoc}
         */
        public function locate( $ip_address ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
            $headers = isset( $_SERVER ) && is_array( $_SERVER ) ? $_SERVER : [];
            $headers = apply_filters( 'visibloc_jlg_geolocation_server_data', $headers );

            $candidates = [
                'HTTP_CF_IPCOUNTRY',
                'HTTP_X_COUNTRY_CODE',
                'HTTP_X_GEO_COUNTRY',
                'GEOIP_COUNTRY_CODE',
                'HTTP_X_APPENGINE_COUNTRY',
            ];

            foreach ( $candidates as $key ) {
                if ( ! isset( $headers[ $key ] ) ) {
                    continue;
                }

                $value = $headers[ $key ];

                if ( ! is_string( $value ) ) {
                    continue;
                }

                $code = visibloc_jlg_normalize_country_code( $value );

                if ( '' === $code ) {
                    continue;
                }

                return [
                    'country_code' => $code,
                    'source'       => $key,
                ];
            }

            /**
             * Allow third-parties to provide a fallback geolocation match.
             *
             * @param array<string, mixed>|null $result   The provider result.
             * @param string                    $ip       The request IP address.
             * @param array<string, mixed>      $headers  Sanitized server headers.
             */
            $filtered = apply_filters( 'visibloc_jlg_geolocation_fallback_result', null, $ip_address, $headers );

            if ( is_array( $filtered ) ) {
                $code = visibloc_jlg_normalize_country_code( $filtered['country_code'] ?? '' );

                if ( '' !== $code ) {
                    return [
                        'country_code' => $code,
                        'source'       => isset( $filtered['source'] ) ? (string) $filtered['source'] : 'filtered',
                        'country_name' => isset( $filtered['country_name'] ) ? (string) $filtered['country_name'] : '',
                    ];
                }
            }

            return [
                'country_code' => '',
                'source'       => '',
            ];
        }
    }
}

if ( ! class_exists( 'Visibloc_Jlg_Transient_Geolocation_Provider' ) ) {
    /**
     * Provider decorator that caches results inside WordPress transients.
     */
    class Visibloc_Jlg_Transient_Geolocation_Provider implements Visibloc_Jlg_Geolocation_Provider_Interface {
        /**
         * Inner provider instance.
         *
         * @var Visibloc_Jlg_Geolocation_Provider_Interface
         */
        private $inner;

        /**
         * Cache lifetime in seconds.
         *
         * @var int
         */
        private $ttl;

        /**
         * Constructor.
         *
         * @param Visibloc_Jlg_Geolocation_Provider_Interface $inner Inner provider.
         * @param int                                          $ttl   Cache lifetime in seconds.
         */
        public function __construct( Visibloc_Jlg_Geolocation_Provider_Interface $inner, $ttl ) {
            $this->inner = $inner;
            $this->ttl   = max( 0, (int) $ttl );
        }

        /**
         * {@inheritdoc}
         */
        public function locate( $ip_address ) {
            $cache_key = visibloc_jlg_get_geolocation_cache_key( $ip_address );

            if ( $this->ttl > 0 && function_exists( 'get_transient' ) ) {
                $cached_value = get_transient( $cache_key );

                if ( is_array( $cached_value ) ) {
                    return $cached_value;
                }
            }

            $result = $this->inner->locate( $ip_address );

            if ( $this->ttl > 0 && function_exists( 'set_transient' ) ) {
                set_transient( $cache_key, $result, $this->ttl );
            }

            return $result;
        }
    }
}

if ( ! function_exists( 'visibloc_jlg_normalize_country_code' ) ) {
    /**
     * Normalize a raw country code to the ISO 3166-1 alpha-2 format.
     *
     * @param mixed $value Raw input.
     * @return string
     */
    function visibloc_jlg_normalize_country_code( $value ) {
        if ( ! is_string( $value ) ) {
            return '';
        }

        $code = strtoupper( preg_replace( '/[^a-zA-Z]/', '', $value ) );

        if ( 2 !== strlen( $code ) ) {
            return '';
        }

        return $code;
    }
}

if ( ! function_exists( 'visibloc_jlg_get_request_ip_address' ) ) {
    /**
     * Attempt to determine the visitor IP address.
     *
     * @return string
     */
    function visibloc_jlg_get_request_ip_address() {
        $server = isset( $_SERVER ) && is_array( $_SERVER ) ? $_SERVER : [];
        $server = apply_filters( 'visibloc_jlg_geolocation_server_data', $server );

        $candidates = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        $detected_ip = '';

        foreach ( $candidates as $key ) {
            if ( empty( $server[ $key ] ) ) {
                continue;
            }

            $value = $server[ $key ];

            if ( ! is_string( $value ) ) {
                continue;
            }

            $first_ip = trim( current( explode( ',', $value ) ) );

            if ( function_exists( 'filter_var' ) && filter_var( $first_ip, FILTER_VALIDATE_IP ) ) {
                $detected_ip = $first_ip;

                break;
            }

            if ( '' !== $first_ip ) {
                $detected_ip = $first_ip;

                break;
            }
        }

        /**
         * Allow third-parties to override the detected IP address.
         *
         * @param string $ip Current best effort IP address.
         */
        return (string) apply_filters( 'visibloc_jlg_geolocation_ip_address', $detected_ip );
    }
}

if ( ! function_exists( 'visibloc_jlg_get_geolocation_cache_key' ) ) {
    /**
     * Build the cache key associated with an IP address.
     *
     * @param string $ip_address IP address.
     * @return string
     */
    function visibloc_jlg_get_geolocation_cache_key( $ip_address ) {
        $suffix = '' !== $ip_address ? md5( $ip_address ) : 'default';

        return 'visibloc_geo_' . $suffix;
    }
}

if ( ! function_exists( 'visibloc_jlg_get_geolocation_cache_ttl' ) ) {
    /**
     * Retrieve the geolocation cache lifetime in seconds.
     *
     * @return int
     */
    function visibloc_jlg_get_geolocation_cache_ttl() {
        $default_ttl = defined( 'MINUTE_IN_SECONDS' ) // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
            ? 30 * MINUTE_IN_SECONDS
            : 1800;

        /**
         * Filter the geolocation cache lifetime.
         *
         * @param int $ttl Cache lifetime in seconds.
         */
        return (int) apply_filters( 'visibloc_jlg_geolocation_cache_ttl', $default_ttl );
    }
}

if ( ! function_exists( 'visibloc_jlg_get_geolocation_provider' ) ) {
    /**
     * Retrieve the configured geolocation provider.
     *
     * @return Visibloc_Jlg_Geolocation_Provider_Interface
     */
    function visibloc_jlg_get_geolocation_provider() {
        static $provider = null;

        if ( $provider instanceof Visibloc_Jlg_Geolocation_Provider_Interface ) {
            return $provider;
        }

        $provider = new Visibloc_Jlg_Request_Header_Geolocation_Provider();

        /**
         * Allow developers to replace the underlying geolocation provider.
         *
         * @param Visibloc_Jlg_Geolocation_Provider_Interface $provider Provider instance.
         */
        $provider = apply_filters( 'visibloc_jlg_geolocation_provider', $provider );

        if ( ! $provider instanceof Visibloc_Jlg_Geolocation_Provider_Interface ) {
            $provider = new Visibloc_Jlg_Request_Header_Geolocation_Provider();
        }

        $cache_ttl = visibloc_jlg_get_geolocation_cache_ttl();

        if ( $cache_ttl > 0 ) {
            $provider = new Visibloc_Jlg_Transient_Geolocation_Provider( $provider, $cache_ttl );
        }

        return $provider;
    }
}

if ( ! function_exists( 'visibloc_jlg_get_geolocation_context' ) ) {
    /**
     * Retrieve the geolocation context for the current request.
     *
     * @param bool $reset_cache Optional. Whether to flush the runtime cache.
     * @return array<string, mixed>
     */
    function visibloc_jlg_get_geolocation_context( $reset_cache = false ) {
        static $cache = null;

        if ( $reset_cache ) {
            $cache = null;
        }

        if ( null !== $cache ) {
            return $cache;
        }

        $ip_address = visibloc_jlg_get_request_ip_address();
        $provider   = visibloc_jlg_get_geolocation_provider();
        $result     = $provider->locate( $ip_address );

        $country_code = '';
        $country_name = '';
        $source       = '';

        if ( is_array( $result ) ) {
            $country_code = visibloc_jlg_normalize_country_code( $result['country_code'] ?? '' );
            $country_name = isset( $result['country_name'] ) && is_string( $result['country_name'] )
                ? $result['country_name']
                : '';
            $source       = isset( $result['source'] ) ? (string) $result['source'] : '';
        }

        $cache = [
            'ip'           => $ip_address,
            'country_code' => $country_code,
            'country_name' => $country_name,
            'source'       => $source,
        ];

        /**
         * Filter the resolved geolocation context.
         *
         * @param array<string, mixed>                            $context  Resolved context.
         * @param array<string, mixed>|object|null                $result   Raw provider result.
         * @param string                                           $ip       Original IP address.
         * @param Visibloc_Jlg_Geolocation_Provider_Interface $provider Provider instance.
         */
        $cache = apply_filters( 'visibloc_jlg_geolocation_context', $cache, $result, $ip_address, $provider );

        return $cache;
    }
}

if ( ! function_exists( 'visibloc_jlg_get_geolocation_countries_map' ) ) {
    /**
     * Retrieve the full map of ISO country codes to localized labels.
     *
     * @return array<string, string>
     */
    function visibloc_jlg_get_geolocation_countries_map() {
        static $countries = null;

        if ( null === $countries ) {
            $countries_file = dirname( __FILE__ ) . '/data/geolocation-countries.php';

            $countries = file_exists( $countries_file ) ? include $countries_file : [];

            if ( ! is_array( $countries ) ) {
                $countries = [];
            }
        }

        return $countries;
    }
}

if ( ! function_exists( 'visibloc_jlg_get_geolocation_country_name' ) ) {
    /**
     * Retrieve the localized label associated with a country code.
     *
     * @param string $country_code ISO 3166-1 alpha-2 country code.
     * @return string
     */
    function visibloc_jlg_get_geolocation_country_name( $country_code ) {
        $countries = visibloc_jlg_get_geolocation_countries_map();
        $code      = visibloc_jlg_normalize_country_code( $country_code );

        return isset( $countries[ $code ] ) ? (string) $countries[ $code ] : '';
    }
}
