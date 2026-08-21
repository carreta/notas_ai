<?php

namespace App\AI\Providers;

use App\AI\DTO\AnalysisRequest;
use App\AI\Exceptions\AiConfigurationException;
use App\AI\Exceptions\AiDependencyException;
use App\AI\Exceptions\AiRateLimitException;
use App\AI\Exceptions\AiTimeoutException;

/**
 * Deterministic, network-free fake implementation of {@see AnalysisProvider}
 * for tests. The caller selects which outcome occurs via {@see setOutcome()}.
 *
 * Supported outcomes:
 *  - valid           : returns a valid structured JSON response
 *  - invalid_response: returns malformed content (-> AI_INVALID_RESPONSE)
 *  - config          : throws AiConfigurationException
 *  - dependency      : throws AiDependencyException
 *  - timeout         : throws AiTimeoutException
 *  - rate_limit      : throws AiRateLimitException
 *  - internal        : throws a generic Throwable (-> INTERNAL_ERROR)
 */
final class FakeAnalysisProvider implements AnalysisProvider
{
    private string $outcome = 'valid';

    public function setOutcome(string $outcome): void
    {
        $this->outcome = $outcome;
    }

    public function analyze(AnalysisRequest $request): string
    {
        return match ($this->outcome) {
            'valid' => self::validRawResponse(),
            'invalid_response' => '{"summary": 123}',
            'config' => throw new AiConfigurationException('fake configuration failure'),
            'dependency' => throw new AiDependencyException('fake dependency failure'),
            'timeout' => throw new AiTimeoutException('fake timeout failure'),
            'rate_limit' => throw new AiRateLimitException('fake rate-limit failure'),
            'internal' => throw new \RuntimeException('fake unexpected internal failure'),
            default => throw new \InvalidArgumentException("Unknown fake outcome: {$this->outcome}"),
        };
    }

    /**
     * A valid raw provider response accepted by the FR-004 pipeline.
     */
    public static function validRawResponse(): string
    {
        return (string) json_encode([
            'summary' => 'Fake analysis summary.',
            'decisions' => [
                ['text' => 'Adopt the provider-independent AI port.'],
            ],
            'action_items' => [
                [
                    'task' => 'Send proposal',
                    'owner' => 'Ana',
                    'priority' => 'HIGH',
                    'priority_source' => 'EXPLICIT',
                    'due_date_text' => 'next Friday',
                    'due_date' => '2026-08-28',
                    'due_date_source' => 'INFERRED',
                ],
            ],
            'open_questions' => [
                ['text' => 'Who owns the infrastructure?'],
            ],
        ]);
    }
}
