<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Reusable QR design presets. A template stores only the styling (design JSON),
 | so it can be applied to any new QR code in the builder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name');
            $table->json('design')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_templates');
    }
};
