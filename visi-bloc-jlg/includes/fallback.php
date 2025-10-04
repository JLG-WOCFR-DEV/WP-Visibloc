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
        'orderby'          => 'title',
        'order'            => 'ASC',
        'suppress_filters' => false,
    ];

    /**
     * Filters the arguments used when looking up reusable blocks available as fallbacks.
     *
     * Allowing the query arguments to be filtered lets integrators re-introduce pagination
     * or otherwise tailor the lookup to their needs when a site has an extremely large
     * collection of reusable blocks.
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

    $posts = get_posts( $query_args );

    if ( empty( $posts ) ) {
        return [];
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

    return $blocks;
}

/**
 * Data passed to the editor for fallback settings.
 *
 * @return array
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

    return [
        'mode'       => $settings['mode'],
        'hasContent' => $has_markup,
        'summary'    => $summary,
        'blockId'    => $settings['mode'] === 'block' ? $settings['block_id'] : 0,
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
