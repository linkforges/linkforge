<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_a_long_description_without_overflowing(): void
    {
        // Regression: the description column was VARCHAR(191); long release notes
        // (e.g. the in-app updater's audit entry) overflowed with SQLSTATE 22001.
        $long = str_repeat('a long audit description segment ', 30); // ~960 chars

        AuditLog::record('app.update', $long);

        $row = AuditLog::firstOrFail();
        $this->assertSame('app.update', $row->action);
        $this->assertGreaterThan(191, strlen((string) $row->description));
    }
}
