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
}
