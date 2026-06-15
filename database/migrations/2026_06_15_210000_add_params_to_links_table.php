<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | UTM / custom query parameters appended to a link's destination at redirect
 | time. Stored separately from long_url so they stay editable and the canonical
 | target URL is kept clean. Null = none.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->json('params')->nullable()->after('long_url');
        });
    }

    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn('params');
        });
    }
};
