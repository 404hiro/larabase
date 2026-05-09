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
        Schema::create('titles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        $now = now();
        $titles = [
            'デザイナー',
            'イラストレーター',
            'フォトグラファー',
            '映像クリエイター',
            'アーティスト',
            'クリエイター',
            'インフルエンサー',
            'ブロガー / ライター',
            'エンジニア',
            '開発者',
            '起業家 / 経営者',
            'フリーランス',
            'マーケター',
            'プランナー',
            'モデル',
            'タレント',
            '講師',
            'コンサルタント',
            'ショップオーナー',
            '美容',
            'ファッション',
            'その他',
        ];

        DB::table('titles')->insert(array_map(
            fn (string $title, int $index): array => [
                'name' => $title,
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $titles,
            array_keys($titles),
        ));

        Schema::table('links', function (Blueprint $table) {
            $table->foreignId('title_id')
                ->nullable()
                ->after('display_name')
                ->constrained()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropConstrainedForeignId('title_id');
        });

        Schema::dropIfExists('titles');
    }
};
