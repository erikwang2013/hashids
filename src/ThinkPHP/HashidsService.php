<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace Erikwang2013\Hashids\ThinkPHP;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Hashids\Hashids as HashidsClient;
use think\Service as ThinkService;

final class HashidsService extends ThinkService
{
    public function register(): void
    {
        // HashidsFactory 无状态、构造器无参：容器 make() 时构造一次并记入自己的
        // instances，天然就是单实例。这行 bind 在 think-container v3 里其实**是空操作**
        // （bind() 在 abstract == concrete 时直接跳过，不会写进 bind 表），保留只为语义
        // 完整，并不产生绑定。不要依赖它做共享 —— 共享来自容器自身的实例记忆。
        $this->app->bind(HashidsFactory::class, HashidsFactory::class);

        $this->app->bind(HashidsManager::class, fn (): HashidsManager => new HashidsManager(
            $this->app->config->get('hashids'),
            $this->app->make(HashidsFactory::class)
        ));

        $this->app->bind('hashids', fn (): HashidsManager => $this->app->make(HashidsManager::class));

        $this->app->bind('hashids.factory', fn (): HashidsFactory => $this->app->make(HashidsFactory::class));

        $this->app->bind('hashids.connection', fn (): HashidsClient => $this->app->make(HashidsManager::class)->connection());

        $this->app->bind(HashidsClient::class, fn (): HashidsClient => $this->app->make(HashidsManager::class)->connection());
    }
}
