<?php

namespace App\Livewire;

use App\AI\AnalysisOrchestrator;
use App\AI\AnalysisOutcome;
use App\Models\Analysis;
use App\Models\Meeting;
use Livewire\Attributes\On;
use Livewire\Component;

class AnalysisDetailModal extends Component
{
    public ?string $analysisId = null;

    public ?string $meetingId = null;

    public ?Analysis $analysis = null;

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
        $this->activeTab = 'analysis';
        $this->resetReAnalyze();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function reAnalyze(): void
    {
        if (! $this->analysis || ! $this->analysis->meeting) {
            return;
        }

        $this->resetReAnalyze();
        $this->reAnalyzeStage = 'analyzing';
        $this->reAnalyzeProgress = 10;
        $this->reAnalyzeLabel = 'Analyzing transcript...';
    }

    public function executeReAnalyze(): void
    {
        if (!$this->analysis || !$this->analysis->meeting) {
            return;
        }

        $this->reAnalyzeProgress = 30;

        $selectedModelConfig = $this->models[$this->reAnalyzeModel] ?? [];
        $provider = $selectedModelConfig['provider'] ?? config('ai.provider', 'openai');
        $modelKey = $this->reAnalyzeModel;

        try {
            /** @var AnalysisOutcome $outcome */
            $outcome = app(AnalysisOrchestrator::class)->reAnalyze($this->analysis, $provider, $modelKey);
        } catch (\Throwable $exception) {
            report($exception);

            $this->handleReAnalyzeFailure('The analysis could not be completed. Please try again later.', 'INTERNAL_ERROR');

            return;
        }

        if (!$outcome->success) {
            $this->handleReAnalyzeFailure($outcome->userMessage, $outcome->category);

            return;
        }

        // Reload the analysis to get fresh data
        $this->loadAnalysis();
        $this->reAnalyzeStage = 'completed';
        $this->reAnalyzeProgress = 100;
        $this->reAnalyzeLabel = 'Re-analysis completed';

        // TODO: Update history table status when re-analysis completes (requires Livewire component for history table)
    }

    public function retryReAnalyze(): void
    {
        $this->reAnalyze();
    }

    private function handleReAnalyzeFailure(string $error, string $category): void
    {
        // Clear the result field so the UI shows "No analysis data available"
        // but keep the analysis row and logs intact
        if ($this->analysis) {
            $this->analysis->setAttribute('result', []);
            $this->analysis->save();
            $this->analysis->refresh();
        }

        $this->reAnalyzeStage = 'error';
        $this->reAnalyzeProgress = 0;
        $this->reAnalyzeLabel = 'Re-analysis failed';
        $this->reAnalyzeError = $error;
        $this->reAnalyzeErrorCategory = $category;

        // TODO: Update history table status when re-analysis fails (requires Livewire component for history table)
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
        }
    }

    private function loadAnalysisFromMeeting(): void
    {
        if ($this->meetingId) {
            $meeting = Meeting::with('analysis')->find($this->meetingId);
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
