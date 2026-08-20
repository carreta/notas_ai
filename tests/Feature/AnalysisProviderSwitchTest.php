<?php

namespace Tests\Feature;

use App\AI\DTO\AnalysisRequest;
use App\AI\Exceptions\AiRateLimitException;
use App\AI\Exceptions\AiTimeoutException;
use App\AI\Providers\AnalysisProvider;
use App\AI\Providers\FakeAnalysisProvider;
use App\AI\Providers\OpenAIAnalysisProvider;
use App\Livewire\AnalyzeForm;
use App\Models\AnalysisLog;
use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AnalysisProviderSwitchTest extends TestCase
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
            'ai.timeout' => 120,
        ]);
    }

    public function test_default_driver_resolves_openai_provider(): void
    {
        config(['ai.driver' => 'openai']);

        $this->assertInstanceOf(OpenAIAnalysisProvider::class, app(AnalysisProvider::class));
    }

    public function test_fake_driver_resolves_fake_provider(): void
    {
        config(['ai.driver' => 'fake', 'ai.fake_outcome' => 'valid']);

        $this->assertInstanceOf(FakeAnalysisProvider::class, app(AnalysisProvider::class));
    }

    public function test_fake_driver_with_timeout_outcome_is_configured_for_timeout(): void
    {
        config(['ai.driver' => 'fake', 'ai.fake_outcome' => 'timeout']);

        $provider = app(AnalysisProvider::class);
        $this->assertInstanceOf(FakeAnalysisProvider::class, $provider);
        $this->expectException(AiTimeoutException::class);

        $provider->analyze(new AnalysisRequest('any'));
    }

    public function test_fake_driver_with_rate_limit_outcome_is_configured_for_rate_limit(): void
    {
        config(['ai.driver' => 'fake', 'ai.fake_outcome' => 'rate_limit']);

        $provider = app(AnalysisProvider::class);
        $this->assertInstanceOf(FakeAnalysisProvider::class, $provider);
        $this->expectException(AiRateLimitException::class);

        $provider->analyze(new AnalysisRequest('any'));
    }

    public function test_valid_fake_outcome_completes_through_livewire_flow(): void
    {
        config(['ai.driver' => 'fake', 'ai.fake_outcome' => 'valid']);

        $component = Livewire::test(AnalyzeForm::class, ['models' => config('models')])
            ->set('meeting_text', 'Transcript content.')
            ->set('meeting_title', 'Planning')
            ->set('meeting_date', '2026-08-19')
            ->call('save');

        $meetingId = $component->get('meetingId');
        $component->call('analyze');

        $component->assertHasNoErrors();

        $meeting = Meeting::find($meetingId);
        $this->assertSame('COMPLETED', $meeting->status);
        $this->assertDatabaseCount('analyses', 1);
        $this->assertDatabaseCount('analysis_logs', 1);
        $this->assertDatabaseCount('ai_metrics', 1);
    }

    #[DataProvider('fakeFailureOutcomeProvider')]
    public function test_failure_outcome_shows_safe_error_and_fails_meeting(
        string $outcome,
        string $expectedCategory,
        string $safeMessageFragment,
    ): void {
        config(['ai.driver' => 'fake', 'ai.fake_outcome' => $outcome]);

        $component = Livewire::test(AnalyzeForm::class, ['models' => config('models')])
            ->set('meeting_text', 'Transcript content.')
            ->set('meeting_title', 'Planning')
            ->set('meeting_date', '2026-08-19')
            ->call('save');

        $meetingId = $component->get('meetingId');
        $component->call('analyze');

        $component->assertHasErrors('meeting_text');
        $message = $component->errors()->get('meeting_text')[0] ?? '';
        $this->assertStringContainsString($safeMessageFragment, $message);

        $meeting = Meeting::find($meetingId);
        $this->assertSame('FAILED', $meeting->status);
        $this->assertDatabaseCount('analyses', 0);
        $this->assertDatabaseCount('ai_metrics', 0);

        $log = AnalysisLog::where('meeting_id', $meetingId)->first();
        $this->assertNotNull($log);
        $this->assertSame('FAILED', $log->status);
        $this->assertSame($expectedCategory, $log->error_category);
        $this->assertNull($log->error_message);
    }

    public static function fakeFailureOutcomeProvider(): array
    {
        return [
            'config' => ['config', 'AI_CONFIGURATION_ERROR', 'AI analysis is temporarily unavailable.'],
            'dependency' => ['dependency', 'AI_DEPENDENCY_ERROR', 'The AI service is temporarily unavailable.'],
            'timeout' => ['timeout', 'AI_TIMEOUT', 'The analysis took too long. Please try again.'],
            'rate_limit' => ['rate_limit', 'AI_RATE_LIMIT', 'The AI service is temporarily busy. Please try again later.'],
            'invalid_response' => ['invalid_response', 'AI_INVALID_RESPONSE', 'The analysis could not be processed safely.'],
            'internal' => ['internal', 'INTERNAL_ERROR', 'The analysis could not be completed.'],
        ];
    }
}
