<?php

declare(strict_types=1);

/**
 * 契约测试的自举：让「真框架」与「本包」同时可加载。
 *
 * 为什么需要两个 autoloader：
 * - 这些框架包装不进本包 require-dev —— workerman/webman-framework 要求 PHP >= 8.1、
 *   hyperf/* 要求 >= 8.2，而本包声明 `php: ^8.0`，写进 require-dev 会让 8.0 的作业直接失败。
 *   所以它们只在 CI 的 contract 作业（PHP 8.2）里按需安装。
 * - 本地开发时可以用 CONTRACT_VENDOR 指向另一个装好真框架的 vendor 目录。
 *
 * 用 require_once 而非 require：CI 里两者是同一个 vendor（同一个路径，只会加载一次），
 * 本地则是两个不同路径的真实框架/vendor 组合，两个都要加载。两个 vendor 的
 * ComposerAutoloaderInit 类名不同，不会冲突。
 */

$vendors = array_filter([
    getenv('CONTRACT_VENDOR') ?: null,
    dirname(__DIR__, 2) . '/vendor/autoload.php',
]);

foreach ($vendors as $vendor) {
    if (is_file($vendor)) {
        require_once $vendor;
    }
}

// 真框架缺席时明确报错，而不是让测试以莫名其妙的 fatal 结束。
foreach (['Webman\Bootstrap', 'think\Service', 'Hyperf\Contract\ConfigInterface', 'support\Container'] as $symbol) {
    if (!class_exists($symbol) && !interface_exists($symbol)) {
        fwrite(STDERR, sprintf(
            "契约测试需要真实框架符号 [%s]，当前 vendor 里没有。\n" .
            "CI 里由 contract 作业安装；本地请设 CONTRACT_VENDOR 指向装好真框架的 vendor/autoload.php。\n",
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
        '本地请装 swoole，或用 CONTRACT_VENDOR 指向装了扩展的环境。',
        '',
    ]) . "\n");
    exit(1);
}
