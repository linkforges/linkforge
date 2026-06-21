<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class VerifyLicense extends Command
{
    protected $signature = 'license:verify';

    protected $description = 'Re-verify the stored license with the relay. Fail-open: never blocks the site; only a definitive revoke flips the status to invalid.';

    public function handle(LicenseService $license): int
    {
        if ($license->status()['code'] === '') {
            $this->warn('No license code on file; nothing to re-check.');

            return self::SUCCESS;
        }

        $result = $license->recheck();
        $this->info('License re-check: '.$license->status()['status'].' — '.((string) ($result['message'] ?? '')));

        return self::SUCCESS;
    }
}
