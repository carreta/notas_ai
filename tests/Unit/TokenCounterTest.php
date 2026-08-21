<?php

namespace Tests\Unit;

use App\Support\TokenCounter;
use Tests\TestCase;

class TokenCounterTest extends TestCase
{
    private TokenCounter $counter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->counter = new TokenCounter;
    }

    public function test_count_returns_correct_token_count_for_ascii(): void
    {
        // 100 'a' characters = 13 tokens with cl100k_base encoding
        $text = str_repeat('a', 100);

        $this->assertSame(13, $this->counter->count($text));
    }

    public function test_count_handles_empty_string(): void
    {
        $this->assertSame(0, $this->counter->count(''));
    }

    public function test_count_handles_single_character(): void
    {
        // Single 'a' = 1 token
        $this->assertSame(1, $this->counter->count('a'));
    }

    public function test_count_handles_partial_token(): void
    {
        // 'abcde' = 2 tokens
        $this->assertSame(2, $this->counter->count('abcde'));
    }

    public function test_count_handles_unicode_multibyte(): void
    {
        // 10 'é' characters = 10 tokens (each is a separate token in cl100k_base)
        $text = str_repeat('é', 10);

        $this->assertSame(10, $this->counter->count($text));
    }

    public function test_exceeds_limit_returns_true_when_over(): void
    {
        // 41 'a' characters = 6 tokens, exceeds limit of 5
        $text = str_repeat('a', 41);

        $this->assertTrue($this->counter->exceedsLimit($text, 5));
    }

    public function test_exceeds_limit_returns_false_when_equal(): void
    {
        // 40 'a' characters = 6 tokens, limit is 6 (exactly at limit)
        $text = str_repeat('a', 40);

        $this->assertFalse($this->counter->exceedsLimit($text, 6));
    }

    public function test_exceeds_limit_returns_false_when_under(): void
    {
        // 36 'a' characters = 5 tokens, limit is 10
        $text = str_repeat('a', 36);

        $this->assertFalse($this->counter->exceedsLimit($text, 10));
    }

    public function test_exceeds_limit_with_zero_limit(): void
    {
        $this->assertFalse($this->counter->exceedsLimit('', 0));
        $this->assertTrue($this->counter->exceedsLimit('a', 0));
    }
}
