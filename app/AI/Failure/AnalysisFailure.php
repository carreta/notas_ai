<?php

namespace App\AI\Failure;

/**
 * Provider-independent, safe description of a failed analysis.
 *
 * It carries only a controlled category and a user-safe message. It must
 * never embed provider exception text, SQLSTATE, stack traces, or secrets.
 */
final readonly class AnalysisFailure
{
    public function __construct(
        public string $category,
        public string $userMessage,
    ) {}
}
