<?php

namespace App\AI\Parsing;

use App\AI\Exceptions\AiInvalidResponseException;
use Illuminate\Support\Facades\Log; // remove after logging is no longer needed
use JsonException;

final class StructuredAnalysisParser
{
    /**
     * Parse a raw provider response into an untrusted associative array.
     *
     * This step only confirms content exists and that the root JSON value is an
     * object. It does NOT validate the FR-004 contract, nested structures, or
     * normalize any values. The returned array is still untrusted data.
     *
     * Handles models that include reasoning text (e.g., qwen3, deepseek-r1)
     * by extracting the first valid JSON object from the response.
     *
     * @return array<string, mixed>
     *
     * @throws AiInvalidResponseException
     */
    public function parse(string $content): array
    {
        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisParser] parse() started', [
            'content_length' => mb_strlen($content),
            'content_preview' => substr($content, 0, 300),
        ]);

        // Strip UTF-8 BOM if present (some models include it)
        $content = ltrim($content, "\xEF\xBB\xBF");

        // Strip markdown code fences if model wraps JSON in ```json ... ```
        $content = preg_replace('/^```(?:json)?\s*/', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);

        // Remove any non-printable control characters except newlines/tabs
        $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);

        $content = trim($content);

        if ($content === '') {
            // TODO: Revert once testing is sufficient - remove temporary logging
            $this->log('[TEMP][StructuredAnalysisParser] Content is empty after cleaning');
            throw new AiInvalidResponseException;
        }

        // First, try to parse as-is (clean JSON response)
        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisParser] Trying to parse as clean JSON');
        $decoded = $this->tryParseJson($content);
        if ($decoded !== null) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            $this->log('[TEMP][StructuredAnalysisParser] Clean JSON parse successful');

            return $decoded;
        }

        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisParser] Clean JSON parse failed, trying to extract JSON object');

        // If that fails, try to extract a JSON object from the content
        // This handles models that include reasoning text before/after JSON
        $extracted = $this->extractJsonObject($content);
        if ($extracted !== null) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            $this->log('[TEMP][StructuredAnalysisParser] JSON object extracted, attempting parse');
            $decoded = $this->tryParseJson($extracted);
            if ($decoded !== null) {
                // TODO: Revert once testing is sufficient - remove temporary logging
                $this->log('[TEMP][StructuredAnalysisParser] Extracted JSON parse successful');

                return $decoded;
            }
            // TODO: Remove once testing is sufficient - remove temporary logging
            $this->log('[TEMP][StructuredAnalysisParser] Extracted JSON parse failed');
        } else {
            $this->log('[TEMP][StructuredAnalysisParser] No JSON object found in content');
            // TODO: End of remove
        }

        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisParser] All parsing attempts failed');
        throw new AiInvalidResponseException;
    }

    /**
     * Attempt to parse a string as JSON object.
     *
     * @return array<string, mixed>|null
     */
    private function tryParseJson(string $json): ?array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            $this->log('[TEMP][StructuredAnalysisParser] tryParseJson: JSON decode failed', [
                'message' => $e->getMessage(),
                'json_preview' => substr($json, 0, 200),
            ]);

            return null;
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            // TODO: Revert once testing is sufficient - remove temporary logging
            $this->log('[TEMP][StructuredAnalysisParser] tryParseJson: decoded is not an object', [
                'type' => gettype($decoded),
                'is_list' => is_array($decoded) ? array_is_list($decoded) : 'N/A',
            ]);

            return null;
        }

        return $decoded;
    }

    /**
     * Extract the first valid JSON object from text.
     *
     * Uses brace matching to find complete {...} blocks and tests each
     * for valid JSON. Returns the first one that parses as an object.
     */
    private function extractJsonObject(string $text): ?string
    {
        $length = strlen($text);
        $braceCount = 0;
        $start = -1;

        for ($i = 0; $i < $length; $i++) {
            $char = $text[$i];

            if ($char === '{') {
                if ($braceCount === 0) {
                    $start = $i;
                }
                $braceCount++;
            } elseif ($char === '}') {
                if ($braceCount > 0) {
                    $braceCount--;
                    if ($braceCount === 0 && $start !== -1) {
                        // Found a complete {...} block
                        $candidate = substr($text, $start, $i - $start + 1);
                        if ($this->tryParseJson($candidate) !== null) {
                            // TODO: Revert once testing is sufficient - remove temporary logging
                            $this->log('[TEMP][StructuredAnalysisParser] extractJsonObject: found valid JSON object', [
                                'start' => $start,
                                'end' => $i,
                                'length' => $i - $start + 1,
                            ]);

                            return $candidate;
                        }
                        // Not valid JSON, continue searching
                        $start = -1;
                    }
                }
            }
        }

        // TODO: Revert once testing is sufficient - remove temporary logging
        $this->log('[TEMP][StructuredAnalysisParser] extractJsonObject: no valid JSON object found');

        return null;
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
