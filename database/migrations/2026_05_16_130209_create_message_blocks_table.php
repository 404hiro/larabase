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
        Schema::create('message_blocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('link_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('blocked_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('reason', 255)
                ->nullable();

            $table->timestamps();

            $table->unique(['link_id', 'blocked_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_blocks');
    }
};
