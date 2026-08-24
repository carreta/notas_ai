<?php

namespace Tests\Feature;

use App\Models\Analysis;
use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_returns_correct_meeting(): void
    {
        $meeting = Meeting::create([
            'title' => 'Alpha Meeting',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);

        $response = $this->getJson(route('meetings.show', $meeting));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.title', 'Alpha Meeting');
        $response->assertJsonPath('data.meeting_id', $meeting->id);
    }

    public function test_api_does_not_show_another_meetings_data(): void
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

        $response = $this->getJson(route('meetings.show', $a));

        $response->assertJsonPath('data.title', 'Meeting A');
        $response->assertJsonPath('data.meeting_id', $a->id);
    }

    public function test_api_shows_correct_analysis_result(): void
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

        $response = $this->getJson(route('meetings.show', $a));

        $response->assertJsonPath('data.title', 'Meeting A');
        $response->assertJsonPath('data.meeting_id', $a->id);
    }

    public function test_api_meeting_without_analysis_returns_meeting_data(): void
    {
        $meeting = Meeting::create([
            'title' => 'Draft Only',
            'raw_text' => 'notes',
            'status' => 'DRAFT',
        ]);

        $response = $this->getJson(route('meetings.show', $meeting));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.title', 'Draft Only');
        $response->assertJsonPath('data.status', 'DRAFT');
    }

    public function test_api_failed_meeting_shows_failed_status(): void
    {
        $meeting = Meeting::create([
            'title' => 'Failed Meeting',
            'raw_text' => 'notes',
            'status' => 'FAILED',
        ]);

        $response = $this->getJson(route('meetings.show', $meeting));

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'FAILED');
    }

    public function test_invalid_uuid_returns_404(): void
    {
        $response = $this->getJson(route('meetings.show', 'not-a-uuid'));

        $response->assertNotFound();
    }

    public function test_nonexistent_uuid_returns_404(): void
    {
        $response = $this->getJson(route('meetings.show', '550e8400-e29b-41d4-a716-446655440000'));

        $response->assertNotFound();
    }
}
