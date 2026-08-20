<?php

namespace Tests\Feature;

use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_submission_creates_a_meeting(): void
    {
        $response = $this->post('/meetings', [
            'title' => 'Test meeting',
            'raw_text' => 'This is a valid meeting transcript.',
            'meeting_time' => '2026-08-19 10:00:00',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('meetings', [
            'title' => 'Test meeting',
            'raw_text' => 'This is a valid meeting transcript.',
            'status' => 'DRAFT',
        ]);
    }

    public function test_meeting_receives_a_uuid(): void
    {
        $this->post('/meetings', [
            'raw_text' => 'Meeting transcript.',
        ])->assertCreated();

        $meeting = Meeting::first();

        $this->assertNotNull($meeting);
        $this->assertNotEmpty($meeting->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $meeting->id
        );
    }

    public function test_initial_status_is_validated(): void
    {
        $this->post('/meetings', [
            'raw_text' => 'Meeting transcript.',
        ])->assertCreated();

        $this->assertDatabaseHas('meetings', [
            'status' => 'DRAFT',
        ]);
    }

    public function test_meeting_time_can_be_null(): void
    {
        $this->post('/meetings', [
            'raw_text' => 'Meeting without a known meeting time.',
        ])->assertCreated();

        $this->assertDatabaseHas('meetings', [
            'raw_text' => 'Meeting without a known meeting time.',
            'meeting_time' => null,
        ]);
    }

    public function test_invalid_submission_does_not_create_a_meeting(): void
    {
        $response = $this->post('/meetings', [
            'title' => 'Invalid meeting',
        ]);

        $response->assertSessionHasErrors('raw_text');

        $this->assertDatabaseCount('meetings', 0);
    }

    public function test_meeting_with_13500_words_is_accepted(): void
    {
        $rawText = implode(' ', array_fill(0, 13500, 'reunión'));

        $response = $this->post('/meetings', [
            'raw_text' => $rawText,
        ]);

        $response->assertCreated();

        $this->assertDatabaseCount('meetings', 1);
    }

    public function test_meeting_with_more_than_13500_words_is_rejected(): void
    {
        $rawText = implode(' ', array_fill(0, 13501, 'reunión'));

        $response = $this->post('/meetings', [
            'raw_text' => $rawText,
        ]);

        $response->assertSessionHasErrors('raw_text');

        $this->assertDatabaseCount('meetings', 0);
    }

    public function test_meeting_can_be_retrieved_by_id(): void
    {
        $meeting = Meeting::create([
            'title' => 'Test Meeting',
            'raw_text' => 'Meeting notes',
            'status' => 'DRAFT',
            'meeting_time' => null,
        ]);

        $response = $this->getJson("/meetings/{$meeting->id}");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meeting_id', $meeting->id)
            ->assertJsonPath('data.title', 'Test Meeting')
            ->assertJsonPath('data.raw_text', 'Meeting notes')
            ->assertJsonPath('data.status', 'DRAFT');
    }


    public function test_nonexistent_meeting_returns_404(): void
{
    $response = $this->getJson(
        '/meetings/550e8400-e29b-41d4-a716-446655440000'
    );

    $response->assertNotFound();
}


public function test_meeting_has_timestamps(): void
{
    $response = $this->postJson('/meetings', [
        'title' => 'Meeting with timestamps',
        'raw_text' => 'These are the meeting notes.',
    ]);

    $response->assertCreated();

    $meeting = Meeting::first();

    $this->assertNotNull($meeting);
    $this->assertNotNull($meeting->created_at);
    $this->assertNotNull($meeting->updated_at);
}




public function test_meeting_time_is_persisted_when_provided(): void
{
    $meetingTime = '2026-08-19 10:30:00';

    $response = $this->postJson('/meetings', [
        'title' => 'Scheduled Meeting',
        'raw_text' => 'Meeting notes with a scheduled time.',
        'meeting_time' => $meetingTime,
    ]);

    $response->assertCreated();

    $meeting = Meeting::first();

    $this->assertNotNull($meeting);
    $this->assertNotNull($meeting->meeting_time);

    $this->assertSame(
        $meetingTime,
        $meeting->meeting_time->format('Y-m-d H:i:s')
    );
}

public function test_user_cannot_control_meeting_status(): void
{
    $response = $this->postJson('/meetings', [
        'title' => 'Status Protection Test',
        'raw_text' => 'The user should not control the processing status.',
        'status' => 'COMPLETED',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.status', 'DRAFT');

    $meeting = Meeting::first();

    $this->assertNotNull($meeting);
    $this->assertSame('DRAFT', $meeting->status);
}



}
