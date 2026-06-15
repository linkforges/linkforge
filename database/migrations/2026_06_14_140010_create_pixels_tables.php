<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pixels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->enum('provider', ['facebook', 'google', 'tiktok', 'linkedin', 'twitter', 'pinterest', 'quora']);
            $table->string('pixel_id', 120);
            $table->string('name', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('link_pixel', function (Blueprint $table) {
            $table->unsignedBigInteger('link_id');
            $table->unsignedBigInteger('pixel_id');
            $table->primary(['link_id', 'pixel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_pixel');
        Schema::dropIfExists('pixels');
    }
};
