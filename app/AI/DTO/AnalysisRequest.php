<?php

namespace App\AI\DTO;

/**
 * Application-owned, provider-independent analysis request.
 *
 * It contains only application-controlled input: the meeting transcript, an
 * optional reference date, the resolved model/config key, and the schema
 * version. It must never carry provider SDK objects, API keys, credentials,
 * or provider-specific fields.
 */
final readonly class AnalysisRequest
{
    public function __construct(
        public string $content,
        public ?string $referenceDate = null,
        public ?string $model = null,
        public string $schemaVersion = 'meeting-analysis-v1',
    ) {}
}
