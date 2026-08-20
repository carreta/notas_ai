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
     * @return array<string, mixed>
     *
     * @throws AiInvalidResponseException
     */
    public function parse(string $content): array
    {
        $content = trim($content);

        if ($content === '') {
            throw new AiInvalidResponseException;
        }

        try {
            $decoded = json_decode(
                $content,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new AiInvalidResponseException(previous: $exception);
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new AiInvalidResponseException;
        }

        return $decoded;
    }
}
