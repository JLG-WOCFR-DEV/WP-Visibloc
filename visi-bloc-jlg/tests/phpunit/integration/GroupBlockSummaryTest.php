<?php

use PHPUnit\Framework\TestCase;
use Visibloc\Tests\Support\PluginFacade;
use Visibloc\Tests\Support\TestServices;

require_once __DIR__ . '/../../../includes/admin-settings.php';
require_once __DIR__ . '/../../../includes/visibility-logic.php';

class GroupBlockSummaryTest extends TestCase {
    private PluginFacade $plugin;

    protected function setUp(): void {
        parent::setUp();

        visibloc_test_reset_state();
        $GLOBALS['visibloc_posts']           = [];
        $GLOBALS['visibloc_test_options']    = [];
        $GLOBALS['visibloc_test_transients'] = [];

        $this->plugin = TestServices::plugin();
        $this->plugin->clearCaches();
    }

    protected function tearDown(): void {
        $this->plugin->clearCaches();

        $GLOBALS['visibloc_posts']           = [];
        $GLOBALS['visibloc_test_options']    = [];
        $GLOBALS['visibloc_test_transients'] = [];

        parent::tearDown();
    }

    public function test_generate_group_block_summary_from_content_counts_hidden_device_and_scheduled_blocks(): void {
        $sample_content = <<<'HTML'
<!-- wp:core/group {"isHidden":true} -->
<div class="wp-block-group">Hidden group</div>
<!-- /wp:core/group -->

<!-- wp:core/group {"deviceVisibility":"mobile"} -->
<div class="wp-block-group">Mobile only group</div>
<!-- /wp:core/group -->

<!-- wp:core/group {"isSchedulingEnabled":true,"publishStartDate":"2024-05-01T09:00:00","publishEndDate":"2024-05-10T17:00:00"} -->
<div class="wp-block-group">
    <!-- wp:core/group {"isHidden":true} -->
    <div class="wp-block-group">Nested hidden group</div>
    <!-- /wp:core/group -->
</div>
<!-- /wp:core/group -->

<!-- wp:core/paragraph -->
<p>Paragraph outside of target block.</p>
<!-- /wp:core/paragraph -->
HTML;

        $summary = $this->plugin->generateGroupBlockSummaryFromContent( 101, $sample_content );

        $this->assertSame( 2, $summary['hidden'] );
        $this->assertSame( 1, $summary['device'] );
        $this->assertCount( 1, $summary['scheduled'] );
        $this->assertSame(
            [
                'start' => '2024-05-01T09:00:00',
                'end'   => '2024-05-10T17:00:00',
            ],
            $summary['scheduled'][0]
        );
    }

    public function test_summary_ignores_false_like_strings_for_hidden_and_scheduled_flags(): void {
        $sample_content = <<<'HTML'
<!-- wp:core/group {"isHidden":"false"} -->
<div class="wp-block-group">Should be visible</div>
<!-- /wp:core/group -->

<!-- wp:core/group {"isHidden":"0"} -->
<div class="wp-block-group">Also visible</div>
<!-- /wp:core/group -->

<!-- wp:core/group {"isHidden":[]} -->
<div class="wp-block-group">Array flag should be ignored</div>
<!-- /wp:core/group -->

<!-- wp:core/group {"isHidden":{"unexpected":true}} -->
<div class="wp-block-group">Object-like flag should be ignored</div>
<!-- /wp:core/group -->

<!-- wp:core/group {"isSchedulingEnabled":"false","publishStartDate":"2099-01-01T00:00:00","publishEndDate":"2099-01-02T00:00:00"} -->
<div class="wp-block-group">Scheduling disabled via string</div>
<!-- /wp:core/group -->

<!-- wp:core/group {"isSchedulingEnabled":"0","publishStartDate":"2099-02-01T00:00:00"} -->
<div class="wp-block-group">Scheduling disabled via zero</div>
<!-- /wp:core/group -->

<!-- wp:core/group {"isSchedulingEnabled":[],"publishStartDate":"2099-03-01T00:00:00"} -->
<div class="wp-block-group">Scheduling disabled via array</div>
<!-- /wp:core/group -->
HTML;

        $summary = $this->plugin->generateGroupBlockSummaryFromContent( 202, $sample_content );

        $this->assertSame( 0, $summary['hidden'] );
        $this->assertSame( 0, $summary['device'] );
        $this->assertSame( [], $summary['scheduled'] );
    }

    public function test_generate_group_block_summary_honors_supported_blocks_filter(): void {
        add_filter(
            'visibloc_supported_blocks',
            static function( $blocks ) {
                if ( ! is_array( $blocks ) ) {
                    $blocks = [];
                }

                $blocks[] = 'myplugin/customblock';

                return $blocks;
            }
        );

        try {
            $sample_content = <<<'HTML'
<!-- wp:core/group {"isHidden":true} -->
<div class="wp-block-group">Hidden group</div>
<!-- /wp:core/group -->

<!-- wp:myplugin/customblock {"isHidden":true} -->
<div class="wp-block-myplugin-customblock">Hidden custom block</div>
<!-- /wp:myplugin/customblock -->

<!-- wp:myplugin/customblock {"deviceVisibility":"desktop"} -->
<div class="wp-block-myplugin-customblock">Desktop custom block</div>
<!-- /wp:myplugin/customblock -->

<!-- wp:myplugin/customblock {"isSchedulingEnabled":true,"publishStartDate":"2025-01-01T10:00:00","publishEndDate":"2025-01-02T10:00:00"} -->
<div class="wp-block-myplugin-customblock">Scheduled custom block</div>
<!-- /wp:myplugin/customblock -->
HTML;

            $summary = $this->plugin->generateGroupBlockSummaryFromContent( 303, $sample_content );

            $this->assertSame( 2, $summary['hidden'] );
            $this->assertSame( 1, $summary['device'] );
            $this->assertCount( 1, $summary['scheduled'] );
            $this->assertSame(
                [
                    'start' => '2025-01-01T10:00:00',
                    'end'   => '2025-01-02T10:00:00',
                ],
                $summary['scheduled'][0]
            );
        } finally {
            remove_all_filters( 'visibloc_supported_blocks' );
        }
    }

    public function test_generate_group_block_summary_counts_custom_block_when_filter_overrides_defaults(): void {
        add_filter(
            'visibloc_supported_blocks',
            static function() {
                return [ 'myplugin/customblock' ];
            }
        );

        try {
            $sample_content = <<<'HTML'
<!-- wp:core/group {"isHidden":true} -->
<div class="wp-block-group">Hidden group</div>
<!-- /wp:core/group -->

<!-- wp:myplugin/customblock {"isHidden":true,"deviceVisibility":"tablet","isSchedulingEnabled":true,"publishStartDate":"2025-03-01T12:00:00"} -->
<div class="wp-block-myplugin-customblock">Custom block</div>
<!-- /wp:myplugin/customblock -->
HTML;

            $summary = $this->plugin->generateGroupBlockSummaryFromContent( 404, $sample_content );

            $this->assertSame( 1, $summary['hidden'] );
            $this->assertSame( 1, $summary['device'] );
            $this->assertCount( 1, $summary['scheduled'] );
            $this->assertSame(
                [
                    'start' => '2025-03-01T12:00:00',
                    'end'   => null,
                ],
                $summary['scheduled'][0]
            );
        } finally {
            remove_all_filters( 'visibloc_supported_blocks' );
        }
    }

    public function test_rebuild_and_collect_group_block_metadata_caches_results_for_admin_renderers(): void {
        $primary_content = <<<'HTML'
<!-- wp:core/group {"isHidden":true} -->
<div class="wp-block-group">Hidden group</div>
<!-- /wp:core/group -->

<!-- wp:core/group {"deviceVisibility":"tablet"} -->
<div class="wp-block-group">Tablet only group</div>
<!-- /wp:core/group -->

<!-- wp:core/group {"isSchedulingEnabled":true,"publishStartDate":"2024-05-01T09:00:00","publishEndDate":"2024-05-05T17:00:00"} -->
<div class="wp-block-group">Campaign warm-up</div>
<!-- /wp:core/group -->

<!-- wp:core/group {"isSchedulingEnabled":true,"publishStartDate":"2024-05-01T09:00:00","publishEndDate":"2024-05-10T17:00:00"} -->
<div class="wp-block-group">
    <!-- wp:core/group {"isHidden":true} -->
    <div class="wp-block-group">Nested hidden group</div>
    <!-- /wp:core/group -->
</div>
<!-- /wp:core/group -->
HTML;

        $secondary_content = <<<'HTML'
<!-- wp:core/group {"isSchedulingEnabled":true,"publishStartDate":"2024-04-20T08:00:00","publishEndDate":"2024-04-25T20:00:00"} -->
<div class="wp-block-group">Spring teaser</div>
<!-- /wp:core/group -->

<!-- wp:core/group {"isSchedulingEnabled":true,"publishStartDate":"2024-06-15T12:00:00"} -->
<div class="wp-block-group">Future launch</div>
<!-- /wp:core/group -->

<!-- wp:core/group {"isSchedulingEnabled":true,"publishEndDate":"2024-08-01T18:00:00"} -->
<div class="wp-block-group">Sunset promo</div>
<!-- /wp:core/group -->

<!-- wp:core/group {"deviceVisibility":"desktop"} -->
<div class="wp-block-group">Desktop only group</div>
<!-- /wp:core/group -->
HTML;

        $GLOBALS['visibloc_posts'] = [
            101 => [
                'post_content' => $primary_content,
                'post_title'   => 'Landing Page',
                'post_type'    => 'page',
                'post_status'  => 'publish',
            ],
            102 => [
                'post_content' => $secondary_content,
                'post_title'   => 'Campaign Teaser',
                'post_type'    => 'page',
                'post_status'  => 'future',
            ],
        ];

        $summaries = $this->plugin->rebuildGroupBlockSummaryIndex();

        $this->assertArrayHasKey( 101, $summaries );
        $this->assertArrayHasKey( 102, $summaries );
        $this->assertSame( 2, $summaries[101]['hidden'] );
        $this->assertSame( 1, $summaries[101]['device'] );
        $this->assertCount( 2, $summaries[101]['scheduled'] );
        $this->assertSame( 0, $summaries[102]['hidden'] );
        $this->assertSame( 1, $summaries[102]['device'] );
        $this->assertCount( 3, $summaries[102]['scheduled'] );

        $metadata = $this->plugin->collectGroupBlockMetadata();

        $this->assertSame( $metadata, get_transient( 'visibloc_group_block_metadata' ) );

        $this->assertCount( 1, $metadata['hidden'] );
        $this->assertSame( 101, $metadata['hidden'][0]['id'] );
        $this->assertSame( 'Landing Page', $metadata['hidden'][0]['title'] );
        $this->assertSame( 'https://example.com/wp-admin/post.php?post=101', $metadata['hidden'][0]['link'] );
        $this->assertSame( 2, $metadata['hidden'][0]['block_count'] );

        $this->assertCount( 2, $metadata['device'] );
        $device_ids = array_column( $metadata['device'], 'id' );
        sort( $device_ids );
        $this->assertSame( [ 101, 102 ], $device_ids );

        $this->assertCount( 5, $metadata['scheduled'] );

        $scheduled_windows = array_map(
            static function ( $item ) {
                return [
                    'id'    => $item['id'],
                    'start' => $item['start'] ?? null,
                    'end'   => $item['end'] ?? null,
                ];
            },
            $metadata['scheduled']
        );

        $this->assertSame(
            [
                [
                    'id'    => 102,
                    'start' => '2024-04-20T08:00:00',
                    'end'   => '2024-04-25T20:00:00',
                ],
                [
                    'id'    => 101,
                    'start' => '2024-05-01T09:00:00',
                    'end'   => '2024-05-05T17:00:00',
                ],
                [
                    'id'    => 101,
                    'start' => '2024-05-01T09:00:00',
                    'end'   => '2024-05-10T17:00:00',
                ],
                [
                    'id'    => 102,
                    'start' => '2024-06-15T12:00:00',
                    'end'   => null,
                ],
                [
                    'id'    => 102,
                    'start' => null,
                    'end'   => '2024-08-01T18:00:00',
                ],
            ],
            $scheduled_windows,
            'Scheduled entries should be sorted chronologically by their start then end dates, with open starts last.'
        );

        $grouped_hidden = $this->plugin->groupPostsById( $metadata['hidden'] );
        $this->assertCount( 1, $grouped_hidden );
        $this->assertSame( 2, $grouped_hidden[0]['block_count'] );

        $GLOBALS['visibloc_test_options']['visibloc_group_block_summary'] = [];

        $cached_metadata = $this->plugin->collectGroupBlockMetadata();
        $this->assertSame( $metadata, $cached_metadata );

        $cached_scheduled_windows = array_map(
            static function ( $item ) {
                return [
                    'id'    => $item['id'],
                    'start' => $item['start'] ?? null,
                    'end'   => $item['end'] ?? null,
                ];
            },
            $cached_metadata['scheduled']
        );

        $this->assertSame(
            [
                [
                    'id'    => 102,
                    'start' => '2024-04-20T08:00:00',
                    'end'   => '2024-04-25T20:00:00',
                ],
                [
                    'id'    => 101,
                    'start' => '2024-05-01T09:00:00',
                    'end'   => '2024-05-05T17:00:00',
                ],
                [
                    'id'    => 101,
                    'start' => '2024-05-01T09:00:00',
                    'end'   => '2024-05-10T17:00:00',
                ],
                [
                    'id'    => 102,
                    'start' => '2024-06-15T12:00:00',
                    'end'   => null,
                ],
                [
                    'id'    => 102,
                    'start' => null,
                    'end'   => '2024-08-01T18:00:00',
                ],
            ],
            $cached_scheduled_windows,
            'Cached scheduled entries should preserve the chronological order.'
        );
    }
}
