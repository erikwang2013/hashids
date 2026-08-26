<?php

declare(strict_types=1);

namespace Erikwang2013\Hashids\Tests\Hyperf;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Erikwang2013\Hashids\Hyperf\ConfigProvider;
use Erikwang2013\Hashids\Hyperf\HashidsClientFactory;
use Erikwang2013\Hashids\Hyperf\HashidsManagerFactory;
use Hashids\Hashids as HashidsClient;
use PHPUnit\Framework\TestCase;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', sys_get_temp_dir() . '/hashids-hyperf-base');
}

final class ConfigProviderTest extends TestCase
{
    public function test_invoke_returns_dependencies_mapping(): void
    {
        $config = (new ConfigProvider())();

        self::assertIsArray($config);
        self::assertArrayHasKey('dependencies', $config);
        self::assertSame([
            HashidsFactory::class => HashidsFactory::class,
            HashidsManager::class => HashidsManagerFactory::class,
            HashidsClient::class => HashidsClientFactory::class,
        ], $config['dependencies']);
    }

    public function test_invoke_returns_publish_entry_for_config_file(): void
    {
        $config = (new ConfigProvider())();

        self::assertArrayHasKey('publish', $config);
        self::assertCount(1, $config['publish']);

        $publish = $config['publish'][0];
        self::assertSame('hashids', $publish['id']);
        self::assertIsString($publish['description']);
        self::assertFileExists($publish['source']);
        self::assertStringEndsWith('config/autoload/hashids.php', $publish['source']);
        self::assertSame(BASE_PATH . '/config/autoload/hashids.php', $publish['destination']);
    }
}
