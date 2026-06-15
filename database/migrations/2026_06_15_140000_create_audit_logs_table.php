<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Audit trail of admin actions: who did what, to which record, and when.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index(); // the admin who acted
            $table->string('action', 60)->index();                     // e.g. user.delete, plan.update
            $table->string('target_type', 60)->nullable();             // class basename
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
