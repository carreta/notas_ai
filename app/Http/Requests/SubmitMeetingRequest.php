<?php

namespace App\Http\Requests;

use App\Rules\SafeText;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $modelKeys = array_keys(config('models', []));

        // Get the model from input to determine dynamic max_chars
        $modelKey = $this->input('model', 'chatgpt-sol');
        $maxChars = config("models.{$modelKey}.max_chars", 50000);

        return [
            'meeting-title' => ['required', 'string', 'max:255'],
            'meeting-date' => ['required', 'date', 'before_or_equal:today'],

            'transcript' => [
                'required',
                'string',
                "max:{$maxChars}",
                new SafeText,

                // Custom validation rule to check word count (TD-001)
                function (string $attribute, mixed $value, Closure $fail): void {
                    // Split the transcript by whitespace to count the total number of words.
                    $words = preg_split(
                        '/\s+/u',
                        trim($value),
                        -1,
                        PREG_SPLIT_NO_EMPTY
                    );

                    if (count($words) > 13500) {
                        $fail('The meeting transcript must not exceed 13,500 words.');
                    }
                },
            ],

            'model' => ['required', 'string', Rule::in($modelKeys)],
        ];
    }

    public function messages(): array
    {
        return [
            'transcript.max' => 'The transcript exceeds the maximum allowed characters for the selected model.',
            'model.in' => 'The selected model is invalid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'meeting-title' => 'meeting title',
            'meeting-date' => 'meeting date',
            'transcript' => 'transcript',
            'model' => 'model',
        ];
    }
}
