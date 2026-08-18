<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SafeText implements Rule
{
    private const PATTERNS = [
        // XSS patterns
        '/<script\b[^>]*>/i',
        '/javascript:/i',
        '/on\w+\s*=/i',
        '/eval\s*\(/i',
        '/expression\s*\(/i',
        '/vbscript:/i',
        '/data:text\/html/i',

        // SQL injection patterns
        '/union\s+select/i',
        '/drop\s+table/i',
        '/insert\s+into/i',
        '/delete\s+from/i',
        '/;\s*--/',
        '/\'\s*;\s*--/',

        // Path traversal
        '/\.\.\//',
        '/\.\.\\\\/',
    ];

    public function passes($attribute, $value): bool
    {
        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $value)) {
                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        return 'The :attribute contains potentially malicious content.';
    }
}
