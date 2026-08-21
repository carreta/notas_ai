<?php

namespace Tests\Unit\AI;

use App\AI\Exceptions\AiInvalidResponseException;
use App\AI\Parsing\StructuredAnalysisParser;
use PHPUnit\Framework\TestCase;

class StructuredAnalysisParserTest extends TestCase
{
    private StructuredAnalysisParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new StructuredAnalysisParser;
    }

    public function test_valid_json_object_parses_successfully(): void
    {
        $json = '{"summary":"Project meeting","decisions":[],"action_items":[],"open_questions":[]}';

        $result = $this->parser->parse($json);

        $this->assertSame('Project meeting', $result['summary']);
        $this->assertSame([], $result['decisions']);
    }

    public function test_whitespace_around_valid_json_is_accepted(): void
    {
        $json = "  {\"summary\":\"ok\"}  \n";

        $result = $this->parser->parse($json);

        $this->assertSame('ok', $result['summary']);
    }

    public function test_empty_string_throws_ai_invalid_response(): void
    {
        $this->expectException(AiInvalidResponseException::class);

        $this->parser->parse('');
    }

    public function test_whitespace_only_string_throws(): void
    {
        $this->expectException(AiInvalidResponseException::class);

        $this->parser->parse("   \n\t  ");
    }

    public function test_malformed_json_throws(): void
    {
        $this->expectException(AiInvalidResponseException::class);

        $this->parser->parse('{"summary":');
    }

    public function test_json_list_root_throws(): void
    {
        $this->expectException(AiInvalidResponseException::class);

        $this->parser->parse('[1, 2, 3]');
    }

    public function test_json_string_root_throws(): void
    {
        $this->expectException(AiInvalidResponseException::class);

        $this->parser->parse('"hello"');
    }

    public function test_json_integer_root_throws(): void
    {
        $this->expectException(AiInvalidResponseException::class);

        $this->parser->parse('123');
    }

    public function test_json_boolean_root_throws(): void
    {
        $this->expectException(AiInvalidResponseException::class);

        $this->parser->parse('true');
    }

    public function test_json_null_root_throws(): void
    {
        $this->expectException(AiInvalidResponseException::class);

        $this->parser->parse('null');
    }

    public function test_nested_values_are_returned_unchanged(): void
    {
        $json = '{"anything":"value","nested":{"a":[1,2,3]}}';

        $result = $this->parser->parse($json);

        $this->assertSame('value', $result['anything']);
        $this->assertSame(['a' => [1, 2, 3]], $result['nested']);
    }

    public function test_parser_does_not_require_contract_fields_yet(): void
    {
        $json = '{"anything":"value"}';

        $result = $this->parser->parse($json);

        $this->assertSame('value', $result['anything']);
    }

    public function test_exception_exposes_application_error_category(): void
    {
        $exception = new AiInvalidResponseException;

        $this->assertSame('AI_INVALID_RESPONSE', $exception->errorCategory());
    }

    public function test_exception_default_message_has_no_raw_content(): void
    {
        $exception = new AiInvalidResponseException;

        $this->assertStringNotContainsString('{"', $exception->getMessage());
    }

    public function test_json_with_reasoning_before_is_extracted(): void
    {
        $content = <<<'TEXT'
Thinking through this step-by-step:
1. First I analyze the transcript
2. Then I create the JSON

{"summary":"Meeting about AI demo","decisions":["Use fixed transcript"],"action_items":[],"open_questions":[]}
TEXT;

        $result = $this->parser->parse($content);

        $this->assertSame('Meeting about AI demo', $result['summary']);
        $this->assertSame(['Use fixed transcript'], $result['decisions']);
    }

    public function test_json_with_reasoning_after_is_extracted(): void
    {
        $content = <<<'TEXT'
{"summary":"Meeting about AI demo","decisions":[],"action_items":[],"open_questions":[]}

This was my reasoning process:
- Step 1: Identify key topics
- Step 2: Extract action items
TEXT;

        $result = $this->parser->parse($content);

        $this->assertSame('Meeting about AI demo', $result['summary']);
    }

    public function test_json_with_reasoning_before_and_after_is_extracted(): void
    {
        $content = <<<'TEXT'
Thinking through this step-by-step:
**Summary**: Need to create concise summary

{"summary":"AI demo planning","decisions":["Fixed transcript"],"action_items":[],"open_questions":["Provider selection"]}

The analysis is complete.
TEXT;

        $result = $this->parser->parse($content);

        $this->assertSame('AI demo planning', $result['summary']);
        $this->assertSame(['Fixed transcript'], $result['decisions']);
        $this->assertSame(['Provider selection'], $result['open_questions']);
    }

    public function test_json_in_markdown_fence_with_reasoning_is_extracted(): void
    {
        $content = <<<'TEXT'
Here is my analysis:

```json
{"summary":"Test meeting","decisions":[],"action_items":[],"open_questions":[]}
```

The JSON above is the result.
TEXT;

        $result = $this->parser->parse($content);

        $this->assertSame('Test meeting', $result['summary']);
    }

    public function test_nested_braces_in_reasoning_does_not_break_extraction(): void
    {
        $content = <<<'TEXT'
Reasoning with {nested braces} in text:

{"summary":"Real meeting","decisions":["Decision with {braces}"],"action_items":[],"open_questions":[]}

More text {here}.
TEXT;

        $result = $this->parser->parse($content);

        $this->assertSame('Real meeting', $result['summary']);
        $this->assertSame(['Decision with {braces}'], $result['decisions']);
    }

    public function test_invalid_json_still_throws(): void
    {
        $this->expectException(AiInvalidResponseException::class);

        $this->parser->parse('Just some text without any JSON object');
    }
}
