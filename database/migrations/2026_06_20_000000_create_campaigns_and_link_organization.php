<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name');
            $table->string('color', 20)->nullable();
            $table->timestamps();
        });

        Schema::table('links', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->nullable()->index()->after('domain_id');
            $table->json('tags')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn(['campaign_id', 'tags']);
        });
        Schema::dropIfExists('campaigns');
    }
};
