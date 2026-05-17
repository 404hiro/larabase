<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add UUID column to links
        Schema::table('links', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        // 2. Fill UUIDs
        DB::table('links')->get()->each(function ($link) {
            DB::table('links')->where('id', $link->id)->update(['uuid' => (string) Str::uuid()]);
        });

        Schema::table('links', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });

        // 3. Update foreign keys in other tables
        $tables = [
            'widgets' => 'link_id',
            'messages' => 'link_id',
            'message_publications' => 'link_id',
            'message_blocks' => 'link_id',
        ];

        foreach ($tables as $tableName => $column) {
            Schema::table($tableName, function (Blueprint $table) use ($column) {
                $table->uuid('link_uuid')->nullable()->after($column);
            });

            // Update with loop for PostgreSQL compatibility
            DB::table('links')->get()->each(function ($link) use ($tableName, $column) {
                DB::table($tableName)
                    ->where($column, $link->id)
                    ->update(['link_uuid' => $link->uuid]);
            });

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $column) {
                // Determine the correct foreign key name
                $foreignKey = "{$tableName}_{$column}_foreign";
                $table->dropForeign($foreignKey);
                $table->dropColumn($column);
                $table->renameColumn('link_uuid', $column);
            });

            // Re-add foreign key constraint
            Schema::table($tableName, function (Blueprint $table) use ($column) {
                $table->foreign($column)->references('uuid')->on('links')->onDelete('cascade');
            });
        }

        // 4. Finally, drop the old auto-increment ID and make uuid the primary key
        Schema::table('links', function (Blueprint $table) {
            DB::statement('ALTER TABLE links DROP CONSTRAINT links_pkey');
            $table->dropColumn('id');
            $table->primary('uuid');
            $table->renameColumn('uuid', 'id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
