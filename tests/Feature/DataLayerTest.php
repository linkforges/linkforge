<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\StatDaily;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_plans_seed_with_typed_limits_and_features(): void
    {
        $this->seed(PlanSeeder::class);

        $this->assertSame(4, Plan::count());

        $free = Plan::where('slug', 'free')->first();
        $this->assertSame(25, $free->limit('max_links'));
        $this->assertFalse($free->allows('custom_domains'));

        $business = Plan::where('slug', 'business')->first();
        $this->assertNull($business->limit('max_links')); // unlimited
        $this->assertTrue($business->allows('white_label'));
    }

    public function test_link_relationships_rules_and_json_casts(): void
    {
        $user = User::factory()->create();
        $domain = Domain::create(['host' => 'lnk.test', 'is_default' => true, 'status' => 'active']);

        $link = Link::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'alias' => 'abc',
            'long_url' => 'https://example.com/landing',
            'type' => 'direct',
            'meta' => ['utm' => ['source' => 'newsletter']],
        ]);

        $link->rules()->create([
            'type' => 'geo',
            'match_value' => ['countries' => ['US', 'CA']],
            'target_url' => 'https://example.com/na',
        ]);

        $link->clickEvents()->create([
            'country' => 'US',
            'device' => 'mobile',
            'is_bot' => false,
            'created_at' => now(),
        ]);

        $link->refresh();

        $this->assertSame('lnk.test', $link->domain->host);
        $this->assertSame('lnk.test/abc', $link->shortUrl());
        $this->assertSame(['source' => 'newsletter'], $link->meta['utm']);
        $this->assertCount(1, $link->rules);
        $this->assertSame(['US', 'CA'], $link->rules->first()->match_value['countries']);
        $this->assertSame(1, $link->clickEvents()->count());
        $this->assertTrue($user->links->contains($link));
    }

    public function test_rollup_and_settings_models(): void
    {
        $user = User::factory()->create();
        $domain = Domain::create(['host' => 'r.test', 'status' => 'active']);
        $link = Link::create([
            'user_id' => $user->id, 'domain_id' => $domain->id,
            'alias' => 'xyz', 'long_url' => 'https://example.com', 'type' => 'direct',
        ]);

        StatDaily::create(['link_id' => $link->id, 'day' => today(), 'clicks' => 12, 'uniques' => 9]);
        $this->assertSame(12, $link->dailyStats()->sum('clicks'));

        Setting::put('site_name', 'Acme Links');
        $this->assertSame('Acme Links', Setting::get('site_name'));
        $this->assertSame('fallback', Setting::get('missing_key', 'fallback'));
    }
}
