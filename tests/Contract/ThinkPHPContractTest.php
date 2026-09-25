<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace Erikwang2013\Hashids\Tests\Contract;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Erikwang2013\Hashids\ThinkPHP\HashidsService;
use Hashids\Hashids as HashidsClient;
use PHPUnit\Framework\TestCase;
use think\App;

/**
 * ThinkPHP 适配器契约测试：跑在真实的 topthink/framework + think-container 上。
 *
 * 与 tests/ThinkPHP/HashidsServiceTest.php 的手写替身互补 —— 替身是按我们对框架的
 * 假设写的（假设错了替身测试照样绿），这里验证的是假设本身。由 phpunit.contract.xml
 * 单独运行，主矩阵装不进真框架包。
 */
final class ThinkPHPContractTest extends TestCase
{
    private const SALT = 'thinkphp-contract-salt';

    public function test_registered_service_resolves_all_six_identifiers(): void
    {
        $app = $this->makeApp([
            'default' => 'main',
            'connections' => ['main' => ['salt' => self::SALT, 'length' => 6]],
        ]);

        // 走真实框架的注册路径：App::register() 会 new $service($this)，顺带验证本服务
        // 的构造器签名与 think\Service 兼容（替身不走这条路径，签名不兼容也测不出来）。
        $app->register(HashidsService::class);

        $expected = [
            HashidsManager::class => HashidsManager::class,
            'hashids'             => HashidsManager::class,
            HashidsFactory::class => HashidsFactory::class,
            'hashids.factory'     => HashidsFactory::class,
            HashidsClient::class  => HashidsClient::class,
            'hashids.connection'  => HashidsClient::class,
        ];

        foreach ($expected as $abstract => $class) {
            self::assertInstanceOf($class, $app->make($abstract), "容器标识 [{$abstract}] 解析结果不是 {$class}");
        }

        $manager = $app->make(HashidsManager::class);
        $client  = $manager->connection();
        self::assertSame([42], $client->decode($client->encode(42)));

        $byClass = $app->make(HashidsClient::class);
        self::assertSame([7], $byClass->decode($byClass->encode(7)));

        // 别名/类名解析到同一实例：真正提供「单例」语义的是容器的实例记忆（make() 把
        // 结果写进 instances[]），与 register() 里那行 bind($a, $a) 无关 —— 那行是空操作。
        self::assertSame($manager, $app->make('hashids'));
        self::assertSame($app->make(HashidsFactory::class), $app->make('hashids.factory'));
        self::assertSame($client, $app->make(HashidsClient::class));
        self::assertSame($client, $app->make('hashids.connection'));
    }

    /**
     * 上一条钉的是容器语义，这一条钉的是适配器：register() 之后 HashidsFactory 依然不在
     * 绑定表里（那行 bind($a, $a) 没起作用），但两次解析拿到同一个对象 —— 谁要把它「修」
     * 成 bind($a, fn() => new $a) 或 instance()，这条会红。
     */
    public function test_register_leaves_factory_unbound_yet_shares_one_instance(): void
    {
        $app = $this->makeApp(['connections' => ['main' => ['salt' => self::SALT]]]);
        $app->register(HashidsService::class);

        self::assertFalse(
            $app->bound(HashidsFactory::class),
            'register() 不应把 HashidsFactory 写进绑定表 —— 那行 bind($a, $a) 是空操作'
        );

        $first = $app->make(HashidsFactory::class);
        self::assertTrue($app->exists(HashidsFactory::class), '共享来自 make() 写入的实例记忆');
        self::assertSame($first, $app->make(HashidsFactory::class));
        self::assertNotSame($first, $app->make(HashidsFactory::class, [], true));
    }

    public function test_bind_abstract_equals_concrete_is_a_noop(): void
    {
        $app = new App();

        self::assertFalse($app->bound(HashidsFactory::class), '新容器本不该持有该绑定');

        $app->bind(HashidsFactory::class, HashidsFactory::class);

        // think-container v3 的 Container::bind()：$abstract == $concrete 时直接跳过，
        // 既不写 bind 表也不建实例。这条断言是为了防止有人再照着「bind($a, $a) 让它成为
        // 单例」的旧说法去「修」那行 —— 它本来就是空操作，共享另有来源。
        self::assertFalse($app->bound(HashidsFactory::class), 'bind($a, $a) 不应写入绑定表');
        self::assertFalse($app->exists(HashidsFactory::class), 'bind($a, $a) 不应创建实例');
        self::assertSame(HashidsFactory::class, $app->getAlias(HashidsFactory::class), 'bind($a, $a) 也不应产生别名');

        // 共享来自 make()：首次解析后写入 instances[]，之后的 make() 直接命中实例记忆。
        $first = $app->make(HashidsFactory::class);
        self::assertTrue($app->exists(HashidsFactory::class), '实例记忆是 make() 建立的，不是 bind() 建立的');
        self::assertSame($first, $app->make(HashidsFactory::class));
        self::assertNotSame(
            $first,
            $app->make(HashidsFactory::class, [], true),
            'newInstance=true 绕开实例记忆，说明单例语义只由 instances[] 提供'
        );
    }

    public function test_default_connection_falls_back_to_main_when_default_key_missing(): void
    {
        $app = $this->makeApp([
            'connections' => [
                'main'        => ['salt' => self::SALT, 'length' => 6],
                'alternative' => ['salt' => 'other-salt', 'length' => 12],
            ],
        ]);
        $app->register(HashidsService::class);

        $manager = $app->make(HashidsManager::class);
        self::assertSame('main', $manager->getDefaultConnection());

        // 光看连接名不够：哈希输出由 salt/length 决定，用它证明实际取的是 main 的配置。
        $expected = (new HashidsClient(self::SALT, 6))->encode(42);
        self::assertSame($expected, $app->make(HashidsClient::class)->encode(42));
    }

    public function test_missing_config_key_yields_empty_array_and_manager_still_resolves(): void
    {
        $app = new App();

        // 没发布配置文件时 config->get('hashids') 的真实行为：[]（Config::pull() 返回类型是
        // array，缺 key 时给空数组）—— 不是 null，也不抛异常。替身测试里假设的是 null。
        self::assertSame([], $app->config->get('hashids'));

        $app->register(HashidsService::class);

        $manager = $app->make(HashidsManager::class);
        self::assertSame('main', $manager->getDefaultConnection());
    }

    /**
     * @param array<string, mixed> $config 写入 config('hashids') 的内容
     */
    private function makeApp(array $config): App
    {
        $app = new App();
        $app->config->set($config, 'hashids');

        return $app;
    }
}
