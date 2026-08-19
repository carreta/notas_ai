<?php

namespace Tests\Unit;

use App\Support\TokenCounter;
use PHPUnit\Framework\TestCase;

class TokenCounterTest extends TestCase
{
    private TokenCounter $counter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->counter = new TokenCounter;
    }

    public function test_count_returns_four_per_character_ratio(): void
    {
        // 100 characters / 4 = 25 tokens
        $text = str_repeat('a', 100);

        $this->assertSame(25, $this->counter->count($text));
    }

    public function test_count_handles_empty_string(): void
    {
        $this->assertSame(0, $this->counter->count(''));
    }

    public function test_count_handles_single_character(): void
    {
        // ceil(1 / 4) = 1
        $this->assertSame(1, $this->counter->count('a'));
    }

    public function test_count_handles_partial_token(): void
    {
        // ceil(5 / 4) = 2
        $this->assertSame(2, $this->counter->count('abcde'));
    }

    public function test_count_handles_unicode_multibyte(): void
    {
        // 10 multibyte chars (é) — mb_strlen counts 10
        // 10 / 4 = 2.5 → ceil = 3
        $text = str_repeat('é', 10);

        $this->assertSame(3, $this->counter->count($text));
    }

    public function test_exceeds_limit_returns_true_when_over(): void
    {
        // 41 chars / 4 = 10.25 → 11 tokens
        $text = str_repeat('a', 41);

        $this->assertTrue($this->counter->exceedsLimit($text, 10));
    }

    public function test_exceeds_limit_returns_false_when_equal(): void
    {
        // 40 chars / 4 = 10 tokens (exactly at limit)
        $text = str_repeat('a', 40);

        $this->assertFalse($this->counter->exceedsLimit($text, 10));
    }

    public function test_exceeds_limit_returns_false_when_under(): void
    {
        $text = str_repeat('a', 36); // 9 tokens

        $this->assertFalse($this->counter->exceedsLimit($text, 10));
    }

    public function test_exceeds_limit_with_zero_limit(): void
    {
        $this->assertFalse($this->counter->exceedsLimit('', 0));
        $this->assertTrue($this->counter->exceedsLimit('a', 0));
    }
}
