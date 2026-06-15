<?php

namespace Tests;

use App\Support\Installer;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The suite assumes an installed site. Tests that exercise the installer
        // (InstallerTest) remove this lock themselves to simulate a fresh upload.
        if (! Installer::isInstalled()) {
            Installer::markInstalled((string) config('linkforge.version'));
        }
    }
}
