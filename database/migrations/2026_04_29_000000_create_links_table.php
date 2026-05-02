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
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('display_name');
            $table->text('bio')->nullable();
            $table->string('avatar_url', 2048)->nullable();
            $table->json('theme_config')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'is_published']);
        });

        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('link');
            $table->text('content')->nullable();
            $table->string('thumbnail_url', 2048)->nullable();
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->integer('w')->default(1);
            $table->integer('h')->default(1);
            $table->integer('x_mobile')->default(0);
            $table->integer('y_mobile')->default(0);
            $table->integer('w_mobile')->default(1);
            $table->integer('h_mobile')->default(1);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['link_id', 'y', 'x']);
            $table->index(['link_id', 'y_mobile', 'x_mobile']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('widgets');
        Schema::dropIfExists('links');
    }
};
