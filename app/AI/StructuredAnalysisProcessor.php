<?php

namespace App\AI;

use App\AI\DTO\AnalysisResult;
use App\AI\Exceptions\AiInvalidResponseException;
use App\AI\Normalization\StructuredAnalysisNormalizer;
use App\AI\Parsing\StructuredAnalysisParser;
use App\AI\Validation\StructuredAnalysisValidator;

/**
 * Application-owned orchestration of the FR-004 structured-response pipeline.
 *
 * Trust boundary:
 *  - Input is raw, UNTRUSTED provider content.
 *  - On success the returned AnalysisResult is trusted application-owned data.
 *  - On any parse/validation failure an AiInvalidResponseException is thrown
 *    (errorCategory = AI_INVALID_RESPONSE) and NO trusted DTO is returned.
 *
 * This component performs no persistence and references no provider SDK types.
 */
final class StructuredAnalysisProcessor
{
    public function __construct(
        private readonly StructuredAnalysisParser $parser = new StructuredAnalysisParser,
        private readonly StructuredAnalysisValidator $validator = new StructuredAnalysisValidator,
        private readonly StructuredAnalysisNormalizer $normalizer = new StructuredAnalysisNormalizer,
    ) {}

    /**
     * Parse, validate, and normalize a raw provider response.
     *
     * @throws AiInvalidResponseException
     */
    public function process(string $content): AnalysisResult
    {
        $parsed = $this->parser->parse($content);
        $validated = $this->validator->validate($parsed);

        return $this->normalizer->normalize($validated);
    }
}
