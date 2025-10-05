<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize fallback settings.
 *
 * @param mixed $value Raw fallback settings.
 * @return array{
 *     mode: string,
 *     text: string,
 *     block_id: int,
 * }
 */
function visibloc_jlg_normalize_fallback_settings( $value ) {
    $normalized = [
        'mode'     => 'none',
        'text'     => '',
        'block_id' => 0,
    ];

    if ( ! is_array( $value ) ) {
        return $normalized;
    }

    $mode = isset( $value['mode'] ) ? sanitize_key( $value['mode'] ) : 'none';

    if ( ! in_array( $mode, [ 'none', 'text', 'block' ], true ) ) {
        $mode = 'none';
    }

    $text     = isset( $value['text'] ) && is_string( $value['text'] ) ? wp_kses_post( $value['text'] ) : '';
    $block_id = isset( $value['block_id'] ) ? absint( $value['block_id'] ) : 0;

    if ( 'block' === $mode ) {
        $block_post = $block_id > 0 ? get_post( $block_id ) : null;

        if ( ! ( $block_post instanceof WP_Post ) || 'wp_block' !== $block_post->post_type || 'publish' !== $block_post->post_status ) {
            $mode     = 'none';
            $block_id = 0;
        }
    }

    if ( 'text' === $mode ) {
        $text = trim( $text );

        if ( '' === $text ) {
            $mode = 'none';
        }
    }

    $normalized['mode']     = $mode;
    $normalized['text']     = 'text' === $mode ? $text : '';
    $normalized['block_id'] = 'block' === $mode ? $block_id : 0;

    return $normalized;
}

/**
 * Retrieve the configured fallback settings.
 *
 * @param bool $reset_cache Optional. Whether to reset the cached value.
 * @return array{
 *     mode: string,
 *     text: string,
 *     block_id: int,
 * }
 */
function visibloc_jlg_get_fallback_settings( $reset_cache = false ) {
    static $cache = null;

    if ( $reset_cache ) {
        $cache = null;
    }

    if ( null !== $cache ) {
        return $cache;
    }

    $raw = get_option( 'visibloc_fallback_settings', [] );

    $cache = visibloc_jlg_normalize_fallback_settings( is_array( $raw ) ? $raw : [] );

    return $cache;
}

/**
 * Check whether fallback settings have usable content.
 *
 * @param array $settings Fallback settings.
 * @return bool
 */
function visibloc_jlg_fallback_has_content( $settings ) {
    if ( ! is_array( $settings ) ) {
        return false;
    }

    $mode = isset( $settings['mode'] ) ? $settings['mode'] : 'none';

    if ( 'text' === $mode ) {
        return isset( $settings['text'] ) && '' !== trim( (string) $settings['text'] );
    }

    if ( 'block' === $mode ) {
        return ! empty( $settings['block_id'] );
    }

    return false;
}

/**
 * Prepare fallback text for display.
 *
 * @param string $text Raw text.
 * @return string
 */
function visibloc_jlg_prepare_fallback_text( $text ) {
    $text = wpautop( wp_kses_post( $text ) );

    return apply_filters( 'visibloc_jlg_fallback_text', $text );
}

/**
 * Generate a new cache version identifier for fallback block lookups.
 *
 * @return string
 */
function visibloc_jlg_generate_fallback_blocks_cache_version() {
    if ( function_exists( 'wp_generate_uuid4' ) ) {
        return wp_generate_uuid4();
    }

    return uniqid( 'visibloc_fallback_', true );
}

/**
 * Retrieve the current cache version used for fallback block listings.
 *
 * @param bool $reset Optional. Whether to reset the in-memory cache version.
 * @return string
 */
function visibloc_jlg_get_fallback_blocks_cache_version( $reset = false ) {
    static $version = null;

    if ( $reset ) {
        $version = null;
    }

    if ( null !== $version ) {
        return $version;
    }

    $default_version = '1';

    if ( function_exists( 'wp_cache_get' ) ) {
        $cached_version = wp_cache_get( 'visibloc_fallback_blocks_version', 'visibloc_jlg' );

        if ( false !== $cached_version && '' !== $cached_version ) {
            $version = (string) $cached_version;

            return $version;
        }
    }

    if ( function_exists( 'get_option' ) ) {
        $stored_version = get_option( 'visibloc_fallback_blocks_cache_version', $default_version );

        if ( is_string( $stored_version ) && '' !== $stored_version ) {
            $version = $stored_version;
        } else {
            $version = (string) $stored_version;
        }
    }

    if ( null === $version ) {
        $version = $default_version;
    }

    if ( function_exists( 'wp_cache_set' ) ) {
        wp_cache_set( 'visibloc_fallback_blocks_version', $version, 'visibloc_jlg' );
    }

    return $version;
}

/**
 * Register a transient key storing cached fallback blocks.
 *
 * @param string $transient_key Transient identifier.
 * @return void
 */
function visibloc_jlg_register_fallback_blocks_transient( $transient_key ) {
    if ( '' === $transient_key ) {
        return;
    }

    if ( ! function_exists( 'get_option' ) || ! function_exists( 'update_option' ) ) {
        return;
    }

    $registry = get_option( 'visibloc_fallback_blocks_transients', [] );

    if ( ! is_array( $registry ) ) {
        $registry = [];
    }

    if ( in_array( $transient_key, $registry, true ) ) {
        return;
    }

    $registry[] = $transient_key;

    update_option( 'visibloc_fallback_blocks_transients', $registry );
}

/**
 * Invalidate cached fallback block listings.
 *
 * @return void
 */
function visibloc_jlg_invalidate_fallback_blocks_cache() {
    $new_version = visibloc_jlg_generate_fallback_blocks_cache_version();

    if ( function_exists( 'update_option' ) ) {
        update_option( 'visibloc_fallback_blocks_cache_version', $new_version );
    }

    if ( function_exists( 'wp_cache_set' ) ) {
        wp_cache_set( 'visibloc_fallback_blocks_version', $new_version, 'visibloc_jlg' );
    }

    visibloc_jlg_get_fallback_blocks_cache_version( true );

    if ( function_exists( 'get_option' ) && function_exists( 'delete_transient' ) ) {
        $registered_transients = get_option( 'visibloc_fallback_blocks_transients', [] );

        if ( is_array( $registered_transients ) ) {
            foreach ( array_unique( array_map( 'strval', $registered_transients ) ) as $transient_key ) {
                if ( '' === $transient_key ) {
                    continue;
                }

                delete_transient( $transient_key );
            }
        }

        if ( function_exists( 'delete_option' ) ) {
            delete_option( 'visibloc_fallback_blocks_transients' );
        }
    }

    if ( function_exists( 'wp_cache_delete' ) ) {
        wp_cache_delete( 'visibloc_fallback_blocks_version', 'visibloc_jlg' );
    }
}

/**
 * Determine whether an array has sequential numeric keys.
 *
 * @param array $array Array to inspect.
 * @return bool
 */
function visibloc_jlg_is_list( array $array ) {
    return array_keys( $array ) === range( 0, count( $array ) - 1 );
}

/**
 * Normalize an array so that associative keys are sorted for cache hashing.
 *
 * @param mixed $value Arbitrary value.
 * @return mixed
 */
function visibloc_jlg_normalize_value_for_cache( $value ) {
    if ( ! is_array( $value ) ) {
        return $value;
    }

    $normalized = [];

    foreach ( $value as $key => $child_value ) {
        $normalized[ $key ] = visibloc_jlg_normalize_value_for_cache( $child_value );
    }

    if ( ! visibloc_jlg_is_list( $normalized ) ) {
        ksort( $normalized );
    }

    return $normalized;
}

/**
 * Retrieve the requested page number for fallback block listings.
 *
 * @return int
 */
function visibloc_jlg_get_fallback_blocks_requested_page() {
    if ( ! isset( $_GET['paged'] ) ) {
        return 0;
    }

    $raw_value = $_GET['paged'];

    if ( is_string( $raw_value ) || is_numeric( $raw_value ) ) {
        return max( 0, absint( $raw_value ) );
    }

    return 0;
}

/**
 * Sanitize the search term requested for fallback block listings.
 *
 * @return string
 */
function visibloc_jlg_get_fallback_blocks_search_term() {
    if ( ! isset( $_GET['s'] ) ) {
        return '';
    }

    $raw_value = $_GET['s'];

    if ( ! is_string( $raw_value ) && ! is_numeric( $raw_value ) ) {
        return '';
    }

    $search_term = trim( wp_unslash( (string) $raw_value ) );

    if ( '' === $search_term ) {
        return '';
    }

    if ( function_exists( 'sanitize_text_field' ) ) {
        $search_term = sanitize_text_field( $search_term );
    } else {
        $search_term = strip_tags( $search_term );
        $search_term = preg_replace( '/[\r\n\t]+/', ' ', $search_term );
        $search_term = trim( $search_term );
    }

    return $search_term;
}

/**
 * Render a reusable block fallback.
 *
 * @param int $block_id Reusable block post ID.
 * @return string
 */
function visibloc_jlg_render_reusable_block_fallback( $block_id ) {
    $block_id   = absint( $block_id );
    $block_post = $block_id > 0 ? get_post( $block_id ) : null;

    if ( ! ( $block_post instanceof WP_Post ) || 'wp_block' !== $block_post->post_type || 'publish' !== $block_post->post_status ) {
        return '';
    }

    $content = $block_post->post_content;

    if ( has_blocks( $content ) ) {
        return do_blocks( $content );
    }

    return apply_filters( 'the_content', $content );
}

/**
 * Retrieve the global fallback markup.
 *
 * @param bool $reset_cache Optional. Whether to reset the cached markup.
 * @return string
 */
function visibloc_jlg_get_global_fallback_markup( $reset_cache = false ) {
    static $cache = null;

    if ( $reset_cache ) {
        $cache = null;
    }

    if ( null !== $cache ) {
        return $cache;
    }

    $settings = visibloc_jlg_get_fallback_settings();

    switch ( $settings['mode'] ) {
        case 'text':
            $cache = visibloc_jlg_prepare_fallback_text( $settings['text'] );
            break;
        case 'block':
            $cache = visibloc_jlg_render_reusable_block_fallback( $settings['block_id'] );
            break;
        default:
            $cache = '';
            break;
    }

    return $cache;
}

/**
 * Compute the fallback markup for a block according to its attributes.
 *
 * @param array $attrs Block attributes.
 * @return string
 */
function visibloc_jlg_get_block_fallback_markup( $attrs ) {
    if ( empty( $attrs ) || ! is_array( $attrs ) ) {
        return visibloc_jlg_get_global_fallback_markup();
    }

    $fallback_enabled = isset( $attrs['fallbackEnabled'] )
        ? visibloc_jlg_normalize_boolean( $attrs['fallbackEnabled'] )
        : true;

    if ( ! $fallback_enabled ) {
        return '';
    }

    $mode = isset( $attrs['fallbackBehavior'] ) && is_string( $attrs['fallbackBehavior'] )
        ? sanitize_key( $attrs['fallbackBehavior'] )
        : 'inherit';

    if ( ! in_array( $mode, [ 'inherit', 'text', 'block' ], true ) ) {
        $mode = 'inherit';
    }

    if ( 'text' === $mode ) {
        $text = isset( $attrs['fallbackCustomText'] ) && is_string( $attrs['fallbackCustomText'] )
            ? $attrs['fallbackCustomText']
            : '';

        $text = trim( $text );

        return '' === $text ? '' : visibloc_jlg_prepare_fallback_text( $text );
    }

    if ( 'block' === $mode ) {
        $block_id = isset( $attrs['fallbackBlockId'] ) ? absint( $attrs['fallbackBlockId'] ) : 0;

        return $block_id > 0 ? visibloc_jlg_render_reusable_block_fallback( $block_id ) : '';
    }

    return visibloc_jlg_get_global_fallback_markup();
}

/**
 * Retrieve reusable blocks available for fallback selection.
 *
 * @return array<int, array{value:int,label:string}>
 */
function visibloc_jlg_get_available_fallback_blocks() {
    $default_args = [
        'post_type'        => 'wp_block',
        'post_status'      => 'publish',
        'numberposts'      => -1,
        'posts_per_page'   => -1,
        'nopaging'         => true,
        'orderby'          => 'title',
        'order'            => 'ASC',
        'suppress_filters' => false,
    ];

    /**
     * Filters the arguments used when looking up reusable blocks available as fallbacks.
     *
     * By default the plugin disables pagination completely (`numberposts` and
     * `posts_per_page` are both set to `-1`, and `nopaging` to `true`) so that no reusable
     * block is hidden from the selector. Allowing the query arguments to be filtered lets
     * integrators re-introduce pagination or otherwise tailor the lookup to their needs when
     * a site has an extremely large collection of reusable blocks.
     *
     * @since 1.1.1
     *
     * @param array $query_args Arguments forwarded to {@see get_posts()}.
     */
    $query_args = apply_filters( 'visibloc_jlg_available_fallback_blocks_query_args', $default_args );

    if ( ! is_array( $query_args ) ) {
        $query_args = $default_args;
    } else {
        $query_args = array_merge( $default_args, $query_args );
    }

    $requested_page = visibloc_jlg_get_fallback_blocks_requested_page();
    $search_term    = visibloc_jlg_get_fallback_blocks_search_term();

    if ( '' !== $search_term ) {
        $query_args['s'] = $search_term;
    }

    if ( $requested_page > 0 ) {
        $per_page = isset( $query_args['posts_per_page'] ) ? (int) $query_args['posts_per_page'] : 0;

        if ( $per_page <= 0 && isset( $query_args['numberposts'] ) ) {
            $per_page = (int) $query_args['numberposts'];
        }

        if ( $per_page <= 0 ) {
            $per_page = (int) apply_filters( 'visibloc_jlg_fallback_blocks_per_page', 50, $query_args, $requested_page );
        }

        if ( $per_page <= 0 ) {
            $per_page = 50;
        }

        $query_args['posts_per_page'] = $per_page;
        $query_args['numberposts']    = $per_page;
        $query_args['nopaging']       = false;
        $query_args['paged']          = $requested_page;
        $query_args['offset']         = max( 0, ( $requested_page - 1 ) * $per_page );
    } elseif ( isset( $query_args['paged'] ) && (int) $query_args['paged'] > 0 ) {
        $per_page = isset( $query_args['posts_per_page'] ) ? (int) $query_args['posts_per_page'] : 0;

        if ( $per_page > 0 ) {
            $query_args['numberposts'] = $per_page;
            $query_args['nopaging']    = false;
            $query_args['offset']      = max( 0, ( (int) $query_args['paged'] - 1 ) * $per_page );
        }
    } else {
        unset( $query_args['paged'] );
    }

    $site_id = 0;

    if ( function_exists( 'get_current_blog_id' ) ) {
        $site_id = (int) get_current_blog_id();
    }

    $locale = '';

    if ( function_exists( 'is_admin' ) && is_admin() && function_exists( 'get_user_locale' ) ) {
        $locale = (string) get_user_locale();
    } elseif ( function_exists( 'determine_locale' ) ) {
        $locale = (string) determine_locale();
    } elseif ( function_exists( 'get_locale' ) ) {
        $locale = (string) get_locale();
    }

    $locale = preg_replace( '/[^A-Za-z0-9_\-]/', '', $locale );

    if ( '' === $locale ) {
        $locale = 'default';
    }

    $cache_version = visibloc_jlg_get_fallback_blocks_cache_version();
    $cache_ttl     = defined( 'HOUR_IN_SECONDS' ) ? HOUR_IN_SECONDS : 3600;
    $normalized_query_args = visibloc_jlg_normalize_value_for_cache( $query_args );
    $cache_payload = [
        'site'       => $site_id,
        'locale'     => $locale,
        'query_args' => $normalized_query_args,
    ];

    $payload_json = function_exists( 'wp_json_encode' ) ? wp_json_encode( $cache_payload ) : json_encode( $cache_payload );
    $payload_json = is_string( $payload_json ) ? $payload_json : '';
    $cache_hash   = md5( $payload_json );
    $cache_key    = sprintf( 'fallback_blocks:%s:%s', $cache_version, $cache_hash );
    $cache_group  = 'visibloc_jlg';

    if ( function_exists( 'wp_cache_get' ) ) {
        $cached_blocks = wp_cache_get( $cache_key, $cache_group );

        if ( false !== $cached_blocks && is_array( $cached_blocks ) ) {
            return $cached_blocks;
        }
    }

    $transient_key = sprintf( 'visibloc_fallback_blocks_%s', $cache_hash );

    if ( function_exists( 'get_transient' ) ) {
        $transient_value = get_transient( $transient_key );

        if ( false !== $transient_value && is_array( $transient_value ) ) {
            if ( function_exists( 'wp_cache_set' ) ) {
                wp_cache_set( $cache_key, $transient_value, $cache_group, $cache_ttl );
            }

            return $transient_value;
        }
    }

    $posts = get_posts( $query_args );

    if ( empty( $posts ) ) {
        $blocks = [];

        if ( function_exists( 'wp_cache_set' ) ) {
            wp_cache_set( $cache_key, $blocks, $cache_group, $cache_ttl );
        }

        if ( function_exists( 'set_transient' ) ) {
            set_transient( $transient_key, $blocks, $cache_ttl );
            visibloc_jlg_register_fallback_blocks_transient( $transient_key );
        }

        return $blocks;
    }

    $blocks = [];

    foreach ( $posts as $post ) {
        if ( ! ( $post instanceof WP_Post ) ) {
            continue;
        }

        $label = get_the_title( $post );

        if ( '' === $label ) {
            $label = sprintf( __( 'Bloc réutilisable #%d', 'visi-bloc-jlg' ), $post->ID );
        }

        $blocks[] = [
            'value' => (int) $post->ID,
            'label' => $label,
        ];
    }

    if ( function_exists( 'wp_cache_set' ) ) {
        wp_cache_set( $cache_key, $blocks, $cache_group, $cache_ttl );
    }

    if ( function_exists( 'set_transient' ) ) {
        set_transient( $transient_key, $blocks, $cache_ttl );
        visibloc_jlg_register_fallback_blocks_transient( $transient_key );
    }

    return $blocks;
}

/**
 * Data passed to the editor for fallback settings.
 *
 * @return array{
 *     mode: string,
 *     hasContent: bool,
 *     summary: string,
 *     blockId: int,
 *     previewHtml: string,
 * }
 */
function visibloc_jlg_get_editor_fallback_settings() {
    $settings   = visibloc_jlg_get_fallback_settings();
    $has_markup = visibloc_jlg_fallback_has_content( $settings );

    $summary = '';

    if ( $has_markup ) {
        if ( 'text' === $settings['mode'] ) {
            $summary = wp_trim_words( wp_strip_all_tags( $settings['text'] ), 20, '…' );
        } elseif ( 'block' === $settings['mode'] ) {
            $block   = get_post( $settings['block_id'] );
            $summary = $block instanceof WP_Post ? $block->post_title : '';
        }
    }

    $preview_html = '';

    if ( $has_markup ) {
        $raw_markup = '';

        if ( 'text' === $settings['mode'] ) {
            $raw_markup = visibloc_jlg_prepare_fallback_text( $settings['text'] );
        } elseif ( 'block' === $settings['mode'] ) {
            $raw_markup = visibloc_jlg_render_reusable_block_fallback( $settings['block_id'] );
        }

        if ( '' !== $raw_markup ) {
            $allowed_length = (int) apply_filters( 'visibloc_jlg_editor_fallback_preview_length', 800 );

            if ( $allowed_length <= 0 ) {
                $allowed_length = 800;
            }

            $sanitized_markup = wp_kses_post( $raw_markup );

            if ( '' !== $sanitized_markup ) {
                $preview_html = trim( wp_html_excerpt( $sanitized_markup, $allowed_length, '&hellip;' ) );

                if ( '' !== $preview_html ) {
                    $preview_html = balanceTags( $preview_html, true );
                }
            }
        }
    }

    return [
        'mode'        => $settings['mode'],
        'hasContent'  => $has_markup,
        'summary'     => $summary,
        'blockId'     => $settings['mode'] === 'block' ? $settings['block_id'] : 0,
        'previewHtml' => $preview_html,
    ];
}

/**
 * Data passed to the editor for fallback block choices.
 *
 * @return array<int, array{value:int,label:string}>
 */
function visibloc_jlg_get_editor_fallback_blocks() {
    return visibloc_jlg_get_available_fallback_blocks();
}

add_action( 'save_post_wp_block', 'visibloc_jlg_invalidate_fallback_blocks_cache' );
add_action( 'delete_post', 'visibloc_jlg_maybe_invalidate_fallback_blocks_cache_on_delete' );

/**
 * Clear fallback block caches when a reusable block is deleted.
 *
 * @param int $post_id Deleted post ID.
 * @return void
 */
function visibloc_jlg_maybe_invalidate_fallback_blocks_cache_on_delete( $post_id ) {
    $post_id = absint( $post_id );

    if ( $post_id <= 0 || ! function_exists( 'get_post' ) ) {
        return;
    }

    $post = get_post( $post_id );

    if ( ! ( $post instanceof WP_Post ) ) {
        return;
    }

    if ( 'wp_block' === ( $post->post_type ?? '' ) ) {
        visibloc_jlg_invalidate_fallback_blocks_cache();
    }
}
