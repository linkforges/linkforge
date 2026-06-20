<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code')->nullable()->unique()->after('role');
            $table->unsignedBigInteger('referred_by')->nullable()->index()->after('referral_code');
            $table->unsignedInteger('referral_clicks')->default(0)->after('referred_by');
        });

        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('method', 40)->default('paypal');
            $table->string('details', 255)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
        });

        Schema::create('referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_id')->index();
            $table->unsignedBigInteger('referred_user_id')->nullable()->index();
            $table->unsignedBigInteger('payment_id')->nullable()->unique(); // idempotency per conversion
            $table->unsignedBigInteger('payout_request_id')->nullable()->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('pending')->index(); // pending|approved|paid|rejected
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_commissions');
        Schema::dropIfExists('payout_requests');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['referral_code', 'referred_by', 'referral_clicks']);
        });
    }
};
