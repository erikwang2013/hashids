<?php

declare(strict_types=1);

namespace Erikwang2013\Hashids\Tests\Webman;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Erikwang2013\Hashids\Tests\Support\StubState;
use Erikwang2013\Hashids\Webman\Bootstrap;
use Hashids\Hashids as HashidsClient;
use PHPUnit\Framework\TestCase;
use support\Container;

require_once __DIR__ . '/../Support/FrameworkStubs.php';

final class BootstrapTest extends TestCase
{
    protected function setUp(): void
    {
        if (!StubState::$globalStubs || !StubState::$webmanStubbed) {
            self::markTestSkipped('webman framework is installed; stub-based tests skipped.');
        }
        StubState::reset();
        Container::reset();
    }

    public function test_start_registers_all_definitions_when_enabled(): void
    {
        StubState::$config = [
            'plugin.erikwang2013.hashids' => ['enable' => true],
            'hashids' => [
                'default' => 'main',
                'connections' => ['main' => ['salt' => 'webman', 'length' => 6]],
            ],
        ];

        Bootstrap::start(null);

        $definitions = Container::instance()->definitions;
        self::assertArrayHasKey(HashidsFactory::class, $definitions);
        self::assertArrayHasKey(HashidsManager::class, $definitions);
        self::assertArrayHasKey('hashids', $definitions);
        self::assertArrayHasKey(HashidsClient::class, $definitions);

        self::assertInstanceOf(HashidsFactory::class, $definitions[HashidsFactory::class]());
        $manager = $definitions[HashidsManager::class]();
        self::assertInstanceOf(HashidsManager::class, $manager);
        self::assertSame([1, 2, 3], $manager->connection()->decode($manager->connection()->encode(1, 2, 3)));

        $client = $definitions[HashidsClient::class]();
        self::assertInstanceOf(HashidsClient::class, $client);
        self::assertSame([9], $client->decode($client->encode(9)));
    }

    public function test_start_does_nothing_when_disabled(): void
    {
        StubState::$config = [
            'plugin.erikwang2013.hashids' => ['enable' => false],
        ];

        Bootstrap::start(null);

        self::assertSame([], Container::instance()->definitions);
    }

    public function test_start_defaults_to_enabled_when_flag_missing(): void
    {
        StubState::$config = [
            'plugin.erikwang2013.hashids' => [],
        ];

        Bootstrap::start(null);

        self::assertNotEmpty(Container::instance()->definitions);
    }

    public function test_start_registers_with_non_array_hashids_config_but_client_resolution_throws(): void
    {
        StubState::$config = [
            'plugin.erikwang2013.hashids' => ['enable' => true],
            'hashids' => 'not-an-array',
        ];

        Bootstrap::start(null);

        $definitions = Container::instance()->definitions;
        self::assertInstanceOf(HashidsManager::class, $definitions[HashidsManager::class]());

        // 配置无效时无可用连接：解析 HashidsClient 抛出 InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);
        $definitions[HashidsClient::class]();
    }
}
