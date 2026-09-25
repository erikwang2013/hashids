<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace Erikwang2013\Hashids\Laravel;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Hashids\Hashids as HashidsClient;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

final class HashidsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    private const CONFIG_PATH = __DIR__ . '/../../config/hashids.php';

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                self::CONFIG_PATH => config_path('hashids.php'),
            ], 'hashids-config');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'hashids');

        $this->app->singleton(HashidsFactory::class, static fn (): HashidsFactory => new HashidsFactory());

        $this->app->singleton(HashidsManager::class, fn ($app): HashidsManager => new HashidsManager(
            $app['config']->get('hashids', []),
            $app->make(HashidsFactory::class)
        ));

        $this->app->alias(HashidsManager::class, 'hashids');

        $this->app->bind(HashidsClient::class, static fn ($app): HashidsClient => $app->make(HashidsManager::class)->connection());

        // 对齐 vinkla/hashids 的容器键命名（其 HashidsServiceProvider 绑定
        // 'hashids' / 'hashids.factory' / 'hashids.connection'，三者都再 alias 到对应类名）。
        // 从 vinkla 迁移过来的代码里若有 app('hashids.factory') 之类的取用，靠这两条才不会断。
        $this->app->alias(HashidsFactory::class, 'hashids.factory');
        $this->app->alias(HashidsClient::class, 'hashids.connection');
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            HashidsFactory::class,
            HashidsManager::class,
            'hashids',
            'hashids.factory',
            'hashids.connection',
            HashidsClient::class,
        ];
    }
}
