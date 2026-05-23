<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('message_publications');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way migration for dropping message_publications table
    }
};
