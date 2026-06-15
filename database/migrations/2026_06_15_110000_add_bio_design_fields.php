<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Bio pages gain a social-links list. The richer design and page settings
 | (header layout, background, font, buttons, avatar, verified, SEO, etc.)
 | live in the existing `theme` / `settings` JSON columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->json('social_links')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->dropColumn('social_links');
        });
    }
};
