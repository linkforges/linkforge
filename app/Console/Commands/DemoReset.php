<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Models\HelpArticle;
use App\Models\Plan;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Services\Affiliate\ReferralService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Resets the public demo to a known, attractive state: recreates the two demo
 * accounts and a rich sample dataset, and clears anything visitors changed.
 * Schedule it hourly (see routes/console.php). No-op unless demo mode is on, so
 * it can sit safely in the scheduler of any install.
 */
class DemoReset extends Command
{
    protected $signature = 'demo:reset {--force : Run even when demo mode is off}';

    protected $description = 'Reset the public demo accounts + sample data';

    public function handle(): int
    {
        if (! \App\Support\Demo::enabled() && ! $this->option('force')) {
            $this->warn('Demo mode is off; nothing to do. Use --force to seed anyway.');

            return self::SUCCESS;
        }

        $plan = Plan::where('slug', 'business')->first() ?? Plan::where('price', '>', 0)->orderByDesc('price')->first();
        $domainId = Domain::where('is_default', true)->value('id');

        $admin = User::updateOrCreate(['email' => \App\Support\Demo::ADMIN_EMAIL], [
            'name' => 'Demo Admin', 'role' => 'admin', 'status' => 'active',
            'password' => Hash::make('demo-'.Str::random(24)), 'plan_id' => $plan?->id,
        ]);
        $user = User::updateOrCreate(['email' => \App\Support\Demo::USER_EMAIL], [
            'name' => 'Demo User', 'role' => 'user', 'status' => 'active',
            'password' => Hash::make('demo-'.Str::random(24)), 'plan_id' => $plan?->id,
        ]);

        // Wipe what the demo account (and visitors) may have changed.
        $user->links()->delete();
        $user->campaigns()->delete();
        $user->pixels()->delete();
        $user->commissions()->delete();
        $user->payoutRequests()->delete();

        // Campaigns + a varied set of links (campaigns, tags, a deep link, click counts).
        $spring = $user->campaigns()->create(['name' => 'Spring sale', 'color' => 'emerald']);
        $news = $user->campaigns()->create(['name' => 'Newsletter', 'color' => 'blue']);

        $links = [
            ['alias' => 'spring', 'long_url' => 'https://example.com/spring-collection', 'title' => 'Spring landing', 'campaign_id' => $spring->id, 'tags' => ['sale', 'q2'], 'clicks' => 1820],
            ['alias' => 'launch', 'long_url' => 'https://example.com/product-launch', 'title' => 'Product launch', 'campaign_id' => $spring->id, 'tags' => ['launch'], 'clicks' => 940],
            ['alias' => 'news', 'long_url' => 'https://example.com/newsletter', 'title' => 'Newsletter signup', 'campaign_id' => $news->id, 'tags' => ['email'], 'clicks' => 610],
            ['alias' => 'getapp', 'long_url' => 'https://example.com/get-the-app', 'title' => 'Get the app (deep link)', 'tags' => ['mobile'], 'clicks' => 430, 'meta' => ['deep_link' => ['ios' => 'myapp://home', 'android' => 'myapp://home']]],
            ['alias' => 'docs', 'long_url' => 'https://example.com/docs', 'title' => 'Documentation', 'clicks' => 275],
            ['alias' => 'promo', 'long_url' => 'https://example.com/promo', 'title' => 'Promo code', 'tags' => ['sale'], 'clicks' => 158],
        ];
        foreach ($links as $l) {
            $user->links()->create(array_merge(['domain_id' => $domainId, 'type' => 'direct', 'safety_status' => 'safe', 'is_active' => true], $l));
        }

        $user->pixels()->create(['provider' => 'facebook', 'pixel_id' => '1234567890', 'name' => 'Meta Pixel']);
        $user->pixels()->create(['provider' => 'google', 'pixel_id' => 'G-DEMO12345', 'name' => 'GA4']);

        // Showcase settings + a populated affiliate dashboard.
        Setting::putMany([
            'guest_shorten' => '1',
            'affiliate_enabled' => '1', 'affiliate_commission_type' => 'percent',
            'affiliate_commission_value' => '25', 'affiliate_min_payout' => '50',
        ]);
        app(ReferralService::class)->codeFor($user);
        $user->forceFill(['referral_clicks' => 132])->save();
        $user->commissions()->create(['amount' => 24.50, 'currency' => 'USD', 'status' => 'approved', 'note' => 'Sample commission']);
        $user->commissions()->create(['amount' => 12.00, 'currency' => 'USD', 'status' => 'pending', 'note' => 'Sample commission']);

        $this->seedContent($admin->id);

        Cache::flush();
        $this->info('Demo reset complete. Admin: '.\App\Support\Demo::ADMIN_EMAIL.' · User: '.\App\Support\Demo::USER_EMAIL);

        return self::SUCCESS;
    }

    /** Reset the blog + help center to a known sample set. */
    private function seedContent(int $authorId): void
    {
        Post::query()->delete();
        Post::create(['author_id' => $authorId, 'title' => 'Why you should own your URL shortener', 'slug' => 'own-your-shortener', 'status' => 'published', 'published_at' => now()->subDays(3), 'excerpt' => 'Stop renting your most important marketing asset.', 'body' => "## Own your links\n\nYour short links are infrastructure. Self-host and keep **100%** of your data.\n\n- No per-click fees\n- Custom domains\n- Unlimited links"]);
        Post::create(['author_id' => $authorId, 'title' => '5 link-marketing tips that actually work', 'slug' => 'link-marketing-tips', 'status' => 'published', 'published_at' => now()->subDay(), 'excerpt' => 'Practical tactics to get more clicks.', 'body' => "## Track everything\n\nUTM tags + pixels turn every link into a data source.\n\n## Brand your domain\n\nBranded links get more clicks."]);

        HelpArticle::query()->delete();
        HelpArticle::create(['category' => 'Getting started', 'title' => 'Creating your first short link', 'slug' => 'first-short-link', 'status' => 'published', 'sort' => 1, 'excerpt' => 'Shorten a URL in three clicks.', 'body' => "## Steps\n\n1. Go to **Links → New link**\n2. Paste your URL\n3. Click **Create**"]);
        HelpArticle::create(['category' => 'Getting started', 'title' => 'Adding a custom domain', 'slug' => 'custom-domain', 'status' => 'published', 'sort' => 2, 'excerpt' => 'Use your own branded domain.', 'body' => "## Add a domain\n\nGo to **Custom domains**, add your host and point a CNAME."]);
        HelpArticle::create(['category' => 'Analytics', 'title' => 'Understanding your click data', 'slug' => 'click-data', 'status' => 'published', 'sort' => 1, 'excerpt' => 'Read the dashboard like a pro.', 'body' => "## Metrics\n\nClicks, unique visitors, countries, devices and referrers — in real time."]);
    }
}
