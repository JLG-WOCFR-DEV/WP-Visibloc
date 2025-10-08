<?php

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/includes/admin-settings.php';

/**
 * @covers ::visibloc_jlg_build_onboarding_checklist_items
 * @covers ::visibloc_jlg_calculate_onboarding_progress
 */
final class AdminOnboardingChecklistTest extends TestCase {
    public function test_build_onboarding_checklist_items_marks_completion_based_on_context() {
        $context = [
            'supported_blocks'  => [ 'core/paragraph' ],
            'preview_roles'     => [ 'administrator', 'editor' ],
            'fallback'          => [
                'mode' => 'text',
                'text' => 'Texte alternatif',
            ],
            'mobile_breakpoint' => 720,
            'tablet_breakpoint' => 1100,
        ];

        $items = visibloc_jlg_build_onboarding_checklist_items( $context );

        $this->assertCount( 4, $items );
        $this->assertTrue( $items[0]['complete'], 'Supported blocks should be marked complete.' );
        $this->assertTrue( $items[1]['complete'], 'Preview roles should be marked complete when non-admin roles are present.' );
        $this->assertTrue( $items[2]['complete'], 'Fallback section should be considered complete when fallback has content.' );
        $this->assertTrue( $items[3]['complete'], 'Custom breakpoints should be marked complete when both differ from defaults.' );
    }

    public function test_build_onboarding_checklist_items_defaults_are_incomplete_without_context() {
        $items = visibloc_jlg_build_onboarding_checklist_items( [] );

        $this->assertCount( 4, $items );
        foreach ( $items as $item ) {
            $this->assertArrayHasKey( 'complete', $item );
            $this->assertFalse( $item['complete'] );
        }
    }

    public function test_calculate_onboarding_progress_counts_completed_items() {
        $items = [
            [ 'key' => 'supported-blocks', 'complete' => true ],
            [ 'key' => 'preview-roles', 'complete' => false ],
            [ 'key' => 'fallback', 'complete' => true ],
            [ 'key' => 'breakpoints', 'complete' => false ],
        ];

        $progress = visibloc_jlg_calculate_onboarding_progress( $items );

        $this->assertSame( 4, $progress['total'] );
        $this->assertSame( 2, $progress['completed'] );
        $this->assertSame( 50, $progress['percent'] );
    }

    public function test_calculate_onboarding_progress_handles_empty_input() {
        $progress = visibloc_jlg_calculate_onboarding_progress( [] );

        $this->assertSame(
            [
                'total'     => 0,
                'completed' => 0,
                'percent'   => 0,
            ],
            $progress
        );
    }
}
