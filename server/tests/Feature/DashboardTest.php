<?php

namespace Tests\Feature;

use App\Models\Excursion;
use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Dashboard отображает счётчики.
     */
    public function test_dashboard_shows_stats(): void
    {
        $user = User::factory()->create();

        News::factory()->create();
        Excursion::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Новости');
        $response->assertSee('Экскурсии');
        $response->assertSee('Статьи');
        $response->assertSee('Экспозиция');
        $response->assertSee('Архив');
    }

    /**
     * Dashboard требует авторизации.
     */
    public function test_dashboard_requires_auth(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }
}
