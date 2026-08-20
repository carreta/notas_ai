<?php

namespace Tests\Feature;

use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingRetrievalTest extends TestCase
{
    use RefreshDatabase;

    public function test_meeting_can_be_retrieved_by_id(): void
    {
        $meeting = Meeting::create([
            'title' => 'Test Meeting',
            'raw_text' => 'Meeting notes',
            'status' => 'DRAFT',
            'meeting_time' => null,
        ]);

        $response = $this->getJson(route('meetings.show', $meeting));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meeting_id', $meeting->id)
            ->assertJsonPath('data.title', 'Test Meeting')
            ->assertJsonPath('data.raw_text', 'Meeting notes')
            ->assertJsonPath('data.status', 'DRAFT')
            ->assertJsonPath('data.meeting_time', null);

        $response->assertJsonStructure([
            'success',
            'data' => [
                'meeting_id',
                'title',
                'raw_text',
                'status',
                'meeting_time',
                'created_at',
                'updated_at',
            ],
            'error',
        ]);
    }

    public function test_nonexistent_meeting_returns_404(): void
    {
        $response = $this->getJson(
            route('meetings.show', '550e8400-e29b-41d4-a716-446655440000')
        );

        $response->assertNotFound();
    }
}
