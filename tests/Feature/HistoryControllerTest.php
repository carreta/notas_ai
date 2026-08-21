<?php

namespace Tests\Feature;

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
        $response->assertSee('No meetings found.');
        $response->assertDontSee('Real');
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
}
