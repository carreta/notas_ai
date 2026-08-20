<?php

namespace App\AI\Exceptions;

/**
 * The AI provider rejected the request due to rate limiting (HTTP 429).
 * Maps to AI_RATE_LIMIT.
 */
final class AiRateLimitException extends AiProviderException
{
    public function errorCategory(): string
    {
        return 'AI_RATE_LIMIT';
    }
}
