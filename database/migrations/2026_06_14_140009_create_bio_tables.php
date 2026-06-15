<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bio_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('slug', 120)->unique();
            $table->string('title', 150)->nullable();
            $table->json('theme')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('bio_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bio_page_id')->index();
            $table->string('type', 40);
            $table->json('content')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_blocks');
        Schema::dropIfExists('bio_pages');
    }
};
