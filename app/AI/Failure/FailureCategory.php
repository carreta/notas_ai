<?php

namespace App\AI\Failure;

/**
 * Application-owned, safe failure categories for AI analysis.
 *
 * These are the ONLY categories a caller may observe. They are intentionally
 * decoupled from provider SDK error types and never contain raw provider
 * messages, stack traces, or secrets.
 */
final class FailureCategory
{
    public const string AI_CONFIGURATION_ERROR = 'AI_CONFIGURATION_ERROR';

    public const string AI_DEPENDENCY_ERROR = 'AI_DEPENDENCY_ERROR';

    public const string AI_TIMEOUT = 'AI_TIMEOUT';

    public const string AI_RATE_LIMIT = 'AI_RATE_LIMIT';

    public const string AI_INVALID_RESPONSE = 'AI_INVALID_RESPONSE';

    public const string PERSISTENCE_ERROR = 'PERSISTENCE_ERROR';

    public const string INTERNAL_ERROR = 'INTERNAL_ERROR';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::AI_CONFIGURATION_ERROR,
            self::AI_DEPENDENCY_ERROR,
            self::AI_TIMEOUT,
            self::AI_RATE_LIMIT,
            self::AI_INVALID_RESPONSE,
            self::PERSISTENCE_ERROR,
            self::INTERNAL_ERROR,
        ];
    }
}
