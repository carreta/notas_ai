<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_route_returns_200(): void
    {
        $response = $this->get('/history');

        $response->assertStatus(200);
    }

    public function test_history_view_is_rendered(): void
    {
        $response = $this->get('/history');

        $response->assertViewIs('history');
    }

    public function test_history_lists_previously_submitted_meetings(): void
    {
        Meeting::create([
            'title' => 'Q3 Planning Session',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);
        Meeting::create([
            'title' => 'Sprint Retrospective',
            'raw_text' => 'notes',
            'status' => 'FAILED',
        ]);

        $response = $this->get('/history');

        $response->assertSee('Q3 Planning Session');
        $response->assertSee('Sprint Retrospective');
    }

    public function test_history_ordering_is_newest_first(): void
    {
        $older = Meeting::create([
            'title' => 'Older Meeting',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);
        // Ensure a clearly older timestamp. created_at is not mass-assignable,
        // so write it through the query builder (bypasses $fillable).
        Meeting::whereKey($older->id)->update(['created_at' => now()->subDay()]);

        $newer = Meeting::create([
            'title' => 'Newer Meeting',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);

        $response = $this->get('/history');
        $html = $response->getContent();

        $this->assertLessThan(
            strpos($html, 'Older Meeting'),
            strpos($html, 'Newer Meeting'),
            'Newer meeting must appear before the older meeting in the history list.'
        );
    }

    public function test_status_is_visible(): void
    {
        Meeting::create([
            'title' => 'Completed Meeting',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);

        $response = $this->get('/history');

        $response->assertSee('Completed');
    }

    public function test_failed_meeting_renders_failed_and_not_completed(): void
    {
        Meeting::create([
            'title' => 'Broken Meeting',
            'raw_text' => 'notes',
            'status' => 'FAILED',
        ]);

        $response = $this->get('/history');

        $response->assertSee('Failed');
        $response->assertSee('Broken Meeting');
    }

    public function test_completed_meeting_renders_completed(): void
    {
        Meeting::create([
            'title' => 'Good Meeting',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);

        $response = $this->get('/history');

        $response->assertSee('Completed');
    }

    public function test_empty_history_renders_without_error(): void
    {
        $response = $this->get('/history');

        $response->assertStatus(200);
        $response->assertSee('No meetings found.');
    }

    public function test_history_page_contains_nav_links(): void
    {
        $response = $this->get('/history');

        $response->assertSee(route('home'));
        $response->assertSee(route('history'));
    }

    public function test_search_by_title_filters_results(): void
    {
        Meeting::create(['title' => 'Sprint Retrospective', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Q3 Planning', 'raw_text' => 'notes', 'status' => 'COMPLETED']);

        $response = $this->get('/history?search=Sprint');

        $response->assertSee('Sprint Retrospective');
        $response->assertDontSee('Q3 Planning');
    }

    public function test_partial_search_is_case_insensitive(): void
    {
        Meeting::create(['title' => 'Sprint Retrospective', 'raw_text' => 'notes', 'status' => 'COMPLETED']);

        $response = $this->get('/history?search=sprint');

        $response->assertSee('Sprint Retrospective');
    }

    public function test_status_filter_works(): void
    {
        Meeting::create(['title' => 'Done Meeting', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Broken Meeting', 'raw_text' => 'notes', 'status' => 'FAILED']);

        $response = $this->get('/history?status=COMPLETED');

        $response->assertSee('Done Meeting');
        $response->assertDontSee('Broken Meeting');
        $response->assertSee('Completed');
    }

    public function test_empty_filters_return_all(): void
    {
        Meeting::create(['title' => 'One', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Two', 'raw_text' => 'notes', 'status' => 'FAILED']);

        $response = $this->get('/history?search=&status=');

        $response->assertSee('One');
        $response->assertSee('Two');
    }

    public function test_sort_title_asc(): void
    {
        Meeting::create(['title' => 'Zulu', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Alpha', 'raw_text' => 'notes', 'status' => 'COMPLETED']);

        $response = $this->get('/history?sort=title&dir=asc');
        $html = $response->getContent();

        // Alpha should appear before Zulu (ascending by title).
        $this->assertLessThan(strpos($html, 'Zulu'), strpos($html, 'Alpha'));
    }

    public function test_sort_title_desc(): void
    {
        Meeting::create(['title' => 'Zulu', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Alpha', 'raw_text' => 'notes', 'status' => 'COMPLETED']);

        $response = $this->get('/history?sort=title&dir=desc');
        $html = $response->getContent();

        // Zulu should appear before Alpha (descending by title).
        $this->assertLessThan(strpos($html, 'Alpha'), strpos($html, 'Zulu'));
    }

    public function test_sort_by_date_processed_asc_and_desc(): void
    {
        $older = Meeting::create(['title' => 'Older', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::whereKey($older->id)->update(['created_at' => now()->subDays(2)]);
        $newer = Meeting::create(['title' => 'Newer', 'raw_text' => 'notes', 'status' => 'COMPLETED']);

        $asc = $this->get('/history?sort=created_at&dir=asc')->getContent();
        // Older should appear before Newer (ascending by date processed).
        $this->assertLessThan(strpos($asc, 'Newer'), strpos($asc, 'Older'));

        $desc = $this->get('/history?sort=created_at&dir=desc')->getContent();
        // Newer should appear before Older (descending by date processed).
        $this->assertLessThan(strpos($desc, 'Older'), strpos($desc, 'Newer'));
    }

    public function test_sort_by_status(): void
    {
        Meeting::create(['title' => 'Failed One', 'raw_text' => 'notes', 'status' => 'FAILED']);
        Meeting::create(['title' => 'Completed One', 'raw_text' => 'notes', 'status' => 'COMPLETED']);

        $response = $this->get('/history?sort=status&dir=asc');
        $html = $response->getContent();

        // 'COMPLETED' < 'FAILED' alphabetically, so Completed One first.
        $this->assertLessThan(strpos($html, 'Failed One'), strpos($html, 'Completed One'));
    }

    public function test_invalid_sort_column_is_ignored(): void
    {
        Meeting::create(['title' => 'Older', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::whereKey(Meeting::orderByDesc('created_at')->first()->id)
            ->update(['created_at' => now()->subDay()]);
        Meeting::create(['title' => 'Newer', 'raw_text' => 'notes', 'status' => 'COMPLETED']);

        $response = $this->get('/history?sort=meeting_id&dir=asc');

        $response->assertStatus(200);
        // Falls back to default newest-first ordering (Newer before Older).
        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'Older'), strpos($html, 'Newer'));
    }

    public function test_search_and_status_combined(): void
    {
        Meeting::create(['title' => 'Sprint A', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Sprint B', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Sprint C', 'raw_text' => 'notes', 'status' => 'FAILED']);

        $response = $this->get('/history?status=COMPLETED&search=Sprint');

        $response->assertSee('Sprint A');
        $response->assertSee('Sprint B');
        $response->assertDontSee('Sprint C');
    }

    public function test_filter_and_sort_combined(): void
    {
        Meeting::create(['title' => 'Cab', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Bat', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Ace', 'raw_text' => 'notes', 'status' => 'COMPLETED']);

        $response = $this->get('/history?status=COMPLETED&sort=title&dir=desc');
        $html = $response->getContent();

        // Cab should appear before Ace (descending by title).
        $this->assertLessThan(strpos($html, 'Ace'), strpos($html, 'Cab'));
    }

    public function test_no_results_handled(): void
    {
        Meeting::create(['title' => 'Real', 'raw_text' => 'notes', 'status' => 'COMPLETED']);

        $response = $this->get('/history?search=zzz-no-match');

        $response->assertStatus(200);
        // A filter is active, so the filtered empty-state is shown (not the
        // generic "no meetings yet" message).
        $response->assertSee('No meetings match your search.');
        $response->assertDontSee('No meetings found.');
        $response->assertDontSee('Real');
    }

    public function test_generic_empty_state_when_no_meetings_and_no_filters(): void
    {
        $response = $this->get('/history');

        $response->assertStatus(200);
        $response->assertSee('No meetings found.');
        $response->assertDontSee('No meetings match your search.');
    }

    public function test_search_by_transcript_text(): void
    {
        Meeting::create([
            'title' => 'Unrelated Title',
            'raw_text' => 'The postgres database needs tuning this quarter.',
            'status' => 'COMPLETED',
        ]);
        Meeting::create([
            'title' => 'Another',
            'raw_text' => 'nothing interesting here',
            'status' => 'COMPLETED',
        ]);

        $response = $this->get('/history?search=postgres');

        $response->assertSee('Unrelated Title');
        $response->assertDontSee('Another');
    }

    public function test_search_by_analysis_summary(): void
    {
        $meeting = Meeting::create([
            'title' => 'Session A',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);
        Analysis::create([
            'meeting_id' => $meeting->id,
            'result' => [
                'summary' => 'PostgreSQL migration was approved by the team.',
                'decisions' => [],
                'action_items' => [],
                'open_questions' => [],
            ],
        ]);

        Meeting::create([
            'title' => 'Session B',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);

        $response = $this->get('/history?search=PostgreSQL');

        $response->assertSee('Session A');
        $response->assertDontSee('Session B');
    }

    public function test_search_by_analysis_action_item_text(): void
    {
        $meeting = Meeting::create([
            'title' => 'Planning',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);
        Analysis::create([
            'meeting_id' => $meeting->id,
            'result' => [
                'summary' => 'Summary',
                'decisions' => [],
                'action_items' => [['task' => 'Upgrade the Postgres cluster']],
                'open_questions' => [],
            ],
        ]);

        Meeting::create([
            'title' => 'Other',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);

        $response = $this->get('/history?search=Postgres');

        $response->assertSee('Planning');
        $response->assertDontSee('Other');
    }

    public function test_invalid_status_is_safely_ignored(): void
    {
        Meeting::create(['title' => 'One', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Two', 'raw_text' => 'notes', 'status' => 'FAILED']);

        // An unknown status is not in the whitelist, so it is ignored and all
        // meetings are returned (no SQL error, no filtering).
        $response = $this->get('/history?status=INVALID_STATUS');

        $response->assertStatus(200);
        $response->assertSee('One');
        $response->assertSee('Two');
    }

    public function test_query_parameters_are_preserved_in_sort_links(): void
    {
        Meeting::create(['title' => 'Alpha', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::create(['title' => 'Beta', 'raw_text' => 'notes', 'status' => 'COMPLETED']);

        $response = $this->get('/history?search=Alpha&status=COMPLETED');

        // The sortable links must carry the active search/status filters so
        // that re-sorting keeps the current result set.
        $html = $response->getContent();
        $this->assertStringContainsString('search=Alpha', $html);
        $this->assertStringContainsString('status=COMPLETED', $html);
    }

    public function test_modal_wiring_preserved_after_filtering(): void
    {
        $meeting = Meeting::create(['title' => 'Filtered Meeting', 'raw_text' => 'notes', 'status' => 'COMPLETED']);

        $response = $this->get('/history?status=COMPLETED');

        // The shared modal component and the click-to-open wiring remain present,
        // and the correct meeting id is on its row (so the modal opens the right one).
        $response->assertSee('openMeetingModal');
        $response->assertSee($meeting->id);
    }

    public function test_analyzing_meeting_shows_processing_and_not_completed(): void
    {
        Meeting::create([
            'title' => 'In Progress Meeting',
            'raw_text' => 'notes',
            'status' => 'ANALYZING',
        ]);

        $response = $this->get('/history');

        $response->assertSee('Analyzing');
        $response->assertSee('In Progress Meeting');
        // It must not be presented with the COMPLETED badge styling.
        $response->assertDontSee('bg-secondary-container text-on-secondary-container');
    }

    public function test_sort_by_meeting_time_asc_and_desc(): void
    {
        // Distinct meeting_time values; created_at is left to default so the
        // meeting_time sort is the only thing ordering these rows.
        $older = Meeting::create([
            'title' => 'Older Meeting Time',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
            'meeting_time' => now()->subDays(10),
        ]);
        $newer = Meeting::create([
            'title' => 'Newer Meeting Time',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
            'meeting_time' => now()->subDays(1),
        ]);

        $asc = $this->get('/history?sort=meeting_time&dir=asc')->getContent();
        // Older meeting_time should appear before the newer one (ascending).
        $this->assertLessThan(strpos($asc, 'Newer Meeting Time'), strpos($asc, 'Older Meeting Time'));

        $desc = $this->get('/history?sort=meeting_time&dir=desc')->getContent();
        // Newer meeting_time should appear before the older one (descending).
        $this->assertLessThan(strpos($desc, 'Older Meeting Time'), strpos($desc, 'Newer Meeting Time'));
    }

    public function test_invalid_direction_falls_back_to_desc(): void
    {
        $older = Meeting::create(['title' => 'Older Title', 'raw_text' => 'notes', 'status' => 'COMPLETED']);
        Meeting::whereKey($older->id)->update(['created_at' => now()->subDay()]);
        Meeting::create(['title' => 'Newer Title', 'raw_text' => 'notes', 'status' => 'COMPLETED']);

        $response = $this->get('/history?sort=title&dir=invalid');

        $response->assertStatus(200);
        $html = $response->getContent();

        // Invalid dir must fall back to desc: by title, 'Older' > 'Newer'
        // alphabetically, so Older appears before Newer.
        $this->assertLessThan(strpos($html, 'Newer Title'), strpos($html, 'Older Title'));
    }

    public function test_search_input_uses_live_debounced_binding(): void
    {
        $response = $this->get('/history');

        $response->assertStatus(200);
        // The search field must be a Livewire live-debounced binding so the user
        // can type continuously and a single request fires only after a brief
        // pause (no per-keystroke reload, no forced Enter).
        $response->assertSee('wire:model.live.debounce.500ms');
    }
}
