<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace Erikwang2013\Hashids\Tests\Contract;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Erikwang2013\Hashids\Yii2\Bootstrap;
use Erikwang2013\Hashids\Yii2\Component;
use Hashids\Hashids as HashidsClient;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\Behavior;
use yii\base\BootstrapInterface;
use yii\console\Application;
use yii\di\Container;

/**
 * Yii 2 适配器契约测试：跑在**真实** yiisoft/yii2 上。
 *
 * 这一层要守住的问题是「适配器约定从未对着真框架验过」。Yii 2 上有三件事是不写下来
 * 就会被记错的，本测试逐条钉死：
 *
 *  1. yii\di\ServiceLocator::get() **不会**回落到 Yii::$container —— 只认登记在自己
 *     _definitions 里的组件。所以「Bootstrap 注册过容器」并不等于 Yii::$app->hashids
 *     可用，那需要应用的 components 配置。这正是本包要额外提供一个 Component 类的原因。
 *  2. yii\base\BootstrapInterface::bootstrap() 的参数**没有类型声明**，实现里加
 *     Application $app 会参数逆变违规、直接 fatal（返回类型协变是允许的，故 : void 安全）。
 *  3. Yii::$container 是全局静态，跨用例会累积定义 —— 测试之间必须换新容器。
 *
 * 由 phpunit.contract.xml 单独运行（真框架装不进 `php: ^8.0` 的主矩阵）。
 */
final class Yii2ContractTest extends TestCase
{
    /** 适配器必须注册的六个标识：3 个别名 + 3 个 FQCN。 */
    private const ALL_IDS = [
        HashidsManager::class,
        'hashids',
        HashidsFactory::class,
        'hashids.factory',
        HashidsClient::class,
        'hashids.connection',
    ];

    private const SALT = 'yii-contract-salt';

    private string $basePath;

    protected function setUp(): void
    {
        // Yii::$container 是全局静态。真应用里它只被创建一次，测试里必须每条用例换新，
        // 否则上一条的注册会泄漏到下一跳 —— 断言「注册了几个」就失去意义。
        Yii::$container = new Container();
        Yii::$app = null;

        $this->basePath = sys_get_temp_dir() . '/hashids-yii-contract-' . bin2hex(random_bytes(4));
        mkdir($this->basePath, 0755, true);
    }

    protected function tearDown(): void
    {
        // 构造 Application 时框架必然注册自己的 error/exception handler：
        // Application::__construct() → registerErrorHandler() → ErrorHandler::register()。
        // （它之所以不会走 "no errorHandler component is configured" 的 exit(1) 分支，
        //   是因为 preInit() 先把 coreComponents() 里的 errorHandler 合进了 $config。）
        //
        // 不撤销的话每条用例都被 PHPUnit 判为 risky —— "did not remove its own error
        // handlers"—— 而且上一条的 handler 会留在栈上影响下一条。真实应用里这个 handler
        // 活到进程结束，所以这里纯属测试收尾，不是产品行为。
        if (Yii::$app !== null && Yii::$app->has('errorHandler')) {
            Yii::$app->getErrorHandler()->unregister();
        }

        Yii::$app = null;
        Yii::$container = new Container();
        self::removeDir($this->basePath);
    }

    /**
     * 接口实现关系与签名兼容 —— 这条先立住，后面几条才谈得上是「真框架在调它」。
     */
    public function test_bootstrap_implements_the_real_interface(): void
    {
        self::assertContains(
            BootstrapInterface::class,
            class_implements(Bootstrap::class),
            '适配器未实现真 yii\base\BootstrapInterface'
        );

        // 真框架就是这样调它的：$app 无类型声明，实现必须照样能接住。
        $app = $this->makeApp(['hashids' => $this->config(self::SALT, 6)]);
        (new Bootstrap())->bootstrap($app);
        self::assertInstanceOf(HashidsManager::class, Yii::$container->get('hashids'));
    }

    /**
     * 容器端口到端到端：真 Application → Bootstrap::bootstrap() → 六个标识全部可解析，
     * 且别名与 FQCN 解析到同一实例。
     */
    public function test_bootstrap_registers_all_six_ids_sharing_one_manager(): void
    {
        $app = $this->makeApp(['hashids' => $this->config(self::SALT, 6)]);
        (new Bootstrap())->bootstrap($app);

        $container = Yii::$container;

        foreach (self::ALL_IDS as $id) {
            self::assertTrue($container->has($id), "$id 未注册进 Yii::\$container");
        }

        $manager = $container->get(HashidsManager::class);
        $factory = $container->get(HashidsFactory::class);
        $client  = $container->get(HashidsClient::class);

        self::assertInstanceOf(HashidsManager::class, $manager);
        self::assertInstanceOf(HashidsFactory::class, $factory);
        self::assertInstanceOf(HashidsClient::class, $client);

        // 别名与 FQCN 必须同一实例：否则「注入 'hashids'」与「注入 HashidsManager::class」
        // 会拿到两套连接池，配置改了只生效一半。
        self::assertSame($manager, $container->get('hashids'));
        self::assertSame($factory, $container->get('hashids.factory'));
        self::assertSame($client, $container->get('hashids.connection'));
        self::assertSame($client, $manager->connection());
        self::assertSame($factory, $manager->getFactory());

        $hash = $client->encode(12345);
        self::assertSame([12345], $client->decode($hash), 'encode/decode 未往返');

        // salt/length 确实取自 params['hashids'] —— 排除「随便造了个能往返的 Hashids」。
        self::assertSame($hash, (new HashidsClient(self::SALT, 6))->encode(12345));
        self::assertNotSame($hash, (new HashidsClient('other-salt', 6))->encode(12345));
        self::assertSame(6, strlen($hash));
    }

    /**
     * 配置来源是 params['hashids']；默认连接名同样来自那里（而不是恒等的 'main'）。
     */
    public function test_config_comes_from_app_params_and_honours_default(): void
    {
        $app = $this->makeApp([
            'hashids' => [
                'default'     => 'alternative',
                'connections' => [
                    'main'        => ['salt' => 'main-salt', 'length' => 6],
                    'alternative' => ['salt' => self::SALT, 'length' => 12],
                ],
            ],
        ]);

        (new Bootstrap())->bootstrap($app);

        $manager = Yii::$container->get(HashidsManager::class);
        self::assertSame('alternative', $manager->getDefaultConnection());

        // 光看连接名不够：哈希输出由 salt/length 决定，用它证明取的确实是 alternative 的配置。
        self::assertSame(
            (new HashidsClient(self::SALT, 12))->encode(42),
            Yii::$container->get(HashidsClient::class)->encode(42)
        );
    }

    /**
     * 默认连接是**惰性**解析的：配置有误时 bootstrap 不能把整个应用拖垮，
     * 要在真正取连接时才抛。与 Webman 侧同一约定。
     */
    public function test_bootstrap_registers_with_non_array_config_but_client_resolution_throws(): void
    {
        $app = $this->makeApp(['hashids' => 'not-an-array']);

        (new Bootstrap())->bootstrap($app);

        self::assertInstanceOf(HashidsManager::class, Yii::$container->get(HashidsManager::class));

        $this->expectException(\InvalidArgumentException::class);
        Yii::$container->get(HashidsClient::class);
    }

    /**
     * params 里没有 hashids 键时的真实降级行为：注册照旧，默认连接名回落 'main'。
     */
    public function test_missing_hashids_param_still_registers_and_falls_back_to_main(): void
    {
        $app = $this->makeApp([]);
        (new Bootstrap())->bootstrap($app);

        $manager = Yii::$container->get(HashidsManager::class);
        self::assertSame('main', $manager->getDefaultConnection());
    }

    /**
     * 真实注册路径：Bootstrap 由**框架自己**从应用的 bootstrap 数组里调起来。
     *
     * 上面几条都是手工 new Bootstrap() 再调 bootstrap() —— 那验证不了「框架认这个类名、
     * 且会在正确的时机调它」。这条走用户实际会写的那条路：配置里只写类名，应用构造完
     * 容器就该已经注册好了。
     */
    public function test_framework_itself_boots_the_adapter_from_the_bootstrap_array(): void
    {
        $app = new Application([
            'id'        => 'hashids-contract',
            'basePath'  => $this->basePath,
            'bootstrap' => [Bootstrap::class],
            'params'    => ['hashids' => $this->config(self::SALT, 6)],
        ]);

        // 什么都没手工调过，容器里已经有六个标识了 —— 是 Application::bootstrap() 调的。
        foreach (self::ALL_IDS as $id) {
            self::assertTrue(Yii::$container->has($id), "$id 未注册 —— 框架没调到适配器");
        }

        $client = Yii::$container->get(HashidsClient::class);
        self::assertSame(
            (new HashidsClient(self::SALT, 6))->encode(42),
            $client->encode(42),
            '配置应来自 params[\'hashids\']'
        );
    }

    /**
     * 组件端口：Yii::$app->hashids 走应用 components 配置，未声明的方法转给 manager。
     */
    public function test_app_component_resolves_and_forwards_to_manager(): void
    {
        $config = $this->config(self::SALT, 6);
        $app = $this->makeApp(
            ['hashids' => $config],
            ['hashids' => ['class' => Component::class, 'config' => $config]]
        );

        $component = $app->hashids;
        self::assertInstanceOf(Component::class, $component);

        // 未声明的方法经 __call 转给 HashidsManager，再 __call 到默认连接。
        $hash = $component->encode(123);
        self::assertSame(6, strlen($hash));
        self::assertSame($hash, (new HashidsClient(self::SALT, 6))->encode(123));
        self::assertSame([123], $component->decode($hash));

        // 属性走 Yii 的 getter 魔术（Component::__get → getManager()）。
        self::assertInstanceOf(HashidsManager::class, $component->manager);
        self::assertInstanceOf(HashidsFactory::class, $component->factory);

        // 具名连接：connection() 返回真实客户端，后续 encode 直接作用在它上面。
        self::assertSame($component->connection()->encode(9), $component->encode(9));
    }

    /**
     * 组件端口**不能**靠 Bootstrap 使能 —— 这正是 Component 类存在的理由。
     *
     * ServiceLocator::get() 只认自己的 _definitions，不认识 Yii::$container；容器里
     * 注册得再全，Yii::$app->hashids 依旧解析不到。谁要是「顺手」把 Component 类删掉
     * 改成只依赖 Bootstrap，这条会红。
     */
    public function test_bootstrap_alone_does_not_make_the_app_component_resolvable(): void
    {
        $app = $this->makeApp(['hashids' => $this->config(self::SALT, 6)]);
        (new Bootstrap())->bootstrap($app);

        self::assertTrue(Yii::$container->has('hashids'), '前提：容器里确实注册过 hashids');
        self::assertFalse($app->has('hashids'), '应用组件本就不该被 Bootstrap 注册');
        self::assertNull($app->get('hashids', false), 'ServiceLocator 不回落容器，这里应取不到');
    }

    /**
     * __call 的转发必须给 behaviors 让路：yii\base\Component::__call 的职责就是分派给
     * 行为。若本类无条件转发到 manager，attachBehavior 挂上的方法会被静默劫持
     * （manager 上没这个方法，报一个看不懂的错）。
     */
    public function test_component_forwards_unknown_methods_but_still_dispatches_behaviors(): void
    {
        $config = $this->config(self::SALT, 6);
        $app = $this->makeApp(
            ['hashids' => $config],
            ['hashids' => ['class' => Component::class, 'config' => $config]]
        );

        $component = $app->hashids;
        $component->attachBehavior('probe', new class extends Behavior {
            public function shout(): string
            {
                return 'behavior-ran';
            }
        });

        self::assertSame('behavior-ran', $component->shout(), '行为被 __call 劫持到 manager 上了');
        // 行为挂上之后，普通转发照常工作。
        self::assertSame([5], $component->decode($component->encode(5)));
    }

    /**
     * 铺一个真实可启动的 Yii 2 应用。
     *
     * @param array<string, mixed> $params     params['hashids'] 的内容
     * @param array<string, mixed> $components 应用 components 配置
     */
    private function makeApp(array $params, array $components = []): Application
    {
        return new Application([
            'id'         => 'hashids-contract',
            // basePath 必须真实存在：Application::preInit() → Module::setBasePath()
            // 会校验目录，缺了直接抛 InvalidArgumentException。
            'basePath'   => $this->basePath,
            'params'     => $params,
            'components' => $components,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function config(string $salt, int $length): array
    {
        return [
            'default'     => 'main',
            'connections' => ['main' => ['salt' => $salt, 'length' => $length]],
        ];
    }

    private static function removeDir(string $dir): void
    {
        if (is_file($dir) || is_link($dir)) {
            unlink($dir);

            return;
        }
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $file) {
            self::removeDir($dir . '/' . $file);
        }
        rmdir($dir);
    }
}
