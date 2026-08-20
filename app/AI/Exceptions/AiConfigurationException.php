<?php

namespace App\AI\Exceptions;

/**
 * The AI provider is misconfigured (missing/invalid credentials, wrong endpoint,
 * unsupported model). Maps to AI_CONFIGURATION_ERROR.
 */
final class AiConfigurationException extends AiProviderException
{
    public function errorCategory(): string
    {
        return 'AI_CONFIGURATION_ERROR';
    }
}
