<?php

namespace App\Validation;

use App\Rules\SafeText;
use Illuminate\Validation\Rule;

trait HasMeetingValidation
{
    protected function preValidationRules(): array
    {
        $modelKey = $this->model ?? 'chatgpt-sol';
        $maxChars = $this->models[$modelKey]['max_chars'] ?? 50000;

        return [
            'meeting_text' => ['required', 'string', "max:{$maxChars}",],
            'meeting_title' => ['required', 'string', 'max:255',],
            'model' => ['required', 'string',],
        ];
    }


    protected function rules(array $modelConfig): array
    {
        $modelKey = $this->model ?? 'chatgpt-sol';
        $maxChars = $modelConfig[$modelKey]['max_chars'] ?? 50000;
        $maxTokens = $modelConfig[$modelKey]['max_tokens'] ?? 20000;
        $modelKeys = array_keys($modelConfig);

        return [
            'meeting_text' => [
                'required',
                'string',
                "max:{$maxChars}",
                new SafeText,
                function ($attribute, $value, $fail) use ($maxTokens) {
                    $tokens = (int) ceil(mb_strlen($value) / 4);
                    if ($tokens > $maxTokens) {
                        $fail("The {$attribute} exceeds the token limit ({$maxTokens} tokens). Estimated: {$tokens} tokens.");
                    }
                },
            ],
            'model' => ['required', 'string', Rule::in($modelKeys)],
            'meeting_title' => ['required', 'string', 'max:255', new SafeText,],
            'meeting_date' => ['nullable', 'date', 'before_or_equal:today'],
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
            'meeting_date.before_or_equal' => 'The meeting date cannot be in the future.',
        ];
    }
}
