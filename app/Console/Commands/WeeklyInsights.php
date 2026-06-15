<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Ai\ClaudeClient;
use App\Services\Ai\InsightWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WeeklyInsights extends Command
{
    protected $signature = 'ai:weekly-insights';

    protected $description = 'Generate an AI weekly performance insight for each active account.';

    public function handle(ClaudeClient $claude, InsightWriter $writer): int
    {
        if (! $claude->enabled()) {
            $this->warn('AI layer not configured (ANTHROPIC_API_KEY missing). Skipping.');

            return self::SUCCESS;
        }

        // Only active accounts: those whose links saw clicks in the last 14 days.
        $activeUserIds = DB::table('links')
            ->where('last_click_at', '>=', now()->subDays(14))
            ->distinct()
            ->pluck('user_id');

        $generated = 0;

        User::whereIn('id', $activeUserIds)->each(function (User $user) use ($writer, &$generated) {
            try {
                if ($writer->generateFor($user) !== null) {
                    $generated++;
                }
            } catch (\Throwable $e) {
                $this->error("User {$user->id}: ".$e->getMessage());
            }
        });

        $this->info("Generated {$generated} weekly insight(s).");

        return self::SUCCESS;
    }
}
