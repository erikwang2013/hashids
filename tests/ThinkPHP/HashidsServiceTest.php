<?php

declare(strict_types=1);

namespace Erikwang2013\Hashids\Tests\ThinkPHP;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Erikwang2013\Hashids\Tests\Support\StubState;
use Erikwang2013\Hashids\ThinkPHP\HashidsService;
use Hashids\Hashids as HashidsClient;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/FrameworkStubs.php';

final class HashidsServiceTest extends TestCase
{
    public function test_register_binds_factory_manager_alias_and_client(): void
    {
        $this->skipIfRealFramework();
        $app = $this->makeApp([
            'default' => 'main',
            'connections' => ['main' => ['salt' => 'thinkphp', 'length' => 6]],
        ]);

        (new HashidsService($app))->register();

        self::assertSame(HashidsFactory::class, $app->bound[HashidsFactory::class]);
        self::assertArrayHasKey(HashidsManager::class, $app->bound);
        self::assertArrayHasKey('hashids', $app->bound);
        self::assertArrayHasKey(HashidsClient::class, $app->bound);

        $manager = $app->bound[HashidsManager::class]();
        self::assertInstanceOf(HashidsManager::class, $manager);
        self::assertSame([42], $manager->connection()->decode($manager->connection()->encode(42)));

        $client = $app->bound[HashidsClient::class]();
        self::assertInstanceOf(HashidsClient::class, $client);
        self::assertSame([7], $client->decode($client->encode(7)));

        $alias = $app->bound['hashids']();
        self::assertInstanceOf(HashidsManager::class, $alias);
    }

    public function test_register_handles_non_array_config(): void
    {
        $this->skipIfRealFramework();
        $app = $this->makeApp('not-an-array');

        (new HashidsService($app))->register();

        $manager = $app->bound[HashidsManager::class]();
        self::assertInstanceOf(HashidsManager::class, $manager);
    }

    public function test_register_handles_missing_config(): void
    {
        $this->skipIfRealFramework();
        $app = $this->makeApp(null);

        (new HashidsService($app))->register();

        $manager = $app->bound[HashidsManager::class]();
        self::assertInstanceOf(HashidsManager::class, $manager);
    }

    private function skipIfRealFramework(): void
    {
        if (!StubState::$thinkStubbed) {
            self::markTestSkipped('topthink/framework is installed; stub-based tests skipped.');
        }
    }

    private function makeApp(mixed $hashidsConfig): object
    {
        $app = new class {
            /** @var array<string, mixed> */
            public array $bound = [];

            public object $config;

            public function bind($abstract, $concrete = null): void
            {
                $this->bound[$abstract] = $concrete;
            }

            public function make($abstract): mixed
            {
                if ($abstract === HashidsFactory::class) {
                    return new HashidsFactory();
                }

                $concrete = $this->bound[$abstract] ?? null;

                return $concrete instanceof \Closure ? $concrete($this) : $concrete;
            }
        };

        $app->config = new class($hashidsConfig) {
            public function __construct(private mixed $data)
            {
            }

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->data ?? $default;
            }
        };

        return $app;
    }
}
