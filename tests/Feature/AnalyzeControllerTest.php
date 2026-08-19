<?php

namespace Tests\Feature;

use Tests\TestCase;

class AnalyzeControllerTest extends TestCase
{
    public function test_analyze_route_returns_200(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_analyze_view_is_rendered(): void
    {
        $response = $this->get('/');

        $response->assertViewIs('analyze');
    }

    public function test_analyze_view_receives_models_config(): void
    {
        $response = $this->get('/');

        $response->assertViewHas('models', function (array $models): bool {
            return isset($models['chatgpt-sol'])
                && isset($models['chatgpt-terra'])
                && isset($models['chatgpt-luna']);
        });
    }

    public function test_page_contains_livewire_component_markup(): void
    {
        $response = $this->get('/');

        $response->assertSee('wire:id', false);
        $response->assertSee('meeting_text', false);
    }

    public function test_page_contains_model_options(): void
    {
        $response = $this->get('/');

        $response->assertSee('ChatGPT Sol');
        $response->assertSee('ChatGPT Terra');
        $response->assertSee('ChatGPT Luna');
    }
}
