<?php

namespace App\AI\Metadata;

use DateTimeInterface;

/**
 * Application-owned, provider-independent analysis metadata.
 *
 * Contains only safe scalar diagnostics:
 *  - provider/model identifiers (controlled application values, not secrets);
 *  - schema/prompt version identifier;
 *  - started/completed timestamps and duration;
 *  - optional token usage from the provider (best-effort);
 *  - an optional safe failure category.
 *
 * It must never carry provider SDK objects, API keys, Authorization
 * headers, credentials, or raw provider request/response payloads.
 */
final readonly class AnalysisMetadata
{
    public function __construct(
        public ?string $provider = null,
        public ?string $model = null,
        public string $schemaVersion = 'meeting-analysis-v1',
        public ?DateTimeInterface $startedAt = null,
        public ?DateTimeInterface $completedAt = null,
        public ?int $durationMs = null,
        public ?int $promptTokens = null,
        public ?int $completionTokens = null,
        public ?int $totalTokens = null,
        public ?string $failureCategory = null,
    ) {}
}
