<?php

declare(strict_types=1);

namespace Erikwang2013\Hashids\Tests\Config;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Erikwang2013\Hashids\Webman\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Plain-array assertions over the shipped config files
 * (no framework dependency required).
 */
final class PluginConfigTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function test_webman_plugin_app_config_enables_by_default(): void
    {
        $config = require $this->root . '/src/config/plugin/erikwang2013/hashids/app.php';

        self::assertIsArray($config);
        self::assertTrue($config['enable']);
    }

    public function test_webman_plugin_bootstrap_registers_webman_bootstrap(): void
    {
        $config = require $this->root . '/src/config/plugin/erikwang2013/hashids/bootstrap.php';

        self::assertIsArray($config);
        self::assertSame([Bootstrap::class], $config);
    }

    public function test_default_config_has_main_and_alternative_connections(): void
    {
        $config = require $this->root . '/config/hashids.php';

        self::assertIsArray($config);
        self::assertSame('main', $config['default']);
        self::assertArrayHasKey('main', $config['connections']);
        self::assertArrayHasKey('alternative', $config['connections']);

        foreach ($config['connections'] as $name => $connection) {
            self::assertIsArray($connection, sprintf('connection [%s] must be an array', $name));
            self::assertArrayHasKey('salt', $connection);
            self::assertArrayHasKey('length', $connection);
        }
    }

    public function test_hyperf_config_is_flat(): void
    {
        $config = require $this->root . '/config/autoload/hashids.php';

        self::assertIsArray($config);
        self::assertSame('main', $config['default']);
        self::assertArrayHasKey('main', $config['connections']);
        self::assertArrayHasKey('alternative', $config['connections']);

        // 不能再套一层 'hashids' =>：Hyperf 已按文件名归并，套了就取不到 connections。
        self::assertArrayNotHasKey('hashids', $config);
    }

    /**
     * 回归：Hyperf 的 ConfigFactory::readPaths() 按**文件名**归并
     * （`Arr::set($config, 'hashids', require $file)`），所以 config('hashids')
     * 返回的就是文件内容本身。这里复现该归并，确认它喂给真实 HashidsManager
     * 后能正常取到连接 —— 修复前会抛 "Hashids connection [main] is not configured"，
     * 即整个 Hyperf 集成不可用。
     */
    public function test_hyperf_config_as_delivered_by_config_factory_is_usable(): void
    {
        $asHyperfDeliversIt = ['hashids' => require $this->root . '/config/autoload/hashids.php'];

        $manager = new HashidsManager($asHyperfDeliversIt['hashids'], new HashidsFactory());

        self::assertSame([1], $manager->decode($manager->encode(1)));
    }
}
