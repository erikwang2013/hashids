<?php

declare(strict_types=1);

namespace Erikwang2013\Hashids\Tests\Laravel\Facades;

use ArrayAccess;
use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Erikwang2013\Hashids\Laravel\Facades\Hashids;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Facade;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

use function Erikwang2013\Hashids\Tests\Support\reflection_accessible;

require_once __DIR__ . '/../../Support/FrameworkStubs.php';

final class HashidsTest extends TestCase
{
    protected function setUp(): void
    {
        Facade::clearResolvedInstances();
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        (reflection_accessible(new ReflectionProperty(Facade::class, 'app')))->setValue(null, null);
        Mockery::close();
    }

    public function test_facade_accessor_is_manager_class(): void
    {
        $method = reflection_accessible(new ReflectionMethod(Hashids::class, 'getFacadeAccessor'));

        self::assertSame(HashidsManager::class, $method->invoke(null));
    }

    public function test_facade_forwards_static_calls_to_default_connection(): void
    {
        $manager = new HashidsManager([
            'default' => 'main',
            'connections' => ['main' => ['salt' => 'facade', 'length' => 8]],
        ], new HashidsFactory());

        $app = Mockery::mock(Container::class, ArrayAccess::class);
        $app->shouldReceive('offsetGet')->with(HashidsManager::class)->andReturn($manager);
        (reflection_accessible(new ReflectionProperty(Facade::class, 'app')))->setValue(null, $app);

        $hash = Hashids::encode(1, 2, 3);
        self::assertIsString($hash);
        self::assertSame([1, 2, 3], Hashids::decode($hash));
    }
}
