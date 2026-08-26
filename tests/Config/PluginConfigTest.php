<?php

declare(strict_types=1);

namespace Erikwang2013\Hashids\Tests\Config;

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

    public function test_hyperf_config_is_nested_under_hashids_key(): void
    {
        $config = require $this->root . '/config/autoload/hashids.php';

        self::assertIsArray($config);
        self::assertArrayHasKey('hashids', $config);
        self::assertSame('main', $config['hashids']['default']);
        self::assertArrayHasKey('main', $config['hashids']['connections']);
        self::assertArrayHasKey('alternative', $config['hashids']['connections']);
    }
}
