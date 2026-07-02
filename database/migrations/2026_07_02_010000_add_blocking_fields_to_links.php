<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->boolean('block_bots')->default(false)->after('is_active');
            $table->json('blocked_referrers')->nullable()->after('block_bots');
            $table->index('block_bots');
        });
    }

    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropIndex(['block_bots']);
            $table->dropColumn(['block_bots', 'blocked_referrers']);
        });
    }
};
