<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace Erikwang2013\Hashids\Webman;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Hashids\Hashids as HashidsClient;
use support\Container;
use Webman\Bootstrap as WebmanBootstrapContract;
use Workerman\Worker;

use function config;

final class Bootstrap implements WebmanBootstrapContract
{
    public static function start(?Worker $worker): void
    {
        // 插件开关的真实位置是 $plugin['app']['enable'] —— 真实 Webman 下
        // config('plugin.<vendor>.<name>') 的形状是 ['app' => [...], 'bootstrap' => [...]]，
        // enable 不在顶层。原先写成 $plugin['enable'] ?? true 是恒真的死代码。
        //
        // 正常流程其实轮不到这里把关：插件 app.php 里 enable 为假时
        // Webman\Config::loadFromDir() 会跳过整个插件目录，本方法根本不会被调用。
        // 这道检查守的是纵深缺口 —— 用户若把 Bootstrap::class 手动注册进应用级
        // config/bootstrap.php，插件即便已关闭，start() 仍会被执行。
        //
        // 那个场景下配置是**整个缺失**的（框架跳过了插件目录，键根本不存在），
        // 所以默认值必须取「关闭」：写成 ?? true 会让 null 兜底成「开」，
        // 缺口原样敞开。合法路径下 app.enable 必然为真（目录级闸门先过了），
        // 这个默认值只影响上面那条非法路径，不会误伤正常安装。
        $plugin = config('plugin.erikwang2013.hashids');
        if (!is_array($plugin) || !($plugin['app']['enable'] ?? false)) {
            return;
        }

        $factory = new HashidsFactory();
        $manager = new HashidsManager(config('hashids'), $factory);

        Container::instance()->addDefinitions([
            HashidsFactory::class => static fn (): HashidsFactory => $factory,
            HashidsManager::class => static fn (): HashidsManager => $manager,
            'hashids' => static fn (): HashidsManager => $manager,
            'hashids.factory' => static fn (): HashidsFactory => $factory,
            'hashids.connection' => static fn (): HashidsClient => $manager->connection(),
            HashidsClient::class => static fn (): HashidsClient => $manager->connection(),
        ]);
    }
}
