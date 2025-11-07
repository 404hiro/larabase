<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::query();

        // 検索機能
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // ステータスフィルター
        if ($request->filled('status')) {
            $status = $request->get('status');
            if ($status === 'active') {
                $query->whereNotNull('email_verified_at');
            } elseif ($status === 'inactive') {
                $query->whereNull('email_verified_at');
            }
        }

        $users = $query->with('roles')
                      ->orderBy('id', 'asc')
                      ->paginate(15)
                      ->withQueryString();

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(User $user): Response
    {
        $user->load('roles', 'permissions');
        
        return Inertia::render('admin/users/Show', [
            'user' => $user,
        ]);
    }

    public function create(): Response
    {
        $roles = \Spatie\Permission\Models\Role::all();
        
        return Inertia::render('admin/users/Create', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'email_verified' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'account' => $validated['account'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'email_verified_at' => $validated['email_verified'] ?? false ? now() : null,
        ]);

        // ロールの設定 - Laravel-permissionの正しい方法
        if (isset($validated['roles']) && !empty($validated['roles'])) {
            $user->assignRole($validated['roles']);
        }

        return redirect()->route('admin.users.index')
                        ->with('success', 'ユーザが正常に作成されました。');
    }

    public function edit(User $user): Response
    {
        $user->load('roles', 'permissions');
        $roles = \Spatie\Permission\Models\Role::all();
        return Inertia::render('admin/users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'account' => $user->account,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
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
            'account' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'max:2048'], // 2MB max
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'email_verified' => ['boolean'],
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
            'account' => $validated['account'],
            'email' => $validated['email'],
            'avatar' => $validated['avatar'] ?? $user->avatar,
            'email_verified_at' => $validated['email_verified'] ?? false ? now() : null,
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