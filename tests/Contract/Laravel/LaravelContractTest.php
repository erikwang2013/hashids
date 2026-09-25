<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace Erikwang2013\Hashids\Tests\Contract\Laravel;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Erikwang2013\Hashids\Laravel\Facades\Hashids as HashidsFacade;
use Erikwang2013\Hashids\Laravel\HashidsServiceProvider;
use Hashids\Hashids as HashidsClient;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase;

/**
 * Laravel 集成契约测试 —— 用**真 Laravel**（orchestra/testbench）跑，不用 Mockery 假容器。
 *
 * 存在理由：tests/Laravel/HashidsServiceProviderTest.php 全是假容器 + 手写替身，
 * 而框架生命周期级别的行为（provider 怎么进 deferred 清单、boot 的时机、console
 * kernel 的 loadDeferredProviders、vendor:publish 的标签注册时机）在替身里根本
 * 不存在 —— 替身是按假设写的，假设错了它只会一路绿下去。本项目负责人在 Laravel
 * 侧两次误判（以为 DeferrableProvider 会让 `vendor:publish --tag=hashids-config`
 * 失效，实测正常；以为容器键已对齐 vinkla 而实际缺 hashids.factory /
 * hashids.connection），两次都只靠手工验证过一次。这个文件把那两条钉进回归网。
 *
 * 跑法：vendor/bin/phpunit --configuration phpunit.contract-laravel.xml
 * （与 Webman 不能同 vendor：两边都定义全局 base_path()，详见 Laravel/bootstrap.php）
 */
final class LaravelContractTest extends TestCase
{
    /**
     * vendor:publish 的目的地。
     *
     * testbench 的应用根是 vendor/orchestra/testbench-core/laravel（骨架目录），
     * `config_path()` 默认指向它的 config/ —— 往那儿发布会写脏 vendor 且留痕。
     * 所以每个测试实例开一个临时目录，在 getEnvironmentSetUp 里 useConfigPath()
     * 把它顶掉：publish 落到临时目录，跑完删掉，仓库与 vendor 都干净。
     */
    private string $configPath = '';

    /**
     * provider 注册完、**尚未 boot** 那一刻的容器抓拍（见 getEnvironmentSetUp）。
     *
     * @var array<string, bool>
     */
    private array $beforeBoot = [];

    protected function setUp(): void
    {
        $this->configPath = sys_get_temp_dir() . '/hashids-laravel-contract-' . getmypid() . '-' . bin2hex(random_bytes(4));
        mkdir($this->configPath, 0755, true);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach (glob($this->configPath . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->configPath);
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [HashidsServiceProvider::class];
    }

    /**
     * 这个钩子被调用的时机很关键：testbench 在
     * CreatesApplication::resolveApplicationBootstrappers() 里按
     *   RegisterProviders（provider 进 deferred 清单）
     *   → defineEnvironment / getEnvironmentSetUp（← 这里）
     *   → BootProviders（boot，publishes 在此注册）
     *   → ConsoleKernel::bootstrap()（loadDeferredProviders，全部 deferred 被加载）
     * 的顺序走。所以这里正对应真应用里「provider 已交给容器、服务还没注册、
     * 谁都没解析过」的那一刻，抓拍下来才有意义。
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app->useConfigPath($this->configPath);

        // 注意不能用 bound() 当「是否已注册」的探针：Application::bound() 被重写成
        // isDeferredService() || parent::bound()，「在 deferred 清单里」也算真。
        // 要证明「一个绑定都没注册」只能直接看 bindings / aliases / loadedProviders。
        $this->beforeBoot = [
            'has' => $app->has('hashids'),
            'deferred' => $app->isDeferredService('hashids'),
            'registered' => $app->isAlias('hashids') || array_key_exists(HashidsManager::class, $app->getBindings()),
            'loaded' => $app->providerIsLoaded(HashidsServiceProvider::class),
        ];
    }

    /**
     * 六个容器标识必须都能解析，且类名与别名拿到的是**同一份** —— 与
     * vinkla/hashids 的 'hashids' / 'hashids.factory' / 'hashids.connection'
     * 对齐，缺一个就是从 vinkla 迁移过来的代码断链；若别名指向另一份实例，
     * 配置就会「改一半生效一半」。
     */
    public function test_all_six_container_identifiers_resolve_to_the_same_instances(): void
    {
        $manager = $this->app->make(HashidsManager::class);
        $factory = $this->app->make(HashidsFactory::class);
        $client = $this->app->make(HashidsClient::class);

        $this->assertSame($manager, $this->app->make('hashids'), "别名 'hashids' 与类名不是同一份");
        $this->assertSame($factory, $this->app->make('hashids.factory'), "别名 'hashids.factory' 与类名不是同一份");
        $this->assertSame($client, $this->app->make('hashids.connection'), "别名 'hashids.connection' 与类名不是同一份");

        // 三者还得互相串起来：拿到的 factory 必须是 manager 用的那个，
        // 取到的连接必须是 manager 的连接（否则各持一份配置）。
        $this->assertSame($factory, $manager->getFactory());
        $this->assertSame($client, $manager->connection());

        $hash = $manager->encode(123, 456);
        $this->assertSame([123, 456], $manager->decode($hash));
        $this->assertSame([123, 456], $this->app->make('hashids')->decode($hash));
        $this->assertSame($hash, $this->app->make('hashids.connection')->encode(123, 456));
    }

    public function test_facade_encodes_decodes_and_switches_connection(): void
    {
        $hash = HashidsFacade::encode(12345);

        $this->assertSame($this->app->make('hashids')->encode(12345), $hash);
        $this->assertSame([12345], HashidsFacade::decode($hash));

        $alternative = HashidsFacade::connection('alternative');

        $this->assertInstanceOf(HashidsClient::class, $alternative);
        $this->assertSame($this->app->make('hashids')->connection('alternative'), $alternative);

        // 两个连接的 salt 不同，同一输入的编码必须不同 —— 顺带证明 salt 是按连接
        // 从配置里取的，不是写死的。
        $this->assertNotSame($hash, $alternative->encode(12345));
        $this->assertSame([12345], $alternative->decode($alternative->encode(12345)));
    }

    /**
     * 换掉配置里的 salt，编码结果必须跟着变 —— 否则「配置驱动」只是句口号。
     * 直接改 config 仓库再重新解析 manager，走的是真实解析路径。
     */
    public function test_connection_salt_comes_from_the_config_repository(): void
    {
        $this->assertSame('', config('hashids.connections.main.salt'), '包默认 salt 应为空串');

        $before = $this->app->make('hashids')->encode(12345);

        $this->app['config']->set('hashids.connections.main.salt', 'CONTRACT-CHANGED-SALT');
        $this->app->forgetInstance(HashidsManager::class);

        $after = $this->app->make('hashids')->encode(12345);

        $this->assertNotSame($before, $after, '改了配置里的 salt 却没改变编码结果 —— salt 没走配置');
    }

    /**
     * DeferrableProvider 的真实行为：provides() 的内容，以及「懒」确实成立。
     *
     * 负责人误判过的那条（DeferrableProvider 会不会挡住 vendor:publish）的答案就在
     * 这里：provider 进的是 deferred 清单，而 ConsoleKernel::bootstrap() 会主动
     * loadDeferredProviders()，所以 artisan 场景下它照样被加载并 boot —— 下面的
     * 发布测试直接验证最终效果，这条验证机制本身。
     */
    public function test_provider_is_deferred_and_resolves_lazily(): void
    {
        $provider = new HashidsServiceProvider($this->app);

        $this->assertEqualsCanonicalizing([
            HashidsFactory::class,
            HashidsManager::class,
            'hashids',
            'hashids.factory',
            'hashids.connection',
            HashidsClient::class,
        ], $provider->provides(), 'provides() 的内容变了 —— deferred 清单与 vinkla 对齐都依赖它');

        // 抓拍点：容器认得这些服务（has → true，因为它们在 deferred 清单里），
        // 但一个都还没注册（registered → false），provider 也还没加载。
        $this->assertTrue($this->beforeBoot['has'], 'deferred 服务应让 has() 为真');
        $this->assertTrue($this->beforeBoot['deferred'], '六个标识必须整体进 deferred 清单（ProviderRepository 按 provides() 建表）');
        $this->assertFalse($this->beforeBoot['registered'], '此刻不该有任何绑定/别名 —— 否则说明 provider 是 eager 注册的');
        $this->assertFalse($this->beforeBoot['loaded'], '此刻 provider 不该已被加载 —— 否则「懒」是假的');

        // 真懒解析：拿一个没跑过 console kernel 的 Application（= 真应用里 artisan
        // 之外的请求生命周期），把 provider 按 ProviderRepository 的做法放进
        // deferred 清单，不预先 resolve 任何东西，直接取绑定。
        $app = new Application();
        $app->instance('config', new Repository(['hashids' => require dirname(__DIR__, 3) . '/config/hashids.php']));
        $app->addDeferredServices(array_fill_keys($provider->provides(), HashidsServiceProvider::class));

        $this->assertTrue($app->isDeferredService('hashids'));
        $this->assertFalse($app->isAlias('hashids'), '取之前不该已注册');

        $hash = $app->make('hashids')->encode(12345);

        $this->assertTrue($app->providerIsLoaded(HashidsServiceProvider::class), '取绑定应触发 deferred provider 加载');
        $this->assertTrue($app->isAlias('hashids'), '取绑定应触发 register()，别名随之建立');
        $this->assertSame([12345], $app->make('hashids.connection')->decode($hash));

        // 跑完 setUp 后（console kernel 已 bootstrap）provider 必然已加载。
        $this->assertTrue($this->app->providerIsLoaded(HashidsServiceProvider::class));
    }

    /**
     * 负责人误判过的那条：DeferrableProvider 不会让
     * `vendor:publish --tag=hashids-config` 失效。
     *
     * 断言落到真实产物：文件写到 config_path()、内容与包内模板逐字节一致。
     */
    public function test_vendor_publish_tag_writes_the_package_config_file(): void
    {
        $target = $this->configPath . '/hashids.php';
        $template = dirname(__DIR__, 3) . '/config/hashids.php';

        $this->assertFileDoesNotExist($target);

        $this->artisan('vendor:publish', ['--tag' => 'hashids-config'])->assertExitCode(0);

        $this->assertFileExists($target, 'vendor:publish --tag=hashids-config 没产出配置文件');
        $this->assertStringContainsString("'connections'", (string) file_get_contents($target));
        $this->assertFileEquals($template, $target, '发布的配置应与包内模板逐字节一致');
    }

    /**
     * 不发布也要能用：mergeConfigFrom 会把包内默认配置合进 config 仓库。
     */
    public function test_config_is_merged_from_the_package_without_publishing(): void
    {
        $this->assertFileDoesNotExist($this->configPath . '/hashids.php');

        $config = config('hashids');

        $this->assertIsArray($config);
        $this->assertSame('main', $config['default']);
        $this->assertSame('', $config['connections']['main']['salt']);
        $this->assertSame('your-salt-string', $config['connections']['alternative']['salt']);

        // 合进仓库的配置还必须真的驱动 manager（不是只在仓库里躺着）。
        $this->assertSame(
            $this->app->make('hashids')->encode(7),
            $this->app->make('hashids')->connection('main')->encode(7)
        );
    }
}
