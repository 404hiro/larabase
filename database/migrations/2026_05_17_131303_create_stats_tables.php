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
        Schema::create('link_view_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('link_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('view_count')->default(0);
            $table->timestamps();

            $table->unique(['link_id', 'date']);
            $table->index(['link_id', 'date']);
        });

        Schema::create('widget_click_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('link_id')->constrained('links')->cascadeOnDelete();
            $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('click_count')->default(0);
            $table->timestamps();

            $table->unique(['widget_id', 'date']);
            $table->index(['link_id', 'date']);
            $table->index(['widget_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('widget_click_daily_stats');
        Schema::dropIfExists('link_view_daily_stats');
    }
};
