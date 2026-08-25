<?php

namespace Tests\Feature;

use App\Livewire\HistoryList;
use App\Models\Analysis;
use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HistoryListTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_meetings_on_load(): void
    {
        Meeting::create(['title' => 'Alpha', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Beta', 'raw_text' => 'notes', 'status' => 'FAILED']);

        Livewire::test(HistoryList::class)
            ->assertSee('Alpha')
            ->assertSee('Beta');
    }

    public function test_search_filters_by_title_live(): void
    {
        Meeting::create(['title' => 'Sprint Retrospective', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Q3 Planning', 'raw_text' => 'notes', 'status' => 'COMPLETED']);

        Livewire::test(HistoryList::class)
            ->set('search', 'Sprint')
            ->assertSee('Sprint Retrospective')
            ->assertDontSee('Q3 Planning');
    }

    public function test_search_filters_by_transcript_live(): void
    {
        Meeting::create(['title' => 'Unrelated', 'raw_text' => 'The postgres database needs tuning.', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Other', 'raw_text' => 'nothing interesting', 'status' => 'COMPLETED']);

        Livewire::test(HistoryList::class)
            ->set('search', 'postgres')
            ->assertSee('Unrelated')
            ->assertDontSee('Other');
    }

    public function test_search_filters_by_analysis_summary_live(): void
    {
        $meeting = Meeting::create(['title' => 'Session A', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Analysis::create([
            'meeting_id' => $meeting->id,
            'result' => [
                'summary' => 'PostgreSQL migration approved',
                'decisions' => [],
                'action_items' => [],
                'open_questions' => [],
            ],
        ]);
        Meeting::create(['title' => 'Session B', 'raw_text' => 'notes', 'status' => 'COMPLETED']);

        Livewire::test(HistoryList::class)
            ->set('search', 'PostgreSQL')
            ->assertSee('Session A')
            ->assertDontSee('Session B');
    }

    public function test_status_filter_works_live(): void
    {
        Meeting::create(['title' => 'Done', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Broken', 'raw_text' => 'notes', 'status' => 'FAILED']);

        Livewire::test(HistoryList::class)
            ->set('status', 'COMPLETED')
            ->assertSee('Done')
            ->assertDontSee('Broken');
    }

    public function test_search_and_status_combined_live(): void
    {
        Meeting::create(['title' => 'Sprint A', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Sprint B', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Sprint C', 'raw_text' => 'notes', 'status' => 'FAILED']);

        Livewire::test(HistoryList::class)
            ->set('search', 'Sprint')
            ->set('status', 'COMPLETED')
            ->assertSee('Sprint A')
            ->assertSee('Sprint B')
            ->assertDontSee('Sprint C');
    }

    public function test_clear_filters_restores_all(): void
    {
        Meeting::create(['title' => 'One', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Two', 'raw_text' => 'notes', 'status' => 'FAILED']);

        Livewire::test(HistoryList::class)
            ->set('search', 'One')
            ->assertDontSee('Two')
            ->call('clearFilters')
            ->assertSee('One')
            ->assertSee('Two');
    }

    public function test_invalid_sort_falls_back_to_newest(): void
    {
        $older = Meeting::create(['title' => 'Older', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::whereKey($older->id)->update(['created_at' => now()->subDay()]);
        Meeting::create(['title' => 'Newer', 'raw_text' => 'notes', 'status' => 'COMPLETED']);

        Livewire::test(HistoryList::class)
            ->set('sort', 'not_a_real_column')
            ->assertStatus(200);
    }
}
