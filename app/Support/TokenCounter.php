<?php

namespace App\Support;

class TokenCounter
{
    public function count(string $text): int
    {
        // TODO: Replace with OpenAI tokenizer (tiktoken) when TD-009 resolved
        // For now: rough approximation ~4 chars per token for English
        return (int) ceil(mb_strlen($text) / 4);
    }

    public function exceedsLimit(string $text, int $maxTokens): bool
    {
        return $this->count($text) > $maxTokens;
    }
}
