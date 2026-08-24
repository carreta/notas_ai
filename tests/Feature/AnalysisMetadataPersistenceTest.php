<?php

namespace Tests\Feature;

use App\AI\DTO\ActionItem;
use App\AI\DTO\AnalysisResult;
use App\AI\DTO\Decision;
use App\AI\DTO\OpenQuestion;
use App\AI\Metadata\AnalysisMetadata;
use App\AI\Persistence\AnalysisPersistenceService;
use App\Models\AiMetric;
use App\Models\Analysis;
use App\Models\AnalysisLog;
use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalysisMetadataPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private AnalysisPersistenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AnalysisPersistenceService;

        // analysis_logs.prompt_version is a FK to prompt_templates.version,
        // so the referenced prompt template must exist before persistence.
        DB::table('prompt_templates')->upsert([
            'id' => (string) Str::uuid(),
            'version' => 'meeting-analysis-v1',
            'system_prompt' => 'system',
            'json_schema' => json_encode(['version' => 'meeting-analysis-v1']),
            'is_active' => true,
            'created_at' => now(),
        ], ['version'], ['system_prompt', 'json_schema', 'is_active', 'created_at']);
    }

    protected function tearDown(): void
    {
        Analysis::flushEventListeners();
        parent::tearDown();
    }

    private function analysisResult(): AnalysisResult
    {
        return new AnalysisResult(
            summary: 'Summary',
            decisions: [new Decision('Decision one')],
            actionItems: [
                new ActionItem(
                    task: 'Task',
                    owner: 'Ana',
                    priority: 'HIGH',
                    prioritySource: 'EXPLICIT',
                    dueDateText: 'next Friday',
                    dueDate: '2026-08-28',
                    dueDateSource: 'INFERRED',
                ),
            ],
            openQuestions: [new OpenQuestion('Open question?')],
        );
    }

    private function meeting(string $status = 'ANALYZING'): Meeting
    {
        return Meeting::create([
            'title' => 'Meeting',
            'raw_text' => 'Transcript.',
            'status' => $status,
            'meeting_time' => '2026-08-19',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function metadata(array $overrides = []): AnalysisMetadata
    {
        return new AnalysisMetadata(
            provider: $overrides['provider'] ?? 'openai',
            model: $overrides['model'] ?? 'gpt-5.6-luna',
            schemaVersion: $overrides['schemaVersion'] ?? 'meeting-analysis-v1',
            startedAt: $overrides['startedAt'] ?? new \DateTimeImmutable('2026-08-19 10:00:00'),
            completedAt: $overrides['completedAt'] ?? new \DateTimeImmutable('2026-08-19 10:00:30'),
            durationMs: $overrides['durationMs'] ?? 30000,
            failureCategory: $overrides['failureCategory'] ?? null,
        );
    }

    private function rawRow(Meeting $meeting): object
    {
        return DB::table('analyses')->where('meeting_id', $meeting->id)->first();
    }

    private function rawLog(Meeting $meeting): AnalysisLog
    {
        $logId = DB::table('analyses')->where('meeting_id', $meeting->id)->value('analysis_metadata');

        return AnalysisLog::findOrFail($logId);
    }

    private function rawMetric(Meeting $meeting): AiMetric
    {
        $analysisId = DB::table('analyses')->where('meeting_id', $meeting->id)->value('id');

        return AiMetric::where('analysis_id', $analysisId)->firstOrFail();
    }

    public function test_success_metadata_is_persisted(): void
    {
        $meeting = $this->meeting();
        $this->service->persistWithMetadata($meeting, $this->analysisResult(), $this->metadata());

        $log = $this->rawLog($meeting);
        $metric = $this->rawMetric($meeting);
        $this->assertSame('openai', $log->provider);
        $this->assertSame('gpt-5.6-luna', $log->model);
        $this->assertSame('meeting-analysis-v1', $log->prompt_version);
        $this->assertSame('2026-08-19 10:00:00', $log->started_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-19 10:00:30', $log->completed_at->format('Y-m-d H:i:s'));
        $this->assertSame(30000, $metric->duration_ms);
        $this->assertNull($log->error_category);
    }

    public function test_metadata_belongs_to_correct_analysis(): void
    {
        $meeting = $this->meeting();
        $analysis = $this->service->persistWithMetadata($meeting, $this->analysisResult(), $this->metadata());

        $this->assertSame($meeting->id, $analysis->meeting_id);
        $this->assertSame('openai', $analysis->analysisMetadata->provider);
    }

    public function test_metadata_cannot_be_attached_to_wrong_analysis_via_provider_output(): void
    {
        $meetingA = $this->meeting();
        $meetingB = $this->meeting();

        $analysis = $this->service->persistWithMetadata($meetingA, $this->analysisResult(), $this->metadata());

        $this->assertSame($meetingA->id, $analysis->meeting_id);
        $this->assertNull($meetingB->fresh()->analysis);
        $this->assertSame('ANALYZING', $meetingB->fresh()->status);
    }

    public function test_provider_identifier_persisted(): void
    {
        $meeting = $this->meeting();
        $this->service->persistWithMetadata($meeting, $this->analysisResult(), $this->metadata(['provider' => 'openai']));

        $this->assertSame('openai', $this->rawLog($meeting)->provider);
    }

    public function test_model_config_identifier_persisted(): void
    {
        $meeting = $this->meeting();
        $this->service->persistWithMetadata($meeting, $this->analysisResult(), $this->metadata(['model' => 'gpt-4o']));

        $this->assertSame('gpt-4o', $this->rawLog($meeting)->model);
    }

    public function test_schema_version_persisted(): void
    {
        $meeting = $this->meeting();
        $this->service->persistWithMetadata($meeting, $this->analysisResult(), $this->metadata(['schemaVersion' => 'meeting-analysis-v1']));

        $this->assertSame('meeting-analysis-v1', $this->rawLog($meeting)->prompt_version);
    }

    public function test_started_at_persisted(): void
    {
        $meeting = $this->meeting();
        $started = new \DateTimeImmutable('2026-08-19 09:15:00');
        $this->service->persistWithMetadata($meeting, $this->analysisResult(), $this->metadata(['startedAt' => $started]));

        $this->assertSame('2026-08-19 09:15:00', $this->rawLog($meeting)->started_at->format('Y-m-d H:i:s'));
    }

    public function test_completed_at_persisted(): void
    {
        $meeting = $this->meeting();
        $completed = new \DateTimeImmutable('2026-08-19 09:15:45');
        $this->service->persistWithMetadata($meeting, $this->analysisResult(), $this->metadata(['completedAt' => $completed]));

        $this->assertSame('2026-08-19 09:15:45', $this->rawLog($meeting)->completed_at->format('Y-m-d H:i:s'));
    }

    public function test_duration_persisted_and_valid(): void
    {
        $meeting = $this->meeting();
        $this->service->persistWithMetadata($meeting, $this->analysisResult(), $this->metadata(['durationMs' => 1234]));

        $metric = $this->rawMetric($meeting);
        $this->assertIsInt($metric->duration_ms);
        $this->assertSame(1234, $metric->duration_ms);
    }

    public function test_success_has_no_failure_category(): void
    {
        $meeting = $this->meeting();
        $this->service->persistWithMetadata($meeting, $this->analysisResult(), $this->metadata(['failureCategory' => null]));

        $this->assertNull($this->rawLog($meeting)->error_category);
    }

    public function test_failure_category_represented_safely(): void
    {
        $meeting = $this->meeting();
        $this->service->persistWithMetadata(
            $meeting,
            $this->analysisResult(),
            $this->metadata(['failureCategory' => 'AI_TIMEOUT']),
        );

        $stored = $this->rawLog($meeting)->error_category;
        $this->assertSame('AI_TIMEOUT', $stored);
        $this->assertContains($stored, [
            'VALIDATION_ERROR',
            'AI_CONFIGURATION_ERROR',
            'AI_DEPENDENCY_ERROR',
            'AI_TIMEOUT',
            'AI_RATE_LIMIT',
            'AI_INVALID_RESPONSE',
            'PERSISTENCE_ERROR',
            'INTERNAL_ERROR',
        ]);
    }

    public function test_secrets_are_not_persisted(): void
    {
        $meeting = $this->meeting();
        $this->service->persistWithMetadata($meeting, $this->analysisResult(), $this->metadata([
            'provider' => 'openai',
            'model' => 'gpt-5.6-luna',
        ]));

        $log = $this->rawLog($meeting);
        $row = $this->rawRow($meeting);
        $serialized = json_encode([
            'provider' => $log->provider,
            'model' => $log->model,
            'prompt_version' => $log->prompt_version,
            'error_category' => $log->error_category,
            'result' => $row->result,
        ]);

        foreach (['sk-', 'Authorization', 'Bearer', 'api_key', 'apiKey', 'OPENAI_API_KEY'] as $leak) {
            $this->assertStringNotContainsString($leak, $serialized, "Leak detected: {$leak}");
        }
    }

    public function test_result_json_contains_no_raw_provider_payload(): void
    {
        $meeting = $this->meeting();
        $this->service->persistWithMetadata($meeting, $this->analysisResult(), $this->metadata());

        $stored = json_decode($this->rawRow($meeting)->result, true);
        $this->assertSame(['summary', 'decisions', 'action_items', 'open_questions'], array_keys($stored));
    }

    public function test_deleting_parent_removes_analysis_and_metadata(): void
    {
        $meeting = $this->meeting();
        $this->service->persistWithMetadata($meeting, $this->analysisResult(), $this->metadata());
        $this->assertDatabaseCount('analyses', 1);
        $this->assertDatabaseCount('analysis_logs', 1);
        $this->assertDatabaseCount('ai_metrics', 1);

        $meeting->delete();

        $this->assertDatabaseCount('analyses', 0);
        $this->assertDatabaseCount('analysis_logs', 0);
        $this->assertDatabaseCount('ai_metrics', 0);
    }

    public function test_metadata_failure_does_not_leave_false_completed(): void
    {
        $meeting = $this->meeting('ANALYZING');

        Analysis::creating(static function (): void {
            throw new \RuntimeException('forced persistence failure');
        });

        try {
            $this->service->persistWithMetadata($meeting, $this->analysisResult(), $this->metadata());
            $this->fail('Expected persistence to fail.');
        } catch (\Throwable $e) {
            // expected: transaction must roll back
        }

        $this->assertDatabaseCount('analyses', 0);
        $this->assertDatabaseCount('analysis_logs', 0);
        $this->assertDatabaseHas('meetings', ['id' => $meeting->id, 'status' => 'ANALYZING']);
    }
}
