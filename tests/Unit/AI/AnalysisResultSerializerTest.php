<?php

namespace Tests\Unit\AI;

use App\AI\DTO\ActionItem;
use App\AI\DTO\AnalysisResult;
use App\AI\DTO\Decision;
use App\AI\DTO\OpenQuestion;
use App\AI\Serialization\AnalysisResultSerializer;
use PHPUnit\Framework\TestCase;

class AnalysisResultSerializerTest extends TestCase
{
    private AnalysisResultSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new AnalysisResultSerializer;
    }

    public function test_maps_only_the_approved_top_level_keys(): void
    {
        $result = new AnalysisResult('Summary text', [], [], []);

        $out = $this->serializer->toArray($result);

        $this->assertSame(
            ['summary', 'decisions', 'action_items', 'open_questions'],
            array_keys($out)
        );
    }

    public function test_serializes_decisions_with_text_key(): void
    {
        $result = new AnalysisResult('S', [new Decision('We chose OpenAI')], [], []);

        $out = $this->serializer->toArray($result);

        $this->assertSame([['text' => 'We chose OpenAI']], $out['decisions']);
    }

    public function test_serializes_open_questions_with_text_key(): void
    {
        $result = new AnalysisResult('S', [], [], [new OpenQuestion('Who owns infra?')]);

        $out = $this->serializer->toArray($result);

        $this->assertSame([['text' => 'Who owns infra?']], $out['open_questions']);
    }

    public function test_serializes_action_items_with_snake_case_provenance_keys(): void
    {
        $item = new ActionItem(
            task: 'Prepare report',
            owner: 'Ana',
            priority: 'HIGH',
            prioritySource: 'EXPLICIT',
            dueDateText: 'next Friday',
            dueDate: '2026-08-28',
            dueDateSource: 'RESOLVED',
        );
        $result = new AnalysisResult('S', [], [$item], []);

        $out = $this->serializer->toArray($result);

        $this->assertSame([
            'task' => 'Prepare report',
            'owner' => 'Ana',
            'priority' => 'HIGH',
            'priority_source' => 'EXPLICIT',
            'due_date_text' => 'next Friday',
            'due_date' => '2026-08-28',
            'due_date_source' => 'RESOLVED',
        ], $out['action_items'][0]);
    }

    public function test_preserves_nullable_owner_and_provenance_as_null(): void
    {
        $item = new ActionItem(
            task: 'Follow up',
            owner: null,
            priority: null,
            prioritySource: null,
            dueDateText: null,
            dueDate: null,
            dueDateSource: null,
        );
        $result = new AnalysisResult('S', [], [$item], []);

        $out = $this->serializer->toArray($result);

        $this->assertNull($out['action_items'][0]['owner']);
        $this->assertNull($out['action_items'][0]['priority']);
        $this->assertNull($out['action_items'][0]['priority_source']);
        $this->assertNull($out['action_items'][0]['due_date_text']);
        $this->assertNull($out['action_items'][0]['due_date']);
        $this->assertNull($out['action_items'][0]['due_date_source']);
    }

    public function test_preserves_empty_collections(): void
    {
        $result = new AnalysisResult('S', [], [], []);

        $out = $this->serializer->toArray($result);

        $this->assertSame([], $out['decisions']);
        $this->assertSame([], $out['action_items']);
        $this->assertSame([], $out['open_questions']);
    }

    public function test_output_contains_no_provider_specific_or_camel_case_keys(): void
    {
        $item = new ActionItem(
            task: 'Task',
            owner: 'Bob',
            priority: 'LOW',
            prioritySource: 'INFERRED',
            dueDateText: 'soon',
            dueDate: null,
            dueDateSource: 'UNRESOLVED',
        );
        $result = new AnalysisResult('S', [new Decision('d')], [$item], [new OpenQuestion('q')]);

        $json = json_encode($this->serializer->toArray($result));

        $this->assertStringNotContainsString('prioritySource', $json);
        $this->assertStringNotContainsString('dueDate', $json);
        $this->assertStringNotContainsString('dueDateText', $json);
        $this->assertStringContainsString('priority_source', $json);
        $this->assertStringContainsString('due_date', $json);
        $this->assertStringContainsString('due_date_text', $json);
    }
}
