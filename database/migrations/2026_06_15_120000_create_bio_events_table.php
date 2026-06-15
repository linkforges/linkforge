<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Bio page analytics events: a page view, or a click on a specific block.
 | Dimensions mirror the link clicks table so the analytics surface is uniform.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bio_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bio_page_id')->index();
            $table->unsignedBigInteger('block_id')->nullable()->index();
            $table->string('type', 10); // view | click
            $table->string('ip_hash', 64)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('device', 20)->nullable();
            $table->string('browser', 40)->nullable();
            $table->string('referer_host')->nullable();
            $table->boolean('is_bot')->default(false);
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_events');
    }
};
