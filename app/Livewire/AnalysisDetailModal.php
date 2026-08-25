<?php

namespace App\Livewire;

use App\AI\AnalysisOrchestrator;
use App\AI\AnalysisOutcome;
use App\Models\Analysis;
use App\Models\Meeting;
use Illuminate\Support\Facades\Log;
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
    }

    public function closeModal(): void
    {
        $this->analysisId = null;
        $this->meetingId = null;
        $this->analysis = null;
        $this->meeting = null;
        $this->activeTab = 'analysis';
        $this->resetReAnalyze();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
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
