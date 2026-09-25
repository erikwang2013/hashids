<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace Erikwang2013\Hashids\Tests\Contract;

use PHPUnit\Framework\TestCase;

/**
 * `Install` 的**端到端**契约测试：跑在真 webman 辅助函数（base_path / copy_dir /
 * remove_dir）上，写进一个真的临时应用根。
 *
 * 为什么必须独立成子进程：见 fixtures/install-probe.php 的头部说明。简言之
 * BASE_PATH 由 getcwd() 决定且是编译期常量，只能靠子进程的 cwd 控制。
 *
 * 这一层守的是**本项目历史上最严重的一个缺陷**：真正的 Webman 里，
 * `support\Plugin::install()` 恒以 `Install::install(true)` 调用，而项目
 * composer.json 又把 post-package-update 也指向它 —— 于是每次
 * `composer install` / `composer update` 都会用包内模板覆盖用户的
 * config/hashids.php，真实 salt 被空 salt 顶掉：已发出的 hashid 全部失效，
 * 新生成的因盐为空可被枚举。
 *
 * 为什么非要用契约层：tests/InstallTest.php 至今仍走手写替身，而**那个替身
 * 当初把这个破坏性行为断言成了正确行为**（test_install_with_confirm_overwrites_
 * existing_config）。替身是按假设写的，假设错了它只会一路绿下去。
 */
final class InstallContractTest extends TestCase
{
    private const REAL_SALT = 'PROD-SECRET-42';

    private string $appRoot;

    protected function setUp(): void
    {
        $this->appRoot = sys_get_temp_dir() . '/hashids-install-contract-' . getmypid() . '-' . bin2hex(random_bytes(4));

        // webman 的 BASE_PATH 探测要求**同时**存在 vendor/ 与 start.php，
        // 缺一个就会继续向上找 —— 这条规则此前只存在于替身假设里。这里用真规则。
        mkdir($this->appRoot . '/vendor', 0755, true);
        mkdir($this->appRoot . '/config', 0755, true);
        file_put_contents($this->appRoot . '/start.php', "<?php\n");
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->appRoot);
    }

    /**
     * 回归：用户配置绝不能被覆盖 —— 这是 Webman 每次 composer install/update
     * 都会走到的那条路径（Plugin::install 恒传 true）。
     */
    public function test_install_with_confirm_never_overwrites_the_user_config(): void
    {
        $dest = $this->appRoot . '/config/hashids.php';
        $userConfig = "<?php\n\nreturn [\n    'default' => 'main',\n    'connections' => [\n"
            . "        'main' => ['salt' => '" . self::REAL_SALT . "', 'length' => 8],\n    ],\n];\n";
        file_put_contents($dest, $userConfig);

        $result = $this->runProbe('install-confirm');

        self::assertTrue($result['config_unchanged'], sprintf(
            "用户配置被覆盖了！\n覆盖前: %s\n覆盖后: %s",
            $result['config_before'],
            $result['config_after']
        ));
        self::assertStringContainsString(self::REAL_SALT, (string) file_get_contents($dest));
    }

    /** 对照：全新应用首次安装必须照常写入配置，否则上面的测试靠「什么都不做」也能过。 */
    public function test_install_with_confirm_writes_config_on_a_fresh_app(): void
    {
        $result = $this->runProbe('install-confirm');

        self::assertFalse($result['config_existed_before']);
        self::assertTrue($result['config_existed_after'], '首次安装应当写入 config/hashids.php');
        self::assertStringContainsString('connections', (string) $result['config_after']);
    }

    /** 插件骨架应当被拷贝进来（走的是真 copy_dir()）。 */
    public function test_install_copies_the_plugin_skeleton(): void
    {
        $result = $this->runProbe('install-confirm');

        self::assertTrue($result['plugin_dir_exists'], '插件目录应被拷贝到 config/plugin/erikwang2013/hashids');
        self::assertTrue($result['plugin_app_exists'], 'app.php 应随插件骨架一起拷入');
    }

    /** 卸载移除插件目录，但**保留**用户配置 —— README 明确承诺过这一点。 */
    public function test_uninstall_removes_plugin_dir_but_keeps_the_user_config(): void
    {
        $this->runProbe('install-confirm');
        file_put_contents(
            $this->appRoot . '/config/hashids.php',
            "<?php return ['connections' => ['main' => ['salt' => '" . self::REAL_SALT . "']]];"
        );

        $result = $this->runProbe('uninstall');

        self::assertFalse($result['plugin_dir_exists'], '卸载应移除 config/plugin/erikwang2013/hashids');
        self::assertTrue($result['config_existed_after'], '卸载不应删除用户的 config/hashids.php');
        self::assertStringContainsString(self::REAL_SALT, (string) $result['config_after']);
    }

    /**
     * 子进程跑一次探测器。cwd 指向临时应用根 —— 这是让真 base_path()
     * 解析到那里的唯一办法。
     *
     * @return array<string, mixed>
     */
    private function runProbe(string $action): array
    {
        $command = [PHP_BINARY, __DIR__ . '/fixtures/install-probe.php', $action];
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

        $process = proc_open($command, $descriptors, $pipes, $this->appRoot, [
            'PATH' => (string) getenv('PATH'),
        ]);

        self::assertIsResource($process, 'proc_open 失败');

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        self::assertSame(0, $exit, "探测器退出码 {$exit}；stderr: {$stderr}");

        $marker = "\n---JSON---\n";
        $position = strpos($stdout, $marker);
        self::assertNotFalse($position, "探测器没有输出 JSON 标记。stdout: {$stdout}");

        $decoded = json_decode(substr($stdout, $position + strlen($marker)), true);
        self::assertIsArray($decoded, '探测器输出不是合法 JSON');

        return $decoded;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            is_dir($path) && !is_link($path) ? $this->removeDir($path) : unlink($path);
        }

        rmdir($dir);
    }
}
