<?php

namespace Tests\Feature;

use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Test meeting',
            'raw_text' => 'This is a valid meeting transcript.',
            'status' => 'DRAFT',
            'meeting_time' => '2026-08-19',
        ], $overrides);
    }

    public function test_valid_data_creates_a_meeting(): void
    {
        $meeting = Meeting::create($this->validData());

        $this->assertDatabaseHas('meetings', [
            'title' => 'Test meeting',
            'raw_text' => 'This is a valid meeting transcript.',
            'status' => 'DRAFT',
        ]);

        $this->assertNotNull($meeting->id);
    }

    public function test_meeting_receives_a_uuid(): void
    {
        $meeting = Meeting::create($this->validData());

        $this->assertNotNull($meeting);
        $this->assertNotEmpty($meeting->id);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $meeting->id
        );
    }

    public function test_initial_status_is_draft(): void
    {
        $meeting = Meeting::create($this->validData());

        $this->assertSame('DRAFT', $meeting->status);
        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
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

    public function test_meeting_has_timestamps(): void
    {
        $meeting = Meeting::create($this->validData());

        $this->assertNotNull($meeting);
        $this->assertNotNull($meeting->created_at);
        $this->assertNotNull($meeting->updated_at);
    }

    public function test_meeting_time_is_persisted_when_provided(): void
    {
        $meetingDate = '2026-08-19';

        $meeting = Meeting::create($this->validData([
            'meeting_time' => $meetingDate,
        ]));

        $this->assertNotNull($meeting);
        $this->assertNotNull($meeting->meeting_time);

        $this->assertSame(
            '2026-08-19',
            $meeting->meeting_time->format('Y-m-d')
        );
    }

    public function test_user_can_control_meeting_status_via_mass_assignment(): void
    {
        $meeting = Meeting::create($this->validData([
            'status' => 'COMPLETED',
        ]));

        $this->assertNotNull($meeting);
        $this->assertSame('COMPLETED', $meeting->status);

        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'status' => 'COMPLETED',
        ]);
    }
}