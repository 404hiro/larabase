<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ユーザー作成
        $adminUser = User::factory()->create([
            'name' => '管理者',
            'google_id' => '1234567890',
        ]);

        User::factory()->create([
            'name' => 'テスト',
            'google_id' => '0987654321',
        ]);

        // ロール作成
        $adminRole = Role::create(['name' => 'admin']);

        // 社長にadminを割り当て
        $adminUser->assignRole($adminRole);

        // その他のユーザーを10人作成
        User::factory(100)->create();
    }
}
