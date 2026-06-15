<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('plan_id');
            $table->string('gateway', 40);
            $table->string('gateway_subscription_id', 190)->nullable();
            $table->enum('status', ['trialing', 'active', 'past_due', 'canceled', 'expired'])->default('active')->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('renews_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('gateway', 40);
            $table->string('gateway_ref', 190)->nullable();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
    }
};
