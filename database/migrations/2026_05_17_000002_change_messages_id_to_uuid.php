<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('messages', 'uuid')) {
            return;
        }

        Schema::table('message_publications', function (Blueprint $table) {
            $table->dropForeign(['message_id']);
            $table->dropIndex(['message_id']);
            $table->dropColumn('message_id');
        });

        Schema::table('message_reports', function (Blueprint $table) {
            $table->dropForeign(['message_id']);
            $table->dropColumn('message_id');
        });

        Schema::table('messages', function (Blueprint $table) {
            DB::statement('ALTER TABLE messages DROP CONSTRAINT messages_pkey');
            $table->dropColumn('id');
            $table->dropColumn('uuid');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->uuid('id')->primary()->first();
        });

        Schema::table('message_publications', function (Blueprint $table) {
            $table->foreignUuid('message_id')->after('id')->constrained()->cascadeOnDelete();
            $table->index(['message_id']);
        });

        Schema::table('message_reports', function (Blueprint $table) {
            $table->foreignUuid('message_id')->after('id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
