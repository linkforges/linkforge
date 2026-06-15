<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Widen stat_dimension.dimension from a fixed enum to a short string so it can
 | hold the new "city" rollup (and any future dimension) without an enum change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stat_dimension', function (Blueprint $table) {
            $table->string('dimension', 24)->change();
        });
    }

    public function down(): void
    {
        Schema::table('stat_dimension', function (Blueprint $table) {
            $table->enum('dimension', ['country', 'device', 'os', 'browser', 'referer', 'language'])->change();
        });
    }
};
