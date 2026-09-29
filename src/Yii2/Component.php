<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace Erikwang2013\Hashids\Yii2;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Hashids\Hashids as HashidsClient;
use yii\base\Component as YiiComponent;

/**
 * 应用组件形态：让 Yii 惯用写法 Yii::$app->hashids 可用。
 *
 *     'components' => [
 *         'hashids' => [
 *             'class' => \Erikwang2013\Hashids\Yii2\Component::class,
 *             'config' => [
 *                 'default' => 'main',
 *                 'connections' => ['main' => ['salt' => 'xxx', 'length' => 6]],
 *             ],
 *         ],
 *     ],
 *
 * 之后：
 *
 *     Yii::$app->hashids->encode(1);
 *     Yii::$app->hashids->connection('alternative')->encode(100);
 *     Yii::$app->hashids->manager;    // 走 getManager()，Yii 的属性魔术
 *
 * 为什么需要这个类：yii\di\ServiceLocator::get() **不会**回落到 Yii::$container，
 * 只认登记在自己 _definitions 里的组件。所以「Bootstrap 已注册容器」并不能让
 * Yii::$app->hashids 生效 —— 组件定义只能来自应用的 components 配置，或这里的类。
 */
final class Component extends YiiComponent
{
    /**
     * Hashids 配置，形状与其它适配器一致：根级 default + connections。
     *
     * @var array<string, mixed>
     */
    public array $config = [];

    private ?HashidsFactory $factory = null;

    private ?HashidsManager $manager = null;

    public function getFactory(): HashidsFactory
    {
        return $this->factory ??= new HashidsFactory();
    }

    public function getManager(): HashidsManager
    {
        return $this->manager ??= new HashidsManager($this->config, $this->getFactory());
    }

    /**
     * 取具名连接（缺省取默认连接）。返回的是真实的 Hashids\Hashids 客户端，
     * 后续 encode()/decode() 直接作用在它上面。
     */
    public function connection(?string $name = null): HashidsClient
    {
        return $this->getManager()->connection($name);
    }

    /**
     * 未知方法转给 HashidsManager —— 它自己再 __call 到默认连接，于是
     * Yii::$app->hashids->encode(1) 等价于 Yii::$app->hashids->connection()->encode(1)。
     *
     * 先问 hasMethod() 是为了不把 Yii 的 behaviors 吃掉：yii\base\Component::__call
     * 的职责正是分派给行为，若本类无条件转发，attachBehavior 挂上的方法会被静默
     * 劫持到 manager 上（那里没有，于是报一个看不懂的错）。
     *
     * @param array<int, mixed> $params
     */
    public function __call($name, $params): mixed
    {
        if ($this->hasMethod($name)) {
            return parent::__call($name, $params);
        }

        return $this->getManager()->{$name}(...$params);
    }
}
