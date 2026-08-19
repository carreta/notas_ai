<?php

namespace App\Livewire;

use App\Models\Meeting;
use App\Validation\HasMeetingValidation;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AnalyzeForm extends Component
{
    use HasMeetingValidation;

    public string $meeting_text = '';

    public string $model = 'chatgpt-sol';

    public ?string $meeting_title = null;

    public ?string $meeting_date = null;

    public array $models = [];

    public string $stage = 'idle';

    public int $maxChars = 50000;

    public int $maxTokens = 20000;

    public array $errorDescriptions = [
        'meeting_text' => 'The transcript must not exceed the character/token limit for the selected model and must not contain unsafe content.',

        'meeting_title' => 'A title is required to identify this analysis. Maximum 255 characters.',

        'meeting_date' => 'The date must be a valid date and cannot be in the future.',

        'model' => 'Please select a valid model from the dropdown.',
    ];

    public function mount(array $models): void
    {
        $this->models = $models;

        $this->model = array_key_first($models) ?? 'chatgpt-sol';

        $this->syncModelLimits();
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
        $this->setStage('validating');

        // Run the full validation pipeline
        $this->dispatch('validation-started');
    }

    public function validation(): void
    {
        // precheck, required fields and character limits.
        try {
            $this->validate(
                $this->preValidationRules(),
                $this->messages()
            );
        } catch (ValidationException $exception) {
            $this->setStage('idle');

            throw $exception;
        }

        // token analysis
        try {
            $this->validateTokenLimit();
        } catch (ValidationException $exception) {
            $this->setStage('idle');

            throw $exception;
        }

        // validation
        try {
            $this->validate(
                $this->rules($this->models),
                $this->messages()
            );
        } catch (ValidationException $exception) {
            // Something such as SafeText, token validation, date validation, etc. failed.
            // Cancel the processing stage and return to idle.
            $this->setStage('idle');

            throw $exception;
        }

        // Validation succeeded.

        // Keep the component in "validating" for the moment.
        // The Blade/Alpine listener waits 1 second before calling $wire.save().
        $this->dispatch('validation-passed');
    }

    public function save(): void
    {
        $this->setStage('saving'); // 25%

        try {
            Meeting::create([
                'title' => $this->meeting_title,
                'raw_text' => $this->meeting_text,
                'status' => 'DRAFT',
                'meeting_time' => $this->meeting_date,
            ]);
        } catch (\Throwable $exception) {
            $this->addError(
                'meeting_text',
                'The meeting could not be saved. Please try again.'
            );

            $this->setStage('idle');

            report($exception);

            return;
        }

        // Persistence succeeded.
        // For now, intentionally stop here.
        // Later this can become an asyncronous process that calls the AI and stores the results.:
        // $this->analyze();
        $this->dispatch('save-passed');
    }

    public function analyze(): void
    {
        $this->setStage('analyzing'); // 50%

        // TODO:
        // Perform the analysis here.
        //
        // If this becomes a long-running operation,
        // move it to a Laravel queued Job rather than
        // blocking the Livewire request.

        // Currently stops here. Later this can become an asyncronous process that stores the results:
        // $this->store();
    }

    public function store(): void
    {
        $this->setStage('storing'); // 75%

        // TODO:
        // Store the analysis result here.

        $this->complete();
    }

    public function complete(): void
    {
        $this->setStage('completed'); // 100%
    }

    public function render()
    {
        return view('livewire.analyze-form', [
            'models' => $this->models,
            'characterCount' => $this->characterCount,
            'maxChars' => $this->maxChars,
            'maxTokens' => $this->maxTokens,
            'errorDescriptions' => $this->errorDescriptions,
        ]);
    }
}
