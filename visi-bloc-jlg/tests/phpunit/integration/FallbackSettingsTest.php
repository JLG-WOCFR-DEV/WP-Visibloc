<?php

use PHPUnit\Framework\TestCase;

class FallbackSettingsTest extends TestCase {
    protected function setUp(): void {
        visibloc_test_reset_state();
        $GLOBALS['visibloc_posts'] = [];
    }

    public function test_block_mode_without_existing_post_is_not_considered_content(): void {
        $settings = [
            'mode'     => 'block',
            'block_id' => 42,
        ];

        $this->assertFalse(
            visibloc_jlg_fallback_has_content( $settings ),
            'Missing reusable blocks should not be treated as usable fallback content.'
        );
    }

    public function test_block_mode_requires_published_reusable_block(): void {
        $GLOBALS['visibloc_posts'][7] = [
            'post_title'   => 'Draft reusable block',
            'post_content' => '<!-- wp:paragraph --><p>Fallback draft</p><!-- /wp:paragraph -->',
            'post_type'    => 'wp_block',
            'post_status'  => 'draft',
        ];

        $draft_settings = [
            'mode'     => 'block',
            'block_id' => 7,
        ];

        $this->assertFalse(
            visibloc_jlg_fallback_has_content( $draft_settings ),
            'Draft reusable blocks should not be used as published fallback content.'
        );

        $GLOBALS['visibloc_posts'][7]['post_status'] = 'publish';

        $this->assertTrue(
            visibloc_jlg_fallback_has_content( $draft_settings ),
            'Published reusable blocks should be considered valid fallback content.'
        );
    }

    public function test_block_mode_rejects_non_reusable_post_types(): void {
        $GLOBALS['visibloc_posts'][9] = [
            'post_title'   => 'Regular post',
            'post_content' => 'Content',
            'post_type'    => 'post',
            'post_status'  => 'publish',
        ];

        $settings = [
            'mode'     => 'block',
            'block_id' => 9,
        ];

        $this->assertFalse(
            visibloc_jlg_fallback_has_content( $settings ),
            'Only reusable blocks should be accepted when fallback mode targets a block.'
        );
    }
}
