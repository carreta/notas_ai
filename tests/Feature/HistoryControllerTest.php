<?php

namespace Tests\Feature;

use Tests\TestCase;

class HistoryControllerTest extends TestCase
{
    public function test_history_route_returns_200(): void
    {
        $response = $this->get('/history');

        $response->assertStatus(200);
    }

    public function test_history_view_is_rendered(): void
    {
        $response = $this->get('/history');

        $response->assertViewIs('history');
    }

    public function test_history_page_contains_dummy_data(): void
    {
        $response = $this->get('/history');

        $response->assertSee('Q3 Planning Session');
        $response->assertSee('Sprint Retrospective');
    }

    public function test_history_page_contains_nav_links(): void
    {
        $response = $this->get('/history');

        $response->assertSee(route('home'));
        $response->assertSee(route('history'));
        $response->assertSee(route('debug'));
    }
}
