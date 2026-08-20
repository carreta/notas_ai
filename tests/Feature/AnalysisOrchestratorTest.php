<?php

namespace Tests\Feature;

use App\AI\AnalysisOrchestrator;
use App\AI\Exceptions\AiTimeoutException;
use App\AI\Failure\AnalysisFailureMapper;
use App\AI\Providers\FakeAnalysisProvider;
use App\Models\Analysis;
use App\Models\AnalysisLog;
use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalysisOrchestratorTest extends TestCase
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

    protected function tearDown(): void
    {
        Analysis::flushEventListeners();

        parent::tearDown();
    }

    private function meeting(string $status = 'DRAFT'): Meeting
    {
        return Meeting::create([
            'title' => 'Meeting',
            'raw_text' => 'Transcript.',
            'status' => $status,
            'meeting_time' => '2026-08-19',
        ]);
    }

    public function test_approved_timeout_configuration_is_120_seconds(): void
    {
        $this->assertSame(120, (int) config('ai.timeout', 120));
    }

    public function test_timeout_exception_maps_to_ai_timeout(): void
    {
        $failure = AnalysisFailureMapper::map(new AiTimeoutException('x'));

        $this->assertSame('AI_TIMEOUT', $failure->category);
    }

    public function test_valid_flow_persists_analysis_and_completes_meeting(): void
    {
        $provider = new FakeAnalysisProvider;
        $provider->setOutcome('valid');

        $meeting = $this->meeting();
        $outcome = (new AnalysisOrchestrator($provider))->analyze($meeting);

        $this->assertTrue($outcome->success);
        $this->assertNull($outcome->category);
        $this->assertSame('COMPLETED', $meeting->fresh()->status);

        $this->assertDatabaseCount('analyses', 1);
        $this->assertDatabaseCount('analysis_logs', 1);
        $this->assertDatabaseCount('ai_metrics', 1);

        $log = AnalysisLog::first();
        $this->assertSame('COMPLETED', $log->status);
        $this->assertNull($log->error_category);
        $this->assertSame('openai', $log->provider);
        $this->assertSame('gpt-4o-mini', $log->model);
        $this->assertSame('meeting-analysis-v1', $log->prompt_version);

        $analysis = Analysis::first();
        $this->assertSame($meeting->id, $analysis->meeting_id);
        $this->assertArrayHasKey('summary', $analysis->result);
    }

    private function assertSafeFailure(string $outcome, string $expectedCategory): void
    {
        $provider = new FakeAnalysisProvider;
        $provider->setOutcome($outcome);

        $meeting = $this->meeting();
        $result = (new AnalysisOrchestrator($provider))->analyze($meeting);

        $this->assertFalse($result->success);
        $this->assertSame($expectedCategory, $result->category);

        $meeting = $meeting->fresh();
        $this->assertSame('FAILED', $meeting->status);
        $this->assertDatabaseCount('analyses', 0);
        $this->assertDatabaseCount('analysis_logs', 1);

        $log = AnalysisLog::first();
        $this->assertSame('FAILED', $log->status);
        $this->assertSame($expectedCategory, $log->error_category);
        $this->assertNull($log->error_message);

        $serialized = json_encode([
            'provider' => $log->provider,
            'model' => $log->model,
            'error_category' => $log->error_category,
            'error_message' => $log->error_message,
        ]);
        $this->assertStringNotContainsString('sk-', $serialized);
        $this->assertStringNotContainsString('Bearer', $serialized);
        $this->assertStringNotContainsString('SQLSTATE', $serialized);
    }

    public function test_configuration_failure_is_safe(): void
    {
        $this->assertSafeFailure('config', 'AI_CONFIGURATION_ERROR');
    }

    public function test_dependency_failure_is_safe(): void
    {
        $this->assertSafeFailure('dependency', 'AI_DEPENDENCY_ERROR');
    }

    public function test_timeout_failure_is_safe(): void
    {
        $this->assertSafeFailure('timeout', 'AI_TIMEOUT');
    }

    public function test_rate_limit_failure_is_safe(): void
    {
        $this->assertSafeFailure('rate_limit', 'AI_RATE_LIMIT');
    }

    public function test_invalid_response_failure_is_safe(): void
    {
        $this->assertSafeFailure('invalid_response', 'AI_INVALID_RESPONSE');
    }

    public function test_internal_failure_is_safe(): void
    {
        $this->assertSafeFailure('internal', 'INTERNAL_ERROR');
    }

    public function test_persistence_failure_is_safe_and_rolls_back(): void
    {
        $provider = new FakeAnalysisProvider;
        $provider->setOutcome('valid');

        $meeting = $this->meeting();

        Analysis::creating(static function (): void {
            throw new \RuntimeException('forced DB failure');
        });

        $result = (new AnalysisOrchestrator($provider))->analyze($meeting);

        $this->assertFalse($result->success);
        $this->assertSame('PERSISTENCE_ERROR', $result->category);
        $this->assertSame('FAILED', $meeting->fresh()->status);

        $this->assertDatabaseCount('analyses', 0);
        $this->assertDatabaseCount('analysis_logs', 1);

        $log = AnalysisLog::first();
        $this->assertSame('FAILED', $log->status);
        $this->assertSame('PERSISTENCE_ERROR', $log->error_category);
        $this->assertNull($log->error_message);
    }

    public function test_failure_of_one_meeting_does_not_affect_another(): void
    {
        $provider = new FakeAnalysisProvider;
        $provider->setOutcome('config');

        $meetingA = $this->meeting();
        $meetingB = $this->meeting();

        (new AnalysisOrchestrator($provider))->analyze($meetingA);

        $this->assertSame('FAILED', $meetingA->fresh()->status);
        $this->assertSame('DRAFT', $meetingB->fresh()->status);
        $this->assertNull($meetingB->fresh()->analysis);
        $this->assertDatabaseCount('analyses', 0);

        $log = AnalysisLog::first();
        $this->assertSame($meetingA->id, $log->meeting_id);
        $this->assertNotSame($meetingB->id, $log->meeting_id);
    }
}
