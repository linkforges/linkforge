<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Lead capture for bio pages: newsletter subscribers and contact-form messages.
 | The page owner reviews and exports these from the Leads dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bio_subscribers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bio_page_id')->index();
            $table->string('email', 190);
            $table->string('name', 120)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
            $table->unique(['bio_page_id', 'email']); // one signup per email per page
        });

        Schema::create('bio_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bio_page_id')->index();
            $table->string('name', 120)->nullable();
            $table->string('email', 190)->nullable();
            $table->text('message');
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_messages');
        Schema::dropIfExists('bio_subscribers');
    }
};
