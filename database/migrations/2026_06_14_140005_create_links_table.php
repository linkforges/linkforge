<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->unsignedBigInteger('domain_id')->index();
            $table->string('alias', 190);
            $table->text('long_url');
            $table->string('title')->nullable();
            $table->enum('type', ['direct', 'frame', 'splash', 'overlay', 'cta'])->default('direct');
            $table->string('password')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('click_limit')->nullable();
            $table->unsignedBigInteger('clicks')->default(0);
            $table->boolean('is_active')->default(true);
            $table->enum('safety_status', ['pending', 'safe', 'flagged', 'blocked'])->default('pending');
            $table->unsignedTinyInteger('safety_score')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('qr_id')->nullable();
            $table->timestamp('last_click_at')->nullable();
            $table->timestamps();

            $table->unique(['domain_id', 'alias']);
            $table->index('safety_status');
            $table->index(['is_active', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
