<?php

namespace App\Console\Commands;

use App\Jobs\ScanLink;
use App\Models\Link;
use Illuminate\Console\Command;

class RescanLinks extends Command
{
    protected $signature = 'safety:rescan {--limit=50}';

    protected $description = 'Queue a batch of links for re-scanning against the threat feeds.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $ids = Link::where('is_active', true)
            ->orderBy('updated_at')
            ->limit($limit)
            ->pluck('id');

        foreach ($ids as $id) {
            ScanLink::dispatch((int) $id);
        }

        $this->info('Queued '.count($ids).' link(s) for rescan.');

        return self::SUCCESS;
    }
}
