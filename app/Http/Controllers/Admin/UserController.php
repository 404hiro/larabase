<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::query();

        // 検索機能
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $users = $query->with('roles')
            ->orderBy('id', 'asc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(User $user): Response
    {
        $user->load('roles', 'permissions');

        return Inertia::render('admin/users/Show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user): Response
    {
        $user->load('roles', 'permissions');
        $roles = \Spatie\Permission\Models\Role::all();

        return Inertia::render('admin/users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'google_id' => $user->google_id,
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                    ];
                })->toArray(),
            ],
            'roles' => $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            })->toArray(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:2048'], // 2MB max
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        // アバター画像の処理
        if ($request->hasFile('avatar')) {
            // 古い画像を削除
            if ($user->avatar) {
                \Storage::disk('public')->delete($user->avatar);
            }

            // 新しい画像を保存
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        $user->update([
            'name' => $validated['name'],
            'avatar' => $validated['avatar'] ?? $user->avatar,
        ]);

        // ロールの更新 - Laravel-permissionの正しい方法
        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        } else {
            // rolesキーが存在しない場合は全てのロールを削除
            $user->syncRoles([]);
        }

        // 更新後のロールを確認
        $user->refresh();

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'ユーザ情報が更新されました。');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'ユーザが削除されました。');
    }
}
