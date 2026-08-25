<?php

namespace Tests\Feature;

use App\AI\AnalysisOrchestrator;
use App\AI\AnalysisOutcome;
use App\Livewire\AnalysisDetailModal;
use App\Models\Analysis;
use App\Models\AnalysisLog;
use App\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class AnalysisDetailModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_modal_opens_meeting_analysis_by_meeting_id(): void
    {
        $meeting = Meeting::create([
            'title' => 'Shared Modal Meeting',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);
        Analysis::create([
            'meeting_id' => $meeting->id,
            'result' => [
                'summary' => 'Unique Shared Summary',
                'decisions' => [],
                'action_items' => [],
                'open_questions' => [],
            ],
        ]);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $meeting->id)
            ->assertSet('meetingId', $meeting->id)
            ->assertSee('Shared Modal Meeting')
            ->assertSee('Unique Shared Summary');
    }

    public function test_modal_shows_correct_meeting_not_another(): void
    {
        $a = Meeting::create([
            'title' => 'Meeting A',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);
        $b = Meeting::create([
            'title' => 'Meeting B',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);

        Analysis::create([
            'meeting_id' => $a->id,
            'result' => ['summary' => 'Summary A', 'decisions' => [], 'action_items' => [], 'open_questions' => []],
        ]);
        Analysis::create([
            'meeting_id' => $b->id,
            'result' => ['summary' => 'Summary B', 'decisions' => [], 'action_items' => [], 'open_questions' => []],
        ]);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $a->id)
            ->assertSee('Summary A')
            ->assertDontSee('Summary B');
    }

    public function test_modal_handles_meeting_without_analysis(): void
    {
        $meeting = Meeting::create([
            'title' => 'No Analysis Meeting',
            'raw_text' => 'notes',
            'status' => 'DRAFT',
        ]);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $meeting->id)
            ->assertSet('meetingId', $meeting->id)
            ->assertSee('No Analysis Meeting')
            ->assertSee('No analysis data available.');
    }

    public function test_modal_renders_summary_with_empty_collections_without_errors(): void
    {
        $meeting = Meeting::create([
            'title' => 'Empty Collections Meeting',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);
        Analysis::create([
            'meeting_id' => $meeting->id,
            'result' => [
                'summary' => 'A valid summary with empty collections',
                'decisions' => [],
                'action_items' => [],
                'open_questions' => [],
            ],
        ]);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $meeting->id)
            ->assertSee('A valid summary with empty collections')
            ->assertSee('Empty Collections Meeting')
            // Summary is visible; the empty collections do not collapse the
            // whole result into the generic "No analysis data available" state.
            ->assertDontSee('No analysis data available.');
    }

    public function test_analyzing_meeting_without_analysis_shows_no_fabricated_result(): void
    {
        $meeting = Meeting::create([
            'title' => 'Analyzing Meeting',
            'raw_text' => 'notes',
            'status' => 'ANALYZING',
        ]);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $meeting->id)
            ->assertSee('Analyzing Meeting')
            ->assertSee('No analysis data available.');
    }

    public function test_modal_switches_from_a_to_b_without_stale_state(): void
    {
        $a = Meeting::create([
            'title' => 'Meeting A',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);
        $b = Meeting::create([
            'title' => 'Meeting B',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);

        Analysis::create([
            'meeting_id' => $a->id,
            'result' => ['summary' => 'Summary A', 'decisions' => [], 'action_items' => [], 'open_questions' => []],
        ]);
        Analysis::create([
            'meeting_id' => $b->id,
            'result' => ['summary' => 'Summary B', 'decisions' => [], 'action_items' => [], 'open_questions' => []],
        ]);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $a->id)
            ->assertSee('Summary A')
            ->assertDontSee('Summary B')
            ->dispatch('openMeetingModal', $b->id)
            ->assertSee('Summary B')
            ->assertDontSee('Summary A');
    }

    public function test_modal_renders_due_date_text_fallback_and_provenance(): void
    {
        $meeting = Meeting::create([
            'title' => 'Provenance Meeting',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);
        Analysis::create([
            'meeting_id' => $meeting->id,
            'result' => [
                'summary' => 'Summary',
                'decisions' => [],
                'action_items' => [
                    [
                        'task' => 'Follow up',
                        'owner' => null,
                        'priority' => 'HIGH',
                        'priority_source' => 'INFERRED',
                        'due_date_text' => 'next Friday',
                        'due_date' => null,
                        'due_date_source' => 'UNRESOLVED',
                    ],
                ],
                'open_questions' => [],
            ],
        ]);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $meeting->id)
            ->assertSee('Follow up')
            // due_date is null but due_date_text must still be shown.
            ->assertSee('Due:')
            ->assertSee('next Friday')
            // Provenance labels are displayed subtly.
            ->assertSee('Inferred')
            ->assertSee('Unresolved')
            // Owner is null, so no broken "Owner:" value is rendered.
            ->assertDontSee('Owner:');
    }

    private function failedMeetingWithAnalysis(): Analysis
    {
        $meeting = Meeting::create([
            'title' => 'Re-analyzable Meeting',
            'raw_text' => 'Transcript to re-analyze.',
            'status' => 'FAILED',
            'meeting_time' => '2026-08-19',
        ]);

        return Analysis::create([
            'meeting_id' => $meeting->id,
            'result' => [
                'summary' => 'Old summary',
                'decisions' => [],
                'action_items' => [],
                'open_questions' => [],
            ],
        ]);
    }

    private function bindOrchestrator($stub): void
    {
        $this->app->bind(AnalysisOrchestrator::class, fn () => $stub);
    }

    /**
     * A meeting whose previous attempt FAILED. Crucially, the orchestrator's
     * fail() path writes an AnalysisLog but NOT an Analysis row, so a FAILED
     * meeting has no Analysis record at all.
     */
    private function failedMeetingWithoutAnalysis(): Meeting
    {
        return Meeting::create([
            'title' => 'Failed Meeting',
            'raw_text' => 'Transcript of a failed meeting.',
            'status' => 'FAILED',
            'meeting_time' => '2026-08-19',
        ]);
    }

    private function enableFakeProvider(string $outcome): void
    {
        config([
            'ai.driver' => 'fake',
            'ai.fake_outcome' => $outcome,
            'ai.provider' => 'openai',
            'ai.model' => 'gpt-5.6-luna',
            'ai.schema_version' => 'meeting-analysis-v1',
        ]);
    }

    public function test_failed_meeting_reanalysis_runs_pipeline_and_completes(): void
    {
        $meeting = $this->failedMeetingWithoutAnalysis();

        $this->enableFakeProvider('valid');

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $meeting->id)
            ->call('reAnalyze')
            ->call('executeReAnalyze')
            ->assertSet('reAnalyzeStage', 'completed')
            ->assertSet('reAnalyzeLabel', 'Re-analysis completed successfully');

        // The real pipeline ran through the provider and created a fresh Analysis.
        $this->assertSame('COMPLETED', $meeting->fresh()->status);
        $this->assertDatabaseCount('analyses', 1);
        $this->assertDatabaseHas('analysis_logs', [
            'meeting_id' => $meeting->id,
            'status' => 'COMPLETED',
        ]);
    }

    public function test_completed_meeting_reanalysis_still_works(): void
    {
        $analysis = $this->failedMeetingWithAnalysis();
        $meeting = $analysis->meeting;
        $meeting->update(['status' => 'COMPLETED']);

        $this->enableFakeProvider('valid');

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('reAnalyze')
            ->call('executeReAnalyze')
            ->assertSet('reAnalyzeStage', 'completed');

        $this->assertSame('COMPLETED', $meeting->fresh()->status);
        // The prior Analysis is updated, not duplicated.
        $this->assertDatabaseCount('analyses', 1);
    }

    public function test_failed_meeting_reanalysis_invalid_response_is_retryable(): void
    {
        $meeting = $this->failedMeetingWithoutAnalysis();

        $this->enableFakeProvider('invalid_response');

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $meeting->id)
            ->call('reAnalyze')
            ->call('executeReAnalyze')
            ->assertSet('reAnalyzeStage', 'error')
            ->assertSet('reAnalyzeErrorCategory', 'AI_INVALID_RESPONSE');

        // Provider was contacted (the pipeline ran) and recorded a FAILED log.
        $this->assertDatabaseHas('analysis_logs', [
            'meeting_id' => $meeting->id,
            'status' => 'FAILED',
            'error_category' => 'AI_INVALID_RESPONSE',
        ]);
        $this->assertSame(1, AnalysisLog::where('meeting_id', $meeting->id)->count());

        // Retry: the failed analysis must be re-attemptable from History.
        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $meeting->id)
            ->call('reAnalyze')
            ->call('executeReAnalyze')
            ->assertSet('reAnalyzeStage', 'error')
            ->assertSet('reAnalyzeErrorCategory', 'AI_INVALID_RESPONSE');

        // A second attempt reached the provider again (no silent block).
        $this->assertSame(2, AnalysisLog::where('meeting_id', $meeting->id)->count());
    }

    public function test_reanalyze_click_is_always_visible_and_acknowledged(): void
    {
        $analysis = $this->failedMeetingWithAnalysis();

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('reAnalyze')
            ->assertSet('reAnalyzeStage', 'analyzing')
            ->assertSet('reAnalyzeLabel', 'Analyzing transcript...');
    }

    public function test_reanalyze_success_shows_completed_and_creates_no_duplicate(): void
    {
        $analysis = $this->failedMeetingWithAnalysis();

        $stub = new class($analysis)
        {
            public function __construct(private $analysis) {}

            public function reAnalyze(Analysis $analysis, ?string $provider = null, ?string $modelKey = null): AnalysisOutcome
            {
                return new AnalysisOutcome(true, $this->analysis, null, '');
            }
        };
        $this->bindOrchestrator($stub);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('reAnalyze')
            ->call('executeReAnalyze')
            ->assertSet('reAnalyzeStage', 'completed')
            ->assertSet('reAnalyzeLabel', 'Re-analysis completed successfully')
            ->assertSet('reAnalyzeProgress', 100);

        // No duplicate analysis rows are created (only one for the meeting).
        $this->assertDatabaseCount('analyses', 1);
    }

    public function test_reanalyze_invalid_response_surfaces_category_code(): void
    {
        $analysis = $this->failedMeetingWithAnalysis();

        $stub = new class
        {
            public function reAnalyze(Analysis $analysis, ?string $provider = null, ?string $modelKey = null): AnalysisOutcome
            {
                return new AnalysisOutcome(false, null, 'AI_INVALID_RESPONSE', 'The analysis could not be processed safely.');
            }
        };
        $this->bindOrchestrator($stub);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('reAnalyze')
            ->call('executeReAnalyze')
            ->assertSet('reAnalyzeStage', 'error')
            ->assertSet('reAnalyzeErrorCategory', 'AI_INVALID_RESPONSE')
            // In the testing environment the technical code is exposed.
            ->assertSet('reAnalyzeError', "Re-analysis failed: AI_INVALID_RESPONSE\nThe analysis could not be processed safely.");
    }

    public function test_reanalyze_throws_before_provider_shows_before_provider_message(): void
    {
        $analysis = $this->failedMeetingWithAnalysis();

        $stub = new class
        {
            public function reAnalyze(Analysis $analysis, ?string $provider = null, ?string $modelKey = null): AnalysisOutcome
            {
                throw new LogicException('Analysis cannot be re-analyzed without its meeting.');
            }
        };
        $this->bindOrchestrator($stub);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('reAnalyze')
            ->call('executeReAnalyze')
            ->assertSet('reAnalyzeStage', 'error')
            ->assertSet('reAnalyzeErrorCategory', 'INTERNAL_ERROR')
            ->assertSet('reAnalyzeError', 'Re-analysis failed before contacting the AI provider.');
    }

    public function test_reanalyze_with_missing_analysis_fails_visibly_not_silently(): void
    {
        // A modal whose analysis was never loaded (state 5: click, no request).
        Livewire::test(AnalysisDetailModal::class)
            ->assertSet('analysisId', null)
            ->call('reAnalyze')
            ->assertSet('reAnalyzeStage', 'error')
            ->assertSet('reAnalyzeErrorCategory', 'INTERNAL_ERROR')
            ->assertSet('reAnalyzeError', 'Re-analysis failed before contacting the AI provider.');
    }

    /**
     * Build a COMPLETED meeting that already has a distinctive successful
     * Analysis and a matching COMPLETED AnalysisLog (attempt 1). This mirrors a
     * real "previously completed" meeting so the failure path can be asserted
     * against a known good baseline (attempt 2 = FAILED).
     */
    private function completedMeetingWithAnalysisAndLogs(array $result): Analysis
    {
        $meeting = Meeting::create([
            'title' => 'Completed Re-analyzable Meeting',
            'raw_text' => 'Transcript to re-analyze.',
            'status' => 'COMPLETED',
            'meeting_time' => '2026-08-19',
        ]);

        $analysis = Analysis::create([
            'meeting_id' => $meeting->id,
            'result' => $result,
        ]);

        AnalysisLog::create([
            'meeting_id' => $meeting->id,
            'status' => 'COMPLETED',
            'provider' => 'openai',
            'model' => 'gpt-5.6-luna',
            'prompt_version' => 'meeting-analysis-v1',
            'error_category' => null,
            'error_message' => null,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);

        return $analysis;
    }

    /**
     * CASE A — FAILED + no Analysis → retry success → new Analysis → COMPLETED.
     */
    public function test_case_a_failed_without_analysis_retry_success_creates_analysis(): void
    {
        $meeting = $this->failedMeetingWithoutAnalysis();
        $this->enableFakeProvider('valid');

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $meeting->id)
            ->call('reAnalyze')
            ->call('executeReAnalyze')
            ->assertSet('reAnalyzeStage', 'completed');

        $this->assertSame('COMPLETED', $meeting->fresh()->status);
        $this->assertDatabaseCount('analyses', 1);
        $this->assertDatabaseHas('analysis_logs', [
            'meeting_id' => $meeting->id,
            'status' => 'COMPLETED',
        ]);
    }

    /**
     * CASE B — FAILED + no Analysis → retry failure → no Analysis → FAILED log.
     */
    public function test_case_b_failed_without_analysis_retry_failure_keeps_no_analysis(): void
    {
        $meeting = $this->failedMeetingWithoutAnalysis();
        $this->enableFakeProvider('invalid_response');

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openMeetingModal', $meeting->id)
            ->call('reAnalyze')
            ->call('executeReAnalyze')
            ->assertSet('reAnalyzeStage', 'error')
            ->assertSet('reAnalyzeErrorCategory', 'AI_INVALID_RESPONSE');

        $this->assertSame('FAILED', $meeting->fresh()->status);
        $this->assertDatabaseCount('analyses', 0);
        $this->assertDatabaseHas('analysis_logs', [
            'meeting_id' => $meeting->id,
            'status' => 'FAILED',
            'error_category' => 'AI_INVALID_RESPONSE',
        ]);
    }

    /**
     * CASE C — COMPLETED + existing Analysis → retry success → Analysis updated,
     * new COMPLETED log, Meeting COMPLETED.
     */
    public function test_case_c_completed_with_analysis_retry_success_updates_analysis(): void
    {
        $originalResult = [
            'summary' => 'Original successful analysis',
            'decisions' => [['text' => 'Keep PostgreSQL']],
            'action_items' => [],
            'open_questions' => [],
        ];
        $analysis = $this->completedMeetingWithAnalysisAndLogs($originalResult);
        $meeting = $analysis->meeting;
        $this->enableFakeProvider('valid');

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('reAnalyze')
            ->call('executeReAnalyze')
            ->assertSet('reAnalyzeStage', 'completed');

        $this->assertSame('COMPLETED', $meeting->fresh()->status);
        // The single Analysis row is updated, never duplicated.
        $this->assertDatabaseCount('analyses', 1);
        // A fresh COMPLETED log is appended for the retry attempt.
        $this->assertSame(2, AnalysisLog::where('meeting_id', $meeting->id)->count());
        $this->assertDatabaseHas('analysis_logs', [
            'meeting_id' => $meeting->id,
            'status' => 'COMPLETED',
        ]);
        // The previous successful summary is replaced with the new one.
        $this->assertNotSame(
            $originalResult['summary'],
            $analysis->fresh()->result['summary'] ?? null
        );
    }

    /**
     * CASE D (regression) — COMPLETED + existing Analysis → retry FAILS →
     * previous Analysis.result is EXACTLY preserved, a new FAILED log is added,
     * and the Meeting status reflects the failed latest attempt. No data loss.
     */
    public function test_case_d_completed_with_analysis_retry_failure_preserves_result(): void
    {
        $originalResult = [
            'summary' => 'Original successful analysis',
            'decisions' => [
                ['text' => 'Keep PostgreSQL'],
            ],
            'action_items' => [],
            'open_questions' => [],
        ];
        $analysis = $this->completedMeetingWithAnalysisAndLogs($originalResult);
        $meeting = $analysis->meeting;

        $logsBefore = AnalysisLog::where('meeting_id', $meeting->id)->count();
        $this->assertSame(1, $logsBefore);

        $this->enableFakeProvider('invalid_response');

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('reAnalyze')
            ->call('executeReAnalyze')
            ->assertSet('reAnalyzeStage', 'error')
            ->assertSet('reAnalyzeErrorCategory', 'AI_INVALID_RESPONSE');

        // The previous successful Analysis row still exists...
        $this->assertDatabaseHas('analyses', ['id' => $analysis->id]);

        // ...and its JSON result is byte-for-byte unchanged (no data loss).
        $this->assertSame($originalResult, $analysis->fresh()->result);

        // Meeting reflects the failed latest attempt, not a false COMPLETED.
        $this->assertSame('FAILED', $meeting->fresh()->status);

        // A new FAILED log is added alongside the old successful log; nothing
        // is overwritten.
        $this->assertSame(2, AnalysisLog::where('meeting_id', $meeting->id)->count());
        $this->assertDatabaseHas('analysis_logs', [
            'meeting_id' => $meeting->id,
            'status' => 'COMPLETED',
        ]);
        $this->assertDatabaseHas('analysis_logs', [
            'meeting_id' => $meeting->id,
            'status' => 'FAILED',
            'error_category' => 'AI_INVALID_RESPONSE',
        ]);
        // No raw exception text leaks into the stored log.
        $failedLog = AnalysisLog::where('meeting_id', $meeting->id)
            ->where('status', 'FAILED')
            ->first();
        $this->assertNull($failedLog->error_message);
    }

    /**
     * UI (Case D) — after a failed re-analysis of a previously COMPLETED meeting
     * the modal still shows the safe error AND the previous valid result; it
     * must NOT collapse to "No analysis data available.".
     */
    public function test_case_d_ui_shows_preserved_result_and_safe_error(): void
    {
        $originalResult = [
            'summary' => 'Original successful analysis',
            'decisions' => [['text' => 'Keep PostgreSQL']],
            'action_items' => [],
            'open_questions' => [],
        ];
        $analysis = $this->completedMeetingWithAnalysisAndLogs($originalResult);
        $this->enableFakeProvider('invalid_response');

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('reAnalyze')
            ->call('executeReAnalyze')
            ->assertSet('reAnalyzeStage', 'error')
            // The safe error category and message are surfaced to the user.
            ->assertSet('reAnalyzeErrorCategory', 'AI_INVALID_RESPONSE')
            ->assertSet('reAnalyzeError', "Re-analysis failed: AI_INVALID_RESPONSE\nThe analysis could not be processed safely.")
            // The previous valid summary is still rendered in the modal.
            ->assertSee('Original successful analysis')
            ->assertSee('Keep PostgreSQL')
            // Crucially, the generic empty-state must not appear.
            ->assertDontSee('No analysis data available.');
    }

    // ---------------------------------------------------------------------
    // Manual analysis editing (human correction)
    // ---------------------------------------------------------------------

    private function sampleResult(): array
    {
        return [
            'summary' => 'Sample summary for editing.',
            'decisions' => [['text' => 'Adopt PostgreSQL']],
            'action_items' => [
                [
                    'task' => 'Update documentation',
                    'owner' => 'Daniel',
                    'priority' => 'HIGH',
                    'priority_source' => 'INFERRED',
                    'due_date_text' => null,
                    'due_date' => null,
                    'due_date_source' => null,
                ],
            ],
            'open_questions' => [['text' => 'Should we migrate?']],
        ];
    }

    private function makeAnalysis(array $result, ?string $metadata = null): Analysis
    {
        $meeting = Meeting::create([
            'title' => 'Editable Meeting',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
        ]);

        return Analysis::create([
            'meeting_id' => $meeting->id,
            'result' => $result,
            'analysis_metadata' => $metadata,
        ]);
    }

    public function test_edit_button_appears_for_valid_analysis(): void
    {
        $analysis = $this->makeAnalysis($this->sampleResult());

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->assertSee('Edit')
            ->assertDontSee('Save Changes');
    }

    public function test_edit_mode_loads_current_result(): void
    {
        $analysis = $this->makeAnalysis($this->sampleResult());

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->assertSet('editing', true)
            ->assertSet('editableResult.summary', 'Sample summary for editing.')
            ->assertSet('editableResult.action_items.0.owner', 'Daniel');
    }

    public function test_owner_can_be_changed(): void
    {
        $analysis = $this->makeAnalysis($this->sampleResult());

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->set('editableResult.action_items.0.owner', 'Emma')
            ->call('saveEdits')
            ->assertHasNoErrors();

        $this->assertSame('Emma', $analysis->fresh()->result['action_items'][0]['owner']);
    }

    public function test_priority_can_be_changed_and_source_cleared(): void
    {
        $analysis = $this->makeAnalysis($this->sampleResult());

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->set('editableResult.action_items.0.priority', 'MEDIUM')
            ->call('saveEdits')
            ->assertHasNoErrors();

        $item = $analysis->fresh()->result['action_items'][0];
        $this->assertSame('MEDIUM', $item['priority']);
        // Provenance must not falsely remain INFERRED after a manual change.
        $this->assertNull($item['priority_source']);
    }

    public function test_invalid_priority_is_rejected(): void
    {
        $analysis = $this->makeAnalysis($this->sampleResult());

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->set('editableResult.action_items.0.priority', 'URGENT')
            ->call('saveEdits')
            ->assertHasErrors(['editableResult.action_items.0.priority']);

        // The original value is preserved (no partial persist).
        $this->assertSame('HIGH', $analysis->fresh()->result['action_items'][0]['priority']);
    }

    public function test_task_text_can_be_edited(): void
    {
        $analysis = $this->makeAnalysis($this->sampleResult());

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->set('editableResult.action_items.0.task', 'Rewrite the runbook')
            ->call('saveEdits')
            ->assertHasNoErrors();

        $this->assertSame('Rewrite the runbook', $analysis->fresh()->result['action_items'][0]['task']);
    }

    public function test_decision_text_can_be_edited(): void
    {
        $analysis = $this->makeAnalysis($this->sampleResult());

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->set('editableResult.decisions.0.text', 'Adopt MySQL instead')
            ->call('saveEdits')
            ->assertHasNoErrors();

        $this->assertSame('Adopt MySQL instead', $analysis->fresh()->result['decisions'][0]['text']);
    }

    public function test_open_question_text_can_be_edited(): void
    {
        $analysis = $this->makeAnalysis($this->sampleResult());

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->set('editableResult.open_questions.0.text', 'Why migrate now?')
            ->call('saveEdits')
            ->assertHasNoErrors();

        $this->assertSame('Why migrate now?', $analysis->fresh()->result['open_questions'][0]['text']);
    }

    public function test_summary_can_be_edited(): void
    {
        $analysis = $this->makeAnalysis($this->sampleResult());

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->set('editableResult.summary', 'Corrected summary')
            ->call('saveEdits')
            ->assertHasNoErrors();

        $this->assertSame('Corrected summary', $analysis->fresh()->result['summary']);
    }

    public function test_due_date_can_be_changed(): void
    {
        $analysis = $this->makeAnalysis($this->sampleResult());

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->set('editableResult.action_items.0.due_date', '2026-09-15')
            ->call('saveEdits')
            ->assertHasNoErrors();

        $item = $analysis->fresh()->result['action_items'][0];
        $this->assertSame('2026-09-15', $item['due_date']);
        // Manual edit must not leave a false due-date provenance/source.
        $this->assertNull($item['due_date_source']);
        $this->assertNull($item['due_date_text']);
    }

    public function test_invalid_due_date_provenance_is_rejected(): void
    {
        $result = $this->sampleResult();
        $result['action_items'][0]['due_date'] = '2026-08-30';
        $result['action_items'][0]['due_date_source'] = 'UNRESOLVED';
        $result['action_items'][0]['due_date_text'] = 'next Friday';
        $analysis = $this->makeAnalysis($result);

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->call('saveEdits')
            ->assertHasErrors(['editableResult.action_items.0.due_date_source']);

        // Nothing was persisted for the rejected edit.
        $this->assertSame('UNRESOLVED', $analysis->fresh()->result['action_items'][0]['due_date_source']);
    }

    public function test_save_updates_result_and_exits_edit_mode(): void
    {
        $analysis = $this->makeAnalysis($this->sampleResult());

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->set('editableResult.summary', 'Persisted summary')
            ->call('saveEdits')
            ->assertSet('editing', false)
            ->assertHasNoErrors();

        $this->assertSame('Persisted summary', $analysis->fresh()->result['summary']);
    }

    public function test_cancel_does_not_modify_result(): void
    {
        $analysis = $this->makeAnalysis($this->sampleResult());

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->set('editableResult.summary', 'CHANGED BUT CANCELLED')
            ->call('cancelEditing')
            ->assertSet('editing', false);

        $this->assertSame('Sample summary for editing.', $analysis->fresh()->result['summary']);
    }

    public function test_provider_is_not_called_during_manual_edit(): void
    {
        $calls = 0;
        $this->app->bind(AnalysisOrchestrator::class, function () use (&$calls) {
            return new class($calls)
            {
                public function __construct(private int &$calls) {}

                public function analyze(...$args): never
                {
                    $this->calls++;
                    throw new LogicException('provider must not be called on manual edit');
                }

                public function reAnalyze(...$args): never
                {
                    $this->calls++;
                    throw new LogicException('provider must not be called on manual edit');
                }
            };
        });

        $analysis = $this->makeAnalysis($this->sampleResult());

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->set('editableResult.action_items.0.owner', 'Emma')
            ->call('saveEdits')
            ->assertHasNoErrors();

        $this->assertSame(0, $calls);
    }

    public function test_no_new_analysis_log_created_for_editing(): void
    {
        $analysis = $this->makeAnalysis($this->sampleResult());

        $before = AnalysisLog::where('meeting_id', $analysis->meeting_id)->count();

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->set('editableResult.action_items.0.owner', 'Emma')
            ->call('saveEdits');

        $after = AnalysisLog::where('meeting_id', $analysis->meeting_id)->count();
        $this->assertSame($before, $after);
    }

    public function test_existing_ai_metadata_unchanged_after_edit(): void
    {
        $analysis = $this->makeAnalysis($this->sampleResult());
        // A real AnalysisLog id acts as the analysis_metadata FK reference.
        $log = AnalysisLog::create([
            'meeting_id' => $analysis->meeting_id,
            'status' => 'COMPLETED',
            'provider' => 'openai',
            'model' => 'gpt-5.6-luna',
            'prompt_version' => 'meeting-analysis-v1',
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);
        $analysis->update(['analysis_metadata' => $log->id]);
        $expected = $log->id;

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->set('editableResult.summary', 'Edited summary')
            ->call('saveEdits')
            ->assertHasNoErrors();

        $this->assertSame($expected, $analysis->fresh()->analysis_metadata);
    }

    public function test_empty_required_summary_and_task_rejected(): void
    {
        $analysis = $this->makeAnalysis($this->sampleResult());

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->set('editableResult.summary', '')
            ->call('saveEdits')
            ->assertHasErrors(['editableResult.summary']);

        // Task is also required.
        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->set('editableResult.action_items.0.task', '')
            ->call('saveEdits')
            ->assertHasErrors(['editableResult.action_items.0.task']);
    }

    public function test_empty_owner_persists_as_null(): void
    {
        $analysis = $this->makeAnalysis($this->sampleResult());

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->set('editableResult.action_items.0.owner', '')
            ->call('saveEdits')
            ->assertHasNoErrors();

        $this->assertNull($analysis->fresh()->result['action_items'][0]['owner']);
    }

    public function test_modal_shows_updated_result_after_save(): void
    {
        $analysis = $this->makeAnalysis($this->sampleResult());

        Livewire::test(AnalysisDetailModal::class)
            ->dispatch('openAnalysisModal', $analysis->id)
            ->call('startEditing')
            ->set('editableResult.action_items.0.owner', 'Emma')
            ->call('saveEdits')
            ->assertSee('Emma')
            ->assertDontSee('Daniel');
    }
}
