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
        Schema::create('message_publications', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('message_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('link_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->text('reply_body')
                ->nullable();

            $table->string('image_url', 2048)
                ->nullable();

            $table->string('image_disk', 64)
                ->nullable();

            $table->string('template', 64)
                ->default('simple');

            $table->string('visibility', 32)
                ->default('public');

            $table->timestamps();

            $table->index(['link_id', 'created_at']);
            $table->index(['message_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_publications');
    }
};
