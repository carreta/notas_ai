<?php

namespace Tests\Unit\AI;

use App\AI\DTO\ActionItem;
use App\AI\DTO\AnalysisResult;
use App\AI\DTO\Decision;
use App\AI\DTO\OpenQuestion;
use App\AI\Normalization\StructuredAnalysisNormalizer;
use PHPUnit\Framework\TestCase;

class StructuredAnalysisNormalizerTest extends TestCase
{
    private StructuredAnalysisNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new StructuredAnalysisNormalizer;
    }

    private function makeResult(ActionItem ...$items): AnalysisResult
    {
        return new AnalysisResult(
            'Meeting summary',
            [new Decision('We chose Postgres')],
            $items,
            [new OpenQuestion('Which provider?')],
        );
    }

    private function actionItem(array $overrides = []): ActionItem
    {
        return new ActionItem(
            $overrides['task'] ?? 'Do the thing',
            $overrides['owner'] ?? 'John',
            $overrides['priority'] ?? null,
            $overrides['prioritySource'] ?? null,
            $overrides['dueDateText'] ?? null,
            $overrides['dueDate'] ?? null,
            $overrides['dueDateSource'] ?? null,
        );
    }

    // ---------- PRIORITY ----------

    public function test_explicit_low_preserved(): void
    {
        $result = $this->makeResult($this->actionItem([
            'priority' => 'LOW',
            'prioritySource' => 'EXPLICIT',
        ]));

        $normalized = $this->normalizer->normalize($result);

        $this->assertSame('LOW', $normalized->actionItems[0]->priority);
        $this->assertSame('EXPLICIT', $normalized->actionItems[0]->prioritySource);
    }

    public function test_explicit_medium_preserved(): void
    {
        $result = $this->makeResult($this->actionItem([
            'priority' => 'MEDIUM',
            'prioritySource' => 'EXPLICIT',
        ]));

        $normalized = $this->normalizer->normalize($result);

        $this->assertSame('MEDIUM', $normalized->actionItems[0]->priority);
    }

    public function test_inferred_high_preserved(): void
    {
        $result = $this->makeResult($this->actionItem([
            'priority' => 'HIGH',
            'prioritySource' => 'INFERRED',
        ]));

        $normalized = $this->normalizer->normalize($result);

        $this->assertSame('HIGH', $normalized->actionItems[0]->priority);
        $this->assertSame('INFERRED', $normalized->actionItems[0]->prioritySource);
    }

    public function test_null_priority_remains_null(): void
    {
        $result = $this->makeResult($this->actionItem([
            'priority' => null,
            'prioritySource' => null,
        ]));

        $normalized = $this->normalizer->normalize($result);

        $this->assertNull($normalized->actionItems[0]->priority);
        $this->assertNull($normalized->actionItems[0]->prioritySource);
    }

    public function test_does_not_infer_priority_from_task_wording(): void
    {
        $result = $this->makeResult($this->actionItem([
            'task' => 'URGENT: fix this immediately',
            'priority' => null,
            'prioritySource' => null,
        ]));

        $normalized = $this->normalizer->normalize($result);

        $this->assertNull($normalized->actionItems[0]->priority);
        $this->assertNull($normalized->actionItems[0]->prioritySource);
    }

    // ---------- DATES ----------

    public function test_explicit_due_date_preserved(): void
    {
        $result = $this->makeResult($this->actionItem([
            'dueDateText' => '2026-08-18',
            'dueDate' => '2026-08-18',
            'dueDateSource' => 'EXPLICIT',
        ]));

        $normalized = $this->normalizer->normalize($result);

        $this->assertSame('2026-08-18', $normalized->actionItems[0]->dueDate);
        $this->assertSame('EXPLICIT', $normalized->actionItems[0]->dueDateSource);
    }

    public function test_resolved_due_date_preserves_text_and_iso(): void
    {
        $result = $this->makeResult($this->actionItem([
            'dueDateText' => 'next Monday',
            'dueDate' => '2026-08-17',
            'dueDateSource' => 'RESOLVED',
        ]));

        $normalized = $this->normalizer->normalize($result);

        $this->assertSame('next Monday', $normalized->actionItems[0]->dueDateText);
        $this->assertSame('2026-08-17', $normalized->actionItems[0]->dueDate);
        $this->assertSame('RESOLVED', $normalized->actionItems[0]->dueDateSource);
    }

    public function test_inferred_date_provenance_preserved(): void
    {
        $result = $this->makeResult($this->actionItem([
            'dueDateText' => 'by end of sprint',
            'dueDate' => '2026-08-30',
            'dueDateSource' => 'INFERRED',
        ]));

        $normalized = $this->normalizer->normalize($result);

        $this->assertSame('INFERRED', $normalized->actionItems[0]->dueDateSource);
        $this->assertSame('2026-08-30', $normalized->actionItems[0]->dueDate);
    }

    public function test_unresolved_relative_date_preserves_text_and_null_date(): void
    {
        $result = $this->makeResult($this->actionItem([
            'dueDateText' => 'next week',
            'dueDate' => null,
            'dueDateSource' => 'UNRESOLVED',
        ]));

        $normalized = $this->normalizer->normalize($result);

        $this->assertSame('next week', $normalized->actionItems[0]->dueDateText);
        $this->assertNull($normalized->actionItems[0]->dueDate);
        $this->assertSame('UNRESOLVED', $normalized->actionItems[0]->dueDateSource);
    }

    public function test_no_date_information_remains_all_null(): void
    {
        $result = $this->makeResult($this->actionItem([
            'dueDateText' => null,
            'dueDate' => null,
            'dueDateSource' => null,
        ]));

        $normalized = $this->normalizer->normalize($result);

        $this->assertNull($normalized->actionItems[0]->dueDateText);
        $this->assertNull($normalized->actionItems[0]->dueDate);
        $this->assertNull($normalized->actionItems[0]->dueDateSource);
    }

    // ---------- STRUCTURE ----------

    public function test_empty_collections_preserved(): void
    {
        $result = new AnalysisResult('s', [], [], []);

        $normalized = $this->normalizer->normalize($result);

        $this->assertSame([], $normalized->decisions);
        $this->assertSame([], $normalized->actionItems);
        $this->assertSame([], $normalized->openQuestions);
    }

    public function test_summary_decisions_open_questions_unchanged(): void
    {
        $result = new AnalysisResult(
            'Original summary',
            [new Decision('decision text')],
            [],
            [new OpenQuestion('question text')],
        );

        $normalized = $this->normalizer->normalize($result);

        $this->assertSame('Original summary', $normalized->summary);
        $this->assertSame('decision text', $normalized->decisions[0]->text);
        $this->assertSame('question text', $normalized->openQuestions[0]->text);
    }

    public function test_returns_new_analysis_result_instance(): void
    {
        $result = $this->makeResult($this->actionItem());

        $normalized = $this->normalizer->normalize($result);

        $this->assertInstanceOf(AnalysisResult::class, $normalized);
        $this->assertNotSame($result, $normalized);
    }

    public function test_returns_new_action_item_instances(): void
    {
        $item = $this->actionItem();
        $result = $this->makeResult($item);

        $normalized = $this->normalizer->normalize($result);

        $this->assertNotSame($item, $normalized->actionItems[0]);
        $this->assertSame($item->task, $normalized->actionItems[0]->task);
    }

    public function test_does_not_call_provider_or_persistence(): void
    {
        $result = $this->makeResult($this->actionItem([
            'priority' => 'HIGH',
            'prioritySource' => 'INFERRED',
        ]));

        $normalized = $this->normalizer->normalize($result);

        // Pure transformation: values are preserved, no side effects.
        $this->assertSame('HIGH', $normalized->actionItems[0]->priority);
        $this->assertSame('INFERRED', $normalized->actionItems[0]->prioritySource);
    }
}
