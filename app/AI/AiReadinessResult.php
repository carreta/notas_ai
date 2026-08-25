<?php

namespace App\AI;

/**
 * Read-only result of an AI provider readiness check (FR-011).
 *
 * Never carries secret values; {@see $issues} references only the
 * environment variable NAMES a user must set, never their contents.
 */
final readonly class AiReadinessResult
{
    /**
     * @param  string[]  $issues  Non-secret, human-readable issue descriptions.
     */
    public function __construct(
        public string $provider,
        public bool $ready,
        public string $driver,
        public array $issues = [],
    ) {}
}
