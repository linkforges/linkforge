<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_landing_page_loads(): void
    {
        $this->get('/')->assertOk()->assertSee('LinkForge');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Welcome back');
    }

    public function test_register_page_renders(): void
    {
        $this->get('/register')->assertOk()->assertSee('Create your account');
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_can_register_and_reach_dashboard(): void
    {
        $response = $this->post('/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'forge-strong-pass-1',
            'password_confirmation' => 'forge-strong-pass-1',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);

        $this->actingAs(User::firstWhere('email', 'ada@example.com'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Welcome back');
    }

    public function test_registered_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'grace@example.com',
            'password' => bcrypt('forge-strong-pass-1'),
        ]);

        $this->post('/login', [
            'email' => 'grace@example.com',
            'password' => 'forge-strong-pass-1',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }
}
