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
            // 真实 Webman 下插件配置的形状是 ['app' => [...], 'bootstrap' => [...]]，
            // enable 位于 app 之下、不在顶层（Config::loadFromDir 会加上这一层）。
            // 旧版这里写成 ['enable' => true] —— 那是在真框架里不存在的形状，
            // 于是 Bootstrap::start() 的开关检查成了恒真的死代码。
            'plugin.erikwang2013.hashids' => ['app' => ['enable' => true]],
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
        self::assertArrayHasKey('hashids.factory', $definitions);
        self::assertArrayHasKey('hashids.connection', $definitions);
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
            'plugin.erikwang2013.hashids' => ['app' => ['enable' => false]],
        ];

        Bootstrap::start(null);

        self::assertSame([], Container::instance()->definitions);
    }

    /**
     * 插件配置**整个缺失**时必须不注册 —— 这正是「用户在应用级 bootstrap.php
     * 手动注册了 Bootstrap::class，而插件已关闭」的场景：框架跳过了插件目录，
     * config() 里根本没有那个键。
     *
     * 这条取代了原先的 test_start_defaults_to_enabled_when_flag_missing。那个测试
     * 用的是 ['plugin.erikwang2013.hashids' => []] 这种真框架里不存在的形状，
     * 并据此断言「标志缺失 = 启用」；而真实场景里缺失的是整个键（null），
     * 按「启用」兜底会把纵深缺口敞开。契约测试
     * tests/Contract/WebmanContractTest.php 在真框架上覆盖同一场景。
     */
    public function test_start_does_nothing_when_plugin_config_is_absent(): void
    {
        StubState::$config = [];   // 连 'plugin.erikwang2013.hashids' 键都没有

        Bootstrap::start(null);

        self::assertSame([], Container::instance()->definitions);
    }

    public function test_start_registers_with_non_array_hashids_config_but_client_resolution_throws(): void
    {
        StubState::$config = [
            // 真实 Webman 下插件配置的形状是 ['app' => [...], 'bootstrap' => [...]]，
            // enable 位于 app 之下、不在顶层（Config::loadFromDir 会加上这一层）。
            // 旧版这里写成 ['enable' => true] —— 那是在真框架里不存在的形状，
            // 于是 Bootstrap::start() 的开关检查成了恒真的死代码。
            'plugin.erikwang2013.hashids' => ['app' => ['enable' => true]],
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
