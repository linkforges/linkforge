<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('link_id')->index();
            $table->enum('type', ['geo', 'device', 'os', 'language', 'time', 'rotation']);
            $table->json('match_value')->nullable();
            $table->text('target_url');
            $table->unsignedSmallInteger('weight')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_rules');
    }
};
