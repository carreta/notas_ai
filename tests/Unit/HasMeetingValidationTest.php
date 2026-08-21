<?php

namespace Tests\Unit;

use App\Rules\SafeText;
use App\Validation\HasMeetingValidation;
use Illuminate\Validation\Rules\In;
use PHPUnit\Framework\TestCase;

class HasMeetingValidationTest extends TestCase
{
    private function makeClass(string $model = 'chatgpt-sol')
    {
        $models = $this->modelConfig();

        return new class($model, $models)
        {
            use HasMeetingValidation;

            public function __construct(public string $model, public array $models) {}

            public function getRules(array $modelConfig): array
            {
                return $this->rules($modelConfig);
            }

            public function getPreValidationRules(): array
            {
                return $this->preValidationRules();
            }

            public function getMessages(): array
            {
                return $this->messages();
            }
        };
    }

    private function modelConfig(): array
    {
        return [
            'chatgpt-sol' => ['label' => 'ChatGPT Sol', 'max_chars' => 50_000, 'max_tokens' => 20_000],
            'chatgpt-terra' => ['label' => 'ChatGPT Terra', 'max_chars' => 100_000, 'max_tokens' => 50_000],
            'chatgpt-luna' => ['label' => 'ChatGPT Luna', 'max_chars' => 200_000, 'max_tokens' => 100_000],
        ];
    }

    public function test_rules_returns_all_required_fields(): void
    {
        $class = $this->makeClass();
        $rules = $class->getRules($this->modelConfig());

        $this->assertArrayHasKey('meeting_text', $rules);
        $this->assertArrayHasKey('model', $rules);
        $this->assertArrayHasKey('meeting_title', $rules);
        $this->assertArrayHasKey('meeting_date', $rules);
    }

    public function test_meeting_text_includes_required_string_and_safe_text(): void
    {
        $class = $this->makeClass();
        $rules = $class->getRules($this->modelConfig());

        $this->assertContains('required', $rules['meeting_text']);
        $this->assertContains('string', $rules['meeting_text']);

        $safeTextRules = array_filter($rules['meeting_text'], fn ($r) => $r instanceof SafeText);
        $this->assertCount(1, $safeTextRules);
    }

    public function test_meeting_text_pre_validation_includes_max_chars_for_chatgpt_sol(): void
    {
        $class = $this->makeClass('chatgpt-sol');
        $preRules = $class->getPreValidationRules();

        $this->assertContains('max:50000', $preRules['meeting_text']);
    }

    public function test_meeting_text_pre_validation_includes_max_chars_for_chatgpt_terra(): void
    {
        $class = $this->makeClass('chatgpt-terra');
        $preRules = $class->getPreValidationRules();

        $this->assertContains('max:100000', $preRules['meeting_text']);
    }

    public function test_meeting_text_pre_validation_includes_max_chars_for_chatgpt_luna(): void
    {
        $class = $this->makeClass('chatgpt-luna');
        $preRules = $class->getPreValidationRules();

        $this->assertContains('max:200000', $preRules['meeting_text']);
    }

    public function test_model_rule_uses_rule_in(): void
    {
        $class = $this->makeClass();
        $rules = $class->getRules($this->modelConfig());

        $ruleIn = array_filter($rules['model'], fn ($r) => $r instanceof In);

        $this->assertCount(1, $ruleIn);
    }

    public function test_model_rule_includes_required(): void
    {
        $class = $this->makeClass();
        $rules = $class->getRules($this->modelConfig());

        $this->assertContains('required', $rules['model']);
    }

    public function test_title_rules_contain_required_string_max_and_safe_text(): void
    {
        $class = $this->makeClass();
        $rules = $class->getRules($this->modelConfig());

        $this->assertContains('required', $rules['meeting_title']);
        $this->assertContains('string', $rules['meeting_title']);
        $this->assertContains('max:255', $rules['meeting_title']);

        $safeTextRules = array_filter($rules['meeting_title'], fn ($r) => $r instanceof SafeText);
        $this->assertCount(1, $safeTextRules);
    }

    public function test_date_rules_contain_nullable_date_and_before_or_equal(): void
    {
        $class = $this->makeClass();
        $rules = $class->getRules($this->modelConfig());

        $this->assertContains('nullable', $rules['meeting_date']);
        $this->assertContains('date', $rules['meeting_date']);
        $this->assertContains('before_or_equal:today', $rules['meeting_date']);
    }

    public function test_messages_returns_expected_keys(): void
    {
        $class = $this->makeClass();
        $messages = $class->getMessages();

        $this->assertArrayHasKey('meeting_text.max', $messages);
        $this->assertArrayHasKey('meeting_text.required', $messages);
        $this->assertArrayHasKey('model.in', $messages);
        $this->assertArrayHasKey('meeting_title.required', $messages);
    }
}
