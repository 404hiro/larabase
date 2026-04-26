<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role; 
use Spatie\Permission\Models\Permission; 
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         // ユーザー作成
        $adminUser = User::factory()->create([
            'account' => 'admin',
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'account' => 'test',
            'name' => 'テスト',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            ]);


        // ロール作成
        $adminRole = Role::create(['name' => 'admin']);

        // 社長にadminを割り当て
        $adminUser->assignRole($adminRole);

        // その他のユーザーを10人作成
        User::factory(100)->create();
    }
}
