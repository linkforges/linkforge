<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // description was VARCHAR(191) (defaultStringLength) — too short for some
        // audit entries (e.g. release notes). Widen to TEXT.
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
        });
    }
};
