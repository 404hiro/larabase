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
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignId('link_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('sender_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('body');

            $table->string('sender_mode', 32)
                ->default('anonymous');

            $table->string('sender_display_name', 255)
                ->nullable();

            $table->string('status', 32)
                ->default('safe');

            $table->boolean('is_public')
                ->default(false);

            $table->timestamp('published_at')
                ->nullable();

            $table->timestamp('read_at')
                ->nullable();

            $table->json('metadata')
                ->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['link_id', 'created_at']);
            $table->index(['link_id', 'is_public']);
            $table->index(['sender_user_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
