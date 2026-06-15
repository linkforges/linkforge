<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Widen pixels.provider from a fixed enum to a short string so new retargeting
 | providers (Bing, Snapchat, Reddit, GTM, ...) can be added without an enum
 | change. The controller validates the allowed set.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pixels', function (Blueprint $table) {
            $table->string('provider', 24)->change();
        });
    }

    public function down(): void
    {
        Schema::table('pixels', function (Blueprint $table) {
            $table->enum('provider', ['facebook', 'google', 'tiktok', 'linkedin', 'twitter', 'pinterest', 'quora'])->change();
        });
    }
};
