<?php

namespace Tests\Feature;

use App\Livewire\AnalysisDetailModal;
use App\Models\Analysis;
use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnalysisDetailModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_modal_opens_meeting_analysis_by_meeting_id(): void
    {
        $meeting = Meeting::create([
            'title' => 'Shared Modal Meeting',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);
        Analysis::create([
            'meeting_id' => $meeting->id,
            'result' => [
                'summary' => 'Unique Shared Summary',
                'decisions' => [],
                'action_items' => [],
                'open_questions' => [],
            ],
        ]);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $meeting->id)
            ->assertSet('meetingId', $meeting->id)
            ->assertSee('Shared Modal Meeting')
            ->assertSee('Unique Shared Summary');
    }

    public function test_modal_shows_correct_meeting_not_another(): void
    {
        $a = Meeting::create([
            'title' => 'Meeting A',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);
        $b = Meeting::create([
            'title' => 'Meeting B',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);

        Analysis::create([
            'meeting_id' => $a->id,
            'result' => ['summary' => 'Summary A', 'decisions' => [], 'action_items' => [], 'open_questions' => []],
        ]);
        Analysis::create([
            'meeting_id' => $b->id,
            'result' => ['summary' => 'Summary B', 'decisions' => [], 'action_items' => [], 'open_questions' => []],
        ]);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $a->id)
            ->assertSee('Summary A')
            ->assertDontSee('Summary B');
    }

    public function test_modal_handles_meeting_without_analysis(): void
    {
        $meeting = Meeting::create([
            'title' => 'No Analysis Meeting',
            'raw_text' => 'notes',
            'status' => 'DRAFT',
        ]);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $meeting->id)
            ->assertSet('meetingId', $meeting->id)
            ->assertSee('No Analysis Meeting')
            ->assertSee('No analysis data available.');
    }

    public function test_modal_renders_summary_with_empty_collections_without_errors(): void
    {
        $meeting = Meeting::create([
            'title' => 'Empty Collections Meeting',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);
        Analysis::create([
            'meeting_id' => $meeting->id,
            'result' => [
                'summary' => 'A valid summary with empty collections',
                'decisions' => [],
                'action_items' => [],
                'open_questions' => [],
            ],
        ]);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $meeting->id)
            ->assertSee('A valid summary with empty collections')
            ->assertSee('Empty Collections Meeting')
            // Summary is visible; the empty collections do not collapse the
            // whole result into the generic "No analysis data available" state.
            ->assertDontSee('No analysis data available.');
    }

    public function test_analyzing_meeting_without_analysis_shows_no_fabricated_result(): void
    {
        $meeting = Meeting::create([
            'title' => 'Analyzing Meeting',
            'raw_text' => 'notes',
            'status' => 'ANALYZING',
        ]);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $meeting->id)
            ->assertSee('Analyzing Meeting')
            ->assertSee('No analysis data available.');
    }

    public function test_modal_switches_from_a_to_b_without_stale_state(): void
    {
        $a = Meeting::create([
            'title' => 'Meeting A',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);
        $b = Meeting::create([
            'title' => 'Meeting B',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);

        Analysis::create([
            'meeting_id' => $a->id,
            'result' => ['summary' => 'Summary A', 'decisions' => [], 'action_items' => [], 'open_questions' => []],
        ]);
        Analysis::create([
            'meeting_id' => $b->id,
            'result' => ['summary' => 'Summary B', 'decisions' => [], 'action_items' => [], 'open_questions' => []],
        ]);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $a->id)
            ->assertSee('Summary A')
            ->assertDontSee('Summary B')
            ->dispatch('openMeetingModal', $b->id)
            ->assertSee('Summary B')
            ->assertDontSee('Summary A');
    }

    public function test_modal_renders_due_date_text_fallback_and_provenance(): void
    {
        $meeting = Meeting::create([
            'title' => 'Provenance Meeting',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);
        Analysis::create([
            'meeting_id' => $meeting->id,
            'result' => [
                'summary' => 'Summary',
                'decisions' => [],
                'action_items' => [
                    [
                        'task' => 'Follow up',
                        'owner' => null,
                        'priority' => 'HIGH',
                        'priority_source' => 'INFERRED',
                        'due_date_text' => 'next Friday',
                        'due_date' => null,
                        'due_date_source' => 'UNRESOLVED',
                    ],
                ],
                'open_questions' => [],
            ],
        ]);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $meeting->id)
            ->assertSee('Follow up')
            // due_date is null but due_date_text must still be shown.
            ->assertSee('Due:')
            ->assertSee('next Friday')
            // Provenance labels are displayed subtly.
            ->assertSee('Inferred')
            ->assertSee('Unresolved')
            // Owner is null, so no broken "Owner:" value is rendered.
            ->assertDontSee('Owner:');
    }
}
