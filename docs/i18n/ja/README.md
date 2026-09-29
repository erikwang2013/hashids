# erikwang2013/hashids

**Languages:** [中文](../../../README.md) · [English](../en/README.md) · [한국어](../ko/README.md) · [Русский](../ru/README.md) · [Deutsch](../de/README.md) · [Français](../fr/README.md) · [Español](../es/README.md) · [Português](../pt/README.md) · [हिन्दी](../hi/README.md) · [العربية](../ar/README.md) · [বাংলা](../bn/README.md) · [Bahasa Indonesia](../id/README.md) · **日本語**

<p align="center">
  <img src="../../mascot.svg" alt="ハシディ Hashy — erikwang2013/hashids のプロジェクトマスコット" width="200" />
</p>

<p align="center"><strong>ハシディ Hashy</strong> — プロジェクトマスコット。胸の <code>#</code> がトレードマークです</p>

データベースの自動採番 ID を、短くて推測しにくい文字列に置き換えます。**ひとつの API が Laravel、Webman、ThinkPHP、Hyperf、Yii 2、Yii 3 で同時に動作します**。

基盤として [`hashids/hashids`](https://github.com/vinkla/hashids) v5 に依存しています。設定と使い方は [**vinkla/hashids**](https://github.com/vinkla/laravel-hashids)（マルチ接続、デフォルト接続、`HashidsManager` + ファクトリ）に合わせてあり、スムーズに置き換えられます。

## プロジェクト概要

**Hashids** は短い ID のジェネレータで、数値 ID（データベースの主キーなど）を短く・一意で・推測しにくい文字列にエンコードします。UUID や Snowflake ID とは異なり、Hashids はユーザーに見える場面（URL、共有コード、注文番号など）に適しており、短く読みやすいまま元の数値を隠せます。

本パッケージ `erikwang2013/hashids` は Hashids の **PHP マルチフレームワーク統合レイヤー**です。設計は [vinkla/hashids](https://github.com/vinkla/laravel-hashids) の API スタイル（マルチ接続、デフォルト接続、Manager + Factory パターン）を参考に合わせつつ、中国でよく使われる他の PHP フレームワークへの対応を追加しています。

**主な特徴：**

- **マルチフレームワーク対応**：同一の API で Laravel、Webman、ThinkPHP、Hyperf、Yii 2、Yii 3 を同時にサポートし、移行コストはごくわずかです。
- **マルチ接続対応**：1 つのアプリで複数の Salt/Length の組み合わせを同時に設定でき（ユーザー ID と注文 ID で別のソルトを使うなど）、`connection('xxx')` で切り替えられます。
- **フレームワーク非依存**：特定のフレームワークに依存せず単独で使え、`new HashidsManager($config, $factory)` だけで動作します。
- **vinkla/hashids との整合**：Laravel における Facade、コンテナバインディング、`config/hashids.php` の形式はいずれも vinkla/hashids と一致し、スムーズに置き換えられます。
- **フレームワーク本来の流儀**：各フレームワークの統合はそれぞれの慣用法に従います——Laravel は ServiceProvider + Facade、Webman は Plugin + Bootstrap、ThinkPHP は Service、Hyperf は ConfigProvider、Yii 2 は Bootstrap + アプリケーションコンポーネント、Yii 3 は ServiceProvider です。

**想定される用途：**

| 用途 | 説明 |
|------|------|
| データベースの自動採番 ID を隠す | `user_id=100` を `/user/3kTMd` にマッピングし、事業規模の露出を防ぎます |
| 短縮リンク / 共有コードの生成 | UUID より短く、ランダム文字列より制御しやすい |
| 注文番号 / 連番 | 可読性が高く、サポート対応やログ調査が容易 |
| マルチテナント / マルチモジュールの分離 | 接続ごとに異なる Salt を使い、エンコード空間を互いに独立させます |

**注意事項：**

- Hashids は **暗号化ではなくエンコード（encode/decode）** です。Salt は推測を難しくするだけで、セキュリティが重要な場面（トークン、パスワードなど）には使えません。
- リリース後に Salt や Length を変更すると、エンコード済みの ID はすべて無効になります。事前に設計して設定を固定してください。
- **Salt を空にすると保護がないのと同じ**：空の Salt ではエンコード結果が列挙可能です（`encode(1)`、`encode(2)`… と順序が予測できます）。`env('HASHIDS_SALT', '')` と書いた場合、
  環境変数の設定漏れはエラーにも警告にもならず、黙って空の Salt に降格するだけです——リリース前に Salt が設定済みか必ず確認してください。
- **常駐メモリ型フレームワーク（Webman / Hyperf）では設定の変更にプロセスの再起動が必要**：`HashidsManager` は構築時に設定をスナップショットし、接続を恒久的にキャッシュします。`config/hashids.php` を書き換えても（設定センターで Salt を差し替えても）
  ホットリロードはされず、`reload` または再起動の後に初めて反映されます。

## プロジェクト構成

```
hashids/
├── src/
│   ├── HashidsManager.php                     # コア：マルチ接続の解決、インスタンスキャッシュ、デフォルト接続の代理
│   ├── HashidsFactory.php                     # コア：接続設定から Hashids インスタンスを構築
│   ├── Mascot.php                             # プロジェクトマスコット「ハシディ Hashy」の ASCII 版
│   ├── Install.php                            # Webman のインストール / アンインストールフック
│   ├── Laravel/
│   │   ├── HashidsServiceProvider.php         # コンテナのシングルトン + エイリアス + 設定の公開
│   │   └── Facades/Hashids.php                # Facade（デフォルト接続）
│   ├── Webman/
│   │   └── Bootstrap.php                      # プロセス起動時にコンテナ定義を登録
│   ├── ThinkPHP/
│   │   └── HashidsService.php                 # think\Service の登録バインディング
│   ├── Hyperf/
│   │   ├── ConfigProvider.php                 # 依存マッピング + 設定の公開
│   │   ├── HashidsManagerFactory.php
│   │   └── HashidsClientFactory.php
│   ├── Yii2/
│   │   ├── Bootstrap.php                      # BootstrapInterface：Yii::$container を登録
│   │   └── Component.php                      # アプリケーションコンポーネント：Yii::$app->hashids
│   ├── Yii3/
│   │   └── ServiceProvider.php                # ServiceProviderInterface：6 つの定義を返す
│   └── config/plugin/erikwang2013/hashids/    # Webman プラグインの骨組み（インストール時にプロジェクトへコピー）
│       ├── app.php                            # enable スイッチ
│       └── bootstrap.php                      # Webman\Bootstrap を登録
├── config/
│   ├── hashids.php                            # フラット設定：Laravel / Webman / ThinkPHP
│   └── autoload/hashids.php                   # Hyperf 設定（構造はフラットと同じ、パスが異なる）
├── tests/
│   ├── HashidsManagerTest.php                 # コアの振る舞い
│   ├── HashidsFactoryTest.php
│   ├── InstallTest.php
│   ├── MascotTest.php                         # プロジェクトマスコット：字形の整列とあいさつ
│   ├── Laravel/ · Webman/ · ThinkPHP/ · Hyperf/ · Yii2/ · Yii3/   # 各フレームワークのアダプタテスト
│   ├── Config/PluginConfigTest.php
│   ├── Contract/                              # 実フレームワークとの契約テスト（CI は別ジョブ、後述）
│   └── Support/FrameworkStubs.php             # テスト用のフレームワーククラス代替
├── docs/
│   ├── mascot.svg                             # プロジェクトマスコット「ハシディ Hashy」
│   ├── architecture.svg                       # アーキテクチャ設計図
│   ├── features.svg                           # 機能設計図
│   └── lifecycle.svg                          # ライフサイクル図
├── .github/workflows/release.yml              # main へのプッシュで自動的にタグを打ち release を作成
├── composer.json
└── phpunit.xml.dist
```

> `vendor/`、`composer.lock` などの依存生成物は記載していません。`src/config/` は**プラグインの骨組み**、`config/` は**公開用の設定サンプル**で、用途が異なります。

## アーキテクチャ設計

![アーキテクチャ設計](./architecture.svg)

**4 層の単方向依存**で、上位層が下位層に依存し、逆方向は成立しません：

| 層 | 役割 | 場所 |
|----|------|------|
| アプリ呼び出し層 | Facade、コンテナ / ヘルパー関数、コンストラクタ注入の 3 つの入口 | 業務コード |
| フレームワーク適応層 | 配線のみ：コンテナバインディング、設定の読み込み、設定の公開 | `src/<Framework>/` |
| コア層 | マルチ接続管理とインスタンス構築、**フレームワークに一切依存しない** | `src/HashidsManager.php`、`src/HashidsFactory.php` |
| 基盤依存 | 実際のエンコード / デコード実装 | `hashids/hashids` |

コア層はこのパッケージの中心です：`HashidsManager` が設定を保持し、`connection($name)` が必要に応じて接続を構築してキャッシュし、`__call()` が接続を指定しないメソッド呼び出しをデフォルト接続へ転送します。`HashidsFactory::make()` は `Hashids\Hashids` を構築する唯一の場所です。6 つのフレームワークの適応クラスを合計しても約 490 行（Facade、Hyperf の 2 つのファクトリ、Yii 2 のアプリケーションコンポーネントを含む）で、カーネルを各コンテナに接続することだけを担います。

## 機能設計

![機能設計](./features.svg)

6 つの機能グループがあり、すべてが同じカーネルを中心にしています：

- **エンコード / デコード API**：`encode()` / `decode()` / `encodeHex()` / `decodeHex()` は `__call()` を経由してデフォルト接続に到達するため、`connection()` を明示する必要はありません。
- **マルチ接続管理**：`connection('alternative')` で切り替え。遅延構築で、同じ接続は一度だけ構築されます。
- **マルチフレームワーク適応**：Laravel は `ServiceProvider`、Webman は `Install` + `Bootstrap`、ThinkPHP は `Service`、Hyperf は `ConfigProvider`、Yii 2 は `BootstrapInterface` + アプリケーションコンポーネント、Yii 3 は `ServiceProviderInterface` と、それぞれの慣用法に従います。
- **コンテナバインディング**：クラス名（`HashidsManager`、`HashidsFactory`、`Hashids\Hashids`）と文字列キー（`'hashids'`、`'hashids.factory'`、`'hashids.connection'`）の二本立てのバインディングで、6 フレームワーク共通です。後ろ 2 つの文字列キーは vinkla/hashids と同名なので、移行が容易です。
- **設定と公開**：6 つのフレームワークで設定の**形は一致**し、いずれも**フラット配列**（ルート直下の `default` + `connections`）です。違いは置き場所だけです——Laravel / Webman / ThinkPHP は `config/hashids.php`、Hyperf は `config/autoload/hashids.php`、Yii 2 は `config/params.php` の `params['hashids']`、Yii 3 は `ServiceProvider` のコンストラクタに直接渡します（フレームワークが設定ファイルの場所を定めていないため）。
- **フレームワーク非依存カーネル**：`new HashidsManager($config, $factory)` だけで動作し、`$config` の引数は非配列も許容します（空配列に正規化）。

## ライフサイクル

![ライフサイクル](./lifecycle.svg)

| 段階 | 何が起こるか |
|------|-----------|
| **インストール期** | `composer require` でパッケージを取得 → Laravel の自動検出 / Webman のインストールフックがプラグインの骨組みをコピー / Yii 2 はアプリケーション設定で `Bootstrap` またはコンポーネントを登録 / Yii 3 は `ContainerConfig::withProviders()` で `ServiceProvider` を登録 → `salt` / `length` / `alphabet` を読み込み |
| **実行期** | コンテナが `HashidsManager` を解決 → `encode()` / `decode()` → `__call()` が `connection($name)` へ転送 → **キャッシュにヒットすればそのまま再利用し、ミスしたときだけ `HashidsFactory::make()` で構築して `$connections` に書き込み** → 短い ID または元の数値を返却 |
| **アンインストール期** | `composer remove` が `Install::uninstall()` をトリガーし、`config/plugin/erikwang2013/hashids` を削除；**`config/hashids.php` は保持され**、削除するかは利用者が判断 |

接続は**必要になったときにだけ構築**されます。デフォルト接続しか呼ばないプロセスは、`alternative` のような未使用の接続のために構築コストを払うことはありません。

## インストール

```bash
composer require erikwang2013/hashids
```

> **実行環境**：基盤の `hashids/hashids` には `ext-bcmath` か `ext-gmp` の**どちらかが必須**です。無い場合、最初のエンコードで
> `RuntimeException: Missing math extension for Hashids` がスローされます。この 2 つの拡張は `hashids/hashids` では `suggest` に載っているだけで、
> Composer 2 は suggest を表示しなくなったため、スリムなイメージ（`php:8.3-fpm-alpine` など）ではインストール時に何のヒントもなく、実行時に初めて壊れ、
> Hashids を使うすべてのリクエストが 500 になります。本パッケージの `composer.json` の `suggest` にはこの 2 つのキーを追加済みですが、拡張が有効になっているかはご自身で確認してください。

## 設定構造（Laravel / Webman / ThinkPHP / Yii 2 / Yii 3）

リポジトリの `config/hashids.php` と一致します：

- `default`：デフォルトの接続名（例：`main`）。
- `connections`：接続名 => `salt`、`length`、任意で `alphabet`。

> **Hyperf** は設定ファイルのパスが異なります（`config/autoload/hashids.php`）。ただし構造は同じです。後述の Hyperf の節を参照してください。
>
> **Yii 2** は専用の設定ファイルを使わず、同じ配列をアプリケーションの `config/params.php` の `params['hashids']` にそのまま置きます。後述の Yii 2 の節を参照してください。
>
> **Yii 3** も設定ファイルを読みません。同じ配列を `ServiceProvider` のコンストラクタに渡すだけです。後述の Yii 3 の節を参照してください。

## フレームワークなしでの使い方

マネージャを直接インスタンス化できます：

```php
use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;

$manager = new HashidsManager(
    require __DIR__ . '/config/hashids.php',
    new HashidsFactory()
);

$hash = $manager->encode(1, 2, 3);
$ids = $manager->decode($hash);
```

---

## Laravel

[vinkla/hashids](https://github.com/vinkla/laravel-hashids) と同様です：コンテナに `HashidsManager` を登録し、マルチ接続に対応します。デフォルト接続では Facade とメソッド転送が使えます。

Laravel 5.5+ は本パッケージの `composer.json` の `extra.laravel` を読み取り、`HashidsServiceProvider` と Facade エイリアス `Hashids` を自動登録します。

**設定の公開（任意）**

```bash
php artisan vendor:publish --tag=hashids-config
```

`config/hashids.php` を生成します。公開しない場合、パッケージは登録段階で内蔵のデフォルト設定をマージします。

**Facade（デフォルト接続）**

```php
use Erikwang2013\Hashids\Laravel\Facades\Hashids;

$hash = Hashids::encode(1, 2, 3);
$numbers = Hashids::decode($hash);
```

**接続を指定する**

```php
use Erikwang2013\Hashids\Laravel\Facades\Hashids;

$hash = Hashids::connection('alternative')->encode(100);
```

**`HashidsManager` の依存注入**

```php
use Erikwang2013\Hashids\HashidsManager;

public function __construct(private HashidsManager $hashids) {}

$this->hashids->encode(1);
$this->hashids->connection('alternative')->encode(2);
```

**基盤の `Hashids\Hashids` を注入する（デフォルト接続）**

```php
use Hashids\Hashids;

public function __construct(private Hashids $hashids) {}
```

Laravel 統合を動作させるには、プロジェクトに `laravel/framework`（`illuminate/support` などを含む）がインストールされている必要があります。本パッケージは `illuminate/*` を **require-dev** としており、パッケージ自身のテスト専用です。

---

## Webman

インストール時に **`Install`** が `config/plugin/erikwang2013/hashids` とルートの **`config/hashids.php`** をコピーし、プラグインの **bootstrap** が Webman コンテナに `HashidsManager` を登録します。

プロジェクトの `composer.json` に `support\Plugin::install` / `update` / `uninstall` フックがすでにある場合、本パッケージのインストール時にインストールスクリプトが自動実行されます（`WEBMAN_PLUGIN = true`）。

**インストール後のファイル**

- `config/plugin/erikwang2013/hashids/app.php`：`enable` スイッチ。
- `config/plugin/erikwang2013/hashids/bootstrap.php`：`Erikwang2013\Hashids\Webman\Bootstrap` を登録。
- `config/hashids.php`：マルチ接続の設定（初回インストール時、または上書きを確認したときに書き込み）。

自動コピーが実行されなかった場合は、パッケージ内から上記パスのサンプル設定を手動でコピーしてください。

**プラグインを無効化する**（`config/plugin/erikwang2013/hashids/app.php`）

```php
<?php
return [
    'enable' => false,
];
```

**コンテナバインディング**

- `Erikwang2013\Hashids\HashidsManager` / `'hashids'`
- `Erikwang2013\Hashids\HashidsFactory` / `'hashids.factory'`
- `Hashids\Hashids` / `'hashids.connection'`（デフォルト接続のインスタンス）

**コントローラの例**

```php
use support\Request;
use Erikwang2013\Hashids\HashidsManager;
use Hashids\Hashids;

class DemoController
{
    public function index(Request $request, HashidsManager $manager)
    {
        $hash = $manager->encode(1, 2, 3);

        $client = \support\Container::instance()->get(Hashids::class);
        $hash2 = $client->encode(4);

        return json(['hash' => $hash, 'hash2' => $hash2]);
    }
}
```

**接続を指定する**

```php
$manager->connection('alternative')->encode(99);
```

Composer でパッケージを削除すると `Plugin::uninstall` がトリガーされ、`config/plugin/erikwang2013/hashids` が削除されます；`config/hashids.php` は**削除されません**。残すかどうかはご自身で判断してください。

---

## ThinkPHP

**独自のサービスクラス**で `HashidsManager` を登録します。設定は引き続き、最上位に `default` と `connections` を持つ **`config/hashids.php`** です。

**サービスの登録**

アプリケーションの **`config/service.php`**（正確なパスは TP のバージョンによって異なる場合があります）の `services` に追加します：

```php
<?php

return [
    // ...
    \Erikwang2013\Hashids\ThinkPHP\HashidsService::class,
];
```

アプリケーション単位の `app/AppService.php` を使う場合は、`register()` に同等のバインディングを書くこともできます。

**設定ファイル**

パッケージ内の `config/hashids.php` をアプリケーションの `config/hashids.php` にコピーします（同名の設定を自分でマージしてもかまいません）。

```php
<?php
return [
    'default' => 'main',
    'connections' => [
        'main' => [
            'salt' => env('HASHIDS_SALT', ''),
            'length' => (int) env('HASHIDS_LENGTH', 0),
        ],
    ],
];
```

**使用例**

```php
use Erikwang2013\Hashids\HashidsManager;
use think\facade\App;

$manager = App::make(HashidsManager::class);
$hash = $manager->encode(10, 20);
```

```php
$manager = app('hashids');
```

```php
use Hashids\Hashids;

$client = app(Hashids::class);
$hash = $client->encode(1);
```

```php
app(HashidsManager::class)->connection('alternative')->encode(100);
```

ThinkPHP 統合は `think\Service` を継承しているため、**`topthink/framework`** 環境で使う必要があります（本パッケージでは suggest に指定）。

---

## Hyperf

Composer の **`extra.hyperf.config`** が `ConfigProvider` を読み込み、コンテナに `HashidsFactory`、`HashidsManager`、デフォルト接続の `Hashids\Hashids` を登録します。

パッケージ内の **`config/autoload/hashids.php`** をプロジェクトの **`config/autoload/hashids.php`** にコピーします（またはプロジェクトの設定公開コマンドを使います）。

このファイルは**フラット配列**（ルート直下の `default` + `connections`）を返す必要があります。

Hyperf の `ConfigFactory` は**ファイル名**で設定をマージします（`Arr::set($config, 'hashids', require $file)`）。そのため `config('hashids')` が返すのはファイルの内容そのものです——**`'hashids' =>` でさらに一段包んではいけません**。包むと `connections` に到達できず、接続を取得するときに `Hashids connection [main] is not configured` がスローされます。

```php
<?php

declare(strict_types=1);

return [
    'default' => 'main',
    'connections' => [
        'main' => [
            'salt' => env('HASHIDS_SALT', ''),
            'length' => (int) env('HASHIDS_LENGTH', 0),
        ],
    ],
];
```

**コンテナバインディング**

| 抽象 | 実装 |
|------|------|
| `Erikwang2013\Hashids\HashidsFactory` / `'hashids.factory'` | デフォルトのコンストラクタ |
| `Erikwang2013\Hashids\HashidsManager` / `'hashids'` | `HashidsManagerFactory` |
| `Hashids\Hashids` / `'hashids.connection'` | `HashidsClientFactory`（デフォルト接続） |

**Controller / コンストラクタ注入**

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Erikwang2013\Hashids\HashidsManager;
use Hashids\Hashids;

class DemoController
{
    public function index(HashidsManager $manager, Hashids $hashids)
    {
        $h1 = $manager->encode(1, 2, 3);
        $h2 = $hashids->encode(4);
        $alt = $manager->connection('alternative')->encode(99);

        return compact('h1', 'h2', 'alt');
    }
}
```

```php
$manager = \Hyperf\Context\ApplicationContext::getContainer()->get(
    \Erikwang2013\Hashids\HashidsManager::class
);
```

6 つのフレームワークで設定の**構造は一致し**（ルート直下の `default` + `connections`）、違うのは置き場所だけです：Hyperf は **`config/autoload/hashids.php`**、Laravel / Webman / ThinkPHP は **`config/hashids.php`**、Yii 2 は **`config/params.php` の `params['hashids']`**、Yii 3 は `ServiceProvider` に直接渡します。

---

## Yii 2

アダプタは **`yii\base\BootstrapInterface`** を実装し、6 つの識別子を `Yii::$container` に登録します。加えて **`yii\base\Component`** のサブクラスも提供しているので、Yii で慣用的な `Yii::$app->hashids` が使えます。

### 2 つの入口、必要なほうを選ぶ（互いに代替不可）

**入口 A：コンテナ —— `config/web.php` の `bootstrap` 配列**

```php
'bootstrap' => [
    'log',
    \Erikwang2013\Hashids\Yii2\Bootstrap::class,
],
```

```php
use Erikwang2013\Hashids\HashidsManager;

$manager = Yii::$container->get(HashidsManager::class);
$hash    = $manager->encode(10, 20);
$alt     = Yii::$container->get('hashids')->connection('alternative')->encode(100);
```

**入口 B：アプリケーションコンポーネント —— `config/web.php` の `components` 配列**

```php
'components' => [
    'hashids' => [
        'class'  => \Erikwang2013\Hashids\Yii2\Component::class,
        'config' => [
            'default'     => 'main',
            'connections' => [
                'main' => ['salt' => getenv('HASHIDS_SALT'), 'length' => 6],
            ],
        ],
    ],
],
```

```php
Yii::$app->hashids->encode(1);                              // __call はデフォルト接続へ転送
Yii::$app->hashids->connection('alternative')->encode(100); // 接続を切り替え
Yii::$app->hashids->manager;                                // 基底の HashidsManager を取得
```

> **なぜ 2 つの入口が必要か**：`yii\di\ServiceLocator::get()` は `Yii::$container` へ**フォールバックしません**。
> 自分自身の `_definitions` に登録されたコンポーネントしか見ないためです。`bootstrap` に登録しただけでは `Yii::$app->hashids` は使えませんし、
> 逆に `components` だけを設定しても `Yii::$container->get()` はインスタンスを取得できません。使うほうだけを設定してください。

### 設定ファイル

Yii 2 には `config/hashids.php` のような取り決めがなく、設定はアプリケーションの **`config/params.php`** の `params['hashids']` に置きます：

```php
return [
    // ...
    'hashids' => [
        'default'     => 'main',
        'connections' => [
            'main' => [
                // Yii 2 には Laravel のようなグローバルな env() ヘルパーがないので、getenv()
                // か、ご自身のアプリの env の仕組みを使ってください。
                'salt'   => getenv('HASHIDS_SALT') ?: '',
                'length' => (int) (getenv('HASHIDS_LENGTH') ?: 0),
            ],
        ],
    ],
];
```

`Bootstrap` が読むのは `Yii::$app->params['hashids']` です。このキーが無い場合は空配列として扱われ（デフォルト接続名は
`main` にフォールバック）、エラーにはなりません —— ただし salt が無いとハッシュは列挙可能です。リリース前にソルトが設定済みか確認してください。

> **デフォルト接続は遅延解決されます**：`bootstrap` の段階では定義を登録するだけで、実際に `Hashids\Hashids` を取得するときに接続を構築します。
> 設定を書き間違えてもアプリ全体の起動は失敗せず、取得時に `InvalidArgumentException` がスローされます。

Yii 2 統合は `yii\base\BootstrapInterface` / `yii\base\Component` を継承しているため、**`yiisoft/yii2`**
環境で使う必要があります（本パッケージでは suggest に指定）。

---

## Yii 3

Yii 3 は PSR-11 コンテナ（`yiisoft/di`）を組み合わせて構成するパッケージ群で、Yii 2 のようなアプリケーション設定配列はありません。
アダプタは **`Yiisoft\Di\ServiceProviderInterface`** を実装したクラスで、`getDefinitions()` が 6 つのコンテナ識別子を返します。

**登録**

```php
use Erikwang2013\Hashids\Yii3\ServiceProvider;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withProviders([
        // 設定は provider に直接渡します —— Yii 3 は設定ファイルの場所を定めていないため、
        // 値の出どころ（params、env、設定センター）はアプリが自分で決めます。
        new ServiceProvider($params['hashids'] ?? []),
    ]);

$container = new Container($config);
```

**使用**

```php
use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Hashids\Hashids;

$manager = $container->get(HashidsManager::class);
$hash    = $manager->encode(10, 20);                                  // デフォルト接続

$container->get('hashids')->connection('alternative')->encode(100);   // 文字列エイリアス + 接続の切り替え
$container->get(Hashids::class)->encode(1);                           // クラス名でデフォルト接続を取得
$container->get(HashidsFactory::class);                               // ファクトリ
```

コンテナはもともと単一インスタンスの意味論を持ちます（同じ id への `get()` は毎回同じインスタンスを返します）ので、エイリアスと FQCN が取得するのは同じ接続の組です。
アダプタの定義は転送するだけでよく、あらかじめオブジェクトを生成しておく必要はありません。

> **例外のクラス名は一段包まれます**：デフォルト接続は遅延解決されるため、組み立て段階では失敗しません。ただし取得時にコンテナが本パッケージの
> `InvalidArgumentException` を **`Yiisoft\Di\BuildingException`** で包んでスローします —— `catch` に
> `InvalidArgumentException` と書いても受け取れないので、`$e->getPrevious()` を確認してください。

> **provider の定義は通常の definitions より優先されます**：実コンテナの組み立て順は `definitions` が先、`providers` が後です。
> そのため hashids のバインディングを上書きしたい場合、`definitions` 側の同名キーを書き換えても**無効**で、`extensions` を使うか provider 自体を変更してください。

Yii 3 統合は `Yiisoft\Di\ServiceProviderInterface` を実装しているため、**`yiisoft/di`** 環境で使う必要があります
（本パッケージでは suggest に指定）。

---

## プロジェクトマスコット

プロジェクトマスコットは **ハシディ Hashy**——頭の上にアンテナ、胸に `#` がプリントされた角丸の四角いマスコットで、ベクター画像は [`docs/mascot.svg`](../../mascot.svg) にあります。

ターミナルでは SVG を表示できないため、コード内に同等の ASCII 版（`Erikwang2013\Hashids\Mascot`）を用意しています：

```php
use Erikwang2013\Hashids\Mascot;

echo Mascot::greet();
```

```
           ●
           │
      ╭─────────╮
      │ ◉     ◉ │
      │    ‿    │
      │    #    │
      ╰──┬───┬──╯
         ╵   ╵
哈希迪 Hashy · 把数据库自增 ID 换成短小、不可猜测的字符串
```

Webman プラグインが `config/hashids.php` を初めて公開するときに、この挨拶を一度だけ一緒に出力します。設定がすでに存在する場合（`composer update` の再実行など）は繰り返し表示しません。

---

## オープンソースは容易ではありません、ご支援いただければ幸いです / Open Source is Not Easy, Your Support is Welcome

<p align="center">
  <img src="../../weixinpay.png" alt="WeChat Pay" width="130" height="130" />
  &nbsp;&nbsp;&nbsp;&nbsp;
  <img src="../../alipay.png" alt="Alipay" width="130" height="130" />
</p>

<p align="center"><strong>WeChat Pay</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Alipay</strong></p>

---


## ライセンス

MIT ライセンスです。詳細は [LICENSE](../../../LICENSE) を参照してください。
