<?php

namespace Tests\Livewire;

use App\Livewire\AnalyzeForm;
use App\Models\Meeting;
use App\Rules\SafeText;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyzeFormTest extends TestCase
{
    private function models(): array
    {
        return config('models');
    }

    public function test_component_renders_initially(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->assertSee('Start Analysis');
    }

    public function test_character_count_updates_reactively(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', 'abc')
            ->assertSee('3')
            ->assertSee('50,000');
    }

    public function test_character_count_clears_on_empty(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', 'abc')
            ->set('meeting_text', '')
            ->assertSee('0');
    }

    public function test_model_switch_updates_max_chars(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('model', 'chatgpt-terra')
            ->assertSee('100,000');
    }

    public function test_model_switch_updates_max_tokens(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('model', 'chatgpt-terra')
            ->assertSee('50,000 tokens');
    }

    public function test_safe_text_rejects_script_tags(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', '<script>alert("xss")</script>hello world')
            ->set('meeting_title', 'Valid Title')
            ->set('meeting_date', '2025-01-15')
            ->call('validation')
            ->assertHasErrors(['meeting_text' => SafeText::class]);
    }

    public function test_char_limit_50001_exceeds_max_for_sol(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', str_repeat('a', 50001))
            ->set('meeting_title', 'Valid Title')
            ->set('meeting_date', '2025-01-15')
            ->call('validation')
            ->assertHasErrors(['meeting_text' => 'max']);
    }

    public function test_token_limit_80001_exceeds_max_for_sol(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('model', 'chatgpt-sol')
            ->set('meeting_text', str_repeat('a', 80001))
            ->set('meeting_title', 'Valid Title')
            ->set('meeting_date', '2025-01-15')
            ->call('validation')
            ->assertHasErrors(['meeting_text' => 'max']);
    }

    public function test_valid_input_passes_validation(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', 'This is a valid meeting transcript.')
            ->set('meeting_title', 'Q3 Planning')
            ->set('meeting_date', '2025-01-15')
            ->call('submit')
            ->assertHasNoErrors();
    }

    public function test_submit_sets_stage_to_validating(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', 'Valid transcript content.')
            ->set('meeting_title', 'Q3 Planning')
            ->set('meeting_date', '2025-01-15')
            ->call('submit')
            ->assertSee('validating')
            ->assertSee('Validating...');
    }

    public function test_save_sets_stage_to_saving(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', 'Valid transcript content.')
            ->set('meeting_title', 'Q3 Planning')
            ->set('meeting_date', '2025-01-15')
            ->call('submit')
            ->call('save')
            ->assertSee('saving')
            ->assertSee('Saving...');
    }

    public function test_button_is_disabled_during_saving(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', 'Valid transcript content.')
            ->set('meeting_title', 'Q3 Planning')
            ->set('meeting_date', '2025-01-15')
            ->call('submit')
            ->call('save')
            ->assertSeeHtml('disabled');
    }

    public function test_error_area_shows_when_validation_fails(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', '')
            ->call('submit')
            ->assertSee('error');
    }

    public function test_no_meeting_date_is_optional_and_persists_null(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', 'Valid transcript content.')
            ->set('meeting_title', 'Q3 Planning')
            ->call('validation')
            ->assertHasNoErrors()
            ->call('save');

        $meeting = Meeting::latest()->first();
        $this->assertNull($meeting->meeting_time);
    }

    public function test_past_meeting_date_is_accepted(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', 'Valid transcript content.')
            ->set('meeting_title', 'Q3 Planning')
            ->set('meeting_date', '2025-01-15')
            ->call('validation')
            ->assertHasNoErrors();
    }

    public function test_todays_meeting_date_is_accepted(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', 'Valid transcript content.')
            ->set('meeting_title', 'Q3 Planning')
            ->set('meeting_date', now()->toDateString())
            ->call('validation')
            ->assertHasNoErrors();
    }

    public function test_future_meeting_date_is_rejected(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', 'Valid transcript content.')
            ->set('meeting_title', 'Q3 Planning')
            ->set('meeting_date', now()->addDay()->toDateString())
            ->call('validation')
            ->assertHasErrors(['meeting_date' => 'before_or_equal']);
    }

    public function test_empty_meeting_date_is_normalized_and_not_required(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', 'Valid transcript content.')
            ->set('meeting_title', 'Q3 Planning')
            ->set('meeting_date', '')
            ->call('validation')
            ->assertHasNoErrors()
            ->assertSet('meeting_date', null);
    }
}
