<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;


class DashboardController extends Controller
{
    public function index(): Response
    {
        $userStats = [
            'total' => User::count(),
            'active' => User::whereNotNull('email_verified_at')->count(),
            'new_this_month' => User::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        // ロール別ユーザ統計
        $roleStats = \Spatie\Permission\Models\Role::withCount('users')->get()->map(function ($role) {
            return [
                'name' => $role->name,
                'display_name' => $role->display_name ?? $role->name,
                'count' => $role->users_count,
            ];
        });

        // ロールなしのユーザ数
        $usersWithoutRoles = User::doesntHave('roles')->count();
        if ($usersWithoutRoles > 0) {
            $roleStats->push([
                'name' => 'no_role',
                'display_name' => 'ロールなし',
                'count' => $usersWithoutRoles,
            ]);
        }

        return Inertia::render('admin/Index', [
            'userStats' => $userStats,
            'roleStats' => $roleStats,
        ]);
    }
}
