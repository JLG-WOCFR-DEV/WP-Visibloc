<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../includes/insights.php';

class InsightsTest extends TestCase {
    protected function setUp(): void {
        visibloc_jlg_reset_insights_buffer();
        delete_option( VISIBLOC_JLG_INSIGHTS_OPTION );
    }

    protected function tearDown(): void {
        visibloc_jlg_reset_insights_buffer();
        delete_option( VISIBLOC_JLG_INSIGHTS_OPTION );
    }

    public function test_record_event_updates_buffer(): void {
        visibloc_jlg_record_insight_event(
            'fallback',
            [
                'reason'     => 'manual-flag',
                'block_name' => 'core/paragraph',
                'post_id'    => 42,
                'post_type'  => 'page',
            ]
        );

        visibloc_jlg_record_insight_event(
            'hidden',
            [
                'reason' => 'roles',
            ]
        );

        $buffer = visibloc_jlg_get_insights_buffer_snapshot();

        $this->assertTrue( $buffer['has_events'], 'Recording events should flag the buffer as dirty.' );
        $this->assertSame( 1, $buffer['counters']['fallback'] ?? 0, 'Fallback counter should increment.' );
        $this->assertSame( 1, $buffer['counters']['hidden'] ?? 0, 'Hidden counter should increment.' );
        $this->assertSame( 1, $buffer['reasons']['manual-flag'] ?? 0, 'Manual flag reason should be tracked.' );
        $this->assertSame( 1, $buffer['reasons']['roles'] ?? 0, 'Role restriction reason should be tracked.' );
        $this->assertCount( 2, $buffer['events'], 'Two events should be present in the buffer.' );
        $this->assertSame( 'fallback', $buffer['events'][0]['event'] ?? '', 'First event should retain its type.' );
    }

    public function test_flush_persists_buffer_and_resets(): void {
        visibloc_jlg_record_insight_event( 'fallback', [ 'reason' => 'manual-flag' ] );
        visibloc_jlg_record_insight_event( 'preview', [ 'reason' => 'schedule-window', 'preview' => true ] );

        visibloc_jlg_flush_insight_events();

        $stored = get_option( VISIBLOC_JLG_INSIGHTS_OPTION );
        $this->assertIsArray( $stored, 'Flushing should persist an array snapshot.' );
        $this->assertSame( 1, $stored['counters']['fallback'] ?? 0, 'Fallback counter should persist to the option.' );
        $this->assertSame( 1, $stored['counters']['preview'] ?? 0, 'Preview counter should persist to the option.' );
        $this->assertArrayHasKey( 'updated_at', $stored, 'Snapshot should include an updated timestamp.' );
        $this->assertGreaterThan( 0, $stored['updated_at'], 'Timestamp should be greater than zero.' );

        $buffer = visibloc_jlg_get_insights_buffer_snapshot();
        $this->assertFalse( $buffer['has_events'], 'Buffer should be reset after flushing.' );
        $this->assertSame( [], $buffer['events'], 'Events should be cleared after flushing.' );
    }

    public function test_snapshot_merges_runtime_events(): void {
        $existing = [
            'counters'   => [ 'hidden' => 2 ],
            'reasons'    => [ 'roles' => 2 ],
            'events'     => [
                [
                    'event'     => 'hidden',
                    'reason'    => 'roles',
                    'block_name'=> 'core/heading',
                    'post_id'   => 12,
                    'timestamp' => time() - 120,
                ],
            ],
            'updated_at' => time() - 60,
        ];

        update_option( VISIBLOC_JLG_INSIGHTS_OPTION, $existing );

        visibloc_jlg_record_insight_event( 'fallback', [ 'reason' => 'manual-flag' ] );

        $snapshot = visibloc_jlg_get_insight_snapshot();

        $this->assertSame( 1, $snapshot['counters']['fallback'] ?? 0, 'Runtime fallback counter should be merged.' );
        $this->assertSame( 2, $snapshot['counters']['hidden'] ?? 0, 'Stored counters should remain available.' );
        $this->assertSame( 1, $snapshot['reasons']['manual-flag'] ?? 0, 'Runtime reasons should merge with stored data.' );
        $this->assertSame( 2, $snapshot['reasons']['roles'] ?? 0, 'Stored reasons should not be lost.' );
        $this->assertSame( 'fallback', $snapshot['events'][0]['event'] ?? '', 'Latest runtime event should be prepended.' );
    }

    public function test_dashboard_model_formats_display_data(): void {
        $now            = time();
        $previous_posts = $GLOBALS['visibloc_posts'] ?? null;
        $GLOBALS['visibloc_posts'][7] = [
            'post_title' => 'Page test',
            'post_type'  => 'page',
        ];

        try {
            $snapshot = [
                'counters'   => [
                    'visible'  => 3,
                    'fallback' => 1,
                    'hidden'   => 2,
                    'preview'  => 1,
                ],
                'reasons'    => [ 'manual-flag' => 2 ],
                'events'     => [
                    [
                        'event'     => 'hidden',
                        'reason'    => 'manual-flag',
                        'block_name'=> 'core/group',
                        'post_id'   => 7,
                        'timestamp' => $now - 90,
                    ],
                ],
                'updated_at' => $now - 30,
            ];

            update_option( VISIBLOC_JLG_INSIGHTS_OPTION, $snapshot );

            $model = visibloc_jlg_get_insight_dashboard_model();

            $this->assertSame( 3, $model['counters']['visible'], 'Visible counter should be exposed in the dashboard model.' );
            $this->assertSame( 1, $model['counters']['fallback'], 'Fallback counter should be exposed in the dashboard model.' );
            $this->assertNotEmpty( $model['reasons'], 'Reasons breakdown should be available.' );
            $this->assertNotEmpty( $model['events'], 'Recent events should be exposed.' );
            $this->assertSame( 'core/group', $model['events'][0]['block_name'], 'Event should retain block name.' );
            $this->assertSame( 'Page test', $model['events'][0]['post_title'], 'Event should resolve the post title.' );
        } finally {
            if ( null === $previous_posts ) {
                unset( $GLOBALS['visibloc_posts'][7] );
            } else {
                $GLOBALS['visibloc_posts'] = $previous_posts;
            }
        }
    }
}
