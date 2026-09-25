<?php

declare(strict_types=1);

/**
 * 契约测试的自举：加载 vendor 并校验真实框架符号在场。
 *
 * 为什么真框架不在 require-dev 里：workerman/webman-framework 要求 PHP >= 8.1、
 * hyperf/* 要求 >= 8.2，而本包声明 `php: ^8.0` —— 写进 require-dev 会让 8.0
 * 的作业解析失败。所以它们由 CI 的 contract 作业（PHP 8.2）用
 * `composer require --dev` 按需装进**同一个** vendor。
 *
 * 刻意只加载一个 vendor（就是本包自己的）。早先支持过「另指一个装了真框架的
 * vendor」（CONTRACT_VENDOR），但那在两个 vendor 都含 phpunit 时会炸
 * （orchestra/testbench 自己依赖 phpunit）—— 同一个类被两个 autoloader 声明。
 * 本地要跑契约套件，就照 CI 的做法把真框架装进本仓库的 vendor。
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// 真框架缺席时明确报错，而不是让测试以莫名其妙的 fatal 结束。
foreach (['Webman\Bootstrap', 'think\Service', 'Hyperf\Contract\ConfigInterface', 'support\Container'] as $symbol) {
    if (!class_exists($symbol) && !interface_exists($symbol)) {
        fwrite(STDERR, sprintf(
            "契约测试需要真实框架符号 [%s]，当前 vendor 里没有。\n" .
            "CI 由 contract 作业安装；本地请照它做：composer require --dev " .
            "topthink/framework:^8.0 workerman/webman-framework:^2.0 " .
            "hyperf/contract:^3.0 hyperf/config:^3.0 hyperf/di:^3.0 orchestra/testbench:^9.0\n",
            $symbol
        ));
        exit(1);
    }
}

// Hyperf 的 DI 容器解析要经过协程上下文（hyperf/context → hyperf/engine），
// 缺 Swoole/Swow 时会以 Class "Swoole\Coroutine" not found 失败 —— 报错栈指向
// hyperf 内部，看不出根因。这里提前拦一道，把话说清楚。
// 真实 Hyperf 部署必然带其中之一，所以这是环境前提，不是本包的依赖。
if (!extension_loaded('swoole') && !extension_loaded('swow') && !extension_loaded('openswoole')) {
    fwrite(STDERR, implode("\n", [
        '契约测试里 Hyperf 那部分需要协程扩展（swoole 或 swow），当前两个都没有。',
        '缺了它，hyperf/di 的 Container::get() 会在 hyperf/engine 内部抛',
        '  Error: Class "Swoole\Coroutine" not found',
        'CI 的 contract 作业通过 setup-php 的 extensions: swoole 安装；',
        '本地请装 swoole。',
        '',
    ]) . "\n");
    exit(1);
}
