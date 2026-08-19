<?php

namespace Tests\Feature;

use Tests\TestCase;

class DebugControllerTest extends TestCase
{
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

    public function test_debug_page_contains_dummy_data(): void
    {
        $response = $this->get('/debug');

        $response->assertSee('Q4 Strategy Planning Kickoff');
        $response->assertSee('Engineering All-Hands');
    }

    public function test_debug_page_contains_nav_links(): void
    {
        $response = $this->get('/debug');

        $response->assertSee(route('home'));
        $response->assertSee(route('history'));
        $response->assertSee(route('debug'));
    }
}
