<?php

use PHPUnit\Framework\TestCase;

class FallbackBlocksTest extends TestCase {
    protected function setUp(): void {
        visibloc_test_reset_state();
        $GLOBALS['visibloc_posts'] = [];
        remove_all_filters( 'visibloc_jlg_available_fallback_blocks_query_args' );
    }

    public function test_query_defaults_disable_pagination(): void {
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
        $this->assertArrayHasKey( 'numberposts', $captured_args );
        $this->assertSame( -1, $captured_args['numberposts'], 'The number of posts should be unlimited by default.' );
        $this->assertArrayHasKey( 'posts_per_page', $captured_args );
        $this->assertSame( -1, $captured_args['posts_per_page'], 'Pagination should remain disabled for WP_Query consumers.' );
        $this->assertArrayHasKey( 'nopaging', $captured_args );
        $this->assertTrue( (bool) $captured_args['nopaging'], 'The query should explicitly disable pagination.' );
    }

    public function test_all_reusable_blocks_are_returned_without_limit(): void {
        for ( $index = 1; $index <= 205; $index++ ) {
            $GLOBALS['visibloc_posts'][ $index ] = [
                'post_title'   => sprintf( 'Reusable block %03d', $index ),
                'post_content' => '<!-- wp:paragraph --><p>Fallback paragraph</p><!-- /wp:paragraph -->',
                'post_type'    => 'wp_block',
                'post_status'  => 'publish',
            ];
        }

        $blocks = visibloc_jlg_get_available_fallback_blocks();

        $this->assertCount(
            205,
            $blocks,
            'All reusable blocks should be listed when no limit is applied.'
        );

        $values = array_column( $blocks, 'value' );

        $this->assertSame(
            range( 1, 205 ),
            $values,
            'The block IDs should cover the entire dataset.'
        );

        $labels = array_column( $blocks, 'label', 'value' );

        $this->assertSame(
            'Reusable block 205',
            $labels[205] ?? null,
            'The last block should be present with its title.'
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
}
