<?php

namespace Tests\Feature;

use App\AI\AnalysisOrchestrator;
use App\AI\DTO\AnalysisRequest;
use App\AI\Providers\AnalysisProvider;
use App\Models\Analysis;
use App\Models\AnalysisLog;
use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalysisFailureFlowTest extends TestCase
{
    use RefreshDatabase;

    private const DANGEROUS_TEXT = 'Authorization: Bearer sk-secret-value SQLSTATE[08006] at /var/www line 42 stack trace';

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('prompt_templates')->upsert([
            'id' => (string) Str::uuid(),
            'version' => 'meeting-analysis-v1',
            'system_prompt' => 'system',
            'json_schema' => json_encode(['version' => 'meeting-analysis-v1']),
            'is_active' => true,
            'created_at' => now(),
        ], ['version'], ['system_prompt', 'json_schema', 'is_active', 'created_at']);

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

    /**
     * Provider that fails with a message containing sensitive/diagnostic text.
     * This simulates a provider SDK or transport exception leaking detail.
     */
    private function dangerousProvider(): AnalysisProvider
    {
        return new class implements AnalysisProvider
        {
            public function analyze(AnalysisRequest $request): string
            {
                throw new \RuntimeException(self::DANGEROUS_TEXT);
            }
        };
    }

    public function test_dangerous_provider_text_is_never_persisted(): void
    {
        $meeting = Meeting::create([
            'title' => 'Meeting',
            'raw_text' => 'Transcript.',
            'status' => 'DRAFT',
            'meeting_time' => '2026-08-19',
        ]);

        $outcome = (new AnalysisOrchestrator($this->dangerousProvider()))->analyze($meeting);

        $this->assertFalse($outcome->success);
        $this->assertSame('INTERNAL_ERROR', $outcome->category);
        $this->assertStringNotContainsString('sk-secret-value', $outcome->userMessage);
        $this->assertStringNotContainsString('Bearer', $outcome->userMessage);
        $this->assertStringNotContainsString('SQLSTATE', $outcome->userMessage);

        $this->assertDatabaseCount('analyses', 0);
        $this->assertDatabaseCount('analysis_logs', 1);

        $log = AnalysisLog::first();

        $serialized = (string) json_encode([
            'provider' => $log->provider,
            'model' => $log->model,
            'prompt_version' => $log->prompt_version,
            'error_category' => $log->error_category,
            'error_message' => $log->error_message,
            'status' => $log->status,
        ]);

        foreach (['sk-secret-value', 'Bearer', 'Authorization', 'OPENAI_API_KEY', 'SQLSTATE', 'stack trace'] as $leak) {
            $this->assertStringNotContainsString($leak, $serialized, "Leak detected: {$leak}");
        }

        $this->assertNull($log->error_message);
        $this->assertSame('FAILED', $meeting->fresh()->status);
    }

    public function test_no_raw_provider_payload_is_stored_on_failure(): void
    {
        $meeting = Meeting::create([
            'title' => 'Meeting',
            'raw_text' => 'Transcript.',
            'status' => 'DRAFT',
            'meeting_time' => '2026-08-19',
        ]);

        (new AnalysisOrchestrator($this->dangerousProvider()))->analyze($meeting);

        $this->assertDatabaseCount('analyses', 0);

        $row = DB::table('analysis_logs')->first();
        $encoded = (string) json_encode((array) $row);

        $this->assertStringNotContainsString('sk-secret-value', $encoded);
        $this->assertStringNotContainsString('raw', strtolower($encoded));
    }
}
