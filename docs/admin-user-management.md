# 管理者ユーザ管理システム 仕様書

## 概要

Laravel + Vue.js (Inertia.js) + shadcn/vue を使用した管理者向けユーザ管理システム。
Laravel-permissionパッケージを使用したロールベースのアクセス制御を実装。

## 技術スタック

- **バックエンド**: Laravel 11
- **フロントエンド**: Vue 3 + TypeScript
- **UIフレームワーク**: shadcn/vue
- **状態管理**: Inertia.js
- **権限管理**: Laravel-permission (Spatie)
- **認証**: Laravel Fortify

## 機能一覧

### 1. 管理者ダッシュボード

#### 1.1 ユーザ数サマリ（ドーナツグラフ）

**場所**: `/admin`

**表示内容**:
- ロール別ユーザ数のドーナツグラフ
  - 中央に総ユーザ数を表示
  - 各ロールを色分けして表示
- 凡例でロール別の詳細情報
  - ロール名
  - ユーザ数
  - パーセンテージ
- ユーザ管理画面へのリンク（ヘッダー右上）

**データ取得**:
```php
// DashboardController::index()
- 総ユーザ数
- ロール別ユーザ数（Laravel-permissionから取得）
- ロールなしユーザ数
```

**使用コンポーネント**:
- `DonutChart.vue`: SVGベースのドーナツグラフ
- `ChartLegend.vue`: 凡例表示

### 2. ユーザ管理機能

#### 2.1 ユーザ一覧

**場所**: `/admin/users`

**表示項目**:
- ID（昇順ソート）
- 名前
- アカウント
- メールアドレス
- 権限（ロール）- バッジ表示
- ステータス（アクティブ/未認証）
- 登録日
- 操作ボタン（詳細/編集/削除）

**機能**:
- 検索機能（名前・メールアドレス）
- ステータスフィルター（すべて/アクティブ/未認証）
- ページネーション（15件/ページ）
- 新規ユーザ作成ボタン

**アクセス制御**:
- `role:admin` ミドルウェアで管理者のみアクセス可能

#### 2.2 ユーザ詳細

**場所**: `/admin/users/{id}`

**表示項目**:
- ID
- 名前
- アカウント
- メールアドレス
- ステータス
- メール認証日時
- 権限（ロール）- バッジ表示
- 直接権限（permissions）- 存在する場合のみ
- 登録日時
- 最終更新日時

**操作**:
- 編集ボタン
- 削除ボタン
- ユーザ一覧に戻るボタン

#### 2.3 ユーザ作成

**場所**: `/admin/users/create`

**入力項目**:
- 名前 *（必須）
- アカウント *（必須、ユニーク）
- メールアドレス *（必須、ユニーク）
- パスワード *（必須、最小8文字）
- 権限（複数選択可能、MultiSelect）
- メール認証済みフラグ（チェックボックス）

**バリデーション**:
```php
'name' => ['required', 'string', 'max:255'],
'account' => ['required', 'string', 'max:255', 'unique:users'],
'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
'password' => ['required', 'string', 'min:8'],
'roles' => ['nullable', 'array'],
'roles.*' => ['string', 'exists:roles,name'],
'email_verified' => ['boolean'],
```

**処理**:
- ユーザ作成
- ロール割り当て（Laravel-permissionの`assignRole`）
- 成功時: ユーザ一覧にリダイレクト + 成功メッセージ

#### 2.4 ユーザ編集

**場所**: `/admin/users/{id}/edit`

**入力項目**:
- 名前 *（必須）
- アカウント *（必須、ユニーク（自分を除く））
- メールアドレス *（必須、ユニーク（自分を除く））
- 権限（複数選択可能、MultiSelect、現在の設定がデフォルト値）
- メール認証済みフラグ（チェックボックス）

**バリデーション**:
```php
'name' => ['required', 'string', 'max:255'],
'account' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
'roles' => ['nullable', 'array'],
'roles.*' => ['string', 'exists:roles,name'],
'email_verified' => ['boolean'],
```

**処理**:
- ユーザ情報更新
- ロール同期（Laravel-permissionの`syncRoles`）
- 成功時: ユーザ詳細にリダイレクト + 成功メッセージ

#### 2.5 ユーザ削除

**処理**:
- 確認ダイアログ表示
- ユーザ削除
- 成功時: ユーザ一覧にリダイレクト + 成功メッセージ

### 3. 通知システム

#### 3.1 トースト通知

**実装方法**: Laravelフラッシュメッセージ + Inertia.js

**通知タイプ**:
- `success`: 成功メッセージ（緑色）
- `error`: エラーメッセージ（赤色）
- `warning`: 警告メッセージ（黄色）
- `info`: 情報メッセージ（デフォルト色）

**表示位置**: 右下（モバイルでは下部）

**動作**:
- 自動表示（ページ遷移時）
- 5秒後に自動消去
- 手動で閉じるボタン付き
- 重複チェック機能（同じメッセージは1回のみ表示）

**使用方法**:
```php
// コントローラーで
return redirect()->route('admin.users.index')
    ->with('success', 'ユーザが正常に作成されました。');
```

**使用コンポーネント**:
- `Toast.vue`: 個別のトースト表示
- `Toaster.vue`: トースト管理コンテナ

### 4. UIコンポーネント

#### 4.1 shadcn/vue コンポーネント

使用しているコンポーネント:
- `Card`, `CardHeader`, `CardTitle`, `CardContent`
- `Button`
- `Input`
- `Label`
- `Badge`
- `Table`, `TableHeader`, `TableBody`, `TableRow`, `TableHead`, `TableCell`
- `MultiSelect`: 複数選択可能なセレクトボックス
- `Checkbox`
- `DonutChart`: カスタムドーナツグラフ
- `ChartLegend`: カスタム凡例
- `Toast`, `Toaster`: カスタムトースト通知

#### 4.2 アイコン

**ライブラリ**: lucide-vue-next

使用アイコン:
- `Users`, `UserCheck`, `UserPlus`: ユーザ関連
- `ExternalLink`: 外部リンク
- `Eye`, `Edit`, `Trash2`: 操作ボタン
- `ArrowLeft`, `ArrowRight`: ナビゲーション
- `Save`, `Plus`, `Search`: アクション
- `X`: 閉じる

### 5. ナビゲーション

#### 5.1 サイドバーメニュー

**メニュー項目**:
1. ダッシュボード (`/admin`)
2. ユーザ管理 (`/admin/users`)

**アクティブ状態**:
- 完全一致または部分一致でアクティブ表示
- `/admin/users/*` のすべてのパスでユーザ管理がアクティブ

#### 5.2 パンくずリスト

各ページで適切なパンくずリストを表示:
- 管理画面 > ユーザ管理
- 管理画面 > ユーザ管理 > ユーザ名 > 編集

### 6. データモデル

#### 6.1 User モデル

**テーブル**: `users`

**主要フィールド**:
- `id`: 主キー
- `name`: 名前
- `account`: アカウント（ユニーク）
- `email`: メールアドレス（ユニーク）
- `password`: パスワード（ハッシュ化）
- `email_verified_at`: メール認証日時
- `avatar`: アバター画像パス
- `created_at`: 作成日時
- `updated_at`: 更新日時

**トレイト**:
- `HasFactory`
- `Notifiable`
- `TwoFactorAuthenticatable`
- `HasRoles` (Laravel-permission)

**リレーション**:
- `roles()`: 多対多（Laravel-permission）
- `permissions()`: 多対多（Laravel-permission）

#### 6.2 Role モデル（Laravel-permission）

**テーブル**: `roles`

**主要フィールド**:
- `id`: 主キー
- `name`: ロール名（ユニーク）
- `display_name`: 表示名（オプション）
- `guard_name`: ガード名
- `created_at`: 作成日時
- `updated_at`: 更新日時

### 7. ルーティング

**管理者ルート** (`routes/web.php`):

```php
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        // ダッシュボード
        Route::get('/', [DashboardController::class, 'index'])
            ->name('admin.dashboard');
        
        // ユーザ管理（RESTful）
        Route::resource('users', UserController::class)->names([
            'index' => 'admin.users.index',
            'show' => 'admin.users.show',
            'create' => 'admin.users.create',
            'store' => 'admin.users.store',
            'edit' => 'admin.users.edit',
            'update' => 'admin.users.update',
            'destroy' => 'admin.users.destroy',
        ]);
    });
```

### 8. セキュリティ

#### 8.1 アクセス制御

- 管理者ルートは`role:admin`ミドルウェアで保護
- 認証済み（`auth`）かつメール認証済み（`verified`）が必須

#### 8.2 バリデーション

- すべての入力データをサーバー側でバリデーション
- ユニーク制約の適切な処理
- XSS対策（Laravelのデフォルト機能）
- CSRF保護（Inertia.jsが自動処理）

### 9. 今後の拡張可能性

#### 9.1 実装予定機能

- [ ] ユーザのバルク操作（一括削除、一括ロール変更）
- [ ] ユーザのエクスポート機能（CSV/Excel）
- [ ] ユーザのインポート機能
- [ ] 詳細な権限管理（Permission単位）
- [ ] ユーザアクティビティログ
- [ ] パスワードリセット機能（管理者から）
- [ ] ユーザプロフィール画像のアップロード

#### 9.2 改善案

- [ ] より詳細な検索フィルター
- [ ] ソート機能の追加
- [ ] ページネーションのカスタマイズ
- [ ] ダークモード対応の強化
- [ ] レスポンシブデザインの最適化

## 開発ガイドライン

### コーディング規約

- **TypeScript**: 型安全性を重視
- **Vue 3 Composition API**: `<script setup>`を使用
- **shadcn/vue**: 一貫したUIコンポーネント
- **日本語**: UIテキストは日本語で統一

### ファイル構成

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Admin/
│   │       ├── DashboardController.php
│   │       └── UserController.php
│   └── Middleware/
│       └── HandleInertiaRequests.php
└── Models/
    └── User.php

resources/
├── js/
│   ├── components/
│   │   ├── ui/
│   │   │   ├── card/
│   │   │   ├── chart/
│   │   │   │   ├── DonutChart.vue
│   │   │   │   └── ChartLegend.vue
│   │   │   ├── select/
│   │   │   │   └── MultiSelect.vue
│   │   │   ├── table/
│   │   │   └── toast/
│   │   │       ├── Toast.vue
│   │   │       └── Toaster.vue
│   │   ├── AppShell.vue
│   │   └── AppSidebar.vue
│   ├── layouts/
│   │   ├── AdminLayout.vue
│   │   └── app/
│   │       └── AppSidebarLayout.vue
│   ├── pages/
│   │   └── admin/
│   │       ├── Index.vue
│   │       └── users/
│   │           ├── Index.vue
│   │           ├── Show.vue
│   │           ├── Create.vue
│   │           └── Edit.vue
│   └── lib/
│       └── utils.ts
└── css/
    └── app.css

routes/
└── web.php
```

## まとめ

このシステムは、Laravel-permissionを使用したロールベースのアクセス制御を持つ、モダンで使いやすい管理者向けユーザ管理システムです。shadcn/vueを使用した統一感のあるUIと、Inertia.jsによるシームレスなSPA体験を提供します。

---

**最終更新日**: 2024年
**バージョン**: 1.0.0
