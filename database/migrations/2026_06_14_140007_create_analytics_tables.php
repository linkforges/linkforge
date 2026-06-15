<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Raw click events. Append-only hot path; pruned after retention window.
        Schema::create('clicks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('link_id');
            $table->char('ip_hash', 64)->nullable();        // sha256(ip+salt), never raw IP (GDPR)
            $table->char('country', 2)->nullable();
            $table->string('region', 80)->nullable();
            $table->string('city', 120)->nullable();
            $table->enum('device', ['desktop', 'mobile', 'tablet', 'bot', 'other'])->nullable();
            $table->string('os', 40)->nullable();
            $table->string('browser', 40)->nullable();
            $table->string('referer_host', 190)->nullable();
            $table->string('language', 10)->nullable();
            $table->boolean('is_bot')->default(false);
            $table->json('utm')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['link_id', 'created_at']);
            $table->index('created_at');
        });

        // Per-day rollup (cron aggregates clicks -> here). Dashboards read this, never raw clicks.
        Schema::create('stat_daily', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('link_id');
            $table->date('day');
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('uniques')->default(0);
            $table->unsignedInteger('bots')->default(0);

            $table->unique(['link_id', 'day']);
        });

        // Per-day, per-dimension rollup (country/device/browser/...).
        Schema::create('stat_dimension', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('link_id');
            $table->date('day');
            $table->enum('dimension', ['country', 'device', 'os', 'browser', 'referer', 'language']);
            $table->string('label', 190);
            $table->unsignedInteger('clicks')->default(0);

            $table->unique(['link_id', 'day', 'dimension', 'label'], 'stat_dim_unique');
            $table->index(['link_id', 'dimension']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stat_dimension');
        Schema::dropIfExists('stat_daily');
        Schema::dropIfExists('clicks');
    }
};
