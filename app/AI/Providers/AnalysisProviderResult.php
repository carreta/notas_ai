<?php

namespace App\AI\Providers;

/**
 * Result from an AI provider analysis call.
 *
 * Carries the raw analysis content plus optional token usage metadata.
 * Token fields are nullable because not all providers return usage,
 * and extraction should never break the primary analysis flow.
 */
final readonly class AnalysisProviderResult
{
    public function __construct(
        public string $content,
        public ?int $promptTokens = null,
        public ?int $completionTokens = null,
        public ?int $totalTokens = null,
    ) {}
}
