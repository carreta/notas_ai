<?php

namespace Tests\Feature;

use App\Livewire\AnalyzeForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MeetingDateCalendarTest extends TestCase
{
    use RefreshDatabase;

    private function models(): array
    {
        return config('models');
    }

    public function test_meeting_date_is_required(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', 'This is a valid meeting transcript.')
            ->set('meeting_title', 'Q3 Planning')
            ->call('validation')
            ->assertHasErrors(['meeting_date' => 'required'])
            ->assertSee('Meeting date is required.');
    }

    public function test_today_is_accepted(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', 'This is a valid meeting transcript.')
            ->set('meeting_title', 'Q3 Planning')
            ->set('meeting_date', now()->toDateString())
            ->call('validation')
            ->assertHasNoErrors();
    }

    public function test_past_date_is_accepted(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', 'This is a valid meeting transcript.')
            ->set('meeting_title', 'Q3 Planning')
            ->set('meeting_date', now()->subDays(10)->toDateString())
            ->call('validation')
            ->assertHasNoErrors();
    }

    public function test_tomorrow_is_rejected_server_side(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', 'This is a valid meeting transcript.')
            ->set('meeting_title', 'Q3 Planning')
            ->set('meeting_date', now()->addDay()->toDateString())
            ->call('validation')
            ->assertHasErrors(['meeting_date' => 'before_or_equal'])
            ->assertSee('The meeting date cannot be in the future.');
    }

    public function test_rendered_calendar_uses_today_as_maximum(): void
    {
        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->assertSee('data-max-date')
            ->assertSee('Meeting Date')
            ->assertSee('Select meeting date')
            ->assertSee('aria-required');
    }

    public function test_future_dates_are_disabled_by_calendar_logic(): void
    {
        $component = Livewire::test(AnalyzeForm::class, ['models' => $this->models()]);

        $this->assertTrue($component->instance()->isDateDisabled(now()->addDay()->toDateString()));
        $this->assertFalse($component->instance()->isDateDisabled(now()->toDateString()));
        $this->assertFalse($component->instance()->isDateDisabled(now()->subDays(5)->toDateString()));
    }

    public function test_selected_date_updates_livewire_property(): void
    {
        $iso = now()->subDays(3)->toDateString();

        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_date', $iso)
            ->assertSet('meeting_date', $iso);
    }

    public function test_date_persists_into_meeting_time(): void
    {
        $iso = now()->subDays(3)->toDateString();

        Livewire::test(AnalyzeForm::class, ['models' => $this->models()])
            ->set('meeting_text', 'This is a valid meeting transcript.')
            ->set('meeting_title', 'Q3 Planning')
            ->set('meeting_date', $iso)
            ->call('validation')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('meetings', ['meeting_time' => $iso]);
    }
}
