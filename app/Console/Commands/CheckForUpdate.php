<?php

namespace App\Console\Commands;

use App\Services\Update\RemoteUpdate;
use Illuminate\Console\Command;

class CheckForUpdate extends Command
{
    protected $signature = 'update:check';

    protected $description = 'Check the LinkForge update server for a new release (sets the in-app badge; never downloads or applies).';

    public function handle(RemoteUpdate $remote): int
    {
        if (! config('update.auto_check')) {
            $this->info('Automatic update checks are disabled.');

            return self::SUCCESS;
        }
        if (! $remote->configured()) {
            $this->warn('No license or update channel configured; skipping.');

            return self::SUCCESS;
        }

        $res = $remote->check();

        if (! empty($res['error'])) {
            $this->warn('Update check failed: '.$res['error']);

            return self::SUCCESS; // never fail the scheduler over a transient check
        }

        $this->info($res['available']
            ? 'Update available: v'.((string) ($res['release']['version'] ?? '?'))
            : 'Up to date.');

        return self::SUCCESS;
    }
}
