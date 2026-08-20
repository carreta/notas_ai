<?php

namespace App\AI\Exceptions;

/**
 * The AI provider could not be reached, or returned a server/transport error
 * (network failure, DNS, 5xx). Maps to AI_DEPENDENCY_ERROR.
 */
final class AiDependencyException extends AiProviderException
{
    public function errorCategory(): string
    {
        return 'AI_DEPENDENCY_ERROR';
    }
}
