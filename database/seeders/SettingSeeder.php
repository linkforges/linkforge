<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'site_name' => config('linkforge.name'),
            'site_tagline' => config('linkforge.tagline'),
            'allow_registration' => '1',
            'default_link_type' => 'direct',
            'clicks_retention_days' => '90',
            'reserved_aliases' => json_encode([
                'admin', 'api', 'app', 'login', 'register', 'dashboard', 'install',
                'settings', 'www', 'assets', 'build', 'report', 'bio', 'qr',
            ]),
        ];

        foreach ($defaults as $key => $value) {
            Setting::put($key, $value);
        }
    }
}
