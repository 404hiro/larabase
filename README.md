# LaraBase - Webサービス開発のベーステンプレート

Webサービスを素早く立ち上げるための、Laravel + Vue.js (Inertia.js) + shadcn/vue によるベーステンプレートです。必要最低限のユーザ機能と管理画面を備えており、これをベースに独自のサービスを拡張開発できます。

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat&logo=laravel)
![Vue.js](https://img.shields.io/badge/Vue.js-3.5-4FC08D?style=flat&logo=vue.js)
![TypeScript](https://img.shields.io/badge/TypeScript-5.2-3178C6?style=flat&logo=typescript)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4.1-38B2AC?style=flat&logo=tailwind-css)

## 📋 目次

- [特徴](#-特徴)
- [技術スタック](#-技術スタック)
- [必要要件](#-必要要件)
- [インストール手順](#-インストール手順)
- [使い方](#-使い方)
- [主な機能](#-主な機能)
- [ディレクトリ構成](#-ディレクトリ構成)
- [開発](#-開発)
- [トラブルシューティング](#-トラブルシューティング)
- [ライセンス](#-ライセンス)

## ✨ 特徴

- 🚀 **すぐに使える**: 認証・ユーザ管理・管理画面が最初から実装済み
- � **高い拡張性*認*: 必要最低限の機能のみを実装し、独自機能を追加しやすい設計
- 🎨 **モダンなUI**: shadcn/vueによる統一感のある美しいデザイン
- � ***柔軟な権限管理**: Laravel-permissionによるロールベース認証
- 📊 **管理画面完備**: ユーザ管理とダッシュボードを標準装備
- � ***SPA体験**: Inertia.jsによるシームレスなページ遷移
- 🌐 **日本語対応**: 完全日本語化されたUI
- 📱 **レスポンシブ**: モバイルからデスクトップまで対応
- 🔔 **通知システム**: トースト通知による操作フィードバック
- 🎯 **型安全**: TypeScriptによる堅牢な開発体験

## 🛠 技術スタック

### バックエンド
- **Laravel 12**: PHPフレームワーク
- **PHP 8.2+**: プログラミング言語
- **PostgreSQL 17**: データベース
- **Laravel Fortify 1.30**: 認証システム
- **Laravel-permission 6.21 (Spatie)**: 権限管理

### フロントエンド
- **Vue 3.5**: プログレッシブJavaScriptフレームワーク
- **TypeScript 5.2**: 型安全な開発
- **Inertia.js 2.1**: モダンなモノリシックアプローチ
- **shadcn/vue**: UIコンポーネントライブラリ
- **Tailwind CSS 4.1**: ユーティリティファーストCSS
- **Vite 7**: 高速ビルドツール

### 開発ツール
- **Laravel Sail**: Docker開発環境
- **ESLint & Prettier**: コード品質管理
- **Wayfinder**: 型安全なルーティング

## 📦 必要要件

- **Docker Desktop**: 最新版
- **Node.js**: 18.x 以上
- **npm**: 9.x 以上

または

- **PHP**: 8.2 以上
- **Composer**: 2.x 以上
- **PostgreSQL**: 17 以上
- **Node.js**: 18.x 以上
- **npm**: 9.x 以上

## 🚀 インストール手順

### 1. リポジトリのクローン

```bash
git clone <repository-url>
cd larabase
```

### 2. 環境設定ファイルのコピー

```bash
cp .env.example .env
```

### 3. Composerの依存関係をインストール

```bash
composer install
```

### 4. アプリケーションキーの生成

```bash
php artisan key:generate
```

### 5. Node.jsの依存関係をインストール

```bash
npm install
```

### 6. データベースのセットアップ

#### Docker (Laravel Sail) を使用する場合

```bash
# Sailエイリアスの設定（オプション）
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'

# Dockerコンテナの起動
sail up -d

# データベースマイグレーション
sail artisan migrate

# シーダーの実行（サンプルデータ）
sail artisan db:seed
```

#### ローカル環境を使用する場合

```bash
# .envファイルでデータベース接続情報を設定
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# データベースマイグレーション
php artisan migrate

# シーダーの実行（サンプルデータ）
php artisan db:seed
```

### 7. ストレージリンクの作成

```bash
# Sailを使用する場合
sail artisan storage:link

# ローカル環境の場合
php artisan storage:link
```

### 8. 開発サーバーの起動

#### Docker (Laravel Sail) を使用する場合

```bash
# バックエンド（既に起動済み）
sail up -d

# フロントエンド（別ターミナル）
npm run dev
```

アプリケーションは以下のURLでアクセスできます：
- **アプリケーション**: http://localhost
- **Vite開発サーバー**: http://localhost:5173
- **Mailpit（メール確認）**: http://localhost:8025

#### ローカル環境を使用する場合

```bash
# バックエンド（ターミナル1）
php artisan serve

# フロントエンド（ターミナル2）
npm run dev
```

アプリケーションは以下のURLでアクセスできます：
- **アプリケーション**: http://localhost:8000
- **Vite開発サーバー**: http://localhost:5173

## 📖 使い方

### 初回ログイン

シーダーを実行した場合、以下のテストアカウントが作成されます：

**管理者アカウント**:
- メールアドレス: `admin@example.com`
- パスワード: `password`

### 標準機能

LaraBaseには以下の機能が最初から実装されています：

#### ユーザ認証
- ログイン・ログアウト
- ユーザ登録
- パスワードリセット
- メール認証
- 二段階認証（オプション）

#### 管理画面（`/admin`）
- ダッシュボード：ユーザ統計の可視化
- ユーザ管理：CRUD操作、検索、フィルター
- ロール・権限管理：Laravel-permissionによる柔軟な権限設定

#### ユーザ機能
- プロフィール編集
- パスワード変更
- アバター画像アップロード

### 拡張開発の始め方

LaraBaseをベースに独自のサービスを開発する手順：

1. **新しいモデルの追加**
   ```bash
   sail artisan make:model YourModel -m
   ```

2. **新しいコントローラーの作成**
   ```bash
   sail artisan make:controller YourController
   ```

3. **新しいVueページの追加**
   ```
   resources/js/pages/your-feature/Index.vue
   ```

4. **ルートの追加**
   ```php
   // routes/web.php
   Route::get('/your-feature', [YourController::class, 'index']);
   ```

5. **サイドバーメニューの追加**
   ```typescript
   // resources/js/components/AppSidebar.vue
   const mainNavItems: NavItem[] = [
       // 既存のメニュー...
       {
           title: 'あなたの機能',
           href: '/your-feature',
           icon: YourIcon,
       },
   ];
   ```

### 基本的な操作

#### ユーザの作成
1. 管理画面（`/admin/users`）にアクセス
2. 「新規ユーザ作成」ボタンをクリック
3. 必要な情報を入力して保存

#### ユーザの編集
1. ユーザ一覧から「編集」ボタンをクリック
2. 情報を更新して保存

#### 権限の管理
1. ユーザ編集画面で権限（ロール）を選択
2. 複数のロールを割り当て可能

## 🎯 実装済み機能

### 認証システム
- ユーザ登録・ログイン・ログアウト
- パスワードリセット
- メール認証
- 二段階認証（オプション）
- セッション管理

### 管理者ダッシュボード
- **ユーザ統計の可視化**: ロール別ユーザ数をドーナツグラフで表示
- **リアルタイム集計**: 総ユーザ数、アクティブユーザ数、今月の新規登録数
- **クイックアクセス**: 各管理機能への直接リンク

### ユーザ管理（CRUD完備）
- **一覧表示**: ID順ソート、ページネーション（15件/ページ）
- **検索・フィルター**: 名前・メールアドレス検索、ステータスフィルター
- **詳細表示**: ユーザ情報、権限、登録日時などの確認
- **作成・編集**: 名前、アカウント、メール、パスワード、権限の管理
- **削除**: 確認ダイアログ付き安全な削除
- **権限管理**: 複数ロールの柔軟な割り当て

### 権限管理システム
- **ロールベース認証**: Laravel-permissionによる実装
- **柔軟な権限設定**: ロールと権限の組み合わせ
- **管理画面アクセス制御**: `role:admin`ミドルウェア

### 通知システム
- **トースト通知**: 操作結果のリアルタイム表示
- **4種類の通知タイプ**: 成功・エラー・警告・情報
- **自動消去**: 5秒後に自動的に消える
- **手動クローズ**: ×ボタンで即座に閉じる

### UIコンポーネント
- **shadcn/vue**: 統一感のある美しいコンポーネント
- **レスポンシブデザイン**: モバイル・タブレット・デスクトップ対応
- **ダークモード対応**: システム設定に追従
- **アクセシビリティ**: WAI-ARIA準拠

## 📁 ディレクトリ構成

```
larabase/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Admin/          # 管理者コントローラー
│   │   └── Middleware/
│   └── Models/                 # Eloquentモデル
├── database/
│   ├── migrations/             # データベースマイグレーション
│   └── seeders/                # シーダー
├── resources/
│   ├── js/
│   │   ├── components/
│   │   │   └── ui/            # shadcn/vueコンポーネント
│   │   ├── layouts/           # レイアウトコンポーネント
│   │   ├── pages/
│   │   │   └── admin/         # 管理画面ページ
│   │   └── lib/               # ユーティリティ
│   └── css/
│       └── app.css            # グローバルスタイル
├── routes/
│   └── web.php                # ルート定義
├── docs/                      # ドキュメント
├── .env.example               # 環境設定サンプル
├── compose.yaml               # Docker Compose設定
└── package.json               # Node.js依存関係
```

## 🔧 開発

### コードフォーマット

```bash
# フォーマット実行
npm run format

# フォーマットチェック
npm run format:check
```

### リント

```bash
# ESLint実行（自動修正）
npm run lint
```

### ビルド

```bash
# 本番用ビルド
npm run build

# SSR対応ビルド
npm run build:ssr
```

### データベース操作

```bash
# マイグレーションのリセット＆再実行
sail artisan migrate:fresh

# シーダー付きリセット
sail artisan migrate:fresh --seed

# 特定のシーダー実行
sail artisan db:seed --class=UserSeeder
```

### キャッシュクリア

```bash
# すべてのキャッシュをクリア
sail artisan optimize:clear

# 個別にクリア
sail artisan cache:clear
sail artisan config:clear
sail artisan route:clear
sail artisan view:clear
```

## 🐛 トラブルシューティング

### Dockerコンテナが起動しない

```bash
# コンテナを停止して再起動
sail down
sail up -d
```

### データベース接続エラー

1. `.env`ファイルのデータベース設定を確認
2. PostgreSQLコンテナが起動しているか確認：`sail ps`
3. データベースが作成されているか確認

### Viteが起動しない

```bash
# node_modulesを削除して再インストール
rm -rf node_modules
npm install
npm run dev
```

### 権限エラー

```bash
# Sailを使用する場合
sail artisan storage:link
sail artisan cache:clear

# ストレージディレクトリの権限設定
sudo chmod -R 775 storage bootstrap/cache
```

### アセットが読み込まれない

```bash
# アセットを再ビルド
npm run build

# 開発モードで起動
npm run dev
```

## 📚 関連ドキュメント

- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org/)
- [Inertia.js Documentation](https://inertiajs.com/)
- [shadcn/vue Documentation](https://www.shadcn-vue.com/)
- [Laravel-permission Documentation](https://spatie.be/docs/laravel-permission)
- [詳細仕様書](./docs/admin-user-management.md)

## 🤝 コントリビューション

プルリクエストを歓迎します。大きな変更の場合は、まずissueを開いて変更内容を議論してください。

## 📄 ライセンス

このプロジェクトは [MIT License](LICENSE) の下でライセンスされています。

## 👥 作成者

エイハブが作ってます。  
Xフォローしてね↓  
https://x.com/d404hiro
---

**注意**: このREADMEは開発環境用です。本番環境にデプロイする際は、適切なセキュリティ設定を行ってください。
