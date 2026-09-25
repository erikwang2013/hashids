<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace Erikwang2013\Hashids\Tests\Contract;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Erikwang2013\Hashids\Webman\Bootstrap;
use Hashids\Hashids as HashidsClient;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use support\Container;
use Webman\Bootstrap as WebmanBootstrapInterface;
use Webman\Config;
use Webman\Container as WebmanContainer;

use function config;
use function copy_dir;

/**
 * Webman 适配器契约测试：跑在**真实** workerman/webman-framework 上，
 * 不用 tests/Support/FrameworkStubs.php 那套手写替身。
 *
 * 手写替身自己实现 config()/Container/copy_dir，替身对框架行为的假设一旦是错的，
 * 测试照样全绿（本包就吃过这个亏：安装时覆盖用户 config/hashids.php，替身没抓到）。
 * 真框架里下面三条行为是「不写下来就会被记错」的，本测试逐条钉死：
 *
 *  1. Webman\Config::loadFromDir() 只加载「同目录下存在 app.php」的配置文件；
 *     且当该 app.php 的 enable 为空时整目录跳过 —— 这才是插件开关的真正位置。
 *  2. 插件目录 bootstrap.php 汇总出的类名列表，由框架逐个 ::start($worker) 调用。
 *  3. copy_dir($src, $dst) 默认**不覆盖**已存在文件，第三参为 true 才覆盖。
 */
final class WebmanContractTest extends TestCase
{
    /** 适配器必须注册的六个标识：3 个别名 + 3 个 FQCN。 */
    private const ALL_IDS = [
        HashidsManager::class,
        'hashids',
        HashidsFactory::class,
        'hashids.factory',
        HashidsClient::class,
        'hashids.connection',
    ];

    /** 临时应用配置目录，扮演真实 Webman 应用的 config/。 */
    private string $configDir;

    protected function setUp(): void
    {
        // Webman\Config 是静态类，跨测试会累积配置，每个测试都从空状态开始。
        Config::clear();
        $this->configDir = sys_get_temp_dir() . '/hashids-webman-contract-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        self::removeDir($this->configDir);
        Config::clear();
    }

    /**
     * 容器绑定端到端：真 Config → 真容器 → Bootstrap::start() → 六个标识全部可解析。
     */
    public function test_start_binds_all_six_ids_in_the_real_container(): void
    {
        $this->bootPlugin('contract-salt', 9);
        Bootstrap::start(null);

        $manager = Container::get(HashidsManager::class);
        $factory = Container::get(HashidsFactory::class);
        $client = Container::get(HashidsClient::class);

        self::assertInstanceOf(HashidsManager::class, $manager);
        self::assertInstanceOf(HashidsFactory::class, $factory);
        self::assertInstanceOf(HashidsClient::class, $client);

        // 字符串别名与 FQCN 必须解析到同一实例：否则「注入 'hashids'」与
        // 「注入 HashidsManager::class」会拿到两套连接池，配置改了只生效一半。
        self::assertSame($manager, Container::get('hashids'));
        self::assertSame($factory, Container::get('hashids.factory'));
        self::assertSame($client, Container::get('hashids.connection'));
        self::assertSame($client, $manager->connection());
        self::assertSame($factory, $manager->getFactory());
        self::assertSame('main', $manager->getDefaultConnection());

        foreach (self::ALL_IDS as $id) {
            self::assertTrue(Container::has($id), "$id 未注册进容器");
        }

        $hash = $client->encode(12345);
        self::assertSame([12345], $client->decode($hash), 'encode/decode 未往返');

        // salt/length 确实取自 config/hashids.php —— 排除「随便造了个能往返的 Hashids」。
        self::assertSame('contract-salt', config('hashids.connections.main.salt'));
        self::assertSame($hash, (new HashidsClient('contract-salt', 9))->encode(12345));
        self::assertNotSame($hash, (new HashidsClient('other-salt', 9))->encode(12345));
        self::assertSame(9, strlen($hash));
    }

    /**
     * 插件关闭时，框架根本不会把适配器列进 bootstrap 列表 —— 即不会注册任何绑定。
     *
     * 注意开关的位置：真 Webman 读的是插件目录下的 app.php 的 enable
     * （见 Config::loadFromDir），而不是本包 Bootstrap::start() 里读的
     * 顶层 $plugin['enable']。这里验的是**实际生效**的那道闸。
     */
    public function test_disabled_plugin_is_skipped_by_the_framework(): void
    {
        $this->bootPlugin('contract-salt', 9, pluginEnabled: false);

        self::assertNull(config('plugin.erikwang2013.hashids'), '插件关闭后整个插件配置应被跳过');
        self::assertNull(config('plugin.erikwang2013.hashids.bootstrap'), '关闭的插件不应出现在 bootstrap 列表里');

        // 开关关掉的是**插件**，不是本包在应用里的 config/hashids.php。
        self::assertSame('contract-salt', config('hashids.connections.main.salt'));

        self::assertFalse(Container::has('hashids'));
        self::assertFalse(Container::has(HashidsManager::class));
    }

    /**
     * 上一条的对照组：开启时框架确实会把适配器类交给 ::start($worker)。
     * 两条一起才有意义 —— 只有它们同时成立，「关闭 = 不注册」才不是空断言。
     */
    public function test_enabled_plugin_exposes_adapter_to_framework_bootstrap_list(): void
    {
        $this->bootPlugin('contract-salt', 9);

        $list = config('plugin.erikwang2013.hashids.bootstrap');
        self::assertIsArray($list, '插件骨架 bootstrap.php 未被真框架读进 bootstrap 列表');
        self::assertSame([Bootstrap::class], array_values($list));
        self::assertContains(
            WebmanBootstrapInterface::class,
            class_implements(Bootstrap::class),
            '适配器未实现真 Webman\Bootstrap 接口'
        );

        // 框架就是这样调它的：静态、参数是 Worker|null。
        Bootstrap::start(null);
        self::assertInstanceOf(HashidsManager::class, Container::get('hashids'));
    }

    /**
     * 纵深缺口：插件已关闭，但 start() 被**直接**调用。
     *
     * 场景是用户把 Bootstrap::class 手动注册进应用级 config/bootstrap.php ——
     * 那条循环不受插件目录跳过保护，插件关了照样会跑到这里。所以这道守卫
     * 必须自己看出来「插件是关的」。
     *
     * 要害：插件关闭 ⟹ 框架跳过整个插件目录 ⟹ config() 里根本没有
     * plugin.erikwang2013.hashids 这个键（见下面 assertNull 的前提断言）。
     * 于是 `$plugin['app']['enable'] ?? true` 的 ?? 分支必然兜底 —— 读到 null
     * 反而当成「开」。守卫必须把「配置缺失」判成关闭：
     * `!is_array($plugin) || !($plugin['app']['enable'] ?? false)`。
     */
    public function test_start_registers_nothing_when_plugin_is_disabled(): void
    {
        $this->bootPlugin('contract-salt', 9, pluginEnabled: false);

        // 前提：框架的目录级跳过确实生效了 —— 这正是「手动注册」场景的起点。
        // 插件关闭时 config() 里根本没有这个键，守卫拿不到 app.enable。
        self::assertNull(
            config('plugin.erikwang2013.hashids'),
            '前置条件不成立：插件关闭时框架本应完全跳过插件目录'
        );

        Bootstrap::start(null);

        self::assertSame([], self::containerDefinitions(), '插件已关闭，容器却被动过：addDefinitions() 被调用了');
        foreach (self::ALL_IDS as $id) {
            self::assertFalse(Container::has($id), "$id 不应被注册（插件已关闭）");
        }
    }

    /**
     * 上一条的对照组：同样直接调 start()，插件开启时六个标识必须全部注册。
     * 只有两条同时存在，「关闭时不注册」才不是空断言 —— 否则 start() 写成空实现
     * 也能让前一条变绿。
     */
    public function test_start_registers_all_six_ids_when_plugin_is_enabled(): void
    {
        $this->bootPlugin('contract-salt', 9);

        self::assertTrue((bool) config('plugin.erikwang2013.hashids.app.enable'));

        Bootstrap::start(null);

        self::assertCount(
            count(self::ALL_IDS),
            self::containerDefinitions(),
            '注册的标识数与预期不符（多了或少了）'
        );
        foreach (self::ALL_IDS as $id) {
            self::assertTrue(Container::has($id), "$id 应被注册");
        }

        // 注册的不只是「有键」，而是能真干活的连接。
        $client = Container::get('hashids.connection');
        self::assertInstanceOf(HashidsClient::class, $client);
        $hash = $client->encode(12345);
        self::assertSame([12345], $client->decode($hash));
        self::assertSame($hash, (new HashidsClient('contract-salt', 9))->encode(12345));
    }

    /**
     * 真容器里的 definitions 表 —— addDefinitions() 是否被调用过，看这里最直接。
     *
     * @return array<string, mixed>
     */
    private static function containerDefinitions(): array
    {
        return (new ReflectionProperty(WebmanContainer::class, 'definitions'))
            ->getValue(Container::instance());
    }

    /**
     * 真 copy_dir() 的覆盖语义 —— 「插件骨架不被覆盖、唯独用户配置被覆盖」这个
     * 不对称的老根子就在这里：默认不覆盖，只有显式传 true 才覆盖。
     */
    public function test_real_copy_dir_never_overwrites_an_existing_file_by_default(): void
    {
        $src = sys_get_temp_dir() . '/hashids-copy-src-' . bin2hex(random_bytes(4));
        $dst = sys_get_temp_dir() . '/hashids-copy-dst-' . bin2hex(random_bytes(4));

        try {
            self::write($src . '/app.php', '<?php return ["enable" => true];');
            self::write($src . '/hashids.php', '<?php return ["salt" => ""];');
            // 用户已经装好骨架、并把真 salt 写进了自己的配置。
            self::write($dst . '/hashids.php', '<?php return ["salt" => "USER-REAL-SALT"];');
            self::write($dst . '/user-only.php', '<?php return ["mine" => true];');

            copy_dir($src, $dst);
            self::assertSame(
                '<?php return ["salt" => "USER-REAL-SALT"];',
                file_get_contents($dst . '/hashids.php'),
                'copy_dir 默认覆盖了已存在的用户配置'
            );
            self::assertFileExists($dst . '/app.php', '缺失的文件应被复制');
            self::assertFileExists($dst . '/user-only.php', 'copy_dir 不是镜像同步，不应删除目标端多余文件');

            copy_dir($src, $dst, true);
            self::assertSame(
                '<?php return ["salt" => ""];',
                file_get_contents($dst . '/hashids.php'),
                'copy_dir($src, $dst, true) 应覆盖 —— 这正是不加第三参的原因'
            );
            self::assertFileExists($dst . '/user-only.php', '即使覆盖模式也不删除多余文件');
        } finally {
            self::removeDir($src);
            self::removeDir($dst);
        }
    }

    /**
     * 铺一个能被真 Webman 加载的临时应用配置目录，然后 Config::load()。
     */
    private function bootPlugin(string $salt, int $length, bool $pluginEnabled = true): void
    {
        // Config::loadFromDir() 只加载「同目录存在 app.php」的配置文件，
        // 所以顶层 app.php 不是摆设：没有它，hashids.php 根本不会被读进来。
        self::write($this->configDir . '/app.php', '<?php return [];');
        // Bootstrap::start() 调 Container::instance()，即 Config::get('container')。
        self::write($this->configDir . '/container.php', '<?php return new \Webman\Container();');
        self::write($this->configDir . '/hashids.php', self::php([
            'default' => 'main',
            'connections' => [
                'main' => ['salt' => $salt, 'length' => $length],
            ],
        ]));
        self::write(
            $this->configDir . '/plugin/erikwang2013/hashids/app.php',
            self::php(['enable' => $pluginEnabled])
        );
        // 与包内 src/config/plugin/erikwang2013/hashids/bootstrap.php 同形。
        self::write(
            $this->configDir . '/plugin/erikwang2013/hashids/bootstrap.php',
            self::php([Bootstrap::class])
        );

        // 与真应用的启动路径同形：support/bootstrap.php 第 54 行
        // support\App::loadAllConfig(['route']) → Config::load(config_path(), ['route'])。
        // （注意别照抄 App::run() 里那份 ['route', 'container'] —— 那是请求期，
        //   此时 container 已被上一次 load 合并进配置，请求期只是不再重复合并。）
        Config::load($this->configDir, ['route']);
    }

    /**
     * @param array<mixed> $value
     */
    private static function php(array $value): string
    {
        return "<?php\n\nreturn " . var_export($value, true) . ";\n";
    }

    private static function write(string $file, string $contents): void
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($file, $contents);
    }

    private static function removeDir(string $dir): void
    {
        if (is_link($dir) || is_file($dir)) {
            unlink($dir);

            return;
        }
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $file) {
            self::removeDir($dir . '/' . $file);
        }
        rmdir($dir);
    }
}
