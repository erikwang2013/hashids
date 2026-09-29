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
use Erikwang2013\Hashids\Yii3\ServiceProvider;
use Hashids\Hashids as HashidsClient;
use PHPUnit\Framework\TestCase;
use Yiisoft\Di\BuildingException;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Di\ServiceProviderInterface;

/**
 * Yii 3 适配器契约测试：跑在**真实** yiisoft/di 上。
 *
 * Yii 3 上没有替身能替代的三件事，逐条钉死：
 *
 *  1. 真容器**只认两种定义写法**（类名字符串 / 可调用），返回预造对象是另一条分支；
 *     适配器刻意只用前两种，这里验证它们真被容器接受。
 *  2. 真 Container::get() 自带单例语义 —— 别名与 FQCN 必须解析到同一实例，靠的是容器
 *     缓存而不是适配器自己造对象。
 *  3. 取默认连接失败时，容器会把本包的 InvalidArgumentException 包进
 *     Yiisoft\Di\BuildingException 再抛（原因是 getPrevious()）—— 不写下来就会
 *     以为「配置错误时抛的是 InvalidArgumentException」而在 catch 里写错类名。
 *
 * 由 phpunit.contract.xml 单独运行（yiisoft/di 要求 PHP >= 8.1，装不进 `php: ^8.0` 的主矩阵）。
 */
final class Yii3ContractTest extends TestCase
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

    private const SALT = 'yii3-contract-salt';

    public function test_service_provider_implements_the_real_interface(): void
    {
        self::assertContains(
            ServiceProviderInterface::class,
            class_implements(ServiceProvider::class),
            '适配器未实现真 Yiisoft\Di\ServiceProviderInterface'
        );
    }

    /**
     * 端到端：ContainerConfig::withProviders() → 真容器 → 六个标识全部可解析且共享实例。
     */
    public function test_provider_registers_all_six_ids_in_the_real_container(): void
    {
        $container = $this->container($this->config(self::SALT, 6));

        foreach (self::ALL_IDS as $id) {
            self::assertTrue($container->has($id), "$id 未注册进容器");
        }

        $manager = $container->get(HashidsManager::class);
        $factory = $container->get(HashidsFactory::class);
        $client  = $container->get(HashidsClient::class);

        self::assertInstanceOf(HashidsManager::class, $manager);
        self::assertInstanceOf(HashidsFactory::class, $factory);
        self::assertInstanceOf(HashidsClient::class, $client);

        // 共享来自真容器的实例缓存（其 get() 文档明写「same instance ... each time」），
        // 适配器的定义只需要转发到对应 FQCN。
        self::assertSame($manager, $container->get('hashids'));
        self::assertSame($factory, $container->get('hashids.factory'));
        self::assertSame($client, $container->get('hashids.connection'));
        self::assertSame($client, $manager->connection());
        self::assertSame($factory, $manager->getFactory());

        $hash = $client->encode(12345);
        self::assertSame([12345], $client->decode($hash), 'encode/decode 未往返');

        // salt/length 确实取自传进 provider 的配置，排除「随便造了个能往返的 Hashids」。
        self::assertSame($hash, (new HashidsClient(self::SALT, 6))->encode(12345));
        self::assertNotSame($hash, (new HashidsClient('other-salt', 6))->encode(12345));
        self::assertSame(6, strlen($hash));
    }

    public function test_default_connection_name_comes_from_config(): void
    {
        $container = $this->container([
            'default'     => 'alternative',
            'connections' => [
                'main'        => ['salt' => 'main-salt', 'length' => 6],
                'alternative' => ['salt' => self::SALT, 'length' => 12],
            ],
        ]);

        self::assertSame('alternative', $container->get(HashidsManager::class)->getDefaultConnection());

        // 光看连接名不够：哈希输出由 salt/length 决定，用它证明取的确实是 alternative 的配置。
        self::assertSame(
            (new HashidsClient(self::SALT, 12))->encode(42),
            $container->get(HashidsClient::class)->encode(42)
        );
    }

    /**
     * 默认连接惰性解析 + 异常链路：装配阶段不出错，取用时容器把本包的
     * InvalidArgumentException 包进 BuildingException 抛出来。
     */
    public function test_non_array_config_fails_lazily_with_a_wrapped_cause(): void
    {
        $container = $this->container('not-an-array');

        self::assertInstanceOf(HashidsManager::class, $container->get(HashidsManager::class));

        try {
            $container->get(HashidsClient::class);
            self::fail('配置无效时取默认连接本应抛异常');
        } catch (BuildingException $e) {
            // 记牢这层包装：catch 里写 \InvalidArgumentException 是接不住的。
            self::assertInstanceOf(\InvalidArgumentException::class, $e->getPrevious());
            self::assertStringContainsString('is not configured', $e->getMessage());
        }
    }

    public function test_missing_config_falls_back_to_main(): void
    {
        $container = $this->container([]);

        self::assertSame('main', $container->get(HashidsManager::class)->getDefaultConnection());
        self::assertTrue($container->has(HashidsClient::class));
    }

    /**
     * 定义形状：适配器只返回类名字符串与闭包（不返回预造对象），真容器要能接受这两者。
     */
    public function test_real_container_accepts_the_definition_shapes_we_return(): void
    {
        $definitions = (new ServiceProvider($this->config(self::SALT, 6)))->getDefinitions();

        foreach ($definitions as $id => $definition) {
            self::assertTrue(
                is_string($definition) || $definition instanceof \Closure,
                "定义 [$id] 既不是类名字符串也不是闭包"
            );
        }

        self::assertContains(HashidsFactory::class, $definitions, '工厂用的是类名字符串定义');
    }

    /**
     * 真容器的装配顺序是「先 definitions、后 providers」，所以 provider 里的定义**覆盖**
     * 应用自己的普通定义。用户若想改 hashids 的绑定，改的定义放 definitions 里是无效的 ——
     * 这条写下来，免得以后有人按「definitions 优先级更高」去排查。
     */
    public function test_provider_definitions_override_plain_definitions(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                // 应用自己塞了一个假的，指望它生效。
                'hashids' => new \stdClass(),
            ])
            ->withProviders([new ServiceProvider($this->config(self::SALT, 6))]);

        $container = new Container($config);

        self::assertInstanceOf(
            HashidsManager::class,
            $container->get('hashids'),
            'provider 的定义应当覆盖普通 definitions（真容器先加 definitions 后加 providers）'
        );
    }

    /**
     * 接口把 getDefinitions() 的纯度写进了契约（"Should only return definitions ...
     * Should be idempotent"）。预造对象会让两个容器共用一份实例，正是这条要挡的写法。
     */
    public function test_provider_is_pure_and_yields_independent_containers(): void
    {
        $provider = new ServiceProvider($this->config(self::SALT, 6));

        self::assertSame(array_keys($provider->getDefinitions()), array_keys($provider->getDefinitions()));
        self::assertSame([], $provider->getExtensions(), '本适配器不扩展任何已注册服务');

        $first  = new Container(ContainerConfig::create()->withProviders([$provider]));
        $second = new Container(ContainerConfig::create()->withProviders([$provider]));

        self::assertNotSame($first->get('hashids'), $second->get('hashids'));
    }

    /**
     * @param mixed $config hashids 配置；故意收 mixed，才能覆盖「配置类型不对」的用例
     */
    private function container(mixed $config): Container
    {
        return new Container(
            ContainerConfig::create()->withProviders([new ServiceProvider($config)])
        );
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
