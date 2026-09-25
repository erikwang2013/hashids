<?php

declare(strict_types=1);

/**
 * Laravel 契约测试的自举。
 *
 * 为什么要与 tests/Contract/bootstrap.php 分开：Laravel 与 Webman **不能装进
 * 同一个 vendor** —— 两者都定义全局函数 base_path()（Laravel 在
 * Illuminate\Foundation\helpers.php，Webman 在 support\helpers.php），
 * 谁生效取决于 composer files-autoload 的顺序。同 vendor 时 Laravel 那份会赢，
 * 于是 Webman 侧的 Install 调用 base_path() 会落到 Laravel 的实现上，
 * 而它需要启动过的 Laravel 应用 → Fatal error:
 *   Call to undefined method Illuminate\Container\Container::basePath()
 *
 * 所以 CI 拆成两个作业：contract（webman/thinkphp/hyperf）与
 * contract-laravel（orchestra/testbench）。文件也放在各自的子目录，
 * 两个 phpunit 配置互不收集对方。
 */

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

foreach (['Illuminate\Foundation\Application', 'Orchestra\Testbench\TestCase'] as $symbol) {
    if (!class_exists($symbol)) {
        fwrite(STDERR, sprintf(
            "Laravel 契约测试需要 [%s]，当前 vendor 里没有。\n" .
            "CI 由 contract-laravel 作业安装；本地请执行：\n" .
            "  composer require --dev --with-all-dependencies orchestra/testbench:^9.0\n" .
            "注意不要与 workerman/webman-framework 同时安装（全局 base_path() 会冲突）。\n",
            $symbol
        ));
        exit(1);
    }
}
