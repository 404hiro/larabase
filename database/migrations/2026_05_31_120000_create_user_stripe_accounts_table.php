<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_stripe_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_account_id')->unique();
            $table->boolean('details_submitted')->default(false);
            $table->boolean('charges_enabled')->default(false);
            $table->boolean('payouts_enabled')->default(false);
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_stripe_accounts');
    }
};
