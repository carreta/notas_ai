<?php

namespace Tests\Feature;

use App\Support\TokenCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingValidationBoundaryTD001Test extends TestCase
{
    use RefreshDatabase;

    public function test_meeting_with_13500_words_is_within_token_limit(): void
    {
        $transcript = implode(
            ' ',
            array_fill(0, 13500, 'a')
        );

        $counter = app(TokenCounter::class);
        $tokens = $counter->count($transcript, 'cl100k_base');

        // 13500 words of single character 'a' should be within the 20000 token limit for chatgpt-sol
        $this->assertLessThanOrEqual(20000, $tokens);
    }

    public function test_meeting_with_excessive_words_exceeds_token_limit(): void
    {
        // Create a transcript that will exceed 20000 tokens
        // Using a more realistic text pattern that generates more tokens per word
        $transcript = str_repeat('This is a test sentence with multiple words. ', 2500);

        $counter = app(TokenCounter::class);
        $tokens = $counter->count($transcript, 'cl100k_base');

        // This should exceed the 20000 token limit for chatgpt-sol
        $this->assertGreaterThan(20000, $tokens);
    }

    public function test_token_counter_respects_model_limits(): void
    {
        $counter = app(TokenCounter::class);

        // chatgpt-sol: 20000 tokens
        $text = str_repeat('word ', 15000);
        $tokens = $counter->count($text, 'cl100k_base');
        $this->assertLessThanOrEqual(20000, $tokens);

        // chatgpt-terra: 50000 tokens
        $text = str_repeat('word ', 40000);
        $tokens = $counter->count($text, 'cl100k_base');
        $this->assertLessThanOrEqual(50000, $tokens);

        // chatgpt-luna: 100000 tokens
        $text = str_repeat('word ', 80000);
        $tokens = $counter->count($text, 'o200k_base');
        $this->assertLessThanOrEqual(100000, $tokens);
    }

    public function test_exceeds_limit_method_works(): void
    {
        $counter = app(TokenCounter::class);

        $shortText = 'Short text';
        $this->assertFalse($counter->exceedsLimit($shortText, 100, 'cl100k_base'));

        $longText = str_repeat('word ', 50000);
        $this->assertTrue($counter->exceedsLimit($longText, 100, 'cl100k_base'));
    }
}
