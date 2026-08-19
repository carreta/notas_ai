<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMeetingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string'],

            'raw_text' => [
                'required',
                'string',

                // Custom validation rule to check word count (TD-001)
                function (string $attribute, mixed $value, Closure $fail): void {
                    preg_match_all('/[\p{L}\p{M}]+/u', $value, $matches);

                    if (count($matches[0]) > 13500) {
                        $fail('The meeting transcript must not exceed 13,500 words.');
                    }
                },
            ],

            'meeting_time' => ['nullable', 'date'],
        ];
    }
}
