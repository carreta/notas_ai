<?php

namespace App\AI;

use App\AI\DTO\AnalysisResult;
use App\AI\Exceptions\AiInvalidResponseException;
use App\AI\Normalization\StructuredAnalysisNormalizer;
use App\AI\Parsing\StructuredAnalysisParser;
use App\AI\Validation\StructuredAnalysisValidator;
use Illuminate\Support\Facades\Log;  // TODO: Remove after logging is no longer needed

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
        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisProcessor] process() started', [
            'content_length' => mb_strlen($content),
            'content_preview' => substr($content, 0, 500),
        ]);

        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisProcessor] Calling parser->parse()');
        $parsed = $this->parser->parse($content);
        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisProcessor] Parser completed', [
            'parsed_keys' => array_keys($parsed),
            'summary_present' => isset($parsed['summary']),
            'decisions_count' => isset($parsed['decisions']) ? count($parsed['decisions']) : 0,
            'action_items_count' => isset($parsed['action_items']) ? count($parsed['action_items']) : 0,
            'open_questions_count' => isset($parsed['open_questions']) ? count($parsed['open_questions']) : 0,
        ]);

        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisProcessor] Calling validator->validate()');
        $validated = $this->validator->validate($parsed);
        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisProcessor] Validator completed', [
            'summary_length' => mb_strlen($validated->summary),
            'decisions_count' => count($validated->decisions),
            'action_items_count' => count($validated->actionItems),
            'open_questions_count' => count($validated->openQuestions),
        ]);

        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisProcessor] Calling normalizer->normalize()');
        $normalized = $this->normalizer->normalize($validated);
        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisProcessor] Normalizer completed', [
            'summary_length' => mb_strlen($normalized->summary),
            'decisions_count' => count($normalized->decisions),
            'action_items_count' => count($normalized->actionItems),
            'open_questions_count' => count($normalized->openQuestions),
        ]);

        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisProcessor] process() completed successfully');

        // original: return $this->normalizer->normalize($validated);
        return $normalized;
    }

    /**
     * Safe logging that works in both web and test contexts.
     */
    private function log(string $message, array $context = []): void
    {
        try {
            if (class_exists(Log::class) && app()->bound('log')) {
                Log::info($message, $context);
            }
        } catch (\Throwable) {
            // Ignore logging failures in test contexts
        }
    }
}
