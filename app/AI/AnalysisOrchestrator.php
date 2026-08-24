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
use Illuminate\Support\Facades\Log;
use LogicException;
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
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalysisOrchestrator] analyze() started', [
            'meeting_id' => $meeting->id,
            'meeting_status_before' => $meeting->status,
            'provider_param' => $provider,
            'model_key_param' => $modelKey,
            'config_ai_provider' => config('ai.provider'),
            'config_ai_model' => config('ai.model'),
        ]);

        $meeting->update(['status' => 'ANALYZING']);

        $startedAt = new DateTimeImmutable;
        $startedMicro = microtime(true);

        // TODO: Revert once testing is sufficient - remove temporary logging
        $request = $this->requestFor($meeting, $provider, $modelKey);
        Log::info('[TEMP][AnalysisOrchestrator] AnalysisRequest built', [
            'content_length' => mb_strlen($request->content),
            'reference_date' => $request->referenceDate,
            'model' => $request->model,
            'schema_version' => $request->schemaVersion,
            'provider' => $request->provider,
            'model_key' => $request->modelKey,
        ]);

        try {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalysisOrchestrator] Calling provider->analyze()', [
                'provider_class' => get_class($this->provider),
            ]);
            // original: $raw = $this->provider->analyze($this->requestFor($meeting, $provider, $modelKey));
            $providerResult = $this->provider->analyze($request);
            $raw = $providerResult->content;
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalysisOrchestrator] Provider returned raw response', [
                'raw_length' => mb_strlen($raw),
                'raw_preview' => substr($raw, 0, 500),
                'prompt_tokens' => $providerResult->promptTokens,
                'completion_tokens' => $providerResult->completionTokens,
                'total_tokens' => $providerResult->totalTokens,
            ]);
        } catch (Throwable $e) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][AnalysisOrchestrator] Provider threw exception', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->fail($meeting, $startedAt, $startedMicro, AnalysisFailureMapper::map($e), $provider, $modelKey);
        }

        try {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalysisOrchestrator] Calling processor->process()');
            $result = $this->processor->process($raw);
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalysisOrchestrator] Processor completed successfully', [
                'summary_length' => mb_strlen($result->summary),
                'decisions_count' => count($result->decisions),
                'action_items_count' => count($result->actionItems),
                'open_questions_count' => count($result->openQuestions),
            ]);
        } catch (Throwable $e) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][AnalysisOrchestrator] Processor threw exception', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->fail($meeting, $startedAt, $startedMicro, AnalysisFailureMapper::map($e), $provider, $modelKey);
        }

        $metadata = new AnalysisMetadata(
            provider: $provider ?? (string) config('ai.provider', 'openai'),
            model: $modelKey ?? (string) config('ai.model', 'gpt-5.6-luna'),
            schemaVersion: (string) config('ai.schema_version', 'meeting-analysis-v1'),
            startedAt: $startedAt,
            completedAt: new DateTimeImmutable,
            durationMs: $this->durationMs($startedMicro),
            promptTokens: $providerResult->promptTokens,
            completionTokens: $providerResult->completionTokens,
            totalTokens: $providerResult->totalTokens,
            failureCategory: null,
        );

        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalysisOrchestrator] Metadata created', [
            'provider' => $metadata->provider,
            'model' => $metadata->model,
            'schema_version' => $metadata->schemaVersion,
            'duration_ms' => $metadata->durationMs,
            'prompt_tokens' => $metadata->promptTokens,
            'completion_tokens' => $metadata->completionTokens,
            'total_tokens' => $metadata->totalTokens,
        ]);

        try {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalysisOrchestrator] Calling persistence->persistWithMetadata()');
            $analysis = $this->persistence->persistWithMetadata($meeting, $result, $metadata);
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalysisOrchestrator] Persistence completed', [
                'analysis_id' => $analysis->id,
            ]);
        } catch (Throwable $e) {
            // Any failure while persisting the trusted result is, by definition,
            // a persistence/database failure -> PERSISTENCE_ERROR, regardless of
            // the underlying exception type (e.g. Eloquent model hook throwing).
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][AnalysisOrchestrator] Persistence threw exception', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->fail($meeting, $startedAt, $startedMicro, new AnalysisFailure(
                FailureCategory::PERSISTENCE_ERROR,
                'The analysis could not be saved.',
            ), $provider, $modelKey);
        }

        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalysisOrchestrator] analyze() completed successfully', [
            'analysis_id' => $analysis->id,
            'duration_ms' => $metadata->durationMs,
        ]);

        return new AnalysisOutcome(true, $analysis, null, '');
    }

    public function reAnalyze(Analysis $analysis, ?string $provider = null, ?string $modelKey = null): AnalysisOutcome
    {
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalysisOrchestrator] reAnalyze() started', [
            'analysis_id' => $analysis->id,
            'provider_param' => $provider,
            'model_key_param' => $modelKey,
        ]);

        $meeting = $analysis->meeting;

        if (! $meeting instanceof Meeting) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][AnalysisOrchestrator] reAnalyze: meeting not found');
            throw new LogicException('Analysis cannot be re-analyzed without its meeting.');
        }

        $meeting->update(['status' => 'ANALYZING']);

        $startedAt = new DateTimeImmutable;
        $startedMicro = microtime(true);

        // TODO: Revert once testing is sufficient - remove temporary logging
        $request = $this->requestFor($meeting, $provider, $modelKey);
        Log::info('[TEMP][AnalysisOrchestrator] reAnalyze: AnalysisRequest built', [
            'content_length' => mb_strlen($request->content),
            'model' => $request->model,
            'provider' => $request->provider,
            'model_key' => $request->modelKey,
        ]);

        try {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalysisOrchestrator] reAnalyze: Calling provider->analyze()');
            // original: $raw = $this->provider->analyze($this->requestFor($meeting, $provider, $modelKey));
            $providerResult = $this->provider->analyze($request);
            $raw = $providerResult->content;
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalysisOrchestrator] reAnalyze: Provider returned raw response', [
                'raw_length' => mb_strlen($raw),
                'prompt_tokens' => $providerResult->promptTokens,
                'completion_tokens' => $providerResult->completionTokens,
                'total_tokens' => $providerResult->totalTokens,
            ]);
        } catch (Throwable $e) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][AnalysisOrchestrator] reAnalyze: Provider threw exception', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $this->fail($meeting, $startedAt, $startedMicro, AnalysisFailureMapper::map($e), $provider, $modelKey);
        }

        try {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalysisOrchestrator] reAnalyze: Calling processor->process()');
            $result = $this->processor->process($raw);
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalysisOrchestrator] reAnalyze: Processor completed');
        } catch (Throwable $e) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][AnalysisOrchestrator] reAnalyze: Processor threw exception', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $this->fail($meeting, $startedAt, $startedMicro, AnalysisFailureMapper::map($e), $provider, $modelKey);
        }

        $metadata = new AnalysisMetadata(
            provider: $provider ?? (string) config('ai.provider', 'openai'),
            model: $modelKey ?? (string) config('ai.model', 'gpt-5.6-luna'),
            schemaVersion: (string) config('ai.schema_version', 'meeting-analysis-v1'),
            startedAt: $startedAt,
            completedAt: new DateTimeImmutable,
            durationMs: $this->durationMs($startedMicro),
            promptTokens: $providerResult->promptTokens,
            completionTokens: $providerResult->completionTokens,
            totalTokens: $providerResult->totalTokens,
            failureCategory: null,
        );

        try {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalysisOrchestrator] reAnalyze: Calling persistence->update()');
            $analysis = $this->persistence->update($analysis, $result, $metadata);
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalysisOrchestrator] reAnalyze: Persistence completed', [
                'analysis_id' => $analysis->id,
            ]);
        } catch (Throwable $e) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][AnalysisOrchestrator] reAnalyze: Persistence threw exception', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $this->fail($meeting, $startedAt, $startedMicro, new AnalysisFailure(
                FailureCategory::PERSISTENCE_ERROR,
                'The analysis could not be saved.',
            ), $provider, $modelKey);
        }

        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalysisOrchestrator] reAnalyze() completed successfully', [
            'analysis_id' => $analysis->id,
        ]);

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
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::warning('[TEMP][AnalysisOrchestrator] fail() called', [
            'meeting_id' => $meeting->id,
            'failure_category' => $failure->category,
            'failure_user_message' => $failure->userMessage,
            'provider_param' => $provider,
            'model_key_param' => $modelKey,
        ]);

        $metadata = new AnalysisMetadata(
            provider: $provider ?? (string) config('ai.provider', 'openai'),
            model: $modelKey ?? (string) config('ai.model', 'gpt-5.6-luna'),
            schemaVersion: (string) config('ai.schema_version', 'meeting-analysis-v1'),
            startedAt: $startedAt,
            completedAt: new DateTimeImmutable,
            durationMs: $this->durationMs($startedMicro),
            promptTokens: null,
            completionTokens: null,
            totalTokens: null,
            failureCategory: $failure->category,
        );

        try {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalysisOrchestrator] fail: recording failure in persistence');
            $this->persistence->recordFailure($meeting, $metadata);
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalysisOrchestrator] fail: failure recorded');
        } catch (Throwable) {
            // Best-effort: the Meeting state below is still corrected.
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][AnalysisOrchestrator] fail: failed to record failure');
        }

        $meeting->update(['status' => 'FAILED']);
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalysisOrchestrator] fail: meeting status set to FAILED');

        return new AnalysisOutcome(false, null, $failure->category, $failure->userMessage);
    }

    private function requestFor(Meeting $meeting, ?string $provider = null, ?string $modelKey = null): AnalysisRequest
    {
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalysisOrchestrator] requestFor() resolving config', [
            'provider_param' => $provider,
            'model_key_param' => $modelKey,
        ]);

        // Resolve model key: from parameter, then meeting, then config default
        $resolvedModelKey = $modelKey ?? config('ai.provider', 'openai');
        $modelConfig = config("models.{$resolvedModelKey}") ?? [];

        // Resolve provider: from parameter, then meeting, then model config, then config default
        $resolvedProvider = $provider ?? $modelConfig['provider'] ?? config('ai.provider', 'openai');
        $providerConfig = config("ai.providers.{$resolvedProvider}") ?? [];

        // The model to send to the provider - use the model from the selected model config
        // or the provider's default model
        $model = $modelConfig['model'] ?? $providerConfig['model'] ?? config('ai.model', 'gpt-5.6-luna');

        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalysisOrchestrator] requestFor() resolved', [
            'resolved_model_key' => $resolvedModelKey,
            'resolved_provider' => $resolvedProvider,
            'model_config' => $modelConfig,
            'final_model' => $model,
        ]);

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
