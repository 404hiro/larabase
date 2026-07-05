<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('can migrate users table on sqlite without dropping columns through the legacy index path', function () {
    config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
    app('db')->purge('sqlite');
    app('db')->setDefaultConnection('sqlite');

    if (DB::getDriverName() !== 'sqlite') {
        $this->markTestSkipped('This test is intended for sqlite environments.');
    }

    Schema::create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password')->nullable();
        $table->string('google_id')->nullable();
    });

    DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');

    $migration = include base_path('database/migrations/2026_05_09_131054_update_users_table_for_google_only.php');
    $migration->up();

    expect(Schema::hasColumn('users', 'email'))->toBeFalse();
    expect(Schema::hasColumn('users', 'email_verified_at'))->toBeFalse();
    expect(Schema::hasColumn('users', 'password'))->toBeFalse();
    expect(Schema::hasColumn('users', 'google_id'))->toBeTrue();
});
