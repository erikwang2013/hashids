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
use Erikwang2013\Hashids\Yii2\Component;
use Hashids\Hashids as HashidsClient;
use PHPUnit\Framework\TestCase;
use yii\base\Behavior;

require_once __DIR__ . '/../Support/FrameworkStubs.php';

/**
 * 替身层：组件形态（Yii::$app->hashids）的自有逻辑。真框架语义由
 * tests/Contract/YiiContractTest.php 在真 yii\base\Component 上钉死。
 */
final class ComponentTest extends TestCase
{
    protected function setUp(): void
    {
        if (!StubState::$yii2Stubbed) {
            self::markTestSkipped('yii2 is installed; stub-based tests skipped.');
        }
        StubState::reset();
    }

    /**
     * Yii 应用配置里那样声明式地铺组件：'config' 键经属性赋值落到 $config 上。
     */
    public function test_config_property_is_applied_and_manager_is_built_lazily(): void
    {
        $component = new Component(['config' => $this->config()]);

        self::assertSame($this->config(), $component->config);
        self::assertInstanceOf(HashidsFactory::class, $component->getFactory());
        self::assertInstanceOf(HashidsManager::class, $component->getManager());

        // 工厂在 manager 内与外部取到的是同一个，避免两套连接池。
        self::assertSame($component->getFactory(), $component->getManager()->getFactory());
    }

    /**
     * 未声明的方法经 __call 转给 HashidsManager，再转默认连接 ——
     * Yii::$app->hashids->encode(1) 靠的就是这条。
     */
    public function test_unknown_methods_forward_to_the_default_connection(): void
    {
        $component = new Component(['config' => $this->config()]);

        $hash = $component->encode(12345);
        self::assertSame(6, strlen($hash));
        self::assertSame($hash, (new HashidsClient('yii-stub-salt', 6))->encode(12345));
        self::assertSame([12345], $component->decode($hash));

        // 多参透传（hashids 的 encode 是可变参数），别被 ...$params 弄丢。
        self::assertSame([1, 2, 3], $component->decode($component->encode(1, 2, 3)));
    }

    /**
     * getManager()/getFactory() 走 Yii 的 getter 魔术，可以当属性读。
     */
    public function test_getters_are_reachable_as_properties(): void
    {
        $component = new Component(['config' => $this->config()]);

        self::assertInstanceOf(HashidsManager::class, $component->manager);
        self::assertInstanceOf(HashidsFactory::class, $component->factory);
        self::assertSame($component->getManager(), $component->manager);
    }

    public function test_connection_returns_the_configured_connection(): void
    {
        $component = new Component([
            'config' => [
                'default'     => 'main',
                'connections' => [
                    'main'        => ['salt' => 'main-salt', 'length' => 6],
                    'alternative' => ['salt' => 'alt-salt', 'length' => 12],
                ],
            ],
        ]);

        self::assertSame(6, strlen($component->connection()->encode(1)));
        self::assertSame(12, strlen($component->connection('alternative')->encode(1)));
        self::assertInstanceOf(HashidsClient::class, $component->connection());
    }

    /**
     * 空配置（应用没配过 hashids）时仍可构造，取连接才抛 —— 与 Bootstrap 侧同一约定。
     */
    public function test_empty_config_defers_failure_to_connection_resolution(): void
    {
        $component = new Component();

        self::assertSame('main', $component->getManager()->getDefaultConnection());

        $this->expectException(\InvalidArgumentException::class);
        $component->connection();
    }

    /**
     * __call 必须给 behaviors 让路：无条件转发会把 attachBehavior 挂上的方法静默劫持到
     * manager 上（那里没有，报一个看不懂的错）。
     */
    public function test_behaviors_are_not_hijacked_by_the_forwarding_call(): void
    {
        $component = new Component(['config' => $this->config()]);
        $component->attachBehavior('probe', new class extends Behavior {
            public function shout(): string
            {
                return 'behavior-ran';
            }
        });

        self::assertSame('behavior-ran', $component->shout());
        // 挂上行为之后普通转发照常。
        self::assertSame([7], $component->decode($component->encode(7)));
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        return [
            'default'     => 'main',
            'connections' => ['main' => ['salt' => 'yii-stub-salt', 'length' => 6]],
        ];
    }
}
