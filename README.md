# erikwang2013/hashids

> 官网 / Website: [https://package.erik.xyz/hashids/](https://package.erik.xyz/hashids/)

**Languages:** **中文** · [English](docs/i18n/en/README.md) · [한국어](docs/i18n/ko/README.md) · [Русский](docs/i18n/ru/README.md) · [Deutsch](docs/i18n/de/README.md) · [Français](docs/i18n/fr/README.md) · [Español](docs/i18n/es/README.md) · [Português](docs/i18n/pt/README.md) · [हिन्दी](docs/i18n/hi/README.md) · [العربية](docs/i18n/ar/README.md) · [বাংলা](docs/i18n/bn/README.md) · [Bahasa Indonesia](docs/i18n/id/README.md) · [日本語](docs/i18n/ja/README.md)

<p align="center">
  <img src="./docs/mascot.svg" alt="哈希迪 Hashy — erikwang2013/hashids 项目宠物" width="200" />
</p>

<p align="center"><strong>哈希迪 Hashy</strong> — 项目宠物，胸口的 <code>#</code> 是它的招牌</p>

把数据库自增 ID 换成短小、不可猜测的字符串，**一套 API 同时跑在 Laravel、Webman、ThinkPHP、Hyperf、Yii 2、Yii 3 上**。

底层依赖 [`hashids/hashids`](https://github.com/vinkla/hashids) v5；配置与用法对齐 [**vinkla/hashids**](https://github.com/vinkla/laravel-hashids)（多连接、默认连接、`HashidsManager` + 工厂），可平滑替换。

## 项目说明

**Hashids** 是一款短 ID 生成器，可将数字 ID（如数据库主键）编码为短小、唯一且不可猜测的字符串。它不同于 UUID 或雪花 ID——Hashids 更适合用于面向用户的场景（URL、分享码、订单号等），在保持短小可读的同时隐藏原始数字。

本包 `erikwang2013/hashids` 是 Hashids 的 **PHP 多框架集成层**，在设计上参考并对齐了 [vinkla/hashids](https://github.com/vinkla/laravel-hashids) 的 API 风格（多连接、默认连接、Manager + Factory 模式），并扩展支持了国内常用的其他 PHP 框架。

**核心特性：**

- **多框架兼容**：同一套 API 同时支持 Laravel、Webman、ThinkPHP、Hyperf、Yii 2、Yii 3，迁移成本极低。
- **多连接支持**：一个应用可同时配置多套 Salt/Length 组合（如用户 ID 与订单 ID 使用不同盐值），通过 `connection('xxx')` 切换。
- **无框架依赖**：不依赖任何特定框架即可独立使用，直接 `new HashidsManager($config, $factory)` 即可工作。
- **对齐 vinkla/hashids**：Laravel 下 Facade、容器绑定、`config/hashids.php` 格式均与 vinkla/hashids 一致，可平滑替换。
- **框架原生风格**：各框架集成遵循各自的惯用法——Laravel 用 ServiceProvider + Facade，Webman 用 Plugin + Bootstrap，ThinkPHP 用 Service，Hyperf 用 ConfigProvider，Yii 2 用 Bootstrap + 应用组件，Yii 3 用 ServiceProvider。

**适用场景：**

| 场景 | 说明 |
|------|------|
| 隐藏数据库自增 ID | 将 `user_id=100` 映射为 `/user/3kTMd`，避免暴露业务规模 |
| 生成短链接/分享码 | 比 UUID 更短，比随机字符串可控 |
| 订单号/流水号 | 可读性好，便于客服沟通与日志排查 |
| 多租户/多模块隔离 | 不同连接使用不同 Salt，确保编码空间相互独立 |

**注意事项：**

- Hashids 是 **编码（encode/decode）而非加密**。Salt 仅增加猜测难度，不可用于安全敏感场景（如 token、密码）。
- 一旦上线后修改 Salt 或 Length，所有已编码的 ID 将变为无效，请提前规划并固定配置。
- **Salt 留空等于没有保护**：空 salt 下编码结果可枚举（`encode(1)`、`encode(2)`… 顺序可预测）。写成
  `env('HASHIDS_SALT', '')` 时，漏配环境变量既不报错也不告警，只是静默降级成空 salt —— 上线前请确认盐已设置。
- **常驻内存框架（Webman / Hyperf）下改配置需要重启进程**：`HashidsManager` 在构造时快照配置并永久缓存连接，
  改 `config/hashids.php`（或用配置中心换盐）不会热生效，`reload` 或重启后才会。

## 项目结构

```
hashids/
├── src/
│   ├── HashidsManager.php                     # 核心：多连接解析、实例缓存、默认连接代理
│   ├── HashidsFactory.php                     # 核心：由连接配置构建 Hashids 实例
│   ├── Mascot.php                             # 项目宠物「哈希迪 Hashy」的 ASCII 版
│   ├── Install.php                            # Webman 安装 / 卸载钩子
│   ├── Laravel/
│   │   ├── HashidsServiceProvider.php         # 容器单例 + 别名 + 配置发布
│   │   └── Facades/Hashids.php                # Facade（默认连接）
│   ├── Webman/
│   │   └── Bootstrap.php                      # 进程启动时注册容器定义
│   ├── ThinkPHP/
│   │   └── HashidsService.php                 # think\Service 注册绑定
│   ├── Hyperf/
│   │   ├── ConfigProvider.php                 # 依赖映射 + 配置发布
│   │   ├── HashidsManagerFactory.php
│   │   └── HashidsClientFactory.php
│   ├── Yii2/
│   │   ├── Bootstrap.php                      # BootstrapInterface：注册 Yii::$container
│   │   └── Component.php                      # 应用组件：Yii::$app->hashids
│   ├── Yii3/
│   │   └── ServiceProvider.php                # ServiceProviderInterface：返回六个定义
│   └── config/plugin/erikwang2013/hashids/    # Webman 插件骨架（安装时拷贝到项目）
│       ├── app.php                            # enable 开关
│       └── bootstrap.php                      # 注册 Webman\Bootstrap
├── config/
│   ├── hashids.php                            # 扁平配置：Laravel / Webman / ThinkPHP
│   └── autoload/hashids.php                   # Hyperf 配置（结构同扁平，路径不同）
├── tests/
│   ├── HashidsManagerTest.php                 # 核心行为
│   ├── HashidsFactoryTest.php
│   ├── InstallTest.php
│   ├── MascotTest.php                         # 项目宠物：字形对齐与问候
│   ├── Laravel/ · Webman/ · ThinkPHP/ · Hyperf/ · Yii2/ · Yii3/   # 各框架适配测试
│   ├── Config/PluginConfigTest.php
│   ├── Contract/                              # 真框架契约测试（CI 单独作业，见下）
│   └── Support/FrameworkStubs.php             # 测试用的框架类替身
├── docs/
│   ├── mascot.svg                             # 项目宠物「哈希迪 Hashy」
│   ├── architecture.svg                       # 架构设计图
│   ├── features.svg                           # 功能设计图
│   └── lifecycle.svg                          # 生命周期图
├── .github/workflows/release.yml              # 推送 main 后自动打 tag 并发 release
├── composer.json
└── phpunit.xml.dist
```

> `vendor/`、`composer.lock` 等依赖产物未列出；`src/config/` 是**插件骨架**，`config/` 是**可发布的配置样例**，两者用途不同。

## 架构设计

![架构设计](./docs/architecture.svg)

**四层单向依赖**，上层依赖下层，反向不成立：

| 层 | 职责 | 位置 |
|----|------|------|
| 应用调用层 | Facade、容器 / 助手函数、构造注入三种入口 | 业务代码 |
| 框架适配层 | 只做接线：容器绑定、配置读取、配置发布 | `src/<Framework>/` |
| 核心层 | 多连接管理与实例构建，**不依赖任何框架** | `src/HashidsManager.php`、`src/HashidsFactory.php` |
| 底层依赖 | 实际编解码实现 | `hashids/hashids` |

核心层是整个包的重心：`HashidsManager` 持有配置，`connection($name)` 按需构建并缓存连接，`__call()` 把未指定连接的方法调用转发到默认连接；`HashidsFactory::make()` 是唯一构造 `Hashids\Hashids` 的地方。六个框架的适配类加起来约 490 行（含 Facade、两个 Hyperf 工厂与 Yii 2 的应用组件）——它们只负责把内核接进各自的容器。

## 功能设计

![功能设计](./docs/features.svg)

六个能力分组，全部围绕同一个内核：

- **编解码 API**：`encode()` / `decode()` / `encodeHex()` / `decodeHex()`，经 `__call()` 落到默认连接，无需显式 `connection()`。
- **多连接管理**：`connection('alternative')` 切换；懒加载构建，同一连接只构建一次。
- **多框架适配**：Laravel 用 `ServiceProvider`、Webman 用 `Install` + `Bootstrap`、ThinkPHP 用 `Service`、Hyperf 用 `ConfigProvider`、Yii 2 用 `BootstrapInterface` + 应用组件、Yii 3 用 `ServiceProviderInterface`，各自遵循框架惯用法。
- **容器绑定**：类名（`HashidsManager`、`HashidsFactory`、`Hashids\Hashids`）与字符串键（`'hashids'`、`'hashids.factory'`、`'hashids.connection'`）双轨绑定，六框架一致；后两个字符串键与 vinkla/hashids 同名，便于迁移。
- **配置与发布**：六个框架的配置**形状一致**，都是**扁平数组**（根级 `default` + `connections`），差别只在放哪儿——Laravel / Webman / ThinkPHP 是 `config/hashids.php`，Hyperf 是 `config/autoload/hashids.php`，Yii 2 是 `config/params.php` 里的 `params['hashids']`，Yii 3 直接传给 `ServiceProvider` 构造函数（框架不约定配置文件）。
- **零框架依赖内核**：`new HashidsManager($config, $factory)` 即可工作，`$config` 入参容忍非数组（归一化为空数组）。

## 生命周期

![生命周期](./docs/lifecycle.svg)

| 阶段 | 发生了什么 |
|------|-----------|
| **安装期** | `composer require` 拉包 → Laravel 自动发现 / Webman 安装钩子拷贝插件骨架 / Yii 2 在应用配置里登记 `Bootstrap` 或组件 / Yii 3 在 `ContainerConfig::withProviders()` 里登记 `ServiceProvider` → 加载 `salt` / `length` / `alphabet` |
| **运行期** | 容器解析出 `HashidsManager` → `encode()` / `decode()` → `__call()` 转发到 `connection($name)` → **命中缓存直接复用，未命中才 `HashidsFactory::make()` 构建并写入 `$connections`** → 返回短 ID 或原数字 |
| **卸载期** | `composer remove` 触发 `Install::uninstall()`，移除 `config/plugin/erikwang2013/hashids`；**`config/hashids.php` 会保留**，是否清理由使用者决定 |

连接是**按需构建**的：只调用默认连接的进程，不会为 `alternative` 之类未使用的连接付出任何构建成本。

## 安装

```bash
composer require erikwang2013/hashids
```

> **运行环境**：底层 `hashids/hashids` **必须**有 `ext-bcmath` 或 `ext-gmp`（二选一），否则第一次编码就会抛
> `RuntimeException: Missing math extension for Hashids`。这两个扩展在 `hashids/hashids` 里只列于 `suggest`，
> 而 Composer 2 已不再打印 suggest —— 于是在精简镜像（如 `php:8.3-fpm-alpine`）上安装期毫无提示、运行期才炸，
> 且每个用到 Hashids 的请求都会 500。本包 `composer.json` 的 `suggest` 已补上这两个键，但仍需你确认扩展已启用。

## 配置结构（Laravel / Webman / ThinkPHP / Yii 2 / Yii 3）

与仓库 `config/hashids.php` 一致：

- `default`：默认连接名（如 `main`）。
- `connections`：连接名 => `salt`、`length`、可选 `alphabet`。

> **Hyperf** 的配置文件路径不同（`config/autoload/hashids.php`），但结构相同，见下文 Hyperf 小节。
>
> **Yii 2** 不用单独的配置文件，直接把同一个数组放进应用 `config/params.php` 的 `params['hashids']`，见下文 Yii 2 小节。
>
> **Yii 3** 也不读配置文件，把同一个数组传给 `ServiceProvider` 构造函数即可，见下文 Yii 3 小节。

## 无框架用法

可直接实例化管理器：

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

与 [vinkla/hashids](https://github.com/vinkla/laravel-hashids) 类似：容器注册 `HashidsManager`，多连接；默认连接支持 Facade 与方法转发。

Laravel 5.5+ 会读取本包 `composer.json` 的 `extra.laravel`，自动注册 `HashidsServiceProvider` 与 Facade 别名 `Hashids`。

**发布配置（可选）**

```bash
php artisan vendor:publish --tag=hashids-config
```

生成 `config/hashids.php`。若不发布，扩展包会在注册阶段合并内置默认配置。

**Facade（默认连接）**

```php
use Erikwang2013\Hashids\Laravel\Facades\Hashids;

$hash = Hashids::encode(1, 2, 3);
$numbers = Hashids::decode($hash);
```

**指定连接**

```php
use Erikwang2013\Hashids\Laravel\Facades\Hashids;

$hash = Hashids::connection('alternative')->encode(100);
```

**依赖注入 `HashidsManager`**

```php
use Erikwang2013\Hashids\HashidsManager;

public function __construct(private HashidsManager $hashids) {}

$this->hashids->encode(1);
$this->hashids->connection('alternative')->encode(2);
```

**注入底层 `Hashids\Hashids`（默认连接）**

```php
use Hashids\Hashids;

public function __construct(private Hashids $hashids) {}
```

运行 Laravel 集成需要项目已安装 `laravel/framework`（含 `illuminate/support` 等）。本包将 `illuminate/*` 列为 **require-dev**，仅供包自身测试。

---

## Webman

通过 **`Install`** 在安装时拷贝 `config/plugin/erikwang2013/hashids` 与根目录 **`config/hashids.php`**，并由插件 **bootstrap** 向 Webman 容器注册 `HashidsManager`。

项目的 `composer.json` 中若已有 `support\Plugin::install` / `update` / `uninstall` 钩子，安装本包时会自动执行安装脚本（`WEBMAN_PLUGIN = true`）。

**安装后文件**

- `config/plugin/erikwang2013/hashids/app.php`：`enable` 开关。
- `config/plugin/erikwang2013/hashids/bootstrap.php`：注册 `Erikwang2013\Hashids\Webman\Bootstrap`。
- `config/hashids.php`：多连接配置（首次安装或确认覆盖时写入）。

若自动拷贝未执行，可从扩展包内手动复制上述路径的示例配置。

**关闭插件**（`config/plugin/erikwang2013/hashids/app.php`）

```php
<?php
return [
    'enable' => false,
];
```

**容器绑定**

- `Erikwang2013\Hashids\HashidsManager` / `'hashids'`
- `Erikwang2013\Hashids\HashidsFactory` / `'hashids.factory'`
- `Hashids\Hashids` / `'hashids.connection'`（默认连接实例）

**控制器示例**

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

**指定连接**

```php
$manager->connection('alternative')->encode(99);
```

Composer 卸载包时会触发 `Plugin::uninstall`，移除 `config/plugin/erikwang2013/hashids`；**不会**删除 `config/hashids.php`，是否保留由你决定。

---

## ThinkPHP

通过 **自定义服务类** 注册 `HashidsManager`；配置仍为顶层含 `default` 与 `connections` 的 **`config/hashids.php`**。

**注册服务**

在应用 **`config/service.php`**（具体路径随 TP 版本可能不同）的 `services` 中加入：

```php
<?php

return [
    // ...
    \Erikwang2013\Hashids\ThinkPHP\HashidsService::class,
];
```

若使用应用级 `app/AppService.php`，也可在 `register()` 中写入等价绑定。

**配置文件**

将扩展包内 `config/hashids.php` 复制到应用 `config/hashids.php`（或自行合并同名配置）。

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

**使用示例**

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

ThinkPHP 集成继承 `think\Service`，需在 **`topthink/framework`** 环境中使用（本包列为 suggest）。

---

## Hyperf

Composer **`extra.hyperf.config`** 会载入 `ConfigProvider`，向容器注册 `HashidsFactory`、`HashidsManager`、默认连接的 `Hashids\Hashids`。

将扩展包内 **`config/autoload/hashids.php`** 复制到项目 **`config/autoload/hashids.php`**（或使用项目的配置发布命令）。

该文件必须返回**扁平数组**（根级 `default` + `connections`）。

Hyperf 的 `ConfigFactory` 按**文件名**归并（`Arr::set($config, 'hashids', require $file)`），所以 `config('hashids')` 返回的就是文件内容本身——**不要再套一层 `'hashids' =>`**。套了的话 `connections` 不可达，取连接时会抛 `Hashids connection [main] is not configured`。

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

**容器绑定**

| 抽象 | 实现 |
|------|------|
| `Erikwang2013\Hashids\HashidsFactory` / `'hashids.factory'` | 默认构造 |
| `Erikwang2013\Hashids\HashidsManager` / `'hashids'` | `HashidsManagerFactory` |
| `Hashids\Hashids` / `'hashids.connection'` | `HashidsClientFactory`（默认连接） |

**Controller / 构造函数注入**

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

六个框架的配置**结构一致**（根级 `default` + `connections`），差别只在放哪儿：Hyperf 用 **`config/autoload/hashids.php`**，Laravel / Webman / ThinkPHP 用 **`config/hashids.php`**，Yii 2 放进 **`config/params.php` 的 `params['hashids']`**，Yii 3 直接传给 `ServiceProvider`。

---

## Yii 2

适配器实现 **`yii\base\BootstrapInterface`**，把六个标识注册进 `Yii::$container`；另提供 **`yii\base\Component`** 子类，让 Yii 惯用的 `Yii::$app->hashids` 可用。

### 两种入口，按需选（不能互相替代）

**入口 A：容器 —— `config/web.php` 的 `bootstrap` 数组**

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

**入口 B：应用组件 —— `config/web.php` 的 `components` 数组**

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
Yii::$app->hashids->encode(1);                              // __call 转默认连接
Yii::$app->hashids->connection('alternative')->encode(100); // 切连接
Yii::$app->hashids->manager;                                // 取底层 HashidsManager
```

> **为什么两种入口都要有**：`yii\di\ServiceLocator::get()` **不会**回落到 `Yii::$container`，
> 只认登记在自己 `_definitions` 里的组件。所以只登记 `bootstrap` 并不会让 `Yii::$app->hashids`
> 生效；反过来只配 `components` 也不会让 `Yii::$container->get()` 拿到实例。要用哪个就配哪个。

### 配置文件

Yii 2 没有 `config/hashids.php` 这种约定，配置放在应用 **`config/params.php`** 的 `params['hashids']`：

```php
return [
    // ...
    'hashids' => [
        'default'     => 'main',
        'connections' => [
            'main' => [
                // Yii 2 没有 Laravel 那样的全局 env() 助手，用 getenv()
                // 或你自己应用里的 env 机制。
                'salt'   => getenv('HASHIDS_SALT') ?: '',
                'length' => (int) (getenv('HASHIDS_LENGTH') ?: 0),
            ],
        ],
    ],
];
```

`Bootstrap` 读的就是 `Yii::$app->params['hashids']`；该键缺失时按空数组处理（默认连接名回落
`main`），不报错 —— 但缺 salt 时哈希可枚举，上线前请确认盐已设置。

> **默认连接是惰性解析的**：`bootstrap` 阶段只登记定义，真正取 `Hashids\Hashids` 时才构建连接。
> 配置写错不会让整个应用启动失败，而是在取用时抛 `InvalidArgumentException`。

Yii 2 集成继承 `yii\base\BootstrapInterface` / `yii\base\Component`，需在 **`yiisoft/yii2`**
环境中使用（本包列为 suggest）。

---

## Yii 3

Yii 3 是一组 PSR-11 容器（`yiisoft/di`）装配起来的包，没有 Yii 2 那种应用配置数组。
适配器是一个实现 **`Yiisoft\Di\ServiceProviderInterface`** 的类，`getDefinitions()` 返回六个容器标识。

**注册**

```php
use Erikwang2013\Hashids\Yii3\ServiceProvider;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withProviders([
        // 配置直接传给 provider —— Yii 3 不约定配置文件位置，
        // 值从哪来（params、env、配置中心）由应用自己决定。
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
$hash    = $manager->encode(10, 20);                                  // 默认连接

$container->get('hashids')->connection('alternative')->encode(100);   // 字符串别名 + 切连接
$container->get(Hashids::class)->encode(1);                           // 按类名取默认连接
$container->get(HashidsFactory::class);                               // 工厂
```

容器自带单例语义（同一个 id 每次 `get()` 返回同一实例），所以别名与 FQCN 拿到的是同一套连接，
适配器的定义只要转发即可，不必预先造对象。

> **报错类名会多包一层**：默认连接是惰性解析的，装配阶段不会失败；但取用时容器会把本包的
> `InvalidArgumentException` 包进 **`Yiisoft\Di\BuildingException`** 抛出 —— `catch` 里写
> `InvalidArgumentException` 是接不住的，要看 `$e->getPrevious()`。

> **provider 的定义优先于普通 definitions**：真容器的装配顺序是先 `definitions`、后 `providers`，
> 所以想覆盖 hashids 的绑定，改 `definitions` 里的同名键**无效**，要用 `extensions` 或改 provider。

Yii 3 集成实现 `Yiisoft\Di\ServiceProviderInterface`，需在 **`yiisoft/di`** 环境中使用
（本包列为 suggest）。

---

## 项目宠物

项目宠物是 **哈希迪 Hashy**——一只头顶天线、胸口印着 `#` 的圆角方块小宠物，矢量图见 [`docs/mascot.svg`](docs/mascot.svg)。

终端里没有 SVG，所以代码里留了一份等价的 ASCII 版本（`Erikwang2013\Hashids\Mascot`）：

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

Webman 插件首次发布 `config/hashids.php` 时会顺带打印一次这次问候；配置已存在时（例如重复执行 `composer update`）不会重复打扰。

---


<!-- pet-expressions:start -->
## 项目宠物表情形态 · 哈希迪 Hashy

<table align="center">
<tr><td align="center"><img src="docs/pet-expressions/smile.svg" width="88" alt="笑"><br><b>笑</b><br><code>:emoji-smile:</code></td><td align="center"><img src="docs/pet-expressions/joy.svg" width="88" alt="欢乐"><br><b>欢乐</b><br><code>:emoji-joy:</code></td><td align="center"><img src="docs/pet-expressions/cry.svg" width="88" alt="哭"><br><b>哭</b><br><code>:emoji-cry:</code></td><td align="center"><img src="docs/pet-expressions/depressed.svg" width="88" alt="沮丧"><br><b>沮丧</b><br><code>:emoji-depressed:</code></td><td align="center"><img src="docs/pet-expressions/terrified.svg" width="88" alt="惊恐"><br><b>惊恐</b><br><code>:emoji-terrified:</code></td><td align="center"><img src="docs/pet-expressions/angry.svg" width="88" alt="愤怒"><br><b>愤怒</b><br><code>:emoji-angry:</code></td></tr>
<tr><td align="center"><img src="docs/pet-expressions/coquettish.svg" width="88" alt="撒娇"><br><b>撒娇</b><br><code>:emoji-coquettish:</code></td><td align="center"><img src="docs/pet-expressions/cute.svg" width="88" alt="卖萌"><br><b>卖萌</b><br><code>:emoji-cute:</code></td><td align="center"><img src="docs/pet-expressions/pleasing.svg" width="88" alt="讨好"><br><b>讨好</b><br><code>:emoji-pleasing:</code></td><td align="center"><img src="docs/pet-expressions/knocking.svg" width="88" alt="敲打"><br><b>敲打</b><br><code>:emoji-knocking:</code></td><td align="center"><img src="docs/pet-expressions/facepalm.svg" width="88" alt="捂脸"><br><b>捂脸</b><br><code>:emoji-facepalm:</code></td><td align="center"><img src="docs/pet-expressions/tongue.svg" width="88" alt="吐舌头"><br><b>吐舌头</b><br><code>:emoji-tongue:</code></td></tr>
</table>

> 12 种彩色动态形态：笑 / 欢乐 / 哭 / 沮丧 / 惊恐 / 愤怒 / 撒娇 / 卖萌 / 讨好 / 敲打 / 捂脸 / 吐舌头。每个 SVG 自带表情、情绪特效与整体动效，并遵循 `prefers-reduced-motion`。
<!-- pet-expressions:end -->

<!-- assets-license:start -->
## 视觉资产授权 / Visual Assets License

本项目的项目宠物、图片、图标、表情（包括 `docs/pet-expressions/` 等目录下的
SVG / PNG）**不是开源资产**，版权归作者所有。未经书面许可不得复制、修改、再分发或
用于商业用途；商业使用需取得商业授权，联系 **erik@erik.xyz**。详见
[ASSETS-LICENSE.md](ASSETS-LICENSE.md)。

> 代码部分仍遵循仓库原有 `LICENSE`；第三方素材（如明确标注的 Go Gopher 等）仍遵循其原许可证。
<!-- assets-license:end -->


## 开源不易，欢迎支持 / Open Source is Not Easy, Your Support is Welcome

<p align="center">
  <img src="./docs/weixinpay.png" alt="微信 WeChat Pay" width="130" height="130" />
  &nbsp;&nbsp;&nbsp;&nbsp;
  <img src="./docs/alipay.png" alt="支付宝 Alipay" width="130" height="130" />
</p>

<p align="center"><strong>微信 WeChat Pay</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>支付宝 Alipay</strong></p>

---


## License

MIT. See [LICENSE](LICENSE).
