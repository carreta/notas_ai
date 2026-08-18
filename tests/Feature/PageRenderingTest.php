<?php

namespace Tests\Feature;

use Tests\TestCase;

class PageRenderingTest extends TestCase
{
    public function test_home_page_returns_200()
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('Analyze Transcript');
        $response->assertSee('Notas IA');
    }

    public function test_history_page_returns_200()
    {
        $response = $this->get(route('history'));
        $response->assertStatus(200);
        $response->assertSee('Meeting History');
        $response->assertSee('Notas IA');
    }

    public function test_debug_page_returns_200()
    {
        $response = $this->get(route('debug'));
        $response->assertStatus(200);
        $response->assertSee('Debug Console');
        $response->assertSee('Notas IA');
    }

    public function test_home_page_has_model_dropdown_with_three_options()
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('ChatGPT Sol');
        $response->assertSee('ChatGPT Terra');
        $response->assertSee('ChatGPT Luna');
    }

    public function test_home_page_has_character_counter()
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('char-count');
        $response->assertSee('char-count-value');
        $response->assertSee('char-count-max');
    }

    public function test_history_page_has_dummy_meetings()
    {
        $response = $this->get(route('history'));
        $response->assertStatus(200);
        $response->assertSee('Q4 Strategy Planning Kickoff');
        $response->assertSee('Product Roadmap Sync');
        $response->assertSee('Client Discovery Call');
        $response->assertSee('Engineering Standup');
    }

    public function test_debug_page_has_dummy_meetings()
    {
        $response = $this->get(route('debug'));
        $response->assertStatus(200);
        $response->assertSee('Q4 Strategy Planning Kickoff');
        $response->assertSee('Product Roadmap Sync');
        $response->assertSee('Client Discovery Call');
        $response->assertSee('Engineering Standup');
    }

    public function test_nav_active_state_on_home()
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        // Home link should have active styling
        $response->assertSee('border-primary-fixed-dim');
    }

    public function test_nav_active_state_on_history()
    {
        $response = $this->get(route('history'));
        $response->assertStatus(200);
        $response->assertSee('border-primary-fixed-dim');
    }

    public function test_nav_active_state_on_debug()
    {
        $response = $this->get(route('debug'));
        $response->assertStatus(200);
        $response->assertSee('border-primary-fixed-dim');
    }

    public function test_migration_smoke_test_still_passes()
    {
        // This ensures the original MigrationSmokeTest still works
        // GET / should return 200 (now routes to home)
        $response = $this->get('/');
        $response->assertStatus(200);
    }
}
