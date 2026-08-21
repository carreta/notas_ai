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
        $response->assertDontSee('Completed');
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
        $response->assertSee('No meetings yet');
    }

    public function test_history_page_contains_nav_links(): void
    {
        $response = $this->get('/history');

        $response->assertSee(route('home'));
        $response->assertSee(route('history'));
    }
}
