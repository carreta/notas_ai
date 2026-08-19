<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingValidationBoundaryTD001Test extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'meeting-title' => 'TD-001 boundary test',
            'meeting-date' => '2026-08-19',
            'transcript' => 'Valid meeting transcript.',
            'model' => 'chatgpt-sol',
        ], $overrides);
    }

    public function test_meeting_with_13500_words_is_accepted(): void
    {
        $transcript = implode(
            ' ',
            array_fill(0, 13500, 'a')
        );

        $response = $this->post(
            route('meetings.store'),
            $this->validPayload([
                'transcript' => $transcript,
            ])
        );

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors('transcript');

        $this->assertDatabaseCount('meetings', 1);
    }

    public function test_meeting_with_more_than_13500_words_is_rejected(): void
    {
        $transcript = implode(
            ' ',
            array_fill(0, 13501, 'a')
        );

        $response = $this->post(
            route('meetings.store'),
            $this->validPayload([
                'transcript' => $transcript,
            ])
        );

        

        $response->assertSessionHasErrors('transcript');

        $this->assertDatabaseCount('meetings', 0);
    }
}
