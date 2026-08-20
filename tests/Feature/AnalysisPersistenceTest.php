<?php

namespace Tests\Feature;

use App\AI\DTO\ActionItem;
use App\AI\DTO\AnalysisResult;
use App\AI\DTO\Decision;
use App\AI\DTO\OpenQuestion;
use App\AI\Persistence\AnalysisPersistenceService;
use App\Models\Analysis;
use App\Models\Meeting;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalysisPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private AnalysisPersistenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AnalysisPersistenceService;
    }

    protected function tearDown(): void
    {
        Analysis::flushEventListeners();
        parent::tearDown();
    }

    private function validResult(): AnalysisResult
    {
        return new AnalysisResult(
            summary: 'Meeting summary',
            decisions: [new Decision('Adopt OpenAI as primary provider')],
            actionItems: [
                new ActionItem(
                    task: 'Send proposal',
                    owner: 'Ana',
                    priority: 'HIGH',
                    prioritySource: 'EXPLICIT',
                    dueDateText: 'next Friday',
                    dueDate: '2026-08-28',
                    dueDateSource: 'RESOLVED',
                ),
                new ActionItem(
                    task: 'Investigate fallback',
                    owner: null,
                    priority: null,
                    prioritySource: null,
                    dueDateText: null,
                    dueDate: null,
                    dueDateSource: null,
                ),
            ],
            openQuestions: [new OpenQuestion('Who owns infrastructure?')],
        );
    }

    private function meeting(string $status = 'ANALYZING'): Meeting
    {
        return Meeting::create([
            'title' => 'Planning meeting',
            'raw_text' => 'Some meeting transcript.',
            'status' => $status,
            'meeting_time' => '2026-08-19',
        ]);
    }

    private function storedResult(Meeting $meeting): array
    {
        $raw = DB::table('analyses')->where('meeting_id', $meeting->id)->first();

        return json_decode($raw->result, true);
    }

    public function test_valid_result_creates_an_analysis_record(): void
    {
        $meeting = $this->meeting();

        $analysis = $this->service->persist($meeting, $this->validResult());

        $this->assertInstanceOf(Analysis::class, $analysis);
        $this->assertNotNull($analysis->id);
        $this->assertDatabaseHas('analyses', ['meeting_id' => $meeting->id]);
    }

    public function test_analysis_belongs_to_the_passed_meeting(): void
    {
        $meeting = $this->meeting();

        $analysis = $this->service->persist($meeting, $this->validResult());

        $this->assertSame($meeting->id, $analysis->meeting_id);
        $this->assertTrue($meeting->fresh()->analysis->is($analysis));
    }

    public function test_stored_json_matches_approved_structure(): void
    {
        $meeting = $this->meeting();
        $this->service->persist($meeting, $this->validResult());

        $stored = $this->storedResult($meeting);

        $this->assertSame(
            ['summary', 'decisions', 'action_items', 'open_questions'],
            array_keys($stored)
        );
    }

    public function test_summary_is_persisted(): void
    {
        $meeting = $this->meeting();
        $this->service->persist($meeting, $this->validResult());

        $this->assertSame('Meeting summary', $this->storedResult($meeting)['summary']);
    }

    public function test_decisions_are_persisted(): void
    {
        $meeting = $this->meeting();
        $this->service->persist($meeting, $this->validResult());

        $this->assertSame(
            [['text' => 'Adopt OpenAI as primary provider']],
            $this->storedResult($meeting)['decisions']
        );
    }

    public function test_action_items_are_persisted_with_snake_case_keys(): void
    {
        $meeting = $this->meeting();
        $this->service->persist($meeting, $this->validResult());

        $items = $this->storedResult($meeting)['action_items'];

        $this->assertSame('Send proposal', $items[0]['task']);
        $this->assertSame('Ana', $items[0]['owner']);
        $this->assertSame('HIGH', $items[0]['priority']);
        $this->assertSame('EXPLICIT', $items[0]['priority_source']);
        $this->assertSame('next Friday', $items[0]['due_date_text']);
        $this->assertSame('2026-08-28', $items[0]['due_date']);
        $this->assertSame('RESOLVED', $items[0]['due_date_source']);
    }

    public function test_open_questions_are_persisted(): void
    {
        $meeting = $this->meeting();
        $this->service->persist($meeting, $this->validResult());

        $this->assertSame(
            [['text' => 'Who owns infrastructure?']],
            $this->storedResult($meeting)['open_questions']
        );
    }

    public function test_nullable_owner_is_preserved_as_null(): void
    {
        $meeting = $this->meeting();
        $this->service->persist($meeting, $this->validResult());

        $items = $this->storedResult($meeting)['action_items'];

        $this->assertNull($items[1]['owner']);
        $this->assertNull($items[1]['priority']);
        $this->assertNull($items[1]['priority_source']);
        $this->assertNull($items[1]['due_date']);
        $this->assertNull($items[1]['due_date_source']);
    }

    public function test_priority_provenance_is_persisted(): void
    {
        $meeting = $this->meeting();
        $this->service->persist($meeting, $this->validResult());

        $items = $this->storedResult($meeting)['action_items'];

        $this->assertSame('HIGH', $items[0]['priority']);
        $this->assertSame('EXPLICIT', $items[0]['priority_source']);
    }

    public function test_due_date_provenance_is_persisted(): void
    {
        $meeting = $this->meeting();
        $this->service->persist($meeting, $this->validResult());

        $items = $this->storedResult($meeting)['action_items'];

        $this->assertSame('next Friday', $items[0]['due_date_text']);
        $this->assertSame('2026-08-28', $items[0]['due_date']);
        $this->assertSame('RESOLVED', $items[0]['due_date_source']);
    }

    public function test_empty_collections_persist_correctly(): void
    {
        $result = new AnalysisResult('Only summary', [], [], []);
        $meeting = $this->meeting();

        $this->service->persist($meeting, $result);

        $stored = $this->storedResult($meeting);
        $this->assertSame([], $stored['decisions']);
        $this->assertSame([], $stored['action_items']);
        $this->assertSame([], $stored['open_questions']);
    }

    public function test_meeting_becomes_completed_only_after_result_persistence(): void
    {
        $meeting = $this->meeting('ANALYZING');

        $this->assertSame('ANALYZING', $meeting->fresh()->status);

        $this->service->persist($meeting, $this->validResult());

        $this->assertSame('COMPLETED', $meeting->fresh()->status);
    }

    public function test_result_belongs_to_the_correct_meeting_of_two(): void
    {
        $meetingA = $this->meeting('ANALYZING');
        $meetingB = $this->meeting('ANALYZING');

        $analysis = $this->service->persist($meetingA, $this->validResult());

        $this->assertSame($meetingA->id, $analysis->meeting_id);
        $this->assertDatabaseCount('analyses', 1);
        $this->assertNull($meetingB->fresh()->analysis);
        $this->assertSame('ANALYZING', $meetingB->fresh()->status);
        $this->assertSame('COMPLETED', $meetingA->fresh()->status);
    }

    public function test_persistence_failure_cannot_leave_false_completed_state(): void
    {
        $meeting = $this->meeting('ANALYZING');

        Analysis::creating(static function (): void {
            throw new \RuntimeException('forced persistence failure');
        });

        try {
            $this->service->persist($meeting, $this->validResult());
            $this->fail('Expected persistence to fail.');
        } catch (\Throwable $e) {
            // expected: transaction must roll back
        }

        $this->assertDatabaseCount('analyses', 0);
        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'status' => 'ANALYZING',
        ]);
    }

    public function test_second_analysis_for_same_meeting_is_blocked(): void
    {
        $meeting = $this->meeting('ANALYZING');
        $result = $this->validResult();

        $first = $this->service->persist($meeting, $result);
        $this->assertNotNull($first->id);

        try {
            $this->service->persist($meeting, $result);
            $this->fail('Expected duplicate analysis to be rejected.');
        } catch (QueryException $e) {
            // unique meeting_id constraint enforced
        }

        $this->assertDatabaseCount('analyses', 1);
        $this->assertSame('COMPLETED', $meeting->fresh()->status);
    }
}
