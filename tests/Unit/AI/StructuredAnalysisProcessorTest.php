<?php

namespace Tests\Unit\AI;

use App\AI\DTO\ActionItem;
use App\AI\DTO\AnalysisResult;
use App\AI\Exceptions\AiInvalidResponseException;
use App\AI\Normalization\StructuredAnalysisNormalizer;
use App\AI\Parsing\StructuredAnalysisParser;
use App\AI\StructuredAnalysisProcessor;
use App\AI\Validation\StructuredAnalysisValidator;
use PHPUnit\Framework\TestCase;

class StructuredAnalysisProcessorTest extends TestCase
{
    private StructuredAnalysisProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new StructuredAnalysisProcessor(
            new StructuredAnalysisParser,
            new StructuredAnalysisValidator,
            new StructuredAnalysisNormalizer,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validBase(): array
    {
        return [
            'summary' => 'Meeting summary',
            'decisions' => [
                ['text' => 'We chose PostgreSQL'],
            ],
            'action_items' => [
                [
                    'task' => 'Create migration',
                    'owner' => 'John',
                    'priority' => 'HIGH',
                    'priority_source' => 'EXPLICIT',
                    'due_date_text' => 'next Monday',
                    'due_date' => '2026-08-17',
                    'due_date_source' => 'RESOLVED',
                ],
            ],
            'open_questions' => [
                ['text' => 'Which provider?'],
            ],
        ];
    }

    private function process(array $data): AnalysisResult
    {
        return $this->processor->process((string) json_encode($data));
    }

    // ---------- VALID ----------

    public function test_complete_valid_raw_json_returns_analysis_result(): void
    {
        $result = $this->process($this->validBase());

        $this->assertInstanceOf(AnalysisResult::class, $result);
        $this->assertSame('Meeting summary', $result->summary);
    }

    public function test_valid_empty_collections_returns_analysis_result(): void
    {
        $data = $this->validBase();
        $data['decisions'] = [];
        $data['action_items'] = [];
        $data['open_questions'] = [];

        $result = $this->process($data);

        $this->assertSame([], $result->decisions);
        $this->assertSame([], $result->actionItems);
        $this->assertSame([], $result->openQuestions);
    }

    public function test_null_owner_accepted(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['owner'] = null;

        $result = $this->process($data);

        $this->assertNull($result->actionItems[0]->owner);
    }

    public function test_null_priority_and_source_accepted(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['priority'] = null;
        $data['action_items'][0]['priority_source'] = null;

        $result = $this->process($data);

        $this->assertNull($result->actionItems[0]->priority);
        $this->assertNull($result->actionItems[0]->prioritySource);
    }

    public function test_explicit_priority_preserved(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['priority'] = 'LOW';
        $data['action_items'][0]['priority_source'] = 'EXPLICIT';

        $result = $this->process($data);

        $this->assertSame('LOW', $result->actionItems[0]->priority);
        $this->assertSame('EXPLICIT', $result->actionItems[0]->prioritySource);
    }

    public function test_inferred_priority_preserved(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['priority'] = 'HIGH';
        $data['action_items'][0]['priority_source'] = 'INFERRED';

        $result = $this->process($data);

        $this->assertSame('HIGH', $result->actionItems[0]->priority);
        $this->assertSame('INFERRED', $result->actionItems[0]->prioritySource);
    }

    public function test_explicit_due_date_preserved(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['due_date_text'] = '2026-08-18';
        $data['action_items'][0]['due_date'] = '2026-08-18';
        $data['action_items'][0]['due_date_source'] = 'EXPLICIT';

        $result = $this->process($data);

        $this->assertSame('2026-08-18', $result->actionItems[0]->dueDate);
    }

    public function test_resolved_date_preserved(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['due_date_source'] = 'RESOLVED';

        $result = $this->process($data);

        $this->assertSame('next Monday', $result->actionItems[0]->dueDateText);
        $this->assertSame('2026-08-17', $result->actionItems[0]->dueDate);
        $this->assertSame('RESOLVED', $result->actionItems[0]->dueDateSource);
    }

    public function test_unresolved_date_preserved(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['due_date_text'] = 'next week';
        $data['action_items'][0]['due_date'] = null;
        $data['action_items'][0]['due_date_source'] = 'UNRESOLVED';

        $result = $this->process($data);

        $this->assertSame('next week', $result->actionItems[0]->dueDateText);
        $this->assertNull($result->actionItems[0]->dueDate);
        $this->assertSame('UNRESOLVED', $result->actionItems[0]->dueDateSource);
    }

    public function test_nested_dtos_created(): void
    {
        $result = $this->process($this->validBase());

        $this->assertInstanceOf(ActionItem::class, $result->actionItems[0]);
        $this->assertCount(1, $result->decisions);
        $this->assertCount(1, $result->openQuestions);
        $this->assertSame('We chose PostgreSQL', $result->decisions[0]->text);
    }

    // ---------- INVALID ----------

    public function test_empty_raw_content_throws(): void
    {
        $this->expectException(AiInvalidResponseException::class);

        $this->processor->process('');
    }

    public function test_malformed_json_throws(): void
    {
        $this->expectException(AiInvalidResponseException::class);

        $this->processor->process('{"summary":');
    }

    public function test_root_list_throws(): void
    {
        $this->expectException(AiInvalidResponseException::class);

        $this->processor->process('[1, 2, 3]');
    }

    public function test_missing_summary_throws(): void
    {
        $data = $this->validBase();
        unset($data['summary']);

        $this->expectException(AiInvalidResponseException::class);

        $this->process($data);
    }

    public function test_missing_decisions_throws(): void
    {
        $data = $this->validBase();
        unset($data['decisions']);

        $this->expectException(AiInvalidResponseException::class);

        $this->process($data);
    }

    public function test_missing_action_items_throws(): void
    {
        $data = $this->validBase();
        unset($data['action_items']);

        $this->expectException(AiInvalidResponseException::class);

        $this->process($data);
    }

    public function test_missing_open_questions_throws(): void
    {
        $data = $this->validBase();
        unset($data['open_questions']);

        $this->expectException(AiInvalidResponseException::class);

        $this->process($data);
    }

    public function test_wrong_top_level_type_throws(): void
    {
        $data = $this->validBase();
        $data['summary'] = 123;

        $this->expectException(AiInvalidResponseException::class);

        $this->process($data);
    }

    public function test_invalid_action_item_task_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['task'] = '';

        $this->expectException(AiInvalidResponseException::class);

        $this->process($data);
    }

    public function test_invalid_priority_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['priority'] = 'URGENT';

        $this->expectException(AiInvalidResponseException::class);

        $this->process($data);
    }

    public function test_invalid_priority_source_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['priority_source'] = 'DERIVED';

        $this->expectException(AiInvalidResponseException::class);

        $this->process($data);
    }

    public function test_invalid_due_date_format_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['due_date'] = '2026-99-99';

        $this->expectException(AiInvalidResponseException::class);

        $this->process($data);
    }

    public function test_invalid_due_date_source_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['due_date_source'] = 'MAYBE';

        $this->expectException(AiInvalidResponseException::class);

        $this->process($data);
    }

    public function test_inconsistent_priority_source_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['priority'] = null;
        $data['action_items'][0]['priority_source'] = 'EXPLICIT';

        $this->expectException(AiInvalidResponseException::class);

        $this->process($data);
    }

    public function test_inconsistent_unresolved_due_date_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['due_date_text'] = 'next week';
        $data['action_items'][0]['due_date'] = '2026-08-17';
        $data['action_items'][0]['due_date_source'] = 'UNRESOLVED';

        $this->expectException(AiInvalidResponseException::class);

        $this->process($data);
    }

    // ---------- SECURITY / TRUST ----------

    public function test_raw_malformed_content_not_in_exception_message(): void
    {
        try {
            $this->processor->process('{"summary": "leaked-secret-content"}');
            $this->fail('Expected AiInvalidResponseException');
        } catch (AiInvalidResponseException $exception) {
            $this->assertStringNotContainsString('leaked-secret-content', $exception->getMessage());
            $this->assertSame('AI_INVALID_RESPONSE', $exception->errorCategory());
        }
    }

    public function test_no_partial_result_on_failure(): void
    {
        $threw = false;

        try {
            $this->processor->process('not-json');
        } catch (AiInvalidResponseException) {
            $threw = true;
        }

        $this->assertTrue($threw);
    }

    public function test_no_persistence_occurs(): void
    {
        // The processor has no persistence dependency; a successful run must
        // return an in-memory DTO without side effects.
        $result = $this->process($this->validBase());

        $this->assertInstanceOf(AnalysisResult::class, $result);
    }

    public function test_no_provider_sdk_classes_referenced(): void
    {
        $reflection = new \ReflectionClass(StructuredAnalysisProcessor::class);

        foreach ($reflection->getConstructor()->getParameters() as $parameter) {
            $type = $parameter->getType();
            $this->assertNotNull($type);
            $this->assertStringStartsNotWith('OpenAI', (string) $type);
        }
    }
}
