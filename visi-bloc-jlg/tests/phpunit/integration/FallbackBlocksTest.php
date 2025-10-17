<?php

use PHPUnit\Framework\TestCase;

class FallbackBlocksTest extends TestCase {
    protected function setUp(): void {
        visibloc_test_reset_state();
        $GLOBALS['visibloc_posts'] = [];
        remove_all_filters( 'visibloc_jlg_available_fallback_blocks_query_args' );
    }

    public function test_query_defaults_enable_pagination(): void {
        $captured_args = null;

        add_filter(
            'visibloc_jlg_available_fallback_blocks_query_args',
            static function ( $args ) use ( &$captured_args ) {
                $captured_args = $args;

                return $args;
            }
        );

        try {
            visibloc_jlg_get_available_fallback_blocks();
        } finally {
            remove_all_filters( 'visibloc_jlg_available_fallback_blocks_query_args' );
        }

        $this->assertIsArray( $captured_args, 'The filter should receive an array of query arguments.' );
        $default_per_page = visibloc_jlg_get_default_fallback_blocks_per_page();

        $this->assertArrayHasKey( 'numberposts', $captured_args );
        $this->assertSame(
            $default_per_page,
            $captured_args['numberposts'],
            'The number of posts should be limited by default.',
        );
        $this->assertArrayHasKey( 'posts_per_page', $captured_args );
        $this->assertSame(
            $default_per_page,
            $captured_args['posts_per_page'],
            'Pagination should be enabled for WP_Query consumers.',
        );
        $this->assertArrayHasKey( 'nopaging', $captured_args );
        $this->assertFalse( (bool) $captured_args['nopaging'], 'The query should explicitly enable pagination.' );
        $this->assertArrayHasKey( 'paged', $captured_args );
        $this->assertSame( 1, $captured_args['paged'], 'The first page should be requested by default.' );
    }

    public function test_default_request_is_limited_to_first_page(): void {
        for ( $index = 1; $index <= 205; $index++ ) {
            $GLOBALS['visibloc_posts'][ $index ] = [
                'post_title'   => sprintf( 'Reusable block %03d', $index ),
                'post_content' => '<!-- wp:paragraph --><p>Fallback paragraph</p><!-- /wp:paragraph -->',
                'post_type'    => 'wp_block',
                'post_status'  => 'publish',
            ];
        }

        $blocks = visibloc_jlg_get_available_fallback_blocks();

        $default_per_page = visibloc_jlg_get_default_fallback_blocks_per_page();

        $this->assertCount(
            $default_per_page,
            $blocks,
            'Only the first page of reusable blocks should be loaded by default.'
        );

        $values = array_column( $blocks, 'value' );

        $this->assertSame(
            range( 1, $default_per_page ),
            $values,
            'The block IDs should cover the first page of the dataset.'
        );
    }

    public function test_filter_can_limit_the_number_of_available_blocks(): void {
        for ( $index = 1; $index <= 30; $index++ ) {
            $GLOBALS['visibloc_posts'][ $index ] = [
                'post_title'   => sprintf( 'Reusable block %03d', $index ),
                'post_content' => '<!-- wp:paragraph --><p>Fallback paragraph</p><!-- /wp:paragraph -->',
                'post_type'    => 'wp_block',
                'post_status'  => 'publish',
            ];
        }

        add_filter(
            'visibloc_jlg_available_fallback_blocks_query_args',
            static function ( $args ) {
                if ( ! is_array( $args ) ) {
                    return $args;
                }

                $args['numberposts'] = 10;
                $args['posts_per_page'] = 10;

                return $args;
            }
        );

        try {
            $limited_blocks = visibloc_jlg_get_available_fallback_blocks();
        } finally {
            remove_all_filters( 'visibloc_jlg_available_fallback_blocks_query_args' );
        }

        $this->assertCount(
            10,
            $limited_blocks,
            'Integrators should be able to narrow the query through the filter.'
        );
    }

    public function test_overrides_can_disable_pagination_entirely(): void {
        for ( $index = 1; $index <= 30; $index++ ) {
            $GLOBALS['visibloc_posts'][ $index ] = [
                'post_title'   => sprintf( 'Reusable block %03d', $index ),
                'post_content' => '<!-- wp:paragraph --><p>Fallback paragraph</p><!-- /wp:paragraph -->',
                'post_type'    => 'wp_block',
                'post_status'  => 'publish',
            ];
        }

        $blocks = visibloc_jlg_get_available_fallback_blocks(
            [
                'paged'          => 0,
                'posts_per_page' => -1,
                'numberposts'    => -1,
                'nopaging'       => true,
            ]
        );

        $this->assertCount(
            30,
            $blocks,
            'Explicit overrides should still be able to disable pagination.',
        );
    }

    public function test_available_fallback_blocks_are_cached_until_invalidated(): void {
        for ( $index = 1; $index <= 3; $index++ ) {
            $GLOBALS['visibloc_posts'][ $index ] = [
                'post_title'   => sprintf( 'Reusable block %02d', $index ),
                'post_content' => '<!-- wp:paragraph --><p>Cached paragraph</p><!-- /wp:paragraph -->',
                'post_type'    => 'wp_block',
                'post_status'  => 'publish',
            ];
        }

        $GLOBALS['visibloc_test_stats']['get_posts_calls'] = 0;

        $first_result = visibloc_jlg_get_available_fallback_blocks();

        $this->assertSame(
            1,
            $GLOBALS['visibloc_test_stats']['get_posts_calls'] ?? 0,
            'The initial lookup should perform a query.'
        );

        $second_result = visibloc_jlg_get_available_fallback_blocks();

        $this->assertSame(
            1,
            $GLOBALS['visibloc_test_stats']['get_posts_calls'] ?? 0,
            'The cached lookup should not trigger another query.'
        );
        $this->assertSame( $first_result, $second_result, 'Cached results should match the initial payload.' );

        visibloc_jlg_invalidate_fallback_blocks_cache();

        $third_result = visibloc_jlg_get_available_fallback_blocks();

        $this->assertSame(
            2,
            $GLOBALS['visibloc_test_stats']['get_posts_calls'] ?? 0,
            'Invalidating the cache should force the next lookup to run a query again.'
        );
        $this->assertSame( $first_result, $third_result, 'Invalidation should not alter the resulting payload.' );
    }

    public function test_requested_pagination_is_honored(): void {
        for ( $index = 1; $index <= 25; $index++ ) {
            $GLOBALS['visibloc_posts'][ $index ] = [
                'post_title'   => sprintf( 'Reusable block %03d', $index ),
                'post_content' => '<!-- wp:paragraph --><p>Paginated paragraph</p><!-- /wp:paragraph -->',
                'post_type'    => 'wp_block',
                'post_status'  => 'publish',
            ];
        }

        $_GET['paged'] = '2';

        add_filter(
            'visibloc_jlg_available_fallback_blocks_query_args',
            static function ( $args ) {
                if ( ! is_array( $args ) ) {
                    return $args;
                }

                $args['posts_per_page'] = 5;

                return $args;
            }
        );

        try {
            $page_two = visibloc_jlg_get_available_fallback_blocks();
        } finally {
            unset( $_GET['paged'] );
            remove_all_filters( 'visibloc_jlg_available_fallback_blocks_query_args' );
            visibloc_test_reset_state();
        }

        $this->assertCount(
            5,
            $page_two,
            'Pagination should limit the number of retrieved blocks.'
        );

        $this->assertSame(
            range( 6, 10 ),
            array_column( $page_two, 'value' ),
            'The second page should begin where the first one stopped.'
        );
    }

    public function test_search_parameter_limits_results(): void {
        for ( $index = 1; $index <= 8; $index++ ) {
            $GLOBALS['visibloc_posts'][ $index ] = [
                'post_title'   => sprintf( 'Reusable block %03d', $index ),
                'post_content' => sprintf( '<!-- wp:paragraph --><p>Search paragraph %03d</p><!-- /wp:paragraph -->', $index ),
                'post_type'    => 'wp_block',
                'post_status'  => 'publish',
            ];
        }

        $_GET['s'] = 'block 003';

        try {
            $matching = visibloc_jlg_get_available_fallback_blocks();
        } finally {
            unset( $_GET['s'] );
            visibloc_test_reset_state();
        }

        $this->assertCount(
            1,
            $matching,
            'The search term should narrow the results to matching blocks.'
        );

        $this->assertSame( 3, $matching[0]['value'], 'The matching block ID should be returned.' );
    }
}
