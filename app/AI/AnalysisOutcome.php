<?php

namespace App\AI;

use App\Models\Analysis;

/**
 * Result of a single synchronous analysis run.
 *
 * On success {@see $success} is true, {@see $analysis} holds the persisted
 * (trusted) Analysis, and {@see $category} is null. On failure {@see $success}
 * is false, {@see $analysis} is null, {@see $category} carries the safe
 * application failure category, and {@see $userMessage} holds a safe,
 * user-facing message.
 */
final readonly class AnalysisOutcome
{
    public function __construct(
        public bool $success,
        public ?Analysis $analysis,
        public ?string $category,
        public string $userMessage,
    ) {}
}
