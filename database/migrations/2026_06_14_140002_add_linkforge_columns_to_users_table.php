<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['user', 'admin'])->default('user')->after('password');
            $table->enum('status', ['active', 'suspended', 'pending'])->default('active')->after('role');
            $table->unsignedBigInteger('plan_id')->nullable()->after('status')->index();
            $table->unsignedInteger('ai_credits')->default(0)->after('plan_id');
            $table->json('settings')->nullable()->after('ai_credits');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status', 'plan_id', 'ai_credits', 'settings']);
        });
    }
};
