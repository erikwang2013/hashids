<?php

declare(strict_types=1);

namespace Erikwang2013\Hashids\Tests;

use Erikwang2013\Hashids\Install;
use Erikwang2013\Hashids\Tests\Support\StubState;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function Erikwang2013\Hashids\Tests\Support\reflection_accessible;

require_once __DIR__ . '/Support/FrameworkStubs.php';

/**
 * Install uses webman framework functions (base_path/copy_dir/remove_dir).
 * Stubs make it safe to run install/uninstall against a temp directory.
 */
final class InstallTest extends TestCase
{
    private string $base;

    protected function setUp(): void
    {
        if (!StubState::$globalStubs) {
            self::markTestSkipped('webman (or full laravel) functions are present; stub-based tests skipped.');
        }
        StubState::reset();
        $this->base = sys_get_temp_dir() . '/hashids-install-' . bin2hex(random_bytes(4));
        StubState::$basePath = $this->base;
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->base);
        StubState::reset();
    }

    public function test_webman_plugin_flag_is_true(): void
    {
        self::assertTrue(Install::WEBMAN_PLUGIN);
    }

    public function test_path_relation_maps_plugin_config(): void
    {
        $prop = reflection_accessible(new ReflectionProperty(Install::class, 'pathRelation'));
        $relation = $prop->getValue();

        self::assertSame(
            'config/plugin/erikwang2013/hashids',
            $relation['config/plugin/erikwang2013/hashids']
        );
    }

    public function test_install_copies_plugin_config_and_publishes_hashids_config(): void
    {
        Install::install();

        self::assertCount(1, StubState::$copied);
        [$source, $dest] = StubState::$copied[0];
        self::assertStringContainsString('config/plugin/erikwang2013/hashids', $source);
        self::assertSame($this->base . '/config/plugin/erikwang2013/hashids', $dest);

        self::assertDirectoryExists($this->base . '/config/plugin/erikwang2013');
        self::assertFileExists($this->base . '/config/hashids.php');
        self::assertStringContainsString("'default' => 'main'", (string) file_get_contents($this->base . '/config/hashids.php'));
    }

    public function test_install_does_not_overwrite_existing_hashids_config(): void
    {
        $dest = $this->base . '/config/hashids.php';
        mkdir(dirname($dest), 0755, true);
        file_put_contents($dest, '<?php return ["custom" => true];');

        Install::install();

        self::assertSame('<?php return ["custom" => true];', file_get_contents($dest));
    }

    public function test_install_with_confirm_overwrites_existing_config(): void
    {
        $dest = $this->base . '/config/hashids.php';
        mkdir(dirname($dest), 0755, true);
        file_put_contents($dest, 'old-content');

        Install::install(true);

        self::assertNotSame('old-content', file_get_contents($dest));
        self::assertStringContainsString('connections', (string) file_get_contents($dest));
    }

    public function test_uninstall_removes_plugin_config_dir(): void
    {
        mkdir($this->base . '/config/plugin/erikwang2013/hashids', 0755, true);

        Install::uninstall();

        self::assertContains($this->base . '/config/plugin/erikwang2013/hashids', StubState::$removed);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        foreach ($items ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path) && !is_link($path)) {
                $this->removeDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
