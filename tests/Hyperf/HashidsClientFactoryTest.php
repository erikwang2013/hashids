<?php

declare(strict_types=1);

namespace Erikwang2013\Hashids\Tests\Hyperf;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Erikwang2013\Hashids\Hyperf\HashidsClientFactory;
use Hashids\Hashids as HashidsClient;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class HashidsClientFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_invoke_returns_default_connection(): void
    {
        $manager = new HashidsManager([
            'default' => 'main',
            'connections' => ['main' => ['salt' => 'client', 'length' => 4]],
        ], new HashidsFactory());

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->with(HashidsManager::class)->andReturn($manager);

        $client = (new HashidsClientFactory())($container);

        self::assertInstanceOf(HashidsClient::class, $client);
        self::assertSame([9], $client->decode($client->encode(9)));
    }

    public function test_invoke_uses_configured_default_connection(): void
    {
        $manager = new HashidsManager([
            'default' => 'secondary',
            'connections' => [
                'main' => ['salt' => 'main-salt', 'length' => 4],
                'secondary' => ['salt' => 'secondary-salt', 'length' => 4],
            ],
        ], new HashidsFactory());

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->with(HashidsManager::class)->andReturn($manager);

        $client = (new HashidsClientFactory())($container);

        $hash = $client->encode(123);
        $secondary = $manager->connection('secondary')->encode(123);
        self::assertSame($secondary, $hash);
        self::assertNotSame($manager->connection('main')->encode(123), $hash);
    }
}
