<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clicks', function (Blueprint $table) {
            $table->boolean('is_duplicate')->default(false)->after('is_bot');
            $table->index('is_duplicate');
        });
    }

    public function down(): void
    {
        Schema::table('clicks', function (Blueprint $table) {
            $table->dropIndex(['is_duplicate']);
            $table->dropColumn('is_duplicate');
        });
    }
};
