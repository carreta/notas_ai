<?php

namespace App\AI\Exceptions;

/**
 * The AI provider request exceeded the approved finite timeout (TD-003: 120s).
 * Maps to AI_TIMEOUT.
 */
final class AiTimeoutException extends AiProviderException
{
    public function errorCategory(): string
    {
        return 'AI_TIMEOUT';
    }
}
