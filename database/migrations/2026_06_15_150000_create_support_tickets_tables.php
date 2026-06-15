<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Support tickets: a customer opens a ticket, staff and the customer exchange
 | threaded messages, and a status tracks who the ball is with.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('subject', 180);
            $table->string('status', 20)->default('open')->index();   // open | answered | closed
            $table->string('priority', 10)->default('normal');         // low | normal | high
            $table->string('category', 30)->default('general');
            $table->timestamp('last_reply_at')->nullable()->index();
            $table->string('last_reply_by', 10)->default('user');      // user | admin
            $table->timestamps();
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();         // author (admin or owner)
            $table->string('author_role', 10)->default('user');        // user | admin
            $table->text('body');
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
    }
};
