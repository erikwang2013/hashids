<?php

declare(strict_types=1);

namespace Erikwang2013\Hashids\Tests\Laravel;

use ArrayAccess;
use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Erikwang2013\Hashids\Laravel\HashidsServiceProvider;
use Hashids\Hashids as HashidsClient;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Erikwang2013\Hashids\Tests\Support\StubState;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Mockery;
use PHPUnit\Framework\TestCase;

use function Erikwang2013\Hashids\Tests\Support\reflection_accessible;

require_once __DIR__ . '/../Support/FrameworkStubs.php';

final class HashidsServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        ServiceProvider::$publishes = [];
        StubState::$basePath = dirname(__DIR__, 2);
    }

    protected function tearDown(): void
    {
        ServiceProvider::$publishes = [];
        Facade::clearResolvedInstances();
        Mockery::close();
    }

    public function test_provider_is_deferred_and_provides_all_services(): void
    {
        $app = Mockery::mock(Container::class);
        $provider = new HashidsServiceProvider($app);

        self::assertInstanceOf(DeferrableProvider::class, $provider);
        self::assertSame([
            HashidsFactory::class,
            HashidsManager::class,
            'hashids',
            HashidsClient::class,
        ], $provider->provides());
    }

    public function test_register_binds_singletons_alias_and_client(): void
    {
        $configData = [
            'default' => 'main',
            'connections' => ['main' => ['salt' => 'laravel', 'length' => 8]],
        ];

        $config = Mockery::mock(Repository::class);
        $config->shouldReceive('get')->with('hashids', [])->andReturn($configData);
        $config->shouldReceive('set')->once();

        $factoryClosure = null;
        $managerClosure = null;
        $clientClosure = null;

        $app = Mockery::mock(Container::class, ArrayAccess::class);
        $app->shouldReceive('make')->with('config')->andReturn($config);
        $app->shouldReceive('offsetGet')->with('config')->andReturn($config);
        $app->shouldReceive('make')->with(HashidsFactory::class)->andReturn(new HashidsFactory());
        $app->shouldReceive('make')->with(HashidsManager::class)->andReturnUsing(function () use (&$managerClosure, $app): HashidsManager {
            return $managerClosure($app);
        });
        $app->shouldReceive('singleton')->with(HashidsFactory::class, Mockery::capture($factoryClosure));
        $app->shouldReceive('singleton')->with(HashidsManager::class, Mockery::capture($managerClosure));
        $app->shouldReceive('alias')->with(HashidsManager::class, 'hashids');
        $app->shouldReceive('bind')->with(HashidsClient::class, Mockery::capture($clientClosure));

        (new HashidsServiceProvider($app))->register();

        self::assertInstanceOf(HashidsFactory::class, $factoryClosure());
        self::assertInstanceOf(HashidsFactory::class, $app->make(HashidsFactory::class));

        $manager = $managerClosure($app);
        self::assertInstanceOf(HashidsManager::class, $manager);
        self::assertSame([1, 2, 3], $manager->connection()->decode($manager->connection()->encode(1, 2, 3)));

        $client = $clientClosure($app);
        self::assertInstanceOf(HashidsClient::class, $client);
        self::assertSame([5], $client->decode($client->encode(5)));
    }

    public function test_register_merges_config_from_package_file(): void
    {
        $config = Mockery::mock(Repository::class);
        $merged = null;
        $config->shouldReceive('get')->with('hashids', [])->andReturn([]);
        $config->shouldReceive('set')->with('hashids', Mockery::capture($merged))->once();

        $app = Mockery::mock(Container::class, ArrayAccess::class);
        $app->shouldReceive('make')->with('config')->andReturn($config);
        $app->shouldReceive('offsetGet')->with('config')->andReturn($config);
        $app->shouldReceive('make')->with(HashidsFactory::class)->andReturn(new HashidsFactory());
        $app->shouldReceive('singleton');
        $app->shouldReceive('alias')->with(HashidsManager::class, 'hashids');
        $app->shouldReceive('bind')->with(HashidsClient::class, Mockery::any());

        (new HashidsServiceProvider($app))->register();

        self::assertIsArray($merged);
        self::assertSame('main', $merged['default']);
        self::assertArrayHasKey('main', $merged['connections']);
        self::assertArrayHasKey('alternative', $merged['connections']);
    }

    public function test_register_manager_closure_tolerates_non_array_config(): void
    {
        $config = Mockery::mock(Repository::class);
        $call = 0;
        $config->shouldReceive('get')->with('hashids', [])->andReturnUsing(function () use (&$call): mixed {
            $call++;

            return $call === 1 ? [] : 'not-an-array';
        });
        $config->shouldReceive('set')->once();

        $app = Mockery::mock(Container::class, ArrayAccess::class);
        $app->shouldReceive('make')->with('config')->andReturn($config);
        $app->shouldReceive('offsetGet')->with('config')->andReturn($config);
        $app->shouldReceive('make')->with(HashidsFactory::class)->andReturn(new HashidsFactory());
        $managerClosure = null;
        $app->shouldReceive('singleton')->with(HashidsFactory::class, Mockery::any());
        $app->shouldReceive('singleton')->with(HashidsManager::class, Mockery::capture($managerClosure));
        $app->shouldReceive('alias')->with(HashidsManager::class, 'hashids');
        $app->shouldReceive('bind')->with(HashidsClient::class, Mockery::any());

        (new HashidsServiceProvider($app))->register();

        self::assertInstanceOf(HashidsManager::class, $managerClosure($app));
    }

    public function test_boot_publishes_config_when_running_in_console(): void
    {
        $app = Mockery::mock(Container::class);
        $app->shouldReceive('runningInConsole')->andReturn(true);

        (new HashidsServiceProvider($app))->boot();

        $publishes = ServiceProvider::$publishes;
        $class = HashidsServiceProvider::class;
        self::assertArrayHasKey($class, $publishes);
        self::assertSame([config_path('hashids.php')], array_values($publishes[$class]));
        self::assertFileExists(config_path('hashids.php'));

        $groups = reflection_accessible(new \ReflectionProperty(ServiceProvider::class, 'publishGroups'))->getValue();
        self::assertArrayHasKey('hashids-config', $groups);
        self::assertSame([config_path('hashids.php')], array_values($groups['hashids-config']));
    }

    public function test_boot_does_not_publish_when_not_in_console(): void
    {
        $app = Mockery::mock(Container::class);
        $app->shouldReceive('runningInConsole')->andReturn(false);

        (new HashidsServiceProvider($app))->boot();

        self::assertArrayNotHasKey(HashidsServiceProvider::class, ServiceProvider::$publishes);
    }
}
