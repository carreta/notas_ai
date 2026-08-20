<?php

namespace Tests\Unit\AI;

use App\AI\DTO\AnalysisResult;
use App\AI\Exceptions\AiInvalidResponseException;
use App\AI\Parsing\StructuredAnalysisParser;
use App\AI\Validation\StructuredAnalysisValidator;
use PHPUnit\Framework\TestCase;

class StructuredAnalysisValidatorTest extends TestCase
{
    private StructuredAnalysisValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new StructuredAnalysisValidator;
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

    private function assertValid(array $data): AnalysisResult
    {
        $result = $this->validator->validate($data);

        $this->assertInstanceOf(AnalysisResult::class, $result);

        return $result;
    }

    private function assertInvalid(array $data): void
    {
        $this->expectException(AiInvalidResponseException::class);

        $this->validator->validate($data);
    }

    // ---------- VALID CASES ----------

    public function test_complete_valid_result(): void
    {
        $result = $this->assertValid($this->validBase());

        $this->assertSame('Meeting summary', $result->summary);
        $this->assertCount(1, $result->decisions);
        $this->assertCount(1, $result->actionItems);
        $this->assertCount(1, $result->openQuestions);
        $this->assertSame('Create migration', $result->actionItems[0]->task);
    }

    public function test_empty_collections_are_valid(): void
    {
        $data = $this->validBase();
        $data['decisions'] = [];
        $data['action_items'] = [];
        $data['open_questions'] = [];

        $result = $this->assertValid($data);

        $this->assertSame([], $result->decisions);
        $this->assertSame([], $result->actionItems);
        $this->assertSame([], $result->openQuestions);
    }

    public function test_owner_null_is_valid(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['owner'] = null;

        $result = $this->assertValid($data);

        $this->assertNull($result->actionItems[0]->owner);
    }

    public function test_priority_null_with_source_null_is_valid(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['priority'] = null;
        $data['action_items'][0]['priority_source'] = null;

        $result = $this->assertValid($data);

        $this->assertNull($result->actionItems[0]->priority);
        $this->assertNull($result->actionItems[0]->prioritySource);
    }

    public function test_priority_low_explicit_is_valid(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['priority'] = 'LOW';
        $data['action_items'][0]['priority_source'] = 'EXPLICIT';

        $result = $this->assertValid($data);

        $this->assertSame('LOW', $result->actionItems[0]->priority);
        $this->assertSame('EXPLICIT', $result->actionItems[0]->prioritySource);
    }

    public function test_priority_high_inferred_is_valid(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['priority'] = 'HIGH';
        $data['action_items'][0]['priority_source'] = 'INFERRED';

        $result = $this->assertValid($data);

        $this->assertSame('HIGH', $result->actionItems[0]->priority);
        $this->assertSame('INFERRED', $result->actionItems[0]->prioritySource);
    }

    public function test_due_date_null_state_is_valid(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['due_date_text'] = null;
        $data['action_items'][0]['due_date'] = null;
        $data['action_items'][0]['due_date_source'] = null;

        $result = $this->assertValid($data);

        $this->assertNull($result->actionItems[0]->dueDate);
        $this->assertNull($result->actionItems[0]->dueDateSource);
    }

    public function test_valid_explicit_iso_date_is_valid(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['due_date_text'] = '2026-08-18';
        $data['action_items'][0]['due_date'] = '2026-08-18';
        $data['action_items'][0]['due_date_source'] = 'EXPLICIT';

        $result = $this->assertValid($data);

        $this->assertSame('2026-08-18', $result->actionItems[0]->dueDate);
        $this->assertSame('EXPLICIT', $result->actionItems[0]->dueDateSource);
    }

    public function test_unresolved_date_with_text_and_null_due_date_is_valid(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['due_date_text'] = 'next Monday';
        $data['action_items'][0]['due_date'] = null;
        $data['action_items'][0]['due_date_source'] = 'UNRESOLVED';

        $result = $this->assertValid($data);

        $this->assertSame('next Monday', $result->actionItems[0]->dueDateText);
        $this->assertNull($result->actionItems[0]->dueDate);
        $this->assertSame('UNRESOLVED', $result->actionItems[0]->dueDateSource);
    }

    // ---------- INVALID TOP-LEVEL ----------

    public function test_summary_missing_throws(): void
    {
        $data = $this->validBase();
        unset($data['summary']);

        $this->assertInvalid($data);
    }

    public function test_decisions_missing_throws(): void
    {
        $data = $this->validBase();
        unset($data['decisions']);

        $this->assertInvalid($data);
    }

    public function test_action_items_missing_throws(): void
    {
        $data = $this->validBase();
        unset($data['action_items']);

        $this->assertInvalid($data);
    }

    public function test_open_questions_missing_throws(): void
    {
        $data = $this->validBase();
        unset($data['open_questions']);

        $this->assertInvalid($data);
    }

    public function test_summary_wrong_type_throws(): void
    {
        $data = $this->validBase();
        $data['summary'] = 123;

        $this->assertInvalid($data);
    }

    public function test_decisions_wrong_type_throws(): void
    {
        $data = $this->validBase();
        $data['decisions'] = 'not-array';

        $this->assertInvalid($data);
    }

    public function test_action_items_wrong_type_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'] = 'not-array';

        $this->assertInvalid($data);
    }

    public function test_open_questions_wrong_type_throws(): void
    {
        $data = $this->validBase();
        $data['open_questions'] = 'not-array';

        $this->assertInvalid($data);
    }

    // ---------- INVALID DECISIONS ----------

    public function test_decision_item_not_object_throws(): void
    {
        $data = $this->validBase();
        $data['decisions'] = ['not-an-object'];

        $this->assertInvalid($data);
    }

    public function test_decision_text_missing_throws(): void
    {
        $data = $this->validBase();
        $data['decisions'] = [['wrong' => 'key']];

        $this->assertInvalid($data);
    }

    public function test_decision_text_wrong_type_throws(): void
    {
        $data = $this->validBase();
        $data['decisions'] = [['text' => 42]];

        $this->assertInvalid($data);
    }

    // ---------- INVALID ACTION ITEMS ----------

    public function test_action_item_task_missing_throws(): void
    {
        $data = $this->validBase();
        unset($data['action_items'][0]['task']);

        $this->assertInvalid($data);
    }

    public function test_action_item_task_empty_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['task'] = '';

        $this->assertInvalid($data);
    }

    public function test_owner_wrong_type_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['owner'] = 123;

        $this->assertInvalid($data);
    }

    public function test_invalid_priority_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'][] = [
            'task' => 'x',
            'priority' => 'URGENT',
            'priority_source' => 'EXPLICIT',
        ];

        $this->assertInvalid($data);
    }

    public function test_priority_present_with_source_null_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['priority'] = 'LOW';
        $data['action_items'][0]['priority_source'] = null;

        $this->assertInvalid($data);
    }

    public function test_priority_null_with_source_explicit_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['priority'] = null;
        $data['action_items'][0]['priority_source'] = 'EXPLICIT';

        $this->assertInvalid($data);
    }

    public function test_invalid_priority_source_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['priority_source'] = 'DERIVED';

        $this->assertInvalid($data);
    }

    public function test_due_date_wrong_type_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['due_date'] = 20260818;

        $this->assertInvalid($data);
    }

    public function test_invalid_iso_date_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['due_date'] = '2026-99-99';

        $this->assertInvalid($data);
    }

    public function test_invalid_due_date_source_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['due_date_source'] = 'MAYBE';

        $this->assertInvalid($data);
    }

    public function test_unresolved_with_due_date_non_null_throws(): void
    {
        $data = $this->validBase();
        $data['action_items'][0]['due_date_text'] = 'next Monday';
        $data['action_items'][0]['due_date'] = '2026-08-17';
        $data['action_items'][0]['due_date_source'] = 'UNRESOLVED';

        $this->assertInvalid($data);
    }

    // ---------- INVALID OPEN QUESTIONS ----------

    public function test_open_question_invalid_structure_throws(): void
    {
        $data = $this->validBase();
        $data['open_questions'] = [['wrong' => 'key']];

        $this->assertInvalid($data);
    }

    // ---------- ERROR CATEGORY ----------

    public function test_invalid_response_exposes_application_category(): void
    {
        $data = $this->validBase();
        unset($data['summary']);

        try {
            $this->validator->validate($data);
            $this->fail('Expected AiInvalidResponseException');
        } catch (AiInvalidResponseException $exception) {
            $this->assertSame('AI_INVALID_RESPONSE', $exception->errorCategory());
        }
    }

    // ---------- INTEGRATION PARSER -> VALIDATOR ----------

    public function test_raw_valid_json_flows_to_dto(): void
    {
        $json = (string) json_encode($this->validBase());

        $parser = new StructuredAnalysisParser;
        $parsed = $parser->parse($json);

        $result = $this->validator->validate($parsed);

        $this->assertInstanceOf(AnalysisResult::class, $result);
        $this->assertSame('Meeting summary', $result->summary);
    }

    public function test_raw_valid_json_but_structurally_invalid_is_rejected(): void
    {
        $json = (string) json_encode([
            'decisions' => [],
            'action_items' => [],
            'open_questions' => [],
        ]);

        $parser = new StructuredAnalysisParser;
        $parsed = $parser->parse($json);

        $this->expectException(AiInvalidResponseException::class);

        $this->validator->validate($parsed);
    }
}
