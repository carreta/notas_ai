<?php

namespace Tests\Feature;

use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'meeting-title' => 'Test meeting',
            'meeting-date' => '2026-08-19',
            'transcript' => 'This is a valid meeting transcript.',
            'model' => 'chatgpt-sol',
        ], $overrides);
    }

    public function test_valid_submission_creates_a_meeting(): void
    {
        $response = $this->post(
            route('meetings.store'),
            $this->validPayload()
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('meetings', [
            'title' => 'Test meeting',
            'raw_text' => 'This is a valid meeting transcript.',
            'status' => 'DRAFT',
        ]);
    }

    public function test_meeting_receives_a_uuid(): void
    {
        $this->post(
            route('meetings.store'),
            $this->validPayload()
        )->assertRedirect();

        $meeting = Meeting::first();

        $this->assertNotNull($meeting);
        $this->assertNotEmpty($meeting->id);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $meeting->id
        );
    }

    public function test_initial_status_is_draft(): void
    {
        $this->post(
            route('meetings.store'),
            $this->validPayload()
        )->assertRedirect();

        $this->assertDatabaseHas('meetings', [
            'status' => 'DRAFT',
        ]);
    }

    public function test_meeting_time_can_be_null(): void
    {
        $meeting = Meeting::create([
            'title' => 'Meeting without time',
            'raw_text' => 'Meeting without a known meeting time.',
            'status' => 'DRAFT',
            'meeting_time' => null,
        ]);

        $this->assertNull($meeting->meeting_time);

        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'meeting_time' => null,
        ]);
    }

    public function test_invalid_submission_does_not_create_a_meeting(): void
    {
        $response = $this->post(
            route('meetings.store'),
            $this->validPayload([
                'transcript' => '',
            ])
        );

        $response->assertSessionHasErrors('transcript');

        $this->assertDatabaseCount('meetings', 0);
    }

    public function test_meeting_has_timestamps(): void
    {
        $this->post(
            route('meetings.store'),
            $this->validPayload()
        )->assertRedirect();

        $meeting = Meeting::first();

        $this->assertNotNull($meeting);
        $this->assertNotNull($meeting->created_at);
        $this->assertNotNull($meeting->updated_at);
    }

    public function test_meeting_time_is_persisted_when_provided(): void
    {
        $meetingDate = '2026-08-19';

        $this->post(
            route('meetings.store'),
            $this->validPayload([
                'meeting-date' => $meetingDate,
            ])
        )->assertRedirect();

        $meeting = Meeting::first();

        $this->assertNotNull($meeting);
        $this->assertNotNull($meeting->meeting_time);

        $this->assertSame(
            '2026-08-19',
            $meeting->meeting_time->format('Y-m-d')
        );
    }

    public function test_user_cannot_control_meeting_status(): void
    {
        $this->post(
            route('meetings.store'),
            $this->validPayload([
                'status' => 'COMPLETED',
            ])
        )->assertRedirect();

        $meeting = Meeting::first();

        $this->assertNotNull($meeting);
        $this->assertSame('DRAFT', $meeting->status);

        $this->assertDatabaseMissing('meetings', [
            'id' => $meeting->id,
            'status' => 'COMPLETED',
        ]);
    }
}
