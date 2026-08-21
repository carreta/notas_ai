<?php

namespace App\AI\Parsing;

use App\AI\Exceptions\AiInvalidResponseException;
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
        echo 'console.log(' . json_encode($content) . ');';
        // Strip UTF-8 BOM if present (some models include it)
        $content = ltrim($content, "\xEF\xBB\xBF");

        // Strip markdown code fences if model wraps JSON in ```json ... ```
        $content = preg_replace('/^```(?:json)?\s*/', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);

        // Remove any non-printable control characters except newlines/tabs
        $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);

        $content = trim($content);

        echo 'console.log(' . json_encode($content) . ');';

        if ($content === '') {
            throw new AiInvalidResponseException;
        }

        // First, try to parse as-is (clean JSON response)
        $decoded = $this->tryParseJson($content);
        if ($decoded !== null) {
            return $decoded;
        }

        // If that fails, try to extract a JSON object from the content
        // This handles models that include reasoning text before/after JSON
        $extracted = $this->extractJsonObject($content);
        if ($extracted !== null) {
            $decoded = $this->tryParseJson($extracted);
            if ($decoded !== null) {
                return $decoded;
            }
        }

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
        } catch (JsonException) {
            return null;
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
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
                            return $candidate;
                        }
                        // Not valid JSON, continue searching
                        $start = -1;
                    }
                }
            }
        }

        return null;
    }
}
