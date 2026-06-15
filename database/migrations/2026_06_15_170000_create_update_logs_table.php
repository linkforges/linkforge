<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | History of applied in-app updates (version, notes, who, when).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('update_logs', function (Blueprint $table) {
            $table->id();
            $table->string('version', 40);
            $table->string('name')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('update_logs');
    }
};
