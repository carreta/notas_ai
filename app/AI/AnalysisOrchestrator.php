<?php

namespace App\AI;

use App\AI\DTO\AnalysisRequest;
use App\AI\Failure\AnalysisFailure;
use App\AI\Failure\AnalysisFailureMapper;
use App\AI\Failure\FailureCategory;
use App\AI\Metadata\AnalysisMetadata;
use App\AI\Persistence\AnalysisPersistenceService;
use App\AI\Providers\AnalysisProvider;
use App\Models\Analysis;
use App\Models\Meeting;
use Carbon\Carbon;
use DateTimeImmutable;
use Throwable;

/**
 * Application service that owns the complete synchronous analysis workflow
 * (TD-003 / TD-004):
 *
 *  Meeting (DRAFT)
 *    -> ANALYZING
 *    -> provider->analyze()            (provider-neutral port)
 *    -> StructuredAnalysisProcessor    (FR-004 parse/validate/normalize)
 *    -> AnalysisPersistenceService      (FR-005 / FR-010 persist + COMPLETED)
 *
 * Any failure is caught, mapped through {@see AnalysisFailureMapper}, recorded
 * as a safe FAILED analysis log, and the Meeting is set to FAILED — never to a
 * falsely COMPLETED state. The raw provider exception never escapes.
 */
final class AnalysisOrchestrator
{
    public function __construct(
        private readonly AnalysisProvider $provider,
        private readonly AnalysisPersistenceService $persistence = new AnalysisPersistenceService,
        private readonly StructuredAnalysisProcessor $processor = new StructuredAnalysisProcessor,
    ) {}

    public function analyze(Meeting $meeting, ?string $provider = null, ?string $modelKey = null): AnalysisOutcome
    {
        $meeting->update(['status' => 'ANALYZING']);

        $startedAt = new DateTimeImmutable;
        $startedMicro = microtime(true);

        try {
            $raw = $this->provider->analyze($this->requestFor($meeting, $provider, $modelKey));
        } catch (Throwable $e) {
            return $this->fail($meeting, $startedAt, $startedMicro, AnalysisFailureMapper::map($e), $provider, $modelKey);
        }

        try {
            $result = $this->processor->process($raw);
        } catch (Throwable $e) {
            return $this->fail($meeting, $startedAt, $startedMicro, AnalysisFailureMapper::map($e), $provider, $modelKey);
        }

        $metadata = new AnalysisMetadata(
            provider: $provider ?? $meeting->provider ?? (string) config('ai.provider', 'openai'),
            model: $modelKey ?? $meeting->model ?? (string) config('ai.model', 'gpt-4o-mini'),
            schemaVersion: (string) config('ai.schema_version', 'meeting-analysis-v1'),
            startedAt: $startedAt,
            completedAt: new DateTimeImmutable,
            durationMs: $this->durationMs($startedMicro),
            failureCategory: null,
        );

        try {
            $analysis = $this->persistence->persistWithMetadata($meeting, $result, $metadata);
        } catch (Throwable $e) {
            // Any failure while persisting the trusted result is, by definition,
            // a persistence/database failure -> PERSISTENCE_ERROR, regardless of
            // the underlying exception type (e.g. Eloquent model hook throwing).
            return $this->fail($meeting, $startedAt, $startedMicro, new AnalysisFailure(
                FailureCategory::PERSISTENCE_ERROR,
                'The analysis could not be saved.',
            ), $provider, $modelKey);
        }

        return new AnalysisOutcome(true, $analysis, null, '');
    }

    public function reAnalyze(Analysis $analysis, ?string $provider = null, ?string $modelKey = null): AnalysisOutcome
    {
        $meeting = $analysis->meeting;
        $meeting->update(['status' => 'ANALYZING']);

        $startedAt = new DateTimeImmutable;
        $startedMicro = microtime(true);

        try {
            $raw = $this->provider->analyze($this->requestFor($meeting, $provider, $modelKey));
        } catch (Throwable $e) {
            return $this->fail($meeting, $startedAt, $startedMicro, AnalysisFailureMapper::map($e), $provider, $modelKey);
        }

        try {
            $result = $this->processor->process($raw);
        } catch (Throwable $e) {
            return $this->fail($meeting, $startedAt, $startedMicro, AnalysisFailureMapper::map($e), $provider, $modelKey);
        }

        $metadata = new AnalysisMetadata(
            provider: $provider ?? $meeting->provider ?? (string) config('ai.provider', 'openai'),
            model: $modelKey ?? $meeting->model ?? (string) config('ai.model', 'gpt-4o-mini'),
            schemaVersion: (string) config('ai.schema_version', 'meeting-analysis-v1'),
            startedAt: $startedAt,
            completedAt: new DateTimeImmutable,
            durationMs: $this->durationMs($startedMicro),
            failureCategory: null,
        );

        try {
            $analysis = $this->persistence->update($analysis, $result, $metadata);
        } catch (Throwable $e) {
            return $this->fail($meeting, $startedAt, $startedMicro, new AnalysisFailure(
                FailureCategory::PERSISTENCE_ERROR,
                'The analysis could not be saved.',
            ), $provider, $modelKey);
        }

        return new AnalysisOutcome(true, $analysis, null, '');
    }

    private function fail(
        Meeting $meeting,
        DateTimeImmutable $startedAt,
        float $startedMicro,
        AnalysisFailure $failure,
        ?string $provider = null,
        ?string $modelKey = null,
    ): AnalysisOutcome {
        $metadata = new AnalysisMetadata(
            provider: $provider ?? $meeting->provider ?? (string) config('ai.provider', 'openai'),
            model: $modelKey ?? $meeting->model ?? (string) config('ai.model', 'gpt-4o-mini'),
            schemaVersion: (string) config('ai.schema_version', 'meeting-analysis-v1'),
            startedAt: $startedAt,
            completedAt: new DateTimeImmutable,
            durationMs: $this->durationMs($startedMicro),
            failureCategory: $failure->category,
        );

        try {
            $this->persistence->recordFailure($meeting, $metadata);
        } catch (Throwable) {
            // Best-effort: the Meeting state below is still corrected.
        }

        $meeting->update(['status' => 'FAILED']);

        return new AnalysisOutcome(false, null, $failure->category, $failure->userMessage);
    }

    private function requestFor(Meeting $meeting, ?string $provider = null, ?string $modelKey = null): AnalysisRequest
    {
        // Resolve model key: from parameter, then meeting, then config default
        $resolvedModelKey = $modelKey ?? $meeting->model ?? config('ai.provider', 'openai');
        $modelConfig = config("models.{$resolvedModelKey}") ?? [];

        // Resolve provider: from parameter, then meeting, then model config, then config default
        $resolvedProvider = $provider ?? $meeting->provider ?? $modelConfig['provider'] ?? config('ai.provider', 'openai');
        $providerConfig = config("ai.providers.{$resolvedProvider}") ?? [];

        // The model to send to the provider - use the model from the selected model config
        // or the provider's default model
        $model = $modelConfig['model'] ?? $providerConfig['model'] ?? config('ai.model', 'gpt-4o-mini');

        return new AnalysisRequest(
            content: $meeting->raw_text ?? '',
            referenceDate: $meeting->meeting_time !== null
                ? Carbon::parse($meeting->meeting_time)->toDateString()
                : null,
            model: $model,
            schemaVersion: (string) config('ai.schema_version', 'meeting-analysis-v1'),
            provider: $resolvedProvider,
            modelKey: $resolvedModelKey,
        );
    }

    private function durationMs(float $startedMicro): int
    {
        return (int) round((microtime(true) - $startedMicro) * 1000);
    }
}
