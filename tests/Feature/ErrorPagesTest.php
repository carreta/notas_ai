<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_404_page_returns_404_status(): void
    {
        $response = $this->get('/nonexistent-page');

        $response->assertStatus(404);
    }

    public function test_404_page_uses_correct_view(): void
    {
        $response = $this->get('/nonexistent-page');

        $response->assertStatus(404);
        $response->assertSee('Notas IA', false);
        $response->assertSee('Page Not Found');
    }

    public function test_404_page_extends_layout(): void
    {
        $response = $this->get('/nonexistent-page');

        $response->assertSee('<html', false);
        $response->assertSee('</html>', false);
    }

    public function test_404_page_contains_nav_with_home_link(): void
    {
        $response = $this->get('/nonexistent-page');

        $response->assertSee(route('home'));
        $response->assertSee(route('history'));
        $response->assertSee(route('debug'));
    }

    public function test_404_page_contains_error_content(): void
    {
        $response = $this->get('/nonexistent-page');

        $response->assertSee('404');
        $response->assertSee('Page Not Found');
    }
}
