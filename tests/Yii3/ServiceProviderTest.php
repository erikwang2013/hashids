<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace Erikwang2013\Hashids\Tests\Yii3;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Erikwang2013\Hashids\Tests\Support\StubState;
use Erikwang2013\Hashids\Yii3\ServiceProvider;
use Hashids\Hashids as HashidsClient;
use PHPUnit\Framework\TestCase;
use Yiisoft\Di\Container;
use Yiisoft\Di\ServiceProviderInterface;

require_once __DIR__ . '/../Support/FrameworkStubs.php';

/**
 * 替身层：主矩阵（PHP 8.0–8.5）装不进 yiisoft/di（要求 PHP >= 8.1），
 * 本文件用最小替身覆盖适配器自身逻辑。真容器语义由
 * tests/Contract/Yii3ContractTest.php 钉死。
 */
final class ServiceProviderTest extends TestCase
{
    /** 适配器必须注册的六个标识：3 个别名 + 3 个 FQCN。 */
    private const ALL_IDS = [
        HashidsManager::class,
        'hashids',
        HashidsFactory::class,
        'hashids.factory',
        HashidsClient::class,
        'hashids.connection',
    ];

    protected function setUp(): void
    {
        if (!StubState::$yii3Stubbed) {
            self::markTestSkipped('yiisoft/di is installed; stub-based tests skipped.');
        }
        StubState::reset();
    }

    public function test_implements_the_real_interface(): void
    {
        self::assertContains(
            ServiceProviderInterface::class,
            class_implements(ServiceProvider::class),
            '适配器未实现真 Yiisoft\Di\ServiceProviderInterface'
        );
    }

    public function test_definitions_cover_all_six_ids_and_resolve_to_shared_instances(): void
    {
        $container = $this->container($this->config('yii3-stub-salt', 6));

        foreach (self::ALL_IDS as $id) {
            self::assertTrue($container->has($id), "$id 未注册进容器");
        }

        $manager = $container->get(HashidsManager::class);
        $factory = $container->get(HashidsFactory::class);
        $client  = $container->get(HashidsClient::class);

        self::assertInstanceOf(HashidsManager::class, $manager);
        self::assertInstanceOf(HashidsFactory::class, $factory);
        self::assertInstanceOf(HashidsClient::class, $client);

        // 别名与 FQCN 必须同一实例：真容器的 get() 本身就有单例语义，
        // 定义写成转发即可，不需要在 getDefinitions() 里预先造对象。
        self::assertSame($manager, $container->get('hashids'));
        self::assertSame($factory, $container->get('hashids.factory'));
        self::assertSame($client, $container->get('hashids.connection'));
        self::assertSame($client, $manager->connection());

        // salt/length 确实取自传进来的配置，排除「随便造了个能往返的 Hashids」。
        $hash = $client->encode(12345);
        self::assertSame([12345], $client->decode($hash));
        self::assertSame($hash, (new HashidsClient('yii3-stub-salt', 6))->encode(12345));
        self::assertSame(6, strlen($hash));
    }

    public function test_default_connection_name_comes_from_config(): void
    {
        $container = $this->container([
            'default'     => 'alternative',
            'connections' => [
                'main'        => ['salt' => 'main-salt', 'length' => 6],
                'alternative' => ['salt' => 'alt-salt', 'length' => 12],
            ],
        ]);

        self::assertSame('alternative', $container->get(HashidsManager::class)->getDefaultConnection());
        self::assertSame(12, strlen($container->get(HashidsClient::class)->encode(42)));
    }

    /**
     * 默认连接是**惰性**解析的：配置有误时装配不能失败，取连接时才抛。
     * 与其它适配器同一约定。
     */
    public function test_non_array_config_is_tolerated_but_client_resolution_throws(): void
    {
        $container = $this->container('not-an-array');

        self::assertInstanceOf(HashidsManager::class, $container->get(HashidsManager::class));

        $this->expectException(\InvalidArgumentException::class);
        $container->get(HashidsClient::class);
    }

    public function test_missing_config_falls_back_to_main(): void
    {
        $container = $this->container([]);

        self::assertSame('main', $container->get(HashidsManager::class)->getDefaultConnection());
        self::assertTrue($container->has(HashidsClient::class));
    }

    /**
     * getDefinitions() 必须是纯的、可重复调用且无副作用 —— 接口把这条写进了契约
     * （"Should only return definitions ... Should be idempotent"）。预造对象再返回
     * 会让两次调用拿到两套 manager，正是这条要挡住的写法。
     */
    public function test_definitions_are_pure_and_idempotent(): void
    {
        $provider = new ServiceProvider($this->config('yii3-stub-salt', 6));

        $first  = $provider->getDefinitions();
        $second = $provider->getDefinitions();

        self::assertSame(array_keys($first), array_keys($second));
        self::assertSame([], $provider->getExtensions(), '本适配器不扩展任何已注册服务');

        // 两个容器各自解析，状态互不串（provider 不持有实例）。
        $a = new Container([], [$provider]);
        $b = new Container([], [$provider]);
        self::assertNotSame($a->get('hashids'), $b->get('hashids'));
    }

    /**
     * provider 里含对象型定义时，真容器的 normalizeDefinition 分支会不同 —— 这里顺带
     * 钉住「我们只返回类名字符串与闭包」，别有人改成预造对象。
     */
    public function test_definitions_hold_no_prebuilt_objects(): void
    {
        $definitions = (new ServiceProvider([]))->getDefinitions();

        foreach ($definitions as $id => $definition) {
            self::assertTrue(
                is_string($definition) || $definition instanceof \Closure,
                "定义 [$id] 既不是类名字符串也不是闭包"
            );
        }
    }

    /**
     * @param mixed $config hashids 配置；故意收 mixed，才能覆盖「配置类型不对」的用例
     */
    private function container(mixed $config): Container
    {
        return new Container([], [new ServiceProvider($config)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function config(string $salt, int $length): array
    {
        return [
            'default'     => 'main',
            'connections' => ['main' => ['salt' => $salt, 'length' => $length]],
        ];
    }
}
