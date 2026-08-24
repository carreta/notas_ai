<?php

namespace Tests\Feature;

use App\Livewire\DebugConsole;
use App\Models\AiMetric;
use App\Models\Analysis;
use App\Models\AnalysisLog;
use App\Models\Meeting;
use App\Models\PromptTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DebugControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_route_returns_200(): void
    {
        $response = $this->get('/debug');

        $response->assertStatus(200);
    }

    public function test_debug_view_is_rendered(): void
    {
        $response = $this->get('/debug');

        $response->assertViewIs('debug');
    }

    public function test_debug_page_lists_meetings_from_the_database(): void
    {
        Meeting::create([
            'title' => 'Database-backed meeting',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
            'meeting_time' => '2026-08-21 14:30:00',
        ]);

        $response = $this->get('/debug');

        $response->assertSee('Database-backed meeting');
        $response->assertSee('0');
    }

    public function test_debug_page_contains_nav_links(): void
    {
        $response = $this->get('/debug');

        $response->assertSee(route('home'));
        $response->assertSee(route('history'));
        $response->assertSee(route('debug'));
    }

    public function test_debug_console_selects_a_meeting_then_its_log_and_displays_the_prompt(): void
    {
        $prompt = PromptTemplate::create([
            'version' => 'debug-prompt-v1',
            'system_prompt' => 'This is the original system prompt.',
            'json_schema' => ['type' => 'object'],
            'is_active' => true,
        ]);

        $meeting = Meeting::create([
            'title' => 'Log inspection meeting',
            'raw_text' => 'notes',
            'status' => 'COMPLETED',
            'meeting_time' => '2026-08-21 14:30:00',
        ]);

        $log = AnalysisLog::create([
            'meeting_id' => $meeting->id,
            'status' => 'COMPLETED',
            'provider' => 'openai',
            'model' => 'gpt-5.6-luna',
            'prompt_version' => $prompt->version,
            'started_at' => '2026-08-21 14:30:00',
            'completed_at' => '2026-08-21 14:30:04',
        ]);

        $analysis = Analysis::create([
            'meeting_id' => $meeting->id,
            'result' => [],
            'analysis_metadata' => $log->id,
        ]);

        AiMetric::create([
            'analysis_id' => $analysis->id,
            'prompt_tokens' => 100,
            'completion_tokens' => 25,
            'total_tokens' => 125,
            'duration_ms' => 4000,
        ]);

        Livewire::test(DebugConsole::class)
            ->assertSee('Log inspection meeting')
            ->call('selectMeeting', $meeting->id)
            ->assertSet('selectedMeetingId', $meeting->id)
            ->assertSee('gpt-5.6-luna')
            ->assertSee('125')
            ->call('selectLog', $log->id)
            ->assertSet('selectedLogId', $log->id)
            ->assertSee('Log Details')
            ->call('setTab', 'prompt')
            ->assertSee('This is the original system prompt.')
            ->call('closeModal')
            ->assertSet('selectedLogId', null)
            ->assertDontSee('Log Details')
            ->call('backToMeetings')
            ->assertSet('selectedMeetingId', null)
            ->assertSee('Debug Console');
    }
}
