<?php

declare(strict_types=1);

namespace Erikwang2013\Hashids\Tests\Hyperf;

use Erikwang2013\Hashids\HashidsManager;
use Erikwang2013\Hashids\Hyperf\HashidsManagerFactory;
use Hyperf\Contract\ConfigInterface;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class HashidsManagerFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_invoke_builds_manager_from_hashids_config(): void
    {
        $config = new class {
            public function get(string $key, mixed $default = null): mixed
            {
                return $key === 'hashids'
                    ? ['default' => 'main', 'connections' => ['main' => ['salt' => 'hyperf', 'length' => 6]]]
                    : $default;
            }
        };

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->with(ConfigInterface::class)->andReturn($config);

        $manager = (new HashidsManagerFactory())($container);

        self::assertInstanceOf(HashidsManager::class, $manager);
        self::assertSame([1, 2, 3], $manager->connection()->decode($manager->connection()->encode(1, 2, 3)));
    }

    public function test_invoke_falls_back_to_empty_config_when_key_missing(): void
    {
        $config = new class {
            public function get(string $key, mixed $default = null): mixed
            {
                return $default;
            }
        };

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->with(ConfigInterface::class)->andReturn($config);

        $manager = (new HashidsManagerFactory())($container);
        self::assertInstanceOf(HashidsManager::class, $manager);

        // 空配置下没有可用连接
        $this->expectException(InvalidArgumentException::class);
        $manager->connection();
    }

    public function test_invoke_normalizes_non_array_config(): void
    {
        $config = new class {
            public function get(string $key, mixed $default = null): mixed
            {
                return 'not-an-array';
            }
        };

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->with(ConfigInterface::class)->andReturn($config);

        $manager = (new HashidsManagerFactory())($container);
        self::assertInstanceOf(HashidsManager::class, $manager);
    }
}
