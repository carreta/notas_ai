<?php

namespace App\Livewire;

use App\AI\AnalysisOrchestrator;
use App\AI\AnalysisOutcome;
use App\Models\Analysis;
use App\Models\Meeting;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use LogicException;

class AnalysisDetailModal extends Component
{
    public ?string $analysisId = null;

    public ?string $meetingId = null;

    public ?Analysis $analysis = null;

    public ?Meeting $meeting = null;

    public string $activeTab = 'analysis';

    // Re-analyze properties. Valid states: idle, analyzing, completed, error.
    public string $reAnalyzeStage = 'idle';

    public string $reAnalyzeModel = 'chatgpt-sol';

    public array $models = [];

    public int $reAnalyzeProgress = 0;

    public string $reAnalyzeLabel = '';

    public ?string $reAnalyzeError = null;

    public ?string $reAnalyzeErrorCategory = null;

    // Manual correction (human edit) state. A user may fix AI-generated
    // result fields without triggering another LLM request or a new
    // AnalysisLog. This is a HUMAN correction, never an AI analysis attempt.
    public bool $editing = false;

    public array $editableResult = [];

    protected $listeners = [
        'openAnalysisModal' => 'openModal',
        'openMeetingModal' => 'openModalByMeeting',
    ];

    public function mount(?string $analysisId = null): void
    {
        $this->analysisId = $analysisId;
        $this->models = config('models');
        $this->reAnalyzeModel = array_key_first($this->models) ?? 'chatgpt-sol';
        $this->loadAnalysis();
    }

    public function openModal(string $analysisId): void
    {
        $this->analysisId = $analysisId;
        $this->meetingId = null;
        $this->loadAnalysis();
        $this->activeTab = 'analysis';
        $this->resetReAnalyze();
        $this->exitEditing();
    }

    #[On('openMeetingModal')]
    public function openModalByMeeting(string $meetingId): void
    {
        logger()->info('[AnalysisDetailModal] openModalByMeeting called', ['meetingId' => $meetingId]);
        $this->meetingId = $meetingId;
        $this->analysisId = null;
        $this->loadAnalysisFromMeeting();
        $this->activeTab = 'analysis';
        $this->resetReAnalyze();
        $this->exitEditing();
    }

    public function closeModal(): void
    {
        $this->analysisId = null;
        $this->meetingId = null;
        $this->analysis = null;
        $this->meeting = null;
        $this->activeTab = 'analysis';
        $this->resetReAnalyze();
        $this->exitEditing();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Enter manual-edit mode by copying the current Analysis.result into a
     * local editable copy. No provider call, no DB write.
     */
    public function startEditing(): void
    {
        if (! $this->analysis) {
            return;
        }

        // Deep copy so cancelling never mutates the original result.
        $this->editableResult = json_decode(json_encode($this->analysis->result ?? []), true) ?? [];
        $this->editing = true;
        $this->resetErrorBag();
    }

    /**
     * Discard unsaved edits and return to read-only mode. No DB write.
     */
    public function cancelEditing(): void
    {
        $this->exitEditing();
    }

    /**
     * Persist the human-corrected result onto the existing Analysis row.
     *
     * This is a HUMAN correction:
     *  - the AI provider is NOT contacted;
     *  - no new AnalysisLog is created (editing is not an AI attempt);
     *  - the existing provider/model/token metadata is left untouched;
     *  - only analyses.result is updated (updated_at changes automatically).
     */
    public function saveEdits(): void
    {
        if (! $this->analysis) {
            return;
        }

        $this->normalizeEditableResult();
        $this->applyProvenanceAdjustments();

        // Field-level validation. On failure Livewire throws and keeps us in
        // edit mode with the error bag populated; nothing is persisted.
        $this->validate($this->editRules());

        // Cross-field contract consistency (priority/due-date sources).
        if (! $this->validateConsistency()) {
            return;
        }

        $this->analysis->update(['result' => $this->editableResult]);

        $this->exitEditing();
        $this->analysis->refresh();

        // Let other components (e.g. History) refresh the displayed result.
        $this->dispatch('analysisUpdated', analysisId: $this->analysis->id);
    }

    private function exitEditing(): void
    {
        $this->editing = false;
        $this->editableResult = [];
        $this->resetErrorBag();
    }

    /**
     * Normalize empty strings to null and guarantee every action-item key
     * exists with the contract shape before validation/persistence.
     */
    private function normalizeEditableResult(): void
    {
        $result = $this->editableResult;

        $result['summary'] = isset($result['summary']) ? (string) $result['summary'] : '';

        $result['decisions'] = array_values(array_map(
            static fn ($d): array => ['text' => is_array($d) ? (string) ($d['text'] ?? '') : (string) $d],
            $result['decisions'] ?? []
        ));

        $result['open_questions'] = array_values(array_map(
            static fn ($q): array => ['text' => is_array($q) ? (string) ($q['text'] ?? '') : (string) $q],
            $result['open_questions'] ?? []
        ));

        $result['action_items'] = array_values(array_map(function ($item): array {
            $item = is_array($item) ? $item : [];

            return [
                'task' => (string) ($item['task'] ?? ''),
                'owner' => $this->nullIfEmpty($item['owner'] ?? null),
                'priority' => $this->nullIfEmpty($item['priority'] ?? null),
                'priority_source' => $item['priority_source'] ?? null,
                'due_date_text' => $this->nullIfEmpty($item['due_date_text'] ?? null),
                'due_date' => $this->nullIfEmpty($item['due_date'] ?? null),
                'due_date_source' => $item['due_date_source'] ?? null,
            ];
        }, $result['action_items'] ?? []));

        $this->editableResult = $result;
    }

    private function nullIfEmpty(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && trim($value) === '') {
            return null;
        }

        return is_string($value) ? $value : (string) $value;
    }

    /**
     * Apply provenance policy when the USER manually changes a controlled
     * value (TD-010 / TD-011): a manual change must not keep a false
     * "EXPLICIT"/"INFERRED"/"RESOLVED"/"UNRESOLVED" provenance.
     *
     *  - priority changed  -> priority_source = null
     *  - due_date changed  -> due_date_source = null AND stale due_date_text cleared
     *
     * Unchanged fields keep their original provenance.
     */
    private function applyProvenanceAdjustments(): void
    {
        $analysis = $this->analysis;
        if ($analysis === null) {
            return;
        }

        $original = $analysis->result;
        $originalItems = is_array($original['action_items'] ?? null) ? $original['action_items'] : [];

        foreach ($this->editableResult['action_items'] ?? [] as $i => $item) {
            $originalItem = is_array($originalItems[$i] ?? null) ? $originalItems[$i] : [];

            if (($originalItem['priority'] ?? null) !== ($item['priority'] ?? null)) {
                $this->editableResult['action_items'][$i]['priority_source'] = null;
            }

            if (($originalItem['due_date'] ?? null) !== ($item['due_date'] ?? null)) {
                $this->editableResult['action_items'][$i]['due_date_source'] = null;
                $this->editableResult['action_items'][$i]['due_date_text'] = null;
            }
        }
    }

    /**
     * Field-level rules for the editable copy. Mirrors the AI contract
     * (StructuredAnalysisValidator) but uses Livewire's error bag so the UI
     * can show per-field messages and stay in edit mode on failure.
     */
    private function editRules(): array
    {
        return [
            'editableResult.summary' => ['required', 'string', 'max:5000'],
            'editableResult.decisions.*.text' => ['required', 'string', 'max:5000'],
            'editableResult.open_questions.*.text' => ['required', 'string', 'max:5000'],
            'editableResult.action_items.*.task' => ['required', 'string', 'max:2000'],
            'editableResult.action_items.*.owner' => ['nullable', 'string', 'max:255'],
            'editableResult.action_items.*.priority' => ['nullable', Rule::in(['LOW', 'MEDIUM', 'HIGH'])],
            'editableResult.action_items.*.due_date' => ['nullable', 'date', 'date_format:Y-m-d'],
        ];
    }

    /**
     * Cross-field contract consistency (TD-010 / TD-011). Returns true when
     * the editable result is internally consistent, otherwise populates the
     * error bag and returns false (caller keeps edit mode).
     */
    private function validateConsistency(): bool
    {
        $valid = true;

        foreach ($this->editableResult['action_items'] ?? [] as $i => $item) {
            $priority = $item['priority'] ?? null;
            $prioritySource = $item['priority_source'] ?? null;

            if ($priority === null && $prioritySource !== null) {
                $this->addError(
                    "editableResult.action_items.{$i}.priority_source",
                    'A priority source requires a priority.'
                );
                $valid = false;
            }

            if ($priority !== null && $prioritySource !== null
                && ! in_array($prioritySource, ['EXPLICIT', 'INFERRED'], true)) {
                $this->addError(
                    "editableResult.action_items.{$i}.priority_source",
                    'Invalid priority source.'
                );
                $valid = false;
            }

            $dueSource = $item['due_date_source'] ?? null;
            $dueDate = $item['due_date'] ?? null;
            $dueText = $item['due_date_text'] ?? null;

            if ($dueSource === 'UNRESOLVED' && ($dueDate !== null || $dueText === null)) {
                $this->addError(
                    "editableResult.action_items.{$i}.due_date_source",
                    'An unresolved due date needs relative text and no absolute date.'
                );
                $valid = false;
            }
        }

        return $valid;
    }

    public function reAnalyze(): void
    {
        // Re-analysis is always sourced from the original Meeting, never from a
        // previous (possibly failed) Analysis. A failed attempt leaves no
        // Analysis row at all, so the Meeting is the only required source.
        if (! $this->meeting) {
            // State 5: the button was pressed but no request can be made.
            // Surface it instead of failing silently.
            $this->reAnalyzeStage = 'error';
            $this->reAnalyzeProgress = 0;
            $this->reAnalyzeLabel = 'Re-analysis failed';
            $this->reAnalyzeError = $this->reAnalyzeBeforeProviderMessage();
            $this->reAnalyzeErrorCategory = 'INTERNAL_ERROR';

            return;
        }

        $this->resetReAnalyze();
        $this->reAnalyzeStage = 'analyzing';
        $this->reAnalyzeProgress = 10;
        $this->reAnalyzeLabel = 'Analyzing transcript...';

        // Update meeting status to ANALYZING when re-analysis starts.
        // Source is the Meeting, which always exists here (guarded above) even
        // when a previous failed attempt left no Analysis row.
        $this->meeting->update(['status' => 'ANALYZING']);
        $this->meeting = $this->meeting->fresh();

        // Dispatch browser event to update history table in real-time
        $this->dispatch('meetingStatusUpdated', meetingId: $this->meeting->id, status: 'ANALYZING');
    }

    public function executeReAnalyze(): void
    {
        if (! $this->meeting) {
            // State 5: no request was ever made to the AI provider.
            $this->handleReAnalyzeFailure($this->reAnalyzeBeforeProviderMessage(), 'INTERNAL_ERROR');

            return;
        }

        $meeting = $this->meeting;
        $meetingId = $meeting->id;
        // The previous attempt, if any. A failed attempt leaves no Analysis row,
        // so this is often null and that is expected.
        $previousAnalysis = $this->analysis;

        $this->reAnalyzeProgress = 30;
        $this->reAnalyzeLabel = 'Re-analysis requested. Sending request to AI provider...';

        Log::info('Re-analysis request prepared', [
            'meeting_id' => $meetingId,
            'meeting_status' => $this->meeting->status,
            'model' => $this->reAnalyzeModel,
        ]);

        $selectedModelConfig = $this->models[$this->reAnalyzeModel] ?? [];
        $provider = $selectedModelConfig['provider'] ?? config('ai.provider', 'openai');
        $modelKey = $this->reAnalyzeModel;

        Log::info('Re-analysis invoking analysis pipeline', [
            'meeting_id' => $meetingId,
        ]);

        try {
            if ($previousAnalysis instanceof Analysis) {
                // A usable prior Analysis exists: re-run the same pipeline,
                // updating that record. Content is still sourced from the Meeting.
                /** @var AnalysisOutcome $outcome */
                $outcome = app(AnalysisOrchestrator::class)->reAnalyze($previousAnalysis, $provider, $modelKey);
            } else {
                // No prior Analysis (e.g. the previous attempt failed and left no
                // Analysis row). Reuse the exact same pipeline as the main Analyze
                // button, sourced entirely from the original Meeting.
                /** @var AnalysisOutcome $outcome */
                $outcome = app(AnalysisOrchestrator::class)->analyze($meeting, $provider, $modelKey);
            }
        } catch (LogicException $exception) {
            // Thrown by the orchestrator before any AI provider call is attempted.
            Log::error('Re-analysis failed before provider', [
                'meeting_id' => $meetingId,
                'meeting_status' => $this->meeting->status,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            $this->handleReAnalyzeFailure($this->reAnalyzeBeforeProviderMessage(), 'INTERNAL_ERROR');

            return;
        } catch (\Throwable $exception) {
            report($exception);

            Log::error('Re-analysis failed', [
                'meeting_id' => $meetingId,
                'meeting_status' => $this->meeting->status,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            $this->handleReAnalyzeFailure('The analysis could not be completed. Please try again later.', 'INTERNAL_ERROR');

            return;
        }

        if (! $outcome->success) {
            // State 4: the request reached the provider (or a later pipeline stage)
            // but did not succeed. Surface the category so the user can tell
            // e.g. AI_INVALID_RESPONSE apart from a generic failure.
            Log::error('Re-analysis failed', [
                'meeting_id' => $meetingId,
                'meeting_status' => $this->meeting->status,
                'category' => $outcome->category,
            ]);

            $this->handleReAnalyzeFailure(
                $this->reAnalyzeAfterProviderMessage($outcome->category, $outcome->userMessage),
                $outcome->category,
            );

            return;
        }

        // State 3: the request completed successfully.
        Log::info('Re-analysis AI request completed', [
            'meeting_id' => $meetingId,
            'analysis_id' => $outcome->analysis?->id,
        ]);

        // Reload the analysis to get fresh data. If this attempt created a new
        // Analysis (no prior analysis_id), reload from the Meeting instead.
        $this->loadAnalysis();
        if (! $this->analysis && $this->meeting) {
            $this->loadAnalysisFromMeeting();
        }

        $this->reAnalyzeStage = 'completed';
        $this->reAnalyzeProgress = 100;
        $this->reAnalyzeLabel = 'Re-analysis completed successfully';

        // Update meeting status to COMPLETED on successful re-analysis
        if ($this->analysis && $this->analysis->meeting) {
            $this->analysis->meeting->update(['status' => 'COMPLETED']);
            $this->meeting = Meeting::find($this->analysis->meeting_id);

            // Dispatch browser event to update history table in real-time
            $this->dispatch('meetingStatusUpdated', meetingId: $this->meeting->id, status: 'COMPLETED');
        }
    }

    public function retryReAnalyze(): void
    {
        $this->reAnalyze();
    }

    private function handleReAnalyzeFailure(string $error, string $category): void
    {
        // A failed re-analysis attempt must NEVER erase or overwrite a
        // previously successful Analysis. The previous result remains the
        // meeting's valid analysis and must stay available for inspection.
        //
        // The orchestrator's failure path already:
        //  - recorded a safe FAILED AnalysisLog (via recordFailure);
        //  - set the Meeting status to FAILED.
        // Here we simply re-read the (untouched) Analysis so the modal keeps
        // showing the last good result, and surface a safe error to the user.
        if ($this->analysis) {
            $this->analysis->refresh();
        }

        // Update meeting status to FAILED on re-analysis failure. Use the
        // Meeting directly so this works even when no prior Analysis exists
        // (a failed attempt leaves no Analysis row).
        if ($this->meeting) {
            $this->meeting->update(['status' => 'FAILED']);
            $this->meeting = $this->meeting->fresh();

            // Dispatch browser event to update history table in real-time
            $this->dispatch('meetingStatusUpdated', meetingId: $this->meeting->id, status: 'FAILED');
        }

        $this->reAnalyzeStage = 'error';
        $this->reAnalyzeProgress = 0;
        $this->reAnalyzeLabel = 'Re-analysis failed';
        $this->reAnalyzeError = $error;
        $this->reAnalyzeErrorCategory = $category;
    }

    /**
     * User-facing message when the click never reaches the AI provider
     * (missing analysis/meeting or orchestrator guard). The technical code is
     * only revealed in local/debug/testing environments.
     */
    private function reAnalyzeBeforeProviderMessage(): string
    {
        if (app()->environment(['local', 'debug', 'testing'])) {
            return 'Re-analysis failed before contacting the AI provider.';
        }

        return 'Re-analysis could not start. Please try again.';
    }

    /**
     * User-facing message when the request reached the provider but did not
     * succeed. In local/debug/testing the safe category code (e.g.
     * AI_INVALID_RESPONSE) is shown so failures are debuggable; in production
     * only the safe, friendly message is exposed.
     */
    private function reAnalyzeAfterProviderMessage(string $category, string $userMessage): string
    {
        if (app()->environment(['local', 'debug', 'testing'])) {
            return "Re-analysis failed: {$category}\n{$userMessage}";
        }

        return "The AI provider was contacted, but the re-analysis failed. {$userMessage}";
    }

    private function resetReAnalyze(): void
    {
        $this->reAnalyzeStage = 'idle';
        $this->reAnalyzeProgress = 0;
        $this->reAnalyzeLabel = '';
        $this->reAnalyzeError = null;
        $this->reAnalyzeErrorCategory = null;
    }

    private function loadAnalysis(): void
    {
        if ($this->analysisId) {
            $this->analysis = Analysis::with('meeting')->find($this->analysisId);
            $this->meeting = Meeting::query()->find($this->analysis?->meeting_id);
        }
    }

    private function loadAnalysisFromMeeting(): void
    {
        if ($this->meetingId) {
            $meeting = Meeting::query()->find($this->meetingId);
            $meeting?->load('analysis');
            $this->meeting = $meeting;
            /** @var Analysis|null $analysis */
            $analysis = $meeting?->analysis;
            $this->analysis = $analysis;
            if ($this->analysis) {
                $this->analysis->load('meeting');
            }
        }
    }

    public function getReAnalyzeErrorDescription(): string
    {
        return match ($this->reAnalyzeErrorCategory) {
            'AI_CONFIGURATION_ERROR' => 'The AI provider is not properly configured. Check that the API key is set in your .env file (OPENAI_API_KEY, LMSTUDIO_API_KEY, or GOOGLE_AI_API_KEY) and the provider URL is correct.',
            'AI_DEPENDENCY_ERROR' => 'The AI service could not be reached. This usually means the local server (LM Studio/Ollama) is not running, or there\'s a network issue. Verify the server is running at the configured URL.',
            'AI_TIMEOUT' => 'The analysis took too long and timed out. Local models can be slow on first run. Try again, or increase the timeout in config/ai.php.',
            'AI_RATE_LIMIT' => 'The AI provider rate limit was exceeded. Wait a moment and retry. For local models, this may indicate the server is busy.',
            'AI_INVALID_RESPONSE' => 'The AI returned a response that could not be parsed. This can happen with some local models that include extra text. The system automatically retries parsing.',
            'PERSISTENCE_ERROR' => 'The analysis completed but could not be saved to the database. This is a system error — your data is safe, but the result was not stored.',
            default => $this->reAnalyzeError ?? 'An unexpected error occurred during analysis. Check the logs for details.',
        };
    }

    public function render()
    {
        return view('livewire.analysis-detail-modal', [
            'analysis' => $this->analysis,
            'meeting' => $this->meeting,
            'activeTab' => $this->activeTab,
            'showModal' => $this->analysisId !== null || $this->meetingId !== null,
            'models' => $this->models,
            'reAnalyzeStage' => $this->reAnalyzeStage,
            'reAnalyzeModel' => $this->reAnalyzeModel,
            'reAnalyzeProgress' => $this->reAnalyzeProgress,
            'reAnalyzeLabel' => $this->reAnalyzeLabel,
            'reAnalyzeError' => $this->reAnalyzeError,
            'reAnalyzeErrorCategory' => $this->reAnalyzeErrorCategory,
        ]);
    }
}
