<?php

declare(strict_types=1);

/**
 * 安装/卸载的**子进程探测器**。
 *
 * 为什么必须是子进程：webman 的 helpers.php 在 composer autoload 阶段就
 * `define('BASE_PATH', ...)`，而 BASE_PATH 是从 **getcwd()** 向上找同时含
 * `vendor/` 与 `start.php` 的目录确定的（见 webman-framework/src/support/helpers.php）。
 * 常量一旦定义就改不了，所以要让 Install 写进临时目录，只能在**子进程**里把
 * cwd 指过去，让它在加载 autoloader 时就把 BASE_PATH 定在那儿。
 *
 * 这样测到的是**真实的 base_path() 解析**——包括「必须同时有 vendor/ 和 start.php」
 * 这条我们此前只能靠替身假设的规则——而不是把 base_path 注入成可控值。
 *
 * 用法：php install-probe.php <install|install-confirm|uninstall>
 * 环境变量：HASHIDS_FRAMEWORK_AUTOLOAD 指向装好真框架的 vendor/autoload.php
 * 输出：人可读内容之后跟一行 `---JSON---`，再跟一行结果 JSON。
 */

$frameworkAutoload = getenv('HASHIDS_FRAMEWORK_AUTOLOAD');
$packageAutoload = dirname(__DIR__, 3) . '/vendor/autoload.php';

if (!is_string($frameworkAutoload) || !is_file($frameworkAutoload)) {
    fwrite(STDERR, "HASHIDS_FRAMEWORK_AUTOLOAD 未指向存在的 vendor/autoload.php\n");
    exit(2);
}

// 顺序要紧：真框架先加载，helpers.php 才会在此时按当前 cwd 定下 BASE_PATH。
require_once $frameworkAutoload;
require_once $packageAutoload;

$action = $argv[1] ?? '';
$configPath = base_path() . '/config/hashids.php';

$before = is_file($configPath) ? (string) file_get_contents($configPath) : null;

switch ($action) {
    case 'install':
        Erikwang2013\Hashids\Install::install();
        break;
    case 'install-confirm':
        // Webman 的 support\Plugin::install() 恒以此形式调用
        Erikwang2013\Hashids\Install::install(true);
        break;
    case 'uninstall':
        Erikwang2013\Hashids\Install::uninstall();
        break;
    default:
        fwrite(STDERR, "未知动作：{$action}\n");
        exit(2);
}

$after = is_file($configPath) ? (string) file_get_contents($configPath) : null;

$pluginDir = base_path() . '/config/plugin/erikwang2013/hashids';

echo "\n---JSON---\n";
echo json_encode([
    'base_path' => base_path(),
    'config_existed_before' => $before !== null,
    'config_existed_after' => $after !== null,
    'config_unchanged' => $before === $after,
    'config_before' => $before,
    'config_after' => $after,
    'plugin_dir_exists' => is_dir($pluginDir),
    'plugin_app_exists' => is_file($pluginDir . '/app.php'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
