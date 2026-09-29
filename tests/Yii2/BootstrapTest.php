<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace Erikwang2013\Hashids\Tests\Yii2;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Erikwang2013\Hashids\Tests\Support\StubState;
use Erikwang2013\Hashids\Yii2\Bootstrap;
use Hashids\Hashids as HashidsClient;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Yii;
use yii\base\BootstrapInterface;
use yii\di\Container;

require_once __DIR__ . '/../Support/FrameworkStubs.php';

/**
 * 替身层：主矩阵（PHP 8.0–8.5）装不进 yiisoft/yii2，本文件用最小替身覆盖适配器自身逻辑。
 * 真框架语义由 tests/Contract/YiiContractTest.php 钉死，两者互补。
 */
final class BootstrapTest extends TestCase
{
    protected function setUp(): void
    {
        if (!StubState::$yii2Stubbed) {
            self::markTestSkipped('yii2 is installed; stub-based tests skipped.');
        }
        StubState::reset();
        // Yii::$container 是全局静态，真应用里只建一次；测试每条换新，免得注册互相污染。
        Yii::$container = new Container();
        Yii::$app = null;
    }

    /**
     * 真接口的 bootstrap() 参数**没有类型声明**。实现里补上 Application $app 会参数逆变
     * 违规、直接 fatal —— 这条不依赖真框架就能守住，值得单独钉住。
     */
    public function test_bootstrap_signature_stays_compatible_with_the_interface(): void
    {
        self::assertContains(
            BootstrapInterface::class,
            class_implements(Bootstrap::class),
            '适配器未实现 yii\base\BootstrapInterface'
        );

        $parameters = (new ReflectionMethod(Bootstrap::class, 'bootstrap'))->getParameters();
        self::assertCount(1, $parameters);
        self::assertNull(
            $parameters[0]->getType(),
            'bootstrap() 的参数不能有类型声明 —— 真接口没有，加了就是参数逆变违规'
        );
    }

    public function test_bootstrap_registers_all_six_ids_sharing_one_manager(): void
    {
        (new Bootstrap())->bootstrap($this->app([
            'hashids' => [
                'default'     => 'main',
                'connections' => ['main' => ['salt' => 'yii-stub-salt', 'length' => 6]],
            ],
        ]));

        $container = Yii::$container;

        foreach ([
            HashidsManager::class,
            'hashids',
            HashidsFactory::class,
            'hashids.factory',
            HashidsClient::class,
            'hashids.connection',
        ] as $id) {
            self::assertTrue($container->has($id), "$id 未注册进 Yii::\$container");
        }

        $manager = $container->get(HashidsManager::class);
        $client  = $container->get(HashidsClient::class);

        self::assertInstanceOf(HashidsManager::class, $manager);
        self::assertInstanceOf(HashidsClient::class, $client);

        // 别名与 FQCN 必须同一实例，否则会拿到两套连接池。
        self::assertSame($manager, $container->get('hashids'));
        self::assertSame($container->get(HashidsFactory::class), $container->get('hashids.factory'));
        self::assertSame($client, $container->get('hashids.connection'));
        self::assertSame($client, $manager->connection());

        self::assertSame([1, 2, 3], $client->decode($client->encode(1, 2, 3)));
        self::assertSame(6, strlen($client->encode(42)));
    }

    /**
     * 配置取自 $app->params['hashids']，默认连接名同源。
     */
    public function test_config_comes_from_app_params_and_honours_default(): void
    {
        (new Bootstrap())->bootstrap($this->app([
            'hashids' => [
                'default'     => 'alternative',
                'connections' => [
                    'main'        => ['salt' => 'main-salt', 'length' => 6],
                    'alternative' => ['salt' => 'alt-salt', 'length' => 12],
                ],
            ],
        ]));

        $manager = Yii::$container->get(HashidsManager::class);
        self::assertSame('alternative', $manager->getDefaultConnection());
        self::assertSame(12, strlen(Yii::$container->get(HashidsClient::class)->encode(42)));
    }

    /**
     * params 里没有 hashids 键（应用没配过）时的降级：注册照旧，默认连接名回落 'main'。
     */
    public function test_missing_hashids_param_still_registers_and_falls_back_to_main(): void
    {
        (new Bootstrap())->bootstrap($this->app([]));

        $manager = Yii::$container->get(HashidsManager::class);
        self::assertSame('main', $manager->getDefaultConnection());
        self::assertTrue(Yii::$container->has(HashidsClient::class));
    }

    /**
     * $app 连 params 属性都没有（接口参数无类型，这里是最坏输入）时不能炸。
     */
    public function test_app_without_params_property_is_tolerated(): void
    {
        (new Bootstrap())->bootstrap(new \stdClass());

        self::assertSame(
            'main',
            Yii::$container->get(HashidsManager::class)->getDefaultConnection()
        );
    }

    /**
     * 配置类型不对时 bootstrap 不能把应用拖垮：注册照常，取默认连接才抛。
     * 与 Webman 侧同一约定。
     */
    public function test_bootstrap_survives_non_array_config_but_client_resolution_throws(): void
    {
        (new Bootstrap())->bootstrap($this->app(['hashids' => 'not-an-array']));

        self::assertInstanceOf(HashidsManager::class, Yii::$container->get(HashidsManager::class));

        $this->expectException(\InvalidArgumentException::class);
        Yii::$container->get(HashidsClient::class);
    }

    /**
     * 铺一个装着应用参数的最小应用对象 —— 真框架那边是 yii\console\Application。
     *
     * @param array<string, mixed> $params 完整的 params 数组（'hashids' 键可有可无）
     */
    private function app(array $params): object
    {
        return new class ($params) {
            /** @var array<string, mixed> */
            public array $params;

            /**
             * @param array<string, mixed> $params
             */
            public function __construct(array $params)
            {
                $this->params = $params;
            }
        };
    }
}
