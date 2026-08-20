<?php

namespace Tests\Feature;

use App\AI\Providers\AnalysisProvider;
use App\AI\Providers\FakeAnalysisProvider;
use App\Livewire\AnalyzeForm;
use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyzeFormFailureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('prompt_templates')->insert([
            'id' => (string) Str::uuid(),
            'version' => 'meeting-analysis-v1',
            'system_prompt' => 'system',
            'json_schema' => json_encode(['version' => 'meeting-analysis-v1']),
            'is_active' => true,
            'created_at' => now(),
        ]);

        config([
            'ai.provider' => 'openai',
            'ai.model' => 'gpt-4o-mini',
            'ai.schema_version' => 'meeting-analysis-v1',
        ]);
    }

    private function bindFake(string $outcome): FakeAnalysisProvider
    {
        $fake = new FakeAnalysisProvider;
        $fake->setOutcome($outcome);

        $this->app->bind(AnalysisProvider::class, static fn (): FakeAnalysisProvider => $fake);

        return $fake;
    }

    public function test_successful_submission_completes_meeting_without_exposing_details(): void
    {
        $this->bindFake('valid');

        $component = Livewire::test(AnalyzeForm::class, ['models' => config('models')])
            ->set('meeting_text', 'Transcript content.')
            ->set('meeting_title', 'Planning')
            ->set('meeting_date', '2026-08-19')
            ->call('save');

        $meetingId = $component->get('meetingId');
        $this->assertNotNull($meetingId);

        $component->call('analyze');

        $component->assertHasNoErrors();

        $meeting = Meeting::find($meetingId);
        $this->assertNotNull($meeting);
        $this->assertSame('COMPLETED', $meeting->status);
        $this->assertDatabaseCount('analyses', 1);
    }

    public function test_provider_failure_shows_safe_message_and_fails_meeting(): void
    {
        $this->bindFake('config');

        $component = Livewire::test(AnalyzeForm::class, ['models' => config('models')])
            ->set('meeting_text', 'Transcript content.')
            ->set('meeting_title', 'Planning')
            ->set('meeting_date', '2026-08-19')
            ->call('save');

        $meetingId = $component->get('meetingId');

        $component->call('analyze');

        $component->assertHasErrors('meeting_text');

        $message = $component->errors()->get('meeting_text')[0] ?? '';
        $this->assertStringNotContainsString('Exception', $message);
        $this->assertStringNotContainsString('Bearer', $message);
        $this->assertStringNotContainsString('sk-', $message);

        $meeting = Meeting::find($meetingId);
        $this->assertSame('FAILED', $meeting->status);
        $this->assertDatabaseCount('analyses', 0);
    }
}
