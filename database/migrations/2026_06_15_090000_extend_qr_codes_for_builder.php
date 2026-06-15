<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Upgrades qr_codes from a per-link design store into a first-class, standalone
 | QR builder: named codes, a content type, static/dynamic mode, the raw content
 | fields (data), and a full styling config (design). content/format become
 | flexible so the client-side builder can export any format.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            $table->string('name')->nullable()->after('user_id');
            $table->string('type')->default('link')->after('name');
            $table->boolean('is_dynamic')->default(false)->after('type');
            $table->json('data')->nullable()->after('content');
        });

        Schema::table('qr_codes', function (Blueprint $table) {
            $table->text('content')->nullable()->change();
            $table->string('format')->default('png')->change();
        });
    }

    public function down(): void
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            $table->dropColumn(['name', 'type', 'is_dynamic', 'data']);
        });
    }
};
