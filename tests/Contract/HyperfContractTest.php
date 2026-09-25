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
use Erikwang2013\Hashids\Hyperf\ConfigProvider;
use Erikwang2013\Hashids\Hyperf\HashidsClientFactory;
use Erikwang2013\Hashids\Hyperf\HashidsManagerFactory;
use Hashids\Hashids as HashidsClient;
use Hyperf\Config\ConfigFactory;
use Hyperf\Config\ProviderConfig;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSource;
use PHPUnit\Framework\TestCase;

/**
 * Hyperf 集成契约测试 —— 用**真框架**跑，不用替身。
 *
 * 这里的存在理由：本包 config/autoload/hashids.php 曾经多包了一层 `'hashids' =>`，
 * 而 Hyperf 的 ConfigFactory::readPaths() 按**文件名**归并（Arr::set($config, 'hashids', require $file)），
 * 于是 config('hashids') 返回 {"hashids":{...}}，connections 不可达，取连接时抛
 * "Hashids connection [main] is not configured"。当时的单元测试全用手写替身，
 * 替身是按同一个错误假设写的，所以一直是绿的 —— 只有真 ConfigFactory 才能戳穿它。
 *
 * 真框架装在另一个 vendor（见 tests/Contract/bootstrap.php），
 * 跑法：CONTRACT_VENDOR=/path/to/vendor/autoload.php vendor/bin/phpunit --configuration phpunit.contract.xml
 */
final class HyperfContractTest extends TestCase
{
    private static string $basePath = '';

    public static function setUpBeforeClass(): void
    {
        self::$basePath = sys_get_temp_dir() . '/hashids-contract-' . getmypid() . '-' . bin2hex(random_bytes(4));

        // 造一个临时 BASE_PATH 应用目录：ConfigFactory 读 config/autoload，Composer 读 composer.lock。
        mkdir(self::$basePath . '/config/autoload', 0777, true);
        copy(
            dirname(__DIR__, 2) . '/config/autoload/hashids.php',
            self::$basePath . '/config/autoload/hashids.php'
        );
        file_put_contents(self::$basePath . '/composer.lock', '{"packages":[],"packages-dev":[]}');

        // 哨兵：用来证明 ConfigFactory 真的读了这个临时目录，而不是别处的 BASE_PATH。
        file_put_contents(
            self::$basePath . '/config/autoload/contract_probe.php',
            "<?php\n\nreturn ['probe' => 'hashids-contract'];\n"
        );

        // 为什么不用全局 BASE_PATH：真框架的 autoload files（workerman/webman-framework 的
        // helpers.php）在 bootstrap 时就已经 define 了全局 BASE_PATH，常量一旦定义就改不了。
        // 但 Hyperf 源码里写的是不带反斜杠的 BASE_PATH，PHP 的常量查找规则是
        // 「先 namespace\BASE_PATH，再全局 BASE_PATH」—— 定义命名空间常量就能为本进程
        // 把 Hyperf 看到的根目录指向临时目录，全局 BASE_PATH 保持不动（不污染、不碰别人的目录）。
        // 两个都要：ConfigFactory 在 Hyperf\Config，Composer（读 composer.lock）在 Hyperf\Support。
        define('Hyperf\Config\BASE_PATH', self::$basePath);
        define('Hyperf\Support\BASE_PATH', self::$basePath);

        // 清掉可能被其它测试类填充过的 provider 配置静态缓存。
        ProviderConfig::clear();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$basePath === '') {
            return;
        }

        @unlink(self::$basePath . '/composer.lock');
        @unlink(self::$basePath . '/config/autoload/hashids.php');
        @unlink(self::$basePath . '/config/autoload/contract_probe.php');
        @rmdir(self::$basePath . '/config/autoload');
        @rmdir(self::$basePath . '/config');
        @rmdir(self::$basePath);

        self::$basePath = '';
    }

    /**
     * 核心回归：真 ConfigFactory 加载本包真实的 config/autoload/hashids.php 后，
     * config('hashids') 必须**扁平** —— 根级就是 default + connections。
     */
    public function test_real_config_factory_delivers_flat_hashids_config(): void
    {
        $config = $this->hyperf_config();

        // 先确认读的是上面那个临时 BASE_PATH（否则下面的断言测的可能是别的目录）。
        self::assertSame('hashids-contract', $config->get('contract_probe.probe'));

        $hashids = $config->get('hashids');

        self::assertIsArray($hashids);
        self::assertArrayHasKey(
            'connections',
            $hashids,
            'config(\'hashids\') 里没有 connections —— 配置文件多半又多包了一层 \'hashids\' =>。'
        );
        self::assertArrayNotHasKey(
            'hashids',
            $hashids,
            'config(\'hashids\') 里还嵌着一个 hashids 键 —— Hyperf 按文件名归并，配置必须扁平。'
        );

        self::assertSame('main', $hashids['default']);
        self::assertArrayHasKey('main', $hashids['connections']);
        self::assertArrayHasKey('alternative', $hashids['connections']);

        // 点号路径可达（Hyperf 用户实际取配置的方式）。
        self::assertIsArray($config->get('hashids.connections.main'));
        self::assertSame('main', $config->get('hashids.default'));
    }

    /**
     * 端到端：ConfigProvider 的依赖表 + 真 DI 容器 → HashidsManager / Hashids\Hashids → 编解码往返。
     */
    public function test_container_resolves_manager_and_client_end_to_end(): void
    {
        $container = $this->hyperf_container();

        $manager = $container->get(HashidsManager::class);
        self::assertInstanceOf(HashidsManager::class, $manager);

        // 配置真的进到了 manager 里：默认连接能取出来且能往返。
        $connection = $manager->connection();
        self::assertSame([1, 2, 3], $connection->decode($connection->encode(1, 2, 3)));
        self::assertSame('main', $manager->getDefaultConnection());

        // HashidsClientFactory 返回的必须是默认连接本身。
        $client = $container->get(HashidsClient::class);
        self::assertInstanceOf(HashidsClient::class, $client);
        self::assertSame($connection, $client);
        self::assertSame([42], $client->decode($client->encode(42)));
    }

    /**
     * 字符串键（vinkla/hashids 迁移过来的代码按字符串取用）在真容器里也要能解析。
     */
    public function test_string_keys_resolve_through_real_container(): void
    {
        $container = $this->hyperf_container();

        $manager = $container->get('hashids');
        self::assertInstanceOf(HashidsManager::class, $manager);
        self::assertSame([7], $manager->connection()->decode($manager->connection()->encode(7)));

        self::assertInstanceOf(HashidsFactory::class, $container->get('hashids.factory'));

        $client = $container->get('hashids.connection');
        self::assertInstanceOf(HashidsClient::class, $client);
        self::assertSame([8], $client->decode($client->encode(8)));
    }

    /**
     * 依赖表：三个字符串键 + 三个类名键，且新装的 Hyperf 应用真的会拿到这张表。
     */
    public function test_config_provider_declares_expected_dependency_map(): void
    {
        $dependencies = (new ConfigProvider())()['dependencies'];

        self::assertSame([
            HashidsFactory::class => HashidsFactory::class,
            HashidsManager::class => HashidsManagerFactory::class,
            HashidsClient::class => HashidsClientFactory::class,
            'hashids' => HashidsManagerFactory::class,
            'hashids.factory' => HashidsFactory::class,
            'hashids.connection' => HashidsClientFactory::class,
        ], $dependencies);

        // 真容器能按这张表把每个键都解析出来（键名写错会在这里炸）。
        $container = $this->hyperf_container();
        foreach ($dependencies as $identifier => $_) {
            self::assertTrue($container->has((string) $identifier), sprintf('容器不认识 [%s]', $identifier));
        }
    }

    /**
     * publish 条目指向本包真实的配置文件，落到 BASE_PATH 下 —— 与上面 ConfigFactory 读的是同一个文件。
     * 这里的 BASE_PATH 取全局常量（Provider 自己用的就是它，Hyperf 应用运行时的真根目录）。
     */
    public function test_publish_entry_targets_the_real_config_file(): void
    {
        $publish = (new ConfigProvider())()['publish'][0];

        self::assertSame(dirname(__DIR__, 2) . '/config/autoload/hashids.php', $publish['source']);
        self::assertFileExists($publish['source']);
        self::assertSame(BASE_PATH . '/config/autoload/hashids.php', $publish['destination']);
    }

    /**
     * 真 ConfigFactory 通过真容器造出的 ConfigInterface。
     */
    private function hyperf_config(): ConfigInterface
    {
        return $this->hyperf_container()->get(ConfigInterface::class);
    }

    /**
     * 只装了本包的 ConfigProvider，所以要手动补上 hyperf/config 自己注册的
     * ConfigInterface => ConfigFactory（真应用里由它自己的 ConfigProvider 提供）。
     */
    private function hyperf_container(): Container
    {
        $definitions = (new ConfigProvider())()['dependencies'];
        $definitions[ConfigInterface::class] = ConfigFactory::class;

        return new Container(new DefinitionSource($definitions));
    }
}
