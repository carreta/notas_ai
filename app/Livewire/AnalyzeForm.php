<?php

namespace App\Livewire;

use App\AI\AnalysisOrchestrator;
use App\AI\AnalysisOutcome;
use App\Models\Meeting;
use App\Validation\HasMeetingValidation;
use Illuminate\Support\Facades\Log; // TODO: Remove after logging is no longer needed
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AnalyzeForm extends Component
{
    use HasMeetingValidation;

    public string $meeting_text = '';

    public string $model = 'chatgpt-sol';

    public ?string $meeting_title = null;

    public ?string $meeting_date = null;

    public string $maxDate = '';

    public array $models = [];

    public string $stage = 'idle';

    /**
     * Id of the Meeting created in {@see save()}; used by {@see analyze()} to
     * locate the meeting across the Livewire request boundary.
     */
    public ?string $meetingId = null;

    public int $maxChars = 50000;

    public int $maxTokens = 20000;

    public int $characterCount = 0;

    public array $errorDescriptions = [
        'meeting_text' => 'The transcript must not exceed the character/token limit for the selected model and must not contain unsafe content.',

        'meeting_title' => 'A title is required to identify this analysis. Maximum 255 characters.',

        'meeting_date' => 'The date must be a valid date and cannot be in the future.',

        'model' => 'Please select a valid model from the dropdown.',
    ];

    /**
     * Track whether the last error was an AI analysis error (vs validation error)
     * so we can show a retry button instead of the generic validation description.
     */
    public bool $lastErrorWasAi = false;

    /**
     * Store the specific AI error category for detailed error descriptions.
     */
    public ?string $lastErrorCategory = null;

    /**
     * Id of the Analysis created in {@see analyze()}; used by {@see complete()} to
     * redirect to the history page with the analysis details.
     */
    public ?string $analysisId = null;

    public function mount(array $models): void
    {
        $this->models = $models;

        $this->model = array_key_first($models) ?? 'chatgpt-sol';

        $this->maxDate = today()->toDateString();

        $this->syncModelLimits();
    }

    /**
     * UI boundary helper: a date is not selectable when it is after today
     * (the maximum allowed meeting date). Mirrors the front-end calendar
     * disabling logic and the server-side `before_or_equal:today` rule.
     */
    public function isDateDisabled(string $iso): bool
    {
        return $iso > today()->toDateString();
    }

    public function getCharacterCountProperty(): int
    {
        return mb_strlen($this->meeting_text);
    }

    public function updatedModel(): void
    {
        $this->syncModelLimits();
    }

    private function syncModelLimits(): void
    {
        $this->maxChars = $this->models[$this->model]['max_chars'] ?? 50000;
        $this->maxTokens = $this->models[$this->model]['max_tokens'] ?? 20000;
    }

    private function setStage(string $stage): void
    {
        $this->stage = $stage;
    }

    // Submit flow:
    // 1. Basic pre-validation: required fields, maximum character limits
    // 2. Full validation: SafeText, token limits, date, model, etc. If a validation error occurs, the stage is reset to idle and the error is displayed.
    // 3. Successful validation: stage remains at validating, Alpine waits one second, Alpine calls save()
    public function submit(): void
    {
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalyzeForm] submit() called', [
            'meeting_text_length' => mb_strlen($this->meeting_text),
            'model' => $this->model,
            'meeting_title' => $this->meeting_title,
            'meeting_date' => $this->meeting_date,
            'stage' => $this->stage,
        ]);

        $this->setStage('validating');

        // Run the full validation pipeline
        $this->dispatch('validation-started');
    }

    public function validation(): void
    {
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalyzeForm] validation() started', [
            'meeting_text_length' => mb_strlen($this->meeting_text),
            'model' => $this->model,
            'stage' => $this->stage,
        ]);

        // An empty meeting date is treated as "no date" (optional field),
        // not as a validation failure. Normalize empty string to null so the
        // nullable rule accepts it and meeting_time is persisted as null.
        if ($this->meeting_date === '') {
            $this->meeting_date = null;
        }

        // precheck, required fields and character limits.
        try {
            $this->validate(
                $this->preValidationRules(),
                $this->messages()
            );
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalyzeForm] pre-validation passed');
        } catch (ValidationException $exception) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::warning('[TEMP][AnalyzeForm] pre-validation failed', [
                'errors' => $exception->errors(),
            ]);
            $this->setStage('idle');

            throw $exception;
        }

        // token analysis
        try {
            $this->validateTokenLimit();
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalyzeForm] token limit validation passed');
        } catch (ValidationException $exception) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::warning('[TEMP][AnalyzeForm] token limit validation failed', [
                'errors' => $exception->errors(),
            ]);
            $this->setStage('idle');

            throw $exception;
        }

        // validation
        try {
            $this->validate(
                $this->rules($this->models),
                $this->messages()
            );
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalyzeForm] full validation passed');
        } catch (ValidationException $exception) {
            // Something such as SafeText, token validation, date validation, etc. failed.
            // Cancel the processing stage and return to idle.
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::warning('[TEMP][AnalyzeForm] full validation failed', [
                'errors' => $exception->errors(),
            ]);
            $this->setStage('idle');

            throw $exception;
        }

        // Validation succeeded.

        // Keep the component in "validating" for the moment.
        // The Blade/Alpine listener waits 1 second before calling $wire.save().
        $this->setStage('saving'); // 25%
        $this->dispatch('validation-passed');
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalyzeForm] validation() completed successfully, dispatched validation-passed');
    }

    public function save(): void
    {
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalyzeForm] save() started', [
            'meeting_title' => $this->meeting_title,
            'meeting_date' => $this->meeting_date,
            'meeting_text_length' => mb_strlen($this->meeting_text),
        ]);

        try {
            $meeting = Meeting::create([
                'title' => $this->meeting_title,
                'raw_text' => $this->meeting_text,
                'status' => 'DRAFT',
                'meeting_time' => $this->meeting_date,
            ]);

            $this->meetingId = $meeting->id;
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalyzeForm] Meeting created successfully', [
                'meeting_id' => $meeting->id,
            ]);
        } catch (\Throwable $exception) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][AnalyzeForm] Failed to create meeting', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $this->addError(
                'meeting_text',
                'The meeting could not be saved. Please try again.'
            );

            $this->setStage('idle');

            report($exception);

            return;
        }

        // Persistence succeeded. The Alpine listener waits briefly, then calls
        // $wire.analyze() which performs the synchronous analysis.
        $this->setStage('analyzing'); // 50%
        $this->dispatch('save-passed');
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalyzeForm] save() completed successfully, dispatched save-passed');
    }

    public function analyze(): void
    {
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalyzeForm] analyze() started', [
            'meeting_id' => $this->meetingId,
            'model' => $this->model,
            'stage' => $this->stage,
        ]);

        $meeting = Meeting::find($this->meetingId);

        if ($meeting === null) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][AnalyzeForm] Meeting not found', [
                'meeting_id' => $this->meetingId,
            ]);
            $this->addError('meeting_text', 'The meeting could not be found.');
            $this->setStage('idle');

            return;
        }

        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalyzeForm] Meeting found', [
            'meeting_id' => $meeting->id,
            'meeting_status' => $meeting->status,
            'meeting_title' => $meeting->title,
        ]);

        // Resolve model/provider from the component's current selection
        $selectedModelConfig = $this->models[$this->model] ?? [];
        $provider = $selectedModelConfig['provider'] ?? config('ai.provider', 'openai');
        $modelKey = $this->model;

        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalyzeForm] Resolved provider and model', [
            'provider' => $provider,
            'model_key' => $modelKey,
            'model_config' => $selectedModelConfig,
            'ai_provider_config' => config('ai.provider'),
        ]);

        try {
            /** @var AnalysisOutcome $outcome */
            $outcome = app(AnalysisOrchestrator::class)->analyze($meeting, $provider, $modelKey);
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::info('[TEMP][AnalyzeForm] AnalysisOrchestrator completed', [
                'success' => $outcome->success,
                'category' => $outcome->category ?? null,
                'user_message' => $outcome->userMessage,
                'analysis_id' => $outcome->analysis?->id,
            ]);
        } catch (\Throwable $exception) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::error('[TEMP][AnalyzeForm] AnalysisOrchestrator threw exception', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            report($exception);

            $this->addError(
                'meeting_text',
                'The analysis could not be completed. Please try again later.'
            );
            $this->lastErrorWasAi = true;
            $this->lastErrorCategory = 'INTERNAL_ERROR';
            $this->setStage('idle');

            return;
        }

        if (! $outcome->success) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            Log::warning('[TEMP][AnalyzeForm] Analysis failed', [
                'category' => $outcome->category,
                'user_message' => $outcome->userMessage,
            ]);
            $this->addError('meeting_text', $outcome->userMessage);
            $this->lastErrorWasAi = true;
            $this->lastErrorCategory = $outcome->category;
            $this->setStage('idle');

            return;
        }

        // Store the analysis ID for the complete() redirect
        $this->analysisId = $outcome->analysis?->id;
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalyzeForm] Analysis successful', [
            'analysis_id' => $this->analysisId,
        ]);

        $this->setStage('storing'); // 75%
        $this->dispatch('analyze-passed');
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalyzeForm] analyze() completed successfully, dispatched analyze-passed');
    }

    public function store(): void
    {
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalyzeForm] store() called', [
            'analysis_id' => $this->analysisId,
        ]);

        $this->setStage('completed'); // 100%
        $this->dispatch('store-passed');
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalyzeForm] store() completed, dispatched store-passed');
    }

    public function complete(): void
    {
        // TODO: Revert once testing is sufficient - remove temporary logging
        Log::info('[TEMP][AnalyzeForm] complete() called', [
            'analysis_id' => $this->analysisId,
        ]);
        if ($this->analysisId) {
            session()->flash('analysis_id', $this->analysisId);
            redirect()->route('history');

            return;
        }

        redirect()->route('history');
    }

    public function retryAnalyze(): void
    {
        $this->setStage('analyzing'); // 50%
        $this->resetErrorBag();
        $this->lastErrorWasAi = false;
        $this->lastErrorCategory = null;
        $this->dispatch('retry');
    }

    /**
     * Get a detailed, user-friendly description for the last AI error category.
     */
    public function getAiErrorDescription(): string
    {
        return match ($this->lastErrorCategory) {
            'AI_CONFIGURATION_ERROR' => 'The AI provider is not properly configured. Check that the API key is set in your .env file (OPENAI_API_KEY, LMSTUDIO_API_KEY, or GOOGLE_AI_API_KEY) and the provider URL is correct.',
            'AI_DEPENDENCY_ERROR' => 'The AI service could not be reached. This usually means the local server (LM Studio/Ollama) is not running, or there\'s a network issue. Verify the server is running at the configured URL.',
            'AI_TIMEOUT' => 'The analysis took too long and timed out. Local models can be slow on first run. Try again, or increase the timeout in config/ai.php.',
            'AI_RATE_LIMIT' => 'The AI provider rate limit was exceeded. Wait a moment and retry. For local models, this may indicate the server is busy.',
            'AI_INVALID_RESPONSE' => 'The AI returned a response that could not be parsed. This can happen with some local models that include extra text. The system automatically retries parsing.',
            'PERSISTENCE_ERROR' => 'The analysis completed but could not be saved to the database. This is a system error — your data is safe, but the result was not stored.',
            default => 'An unexpected error occurred during analysis. Check the logs for details.',
        };
    }

    /**
     * Fallback for test contexts where $wire is not available.
     */
    public function getAiErrorDescriptionFallback(): string
    {
        return match ($this->lastErrorCategory) {
            'AI_CONFIGURATION_ERROR' => 'The AI provider is not properly configured. Check that the API key is set in your .env file (OPENAI_API_KEY, LMSTUDIO_API_KEY, or GOOGLE_AI_API_KEY) and the provider URL is correct.',
            'AI_DEPENDENCY_ERROR' => 'The AI service could not be reached. This usually means the local server (LM Studio/Ollama) is not running, or there\'s a network issue. Verify the server is running at the configured URL.',
            'AI_TIMEOUT' => 'The analysis took too long and timed out. Local models can be slow on first run. Try again, or increase the timeout in config/ai.php.',
            'AI_RATE_LIMIT' => 'The AI provider rate limit was exceeded. Wait a moment and retry. For local models, this may indicate the server is busy.',
            'AI_INVALID_RESPONSE' => 'The AI returned a response that could not be parsed. This can happen with some local models that include extra text. The system automatically retries parsing.',
            'PERSISTENCE_ERROR' => 'The analysis completed but could not be saved to the database. This is a system error — your data is safe, but the result was not stored.',
            default => 'An unexpected error occurred during analysis. Check the logs for details.',
        };
    }

    public function render()
    {
        return view('livewire.analyze-form', [
            'models' => $this->models,
            'characterCount' => $this->characterCount,
            'maxChars' => $this->maxChars,
            'maxTokens' => $this->maxTokens,
            'maxDate' => $this->maxDate,
            'errorDescriptions' => $this->errorDescriptions,
            'aiErrorDescription' => $this->lastErrorWasAi && $this->lastErrorCategory
                ? $this->getAiErrorDescription()
                : null,
        ]);
    }
}
