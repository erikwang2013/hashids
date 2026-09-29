<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace Erikwang2013\Hashids\Yii3;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Hashids\Hashids as HashidsClient;
use Psr\Container\ContainerInterface;
use Yiisoft\Di\ServiceProviderInterface;

/**
 * 把六个容器标识注册进 Yii 3 的 PSR-11 容器（yiisoft/di）。
 *
 * 在应用的容器装配处登记：
 *
 *     $config = ContainerConfig::create()->withProviders([
 *         new ServiceProvider($params['hashids'] ?? []),
 *     ]);
 *     $container = new Container($config);
 *
 * 两点设计约束：
 *
 *  1. 定义只用**类名字符串与闭包**，不返回预造对象。真 Yiisoft\Di\Container 对对象型定义
 *     走的是另一条分支（直接当单例存下），而接口把 getDefinitions() 的契约写成「只返回
 *     定义、无副作用、可重复调用」—— 预造对象会让每次调用产出不同实例，正是要避免的写法。
 *  2. 别名与 FQCN 之间只做转发，不自己缓存。真 Container::get() 自带单例语义
 *     （文档原话：same instance ... each time），转发即可让两者指向同一套连接。
 */
final class ServiceProvider implements ServiceProviderInterface
{
    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param mixed $config Expected shape: array{default?: string, connections?: array<string, array<string, mixed>>}
     */
    public function __construct(mixed $config = [])
    {
        $this->config = is_array($config) ? $config : [];
    }

    public function getDefinitions(): array
    {
        $config = $this->config;

        return [
            HashidsFactory::class => HashidsFactory::class,
            HashidsManager::class => static fn (ContainerInterface $container): HashidsManager => new HashidsManager(
                $config,
                $container->get(HashidsFactory::class)
            ),
            'hashids' => static fn (ContainerInterface $container): HashidsManager => $container->get(HashidsManager::class),
            'hashids.factory' => static fn (ContainerInterface $container): HashidsFactory => $container->get(HashidsFactory::class),
            'hashids.connection' => static fn (ContainerInterface $container): HashidsClient => $container->get(HashidsManager::class)->connection(),
            HashidsClient::class => static fn (ContainerInterface $container): HashidsClient => $container->get(HashidsManager::class)->connection(),
        ];
    }

    public function getExtensions(): array
    {
        return [];
    }
}
