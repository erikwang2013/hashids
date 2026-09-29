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
use Yii;
use yii\base\BootstrapInterface;

/**
 * 把六个容器标识注册进 Yii 2 的 DI 容器（Yii::$container）。
 *
 * 在应用配置的 'bootstrap' 数组里登记本类即可：
 *
 *     'bootstrap' => [\Erikwang2013\Hashids\Yii2\Bootstrap::class],
 *
 * 配置来源是 Yii 惯用的应用参数 params['hashids']（config/params.php），
 * 形状与其它适配器一致：根级 default + connections。
 */
final class Bootstrap implements BootstrapInterface
{
    /**
     * 由应用在 bootstrap 阶段调用。
     *
     * 签名不能加类型：yii\base\BootstrapInterface::bootstrap() 的参数**没有**类型声明，
     * 这里写 `Application $app` 就是参数逆变违规（PHP 直接 fatal）。返回类型可以加
     * （协变），: void 是安全的。
     */
    public function bootstrap($app): void
    {
        $params = is_object($app) ? ($app->params ?? []) : [];
        $config = is_array($params) ? ($params['hashids'] ?? []) : [];

        $factory = new HashidsFactory();
        $manager = new HashidsManager($config, $factory);

        $container = Yii::$container;

        // 已构造好的对象直接当定义登记：yii\di\Container::normalizeDefinition() 收到对象会
        // 原样保留，get() 走 is_object 分支把它作为单例返回 —— 不必再包一层闭包。
        $container->setSingleton(HashidsFactory::class, $factory);
        $container->setSingleton(HashidsManager::class, $manager);
        $container->setSingleton('hashids', $manager);
        $container->setSingleton('hashids.factory', $factory);

        // 这两个必须惰性：解析默认连接会校验 salt/length/alphabet 等配置，配置有误要在
        // **取用时**抛，而不是在 bootstrap 阶段把整个应用拖垮。Webman 那边的等价约定见
        // tests/Webman/BootstrapTest.php::test_start_registers_with_non_array_hashids_config_but_client_resolution_throws
        $container->setSingleton(HashidsClient::class, static fn (): HashidsClient => $manager->connection());
        $container->setSingleton('hashids.connection', static fn (): HashidsClient => $manager->connection());
    }
}
