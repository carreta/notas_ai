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
            ->assertSee('Unknown Meeting')
            ->assertSee('No analysis data available.');
    }
}
