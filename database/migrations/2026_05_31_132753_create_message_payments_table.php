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
        Schema::create('message_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('message_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('payer_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('creator_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->unique();

            $table->integer('amount');
            $table->integer('platform_fee');
            $table->string('currency', 3)->default('jpy');

            $table->string('status', 32)->default('pending');
            $table->timestamp('paid_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['creator_user_id', 'status']);
            $table->index(['message_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_payments');
    }
};
