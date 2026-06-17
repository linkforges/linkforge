<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    public function test_developer_hub_renders_both_tabs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('developer.index'))
            ->assertOk()
            ->assertSee('Developer')
            ->assertSee('API tokens')
            ->assertSee('Webhooks');
    }

    public function test_tokens_tab_shows_the_create_form_for_an_api_plan(): void
    {
        $pro = User::factory()->create(['plan_id' => Plan::where('slug', 'pro')->value('id')]);

        $this->actingAs($pro)->get(route('developer.index'))
            ->assertOk()
            ->assertSee('Create a token');
    }

    public function test_webhooks_tab_shows_the_endpoint_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('developer.index', ['tab' => 'webhooks']))
            ->assertOk()
            ->assertSee('Add an endpoint');
    }

    public function test_legacy_urls_redirect_into_the_hub(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/api-tokens')
            ->assertRedirect(route('developer.index', ['tab' => 'tokens']));

        $this->actingAs($user)->get('/webhooks')
            ->assertRedirect(route('developer.index', ['tab' => 'webhooks']));
    }

    public function test_developer_hub_requires_authentication(): void
    {
        $this->get(route('developer.index'))->assertRedirect(route('login'));
    }
}
