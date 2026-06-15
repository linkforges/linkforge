<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneClicks extends Command
{
    protected $signature = 'clicks:prune';

    protected $description = 'Delete raw click events older than the retention window. Rollups are kept forever.';

    public function handle(): int
    {
        $days = (int) Setting::get('clicks_retention_days', 90);
        $cursor = (int) Setting::get('clicks_rollup_cursor', 0);
        $cutoff = now()->subDays($days);

        // Only delete clicks that are both past retention AND already rolled up.
        $deleted = DB::table('clicks')
            ->where('created_at', '<', $cutoff)
            ->where('id', '<=', $cursor)
            ->delete();

        $this->info("Pruned {$deleted} click(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
