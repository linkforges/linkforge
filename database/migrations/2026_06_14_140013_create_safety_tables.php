<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_scans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('link_id')->index();
            $table->string('provider', 40);
            $table->enum('verdict', ['clean', 'suspicious', 'malicious', 'error']);
            $table->unsignedTinyInteger('score')->nullable();
            $table->json('raw')->nullable();
            $table->timestamp('scanned_at')->nullable();
        });

        Schema::create('abuse_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('link_id')->nullable()->index();
            $table->string('reporter_email', 190)->nullable();
            $table->string('reason');
            $table->enum('status', ['open', 'reviewing', 'actioned', 'dismissed'])->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abuse_reports');
        Schema::dropIfExists('safety_scans');
    }
};
