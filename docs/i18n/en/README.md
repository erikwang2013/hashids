# erikwang2013/hashids

**Languages:** [中文](../../../README.md) · **English** · [한국어](../ko/README.md) · [Русский](../ru/README.md) · [Deutsch](../de/README.md) · [Français](../fr/README.md) · [Español](../es/README.md) · [Português](../pt/README.md) · [हिन्दी](../hi/README.md) · [العربية](../ar/README.md) · [বাংলা](../bn/README.md) · [Bahasa Indonesia](../id/README.md) · [日本語](../ja/README.md)

<p align="center">
  <img src="../../mascot.svg" alt="Hashy — the erikwang2013/hashids project mascot" width="200" />
</p>

<p align="center"><strong>Hashy</strong> — the project mascot, with its signature <code>#</code> on its chest</p>

Turn database auto-increment IDs into short, unguessable strings — **one API that runs on Laravel, Webman, ThinkPHP, Hyperf, Yii 2 and Yii 3 alike**.

Built on [`hashids/hashids`](https://github.com/vinkla/hashids) v5; the configuration and usage follow [**vinkla/hashids**](https://github.com/vinkla/laravel-hashids) (multiple connections, default connection, `HashidsManager` + factory), so it is a drop-in replacement.

## About

**Hashids** is a short ID generator that encodes numeric IDs (such as database primary keys) into short, unique and unguessable strings. Unlike UUIDs or snowflake IDs, Hashids is better suited to user-facing scenarios (URLs, share codes, order numbers), hiding the raw number while staying short and readable.

This package, `erikwang2013/hashids`, is a **multi-framework integration layer for PHP** built on Hashids. Its design follows and aligns with the API style of [vinkla/hashids](https://github.com/vinkla/laravel-hashids) (multiple connections, default connection, Manager + Factory pattern), and extends that support to other widely used PHP frameworks.

**Core features:**

- **Multi-framework**: one API serves Laravel, Webman, ThinkPHP, Hyperf, Yii 2 and Yii 3 at once, so migration costs next to nothing.
- **Multiple connections**: a single application can configure several Salt/Length combinations side by side (say, different salts for user IDs and order IDs), switched with `connection('xxx')`.
- **No framework dependency**: usable standalone without any particular framework — `new HashidsManager($config, $factory)` is all it takes.
- **Aligned with vinkla/hashids**: under Laravel the Facade, the container bindings and the `config/hashids.php` format all match vinkla/hashids, so it is a drop-in replacement.
- **Framework-native style**: each framework integration follows that framework's own idioms — Laravel uses a ServiceProvider + Facade, Webman a Plugin + Bootstrap, ThinkPHP a Service, Hyperf a ConfigProvider, Yii 2 a Bootstrap + application component, Yii 3 a ServiceProvider.

**Use cases:**

| Scenario | Description |
|------|------|
| Hiding database auto-increment IDs | Map `user_id=100` to `/user/3kTMd`, so business scale is not exposed |
| Short links / share codes | Shorter than a UUID, more controllable than a random string |
| Order numbers / serial numbers | Readable, which helps support conversations and log triage |
| Multi-tenant / multi-module isolation | Different connections use different Salts, keeping the encoded spaces independent of one another |

**Caveats:**

- Hashids is **encoding (encode/decode), not encryption**. Salt only makes guessing harder; do not use it for security-sensitive cases (such as tokens or passwords).
- Once in production, changing the Salt or the Length invalidates every ID already encoded, so plan ahead and fix the configuration.
- **An empty Salt means no protection at all**: with an empty salt the encoded output is enumerable (`encode(1)`, `encode(2)`, … predictable in sequence). Written as
  `env('HASHIDS_SALT', '')`, a missing environment variable neither errors nor warns — it silently degrades to an empty salt, so confirm the salt is set before going live.
- **On long-running frameworks (Webman / Hyperf), config changes require a process restart**: `HashidsManager` snapshots the configuration at construction time and caches connections permanently,
  so editing `config/hashids.php` (or swapping the salt through a config center) does not take effect live — only after a `reload` or a restart.

## Project structure

```
hashids/
├── src/
│   ├── HashidsManager.php                     # Core: multi-connection resolution, instance cache, default connection proxy
│   ├── HashidsFactory.php                     # Core: builds a Hashids instance from connection config
│   ├── Mascot.php                             # ASCII version of the mascot, Hashy
│   ├── Install.php                            # Webman install / uninstall hook
│   ├── Laravel/
│   │   ├── HashidsServiceProvider.php         # container singleton + alias + config publishing
│   │   └── Facades/Hashids.php                # Facade (default connection)
│   ├── Webman/
│   │   └── Bootstrap.php                      # registers container definitions at process start
│   ├── ThinkPHP/
│   │   └── HashidsService.php                 # think\Service registration and bindings
│   ├── Hyperf/
│   │   ├── ConfigProvider.php                 # dependency mapping + config publishing
│   │   ├── HashidsManagerFactory.php
│   │   └── HashidsClientFactory.php
│   ├── Yii2/
│   │   ├── Bootstrap.php                      # BootstrapInterface: registers Yii::$container
│   │   └── Component.php                      # application component: Yii::$app->hashids
│   ├── Yii3/
│   │   └── ServiceProvider.php                # ServiceProviderInterface: returns six definitions
│   └── config/plugin/erikwang2013/hashids/    # Webman plugin skeleton (copied into the project on install)
│       ├── app.php                            # the enable switch
│       └── bootstrap.php                      # registers Webman\Bootstrap
├── config/
│   ├── hashids.php                            # flat config: Laravel / Webman / ThinkPHP
│   └── autoload/hashids.php                   # Hyperf config (same flat structure, different path)
├── tests/
│   ├── HashidsManagerTest.php                 # core behaviour
│   ├── HashidsFactoryTest.php
│   ├── InstallTest.php
│   ├── MascotTest.php                         # the mascot: glyph alignment and greeting
│   ├── Laravel/ · Webman/ · ThinkPHP/ · Hyperf/ · Yii2/ · Yii3/   # per-framework adapter tests
│   ├── Config/PluginConfigTest.php
│   ├── Contract/                              # real-framework contract tests (separate CI job, see below)
│   └── Support/FrameworkStubs.php             # framework class stubs for tests
├── docs/
│   ├── mascot.svg                             # the mascot, Hashy
│   ├── architecture.svg                       # architecture diagram
│   ├── features.svg                           # feature design diagram
│   └── lifecycle.svg                          # lifecycle diagram
├── .github/workflows/release.yml              # auto tag + release on push to main
├── composer.json
└── phpunit.xml.dist
```

> Dependency artifacts such as `vendor/` and `composer.lock` are not listed; `src/config/` is the **plugin skeleton** while `config/` holds the **publishable config samples** — the two serve different purposes.

## Architecture

![Architecture](./architecture.svg)

**Four layers, one-way dependency**: each layer depends on the one below it, never the other way round:

| Layer | Responsibility | Location |
|----|------|------|
| Application call layer | Three entry points: Facade, container / helper function, constructor injection | Business code |
| Framework adapter layer | Wiring only: container binding, config reading, config publishing | `src/<Framework>/` |
| Core layer | Multi-connection management and instance construction, **with no framework dependency** | `src/HashidsManager.php`, `src/HashidsFactory.php` |
| Underlying dependency | The actual encode/decode implementation | `hashids/hashids` |

The core layer is the heart of the package: `HashidsManager` holds the configuration, `connection($name)` builds and caches a connection on demand, and `__call()` forwards connection-less method calls to the default connection; `HashidsFactory::make()` is the only place that constructs a `Hashids\Hashids`. The adapter classes for all six frameworks total roughly 490 lines (including the Facade, the two Hyperf factories and the Yii 2 application component) — all they do is wire the core into their respective containers.

## Feature design

![Feature design](./features.svg)

Six capability groups, all built around the same core:

- **Encode/decode API**: `encode()` / `decode()` / `encodeHex()` / `decodeHex()`, reaching the default connection through `__call()`, with no explicit `connection()` needed.
- **Multi-connection management**: switch with `connection('alternative')`; built lazily, so each connection is constructed only once.
- **Multi-framework adapters**: Laravel uses a `ServiceProvider`, Webman an `Install` + `Bootstrap`, ThinkPHP a `Service`, Hyperf a `ConfigProvider`, Yii 2 a `BootstrapInterface` + application component, Yii 3 a `ServiceProviderInterface`, each following its framework's idioms.
- **Container bindings**: class names (`HashidsManager`, `HashidsFactory`, `Hashids\Hashids`) and string keys (`'hashids'`, `'hashids.factory'`, `'hashids.connection'`) are bound in tandem, identically across all six frameworks; the latter two string keys share their names with vinkla/hashids, which eases migration.
- **Configuration and publishing**: all six frameworks share the same config **shape** — a **flat array** (root-level `default` + `connections`) — and differ only in where it lives: Laravel / Webman / ThinkPHP use `config/hashids.php`, Hyperf `config/autoload/hashids.php`, Yii 2 the `params['hashids']` entry of `config/params.php`, and Yii 3 hands it straight to the `ServiceProvider` constructor (the framework prescribes no config file).
- **Framework-free core**: `new HashidsManager($config, $factory)` just works, and `$config` tolerates non-array input (normalized to an empty array).

## Lifecycle

![Lifecycle](./lifecycle.svg)

| Stage | What happens |
|------|-----------|
| **Install** | `composer require` pulls the package → Laravel auto-discovery / the Webman install hook copies the plugin skeleton / Yii 2 registers the `Bootstrap` or the component in the application config / Yii 3 registers the `ServiceProvider` in `ContainerConfig::withProviders()` → `salt` / `length` / `alphabet` are loaded |
| **Runtime** | The container resolves `HashidsManager` → `encode()` / `decode()` → `__call()` forwards to `connection($name)` → **a cache hit is reused as is, and only a miss calls `HashidsFactory::make()` to build and store it in `$connections`** → a short ID or the original number comes back |
| **Uninstall** | `composer remove` triggers `Install::uninstall()`, removing `config/plugin/erikwang2013/hashids`; **`config/hashids.php` is kept**, and whether to clean it up is up to you |

Connections are built **on demand**: a process that only ever uses the default connection pays no construction cost for unused connections such as `alternative`.

## Installation

```bash
composer require erikwang2013/hashids
```

> **Runtime requirements**: the underlying `hashids/hashids` **must** have `ext-bcmath` or `ext-gmp` (one of the two), otherwise the very first encode throws
> `RuntimeException: Missing math extension for Hashids`. Those two extensions are listed only under `suggest` inside `hashids/hashids`,
> and Composer 2 no longer prints suggestions — so on a slim image (such as `php:8.3-fpm-alpine`) you get no warning at install time and it blows up at runtime,
> with every request that touches Hashids returning a 500. This package's `composer.json` now lists both keys under `suggest`, but you still need to confirm the extension is enabled.

## Configuration structure (Laravel / Webman / ThinkPHP / Yii 2 / Yii 3)

Identical to the repository's `config/hashids.php`:

- `default`: the default connection name (e.g. `main`).
- `connections`: connection name => `salt`, `length`, optional `alphabet`.

> **Hyperf**'s config file lives at a different path (`config/autoload/hashids.php`), but the structure is the same; see the Hyperf section below.
>
> **Yii 2** has no separate config file: put the same array into the application's `config/params.php` under `params['hashids']`; see the Yii 2 section below.
>
> **Yii 3** reads no config file either — pass the same array to the `ServiceProvider` constructor; see the Yii 3 section below.

## Framework-free usage

You can instantiate the manager directly:

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

Similar to [vinkla/hashids](https://github.com/vinkla/laravel-hashids): the container registers `HashidsManager` with multiple connections; the default connection supports the Facade and method forwarding.

Laravel 5.5+ reads the `extra.laravel` key of this package's `composer.json` and automatically registers `HashidsServiceProvider` along with the Facade alias `Hashids`.

**Publishing the config (optional)**

```bash
php artisan vendor:publish --tag=hashids-config
```

This generates `config/hashids.php`. If you do not publish it, the package merges its built-in default configuration during registration.

**Facade (default connection)**

```php
use Erikwang2013\Hashids\Laravel\Facades\Hashids;

$hash = Hashids::encode(1, 2, 3);
$numbers = Hashids::decode($hash);
```

**Specifying a connection**

```php
use Erikwang2013\Hashids\Laravel\Facades\Hashids;

$hash = Hashids::connection('alternative')->encode(100);
```

**Injecting `HashidsManager`**

```php
use Erikwang2013\Hashids\HashidsManager;

public function __construct(private HashidsManager $hashids) {}

$this->hashids->encode(1);
$this->hashids->connection('alternative')->encode(2);
```

**Injecting the underlying `Hashids\Hashids` (default connection)**

```php
use Hashids\Hashids;

public function __construct(private Hashids $hashids) {}
```

Running the Laravel integration requires `laravel/framework` to be installed in the project (including `illuminate/support` and friends). This package lists `illuminate/*` as **require-dev**, purely for its own tests.

---

## Webman

At install time **`Install`** copies `config/plugin/erikwang2013/hashids` and the root **`config/hashids.php`**, and the plugin's **bootstrap** registers `HashidsManager` with the Webman container.

If the project's `composer.json` already has the `support\Plugin::install` / `update` / `uninstall` hooks, the install script runs automatically when this package is installed (`WEBMAN_PLUGIN = true`).

**Files after installation**

- `config/plugin/erikwang2013/hashids/app.php`: the `enable` switch.
- `config/plugin/erikwang2013/hashids/bootstrap.php`: registers `Erikwang2013\Hashids\Webman\Bootstrap`.
- `config/hashids.php`: the multi-connection configuration (written on first install or when an overwrite is confirmed).

If the automatic copy did not run, you can copy the sample config from those paths inside the package by hand.

**Disabling the plugin** (`config/plugin/erikwang2013/hashids/app.php`)

```php
<?php
return [
    'enable' => false,
];
```

**Container bindings**

- `Erikwang2013\Hashids\HashidsManager` / `'hashids'`
- `Erikwang2013\Hashids\HashidsFactory` / `'hashids.factory'`
- `Hashids\Hashids` / `'hashids.connection'` (the default connection instance)

**Controller example**

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

**Specifying a connection**

```php
$manager->connection('alternative')->encode(99);
```

Composer triggers `Plugin::uninstall` when the package is removed, which deletes `config/plugin/erikwang2013/hashids`; it will **not** delete `config/hashids.php`, and whether to keep it is up to you.

---

## ThinkPHP

A **custom service class** registers `HashidsManager`; the config is still the **`config/hashids.php`** carrying top-level `default` and `connections`.

**Registering the service**

Add the following to `services` in the application's **`config/service.php`** (the exact path may differ between TP versions):

```php
<?php

return [
    // ...
    \Erikwang2013\Hashids\ThinkPHP\HashidsService::class,
];
```

If you use an application-level `app/AppService.php`, you can also write an equivalent binding inside `register()`.

**Config file**

Copy `config/hashids.php` from inside the package to the application's `config/hashids.php` (or merge the same-named config yourself).

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

**Usage examples**

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

The ThinkPHP integration extends `think\Service` and must be used in a **`topthink/framework`** environment (listed as suggest by this package).

---

## Hyperf

Composer's **`extra.hyperf.config`** loads `ConfigProvider`, which registers `HashidsFactory`, `HashidsManager` and the default connection's `Hashids\Hashids` with the container.

Copy **`config/autoload/hashids.php`** from inside the package to the project's **`config/autoload/hashids.php`** (or use the project's config publish command).

The file must return a **flat array** (root-level `default` + `connections`).

Hyperf's `ConfigFactory` merges by **file name** (`Arr::set($config, 'hashids', require $file)`), so `config('hashids')` returns the file contents themselves — **do not add another `'hashids' =>` layer**. If you do, `connections` becomes unreachable and resolving a connection throws `Hashids connection [main] is not configured`.

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

**Container bindings**

| Abstract | Implementation |
|------|------|
| `Erikwang2013\Hashids\HashidsFactory` / `'hashids.factory'` | default construction |
| `Erikwang2013\Hashids\HashidsManager` / `'hashids'` | `HashidsManagerFactory` |
| `Hashids\Hashids` / `'hashids.connection'` | `HashidsClientFactory` (default connection) |

**Controller / constructor injection**

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

All six frameworks share the same config **structure** (root-level `default` + `connections`) and differ only in where it lives: Hyperf uses **`config/autoload/hashids.php`**, Laravel / Webman / ThinkPHP use **`config/hashids.php`**, Yii 2 puts it in the **`params['hashids']` entry of `config/params.php`**, and Yii 3 passes it straight to the `ServiceProvider`.

---

## Yii 2

The adapter implements **`yii\base\BootstrapInterface`**, registering the six identifiers with `Yii::$container`; it also ships a **`yii\base\Component`** subclass, which makes Yii's idiomatic `Yii::$app->hashids` available.

### Two entry points, pick the one you need (they are not interchangeable)

**Entry A: the container — the `bootstrap` array in `config/web.php`**

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

**Entry B: the application component — the `components` array in `config/web.php`**

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
Yii::$app->hashids->encode(1);                              // __call forwards to the default connection
Yii::$app->hashids->connection('alternative')->encode(100); // switch connections
Yii::$app->hashids->manager;                                // reach the underlying HashidsManager
```

> **Why both entry points are needed**: `yii\di\ServiceLocator::get()` does **not** fall back to `Yii::$container`;
> it only recognises components registered in its own `_definitions`. So registering `bootstrap` alone does not make `Yii::$app->hashids` work,
> and conversely, configuring only `components` does not give `Yii::$container->get()` an instance. Configure whichever one you intend to use.

### The config file

Yii 2 has no `config/hashids.php` convention; the config goes in the application's **`config/params.php`** under `params['hashids']`:

```php
return [
    // ...
    'hashids' => [
        'default'     => 'main',
        'connections' => [
            'main' => [
                // Yii 2 has no global env() helper like Laravel's; use getenv()
                // or whatever env mechanism your application provides.
                'salt'   => getenv('HASHIDS_SALT') ?: '',
                'length' => (int) (getenv('HASHIDS_LENGTH') ?: 0),
            ],
        ],
    ],
];
```

What `Bootstrap` reads is `Yii::$app->params['hashids']`; when that key is missing it is treated as an empty array (the default
connection name falls back to `main`) without erroring — but with no salt the hashes are enumerable, so confirm the salt is set before going live.

> **The default connection is resolved lazily**: the `bootstrap` stage only registers definitions, and the connection is only built
> when `Hashids\Hashids` is actually resolved. A bad config does not break application startup — it throws `InvalidArgumentException` at resolve time.

The Yii 2 integration extends `yii\base\BootstrapInterface` / `yii\base\Component` and must be used in a **`yiisoft/yii2`**
environment (listed as suggest by this package).

---

## Yii 3

Yii 3 is a set of packages wired together by a PSR-11 container (`yiisoft/di`); there is no Yii 2-style application config array.
The adapter is a class implementing **`Yiisoft\Di\ServiceProviderInterface`** whose `getDefinitions()` returns six container identifiers.

**Registration**

```php
use Erikwang2013\Hashids\Yii3\ServiceProvider;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withProviders([
        // The config is passed straight to the provider — Yii 3 prescribes no
        // config file location, so where the values come from is up to the application.
        new ServiceProvider($params['hashids'] ?? []),
    ]);

$container = new Container($config);
```

**Usage**

```php
use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Hashids\Hashids;

$manager = $container->get(HashidsManager::class);
$hash    = $manager->encode(10, 20);                                  // default connection

$container->get('hashids')->connection('alternative')->encode(100);   // string alias + connection switch
$container->get(Hashids::class)->encode(1);                           // default connection, by class name
$container->get(HashidsFactory::class);                               // the factory
```

The container has singleton semantics of its own (the same id returns the same instance on every `get()`), so the alias and the
FQCN resolve to the same set of connections; the adapter's definitions only need to forward, with no need to build objects up front.

> **The exception class gets wrapped one level deeper**: the default connection is resolved lazily, so the assembly stage never fails;
> but at resolve time the container wraps this package's `InvalidArgumentException` in a **`Yiisoft\Di\BuildingException`** —
> catching `InvalidArgumentException` will not catch it; inspect `$e->getPrevious()` instead.

> **Provider definitions take precedence over plain definitions**: the real container assembles `definitions` first and `providers` after,
> so overriding hashids' bindings by editing the same-named key in `definitions` **has no effect** — use `extensions`, or change the provider.

The Yii 3 integration implements `Yiisoft\Di\ServiceProviderInterface` and must be used in a **`yiisoft/di`** environment
(listed as suggest by this package).

---

## Project mascot

The project mascot is **Hashy** — a little rounded square creature with an antenna on its head and a `#` printed on its chest; the vector art lives in [`docs/mascot.svg`](../../mascot.svg).

Terminals have no SVG, so an equivalent ASCII version ships in the code (`Erikwang2013\Hashids\Mascot`):

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

When the Webman plugin publishes `config/hashids.php` for the first time, it prints this greeting once along the way; if the config already exists (say, on a repeated `composer update`) it will not interrupt you a second time.

---

## Open Source is Not Easy, Your Support is Welcome

<p align="center">
  <img src="../../weixinpay.png" alt="WeChat Pay" width="130" height="130" />
  &nbsp;&nbsp;&nbsp;&nbsp;
  <img src="../../alipay.png" alt="Alipay" width="130" height="130" />
</p>

<p align="center"><strong>WeChat Pay</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Alipay</strong></p>

---


## License

MIT. See [LICENSE](../../../LICENSE).
