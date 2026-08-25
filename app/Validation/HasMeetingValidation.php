<?php

namespace App\Validation;

use App\Rules\SafeText;
use App\Support\TokenCounter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait HasMeetingValidation
{
    protected function preValidationRules(): array
    {
        $modelKey = $this->model;
        $maxChars = $this->models[$modelKey]['max_chars'] ?? 50000;

        return [
            'meeting_text' => ['required', 'string', "max:{$maxChars}"],
            'meeting_title' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string'],
        ];
    }

    protected function validateTokenLimit(): void
    {
        $modelKey = $this->model;

        $config = $this->models[$modelKey] ?? [];
        $maxTokens = $config['max_tokens'] ?? 20000;
        $encoding = $config['encoding'] ?? 'cl100k_base';

        $tokenCounter = app(TokenCounter::class);
        $tokens = $tokenCounter->count($this->meeting_text, $encoding);
        if ($tokens > $maxTokens) {
            throw ValidationException::withMessages([
                'meeting_text' => 'The meeting text exceeds the token limit '.
                    "({$maxTokens} tokens). Current: {$tokens} tokens.",
            ]);
        }
    }

    protected function rules(array $modelConfig): array
    {
        $modelKey = $this->model;

        $config = $modelConfig[$modelKey] ?? [];
        $maxChars = $config['max_chars'] ?? 50000;
        $maxTokens = $config['max_tokens'] ?? 20000;
        $encoding = $config['encoding'] ?? 'cl100k_base';
        $modelKeys = array_keys($modelConfig);

        return [
            'meeting_text' => ['required', 'string', new SafeText],
            'model' => ['required', 'string', Rule::in($modelKeys)],
            'meeting_title' => ['required', 'string', 'max:255', new SafeText],
            'meeting_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    protected function messages(): array
    {
        return [
            'meeting_text.required' => 'The transcript is required.',
            'meeting_text.max' => 'The transcript exceeds the maximum allowed characters for the selected model.',
            'meeting_title.required' => 'The meeting title is required.',
            'meeting_title.max' => 'The meeting title cannot exceed 255 characters.',
            'model.required' => 'Please select a model.',
            'model.in' => 'The selected model is invalid.',
            'meeting_date.date' => 'The meeting date must be a valid date.',
            'meeting_date.required' => 'Meeting date is required.',
            'meeting_date.before_or_equal' => 'The meeting date cannot be in the future.',
        ];
    }
}
