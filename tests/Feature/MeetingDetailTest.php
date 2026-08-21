<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_route_loads_correct_meeting(): void
    {
        $meeting = Meeting::create([
            'title' => 'Alpha Meeting',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);

        $response = $this->get(route('meetings.show', $meeting));

        $response->assertStatus(200);
        $response->assertSee('Alpha Meeting');
    }

    public function test_detail_does_not_show_another_meetings_data(): void
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

        $response = $this->get(route('meetings.show', $a));

        $response->assertSee('Meeting A');
        $response->assertDontSee('Meeting B');
    }

    public function test_detail_shows_correct_analysis_result(): void
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
            'result' => [
                'summary' => 'Summary A',
                'decisions' => [],
                'action_items' => [],
                'open_questions' => [],
            ],
        ]);
        Analysis::create([
            'meeting_id' => $b->id,
            'result' => [
                'summary' => 'Summary B',
                'decisions' => [],
                'action_items' => [],
                'open_questions' => [],
            ],
        ]);

        $response = $this->get(route('meetings.show', $a));

        $response->assertSee('Summary A');
        $response->assertDontSee('Summary B');
    }

    public function test_meeting_without_analysis_opens_safely(): void
    {
        $meeting = Meeting::create([
            'title' => 'Draft Only',
            'raw_text' => 'notes',
            'status' => 'DRAFT',
        ]);

        $response = $this->get(route('meetings.show', $meeting));

        $response->assertStatus(200);
        $response->assertSee('No analysis data available.');
    }

    public function test_failed_meeting_shows_failed_status(): void
    {
        $meeting = Meeting::create([
            'title' => 'Failed Meeting',
            'raw_text' => 'notes',
            'status' => 'FAILED',
        ]);

        $response = $this->get(route('meetings.show', $meeting));

        $response->assertStatus(200);
        $response->assertSee('Failed');
        $response->assertDontSee('Completed');
    }

    public function test_invalid_uuid_returns_404(): void
    {
        $response = $this->get(route('meetings.show', 'not-a-uuid'));

        $response->assertNotFound();
    }

    public function test_nonexistent_uuid_returns_404(): void
    {
        $response = $this->get(route('meetings.show', '550e8400-e29b-41d4-a716-446655440000'));

        $response->assertNotFound();
    }
}
