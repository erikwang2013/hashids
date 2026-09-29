<?php

declare(strict_types=1);

/**
 * Stubs for framework symbols that are not installed (webman, ThinkPHP, Yii 2/3) or
 * not provided by the installed illuminate split packages (base_path/config
 * helpers live in the full laravel/framework, not in illuminate/support).
 *
 * Every stub is guarded so that a real framework installation wins; tests
 * that depend on the stubs skip when a real framework is present
 * (see StubState::$globalStubs / StubState::$thinkStubbed / StubState::$yii2Stubbed).
 */

namespace Erikwang2013\Hashids\Tests\Support {
    /**
     * PHP 8.1+ makes non-public reflection access unconditional; 8.0 needs setAccessible().
     * PHP 8.5 deprecates setAccessible() — guard keeps tests clean on every supported version.
     */
    function reflection_accessible(\ReflectionProperty|\ReflectionMethod $reflection): \ReflectionProperty|\ReflectionMethod
    {
        if (\PHP_VERSION_ID < 80100) {
            $reflection->setAccessible(true);
        }

        return $reflection;
    }

    final class StubState
    {
        public static bool $globalStubs = false;

        public static bool $thinkStubbed = false;

        public static bool $webmanStubbed = false;

        public static bool $yii2Stubbed = false;

        public static bool $yii3Stubbed = false;

        /** @var bool|null 真 Yii 2 框架是否在场；null = 还没判过 */
        private static ?bool $yii2Real = null;

        /** @var bool|null 真 Yii 3 框架是否在场；null = 还没判过 */
        private static ?bool $yii3Real = null;

        /** @var array<string, mixed> */
        public static array $config = [];

        /** @var array<int, array{0: string, 1: string}> */
        public static array $copied = [];

        /** @var array<int, string> */
        public static array $removed = [];

        public static string $basePath = '';

        /**
         * 真 Yii 2 框架是否在场。
         *
         * 判据**不能**用 class_exists('Yii')：全局 Yii 类不在 composer autoload 里
         * （yiisoft/yii2 只声明 psr-4 "yii\\"，没有 files/classmap），装了真框架它照样是
         * false —— 那样就会「真 yii\base\Component + 假 Yii 类」混着用，两边语义都不对。
         * 改用 psr-4 可自动加载的 yii\base\Component 作为整组装/不装的判据。
         */
        public static function yii2IsReal(): bool
        {
            return self::$yii2Real ??= class_exists(\yii\base\Component::class);
        }

        /**
         * 真 Yii 3 框架是否在场。Yii 3 的类都在 composer autoload 里，直接判断即可。
         */
        public static function yii3IsReal(): bool
        {
            return self::$yii3Real ??= interface_exists(\Yiisoft\Di\ServiceProviderInterface::class);
        }

        public static function reset(): void
        {
            self::$config = [];
            self::$copied = [];
            self::$removed = [];
            self::$basePath = '';
        }
    }
}

namespace {
    if (
        !function_exists('base_path')
        && !function_exists('config')
        && !function_exists('config_path')
        && !function_exists('copy_dir')
        && !function_exists('remove_dir')
    ) {
        function base_path(?string $path = null): string
        {
            $base = \Erikwang2013\Hashids\Tests\Support\StubState::$basePath;

            return $path === null || $path === '' ? $base : $base . '/' . ltrim($path, '/');
        }

        function config(?string $key = null, mixed $default = null): mixed
        {
            $cfg = \Erikwang2013\Hashids\Tests\Support\StubState::$config;

            if ($key === null) {
                return $cfg;
            }

            return $cfg[$key] ?? $default;
        }

        function config_path(?string $path = null): string
        {
            $base = \Erikwang2013\Hashids\Tests\Support\StubState::$basePath . '/config';

            return $path === null || $path === '' ? $base : $base . '/' . ltrim($path, '/');
        }

        function copy_dir(string $source, string $dest): void
        {
            \Erikwang2013\Hashids\Tests\Support\StubState::$copied[] = [$source, $dest];
        }

        function remove_dir(string $dir): void
        {
            \Erikwang2013\Hashids\Tests\Support\StubState::$removed[] = $dir;
        }

        \Erikwang2013\Hashids\Tests\Support\StubState::$globalStubs = true;
    }
}

namespace Workerman {
    if (!class_exists(Worker::class)) {
        class Worker
        {
        }
    }
}

namespace Webman {
    if (!interface_exists(Bootstrap::class)) {
        interface Bootstrap
        {
            public static function start(?\Workerman\Worker $worker): void;
        }

        \Erikwang2013\Hashids\Tests\Support\StubState::$webmanStubbed = true;
    }
}

namespace support {
    if (!class_exists(Container::class)) {
        class Container
        {
            private static ?self $instance = null;

            /** @var array<string, callable> */
            public array $definitions = [];

            public static function instance(): self
            {
                return self::$instance ??= new self();
            }

            public static function reset(): void
            {
                self::$instance = null;
            }

            public function addDefinitions(array $definitions): void
            {
                $this->definitions = $definitions;
            }
        }
    }
}

namespace think {
    if (!class_exists(Service::class)) {
        class Service
        {
            protected $app;

            public function __construct($app)
            {
                $this->app = $app;
            }
        }

        \Erikwang2013\Hashids\Tests\Support\StubState::$thinkStubbed = true;
    }
}

/*
 * Yii 2 替身。整组一起装或不装：真框架在场（yii\base\Component 可自动加载）时一个都不定义，
 * StubState::$yii2Stubbed 保持 false，替身测试自行跳过。
 *
 * 这些替身是**故意最小**的：只覆盖 src/Yii2 用到的语义。真语义由
 * tests/Contract/Yii2ContractTest.php 在真框架上钉死 —— 替身按错误假设写、测试照样绿，
 * 是本包已经吃过一次的亏。
 */
namespace yii\base {
    if (!\Erikwang2013\Hashids\Tests\Support\StubState::yii2IsReal()) {
        interface BootstrapInterface
        {
            /**
             * 真接口的参数**没有类型声明** —— 实现里写 Application $app 就是参数逆变违规，
             * PHP 直接 fatal。替身照抄这一点，否则替身测试会放行一个真框架上跑不起来的签名。
             */
            public function bootstrap($app);
        }

        class BaseObject
        {
            /**
             * @param array<string, mixed> $config
             */
            public function __construct($config = [])
            {
                foreach ($config as $name => $value) {
                    $this->$name = $value;
                }
            }

            public function __get($name)
            {
                $getter = 'get' . $name;
                if (method_exists($this, $getter)) {
                    return $this->$getter();
                }

                throw new \RuntimeException('Unknown property: ' . static::class . "::$name");
            }

            public function __set($name, $value)
            {
                $setter = 'set' . $name;
                if (method_exists($this, $setter)) {
                    $this->$setter($value);

                    return;
                }

                // 真 BaseObject::__set 对既无 setter 又无对应 getter 的属性抛异常，
                // 不创建动态属性（PHP 8.2 起那样会弃用告警）。
                throw new \RuntimeException('Unknown property: ' . static::class . "::$name");
            }
        }

        class Component extends BaseObject
        {
            /** @var array<string, object> */
            private array $behaviors = [];

            public function attachBehavior(string $name, object $behavior): void
            {
                $this->behaviors[$name] = $behavior;
            }

            public function hasMethod($name, $checkBehaviors = true)
            {
                if (method_exists($this, $name)) {
                    return true;
                }
                if ($checkBehaviors) {
                    foreach ($this->behaviors as $behavior) {
                        if (method_exists($behavior, $name)) {
                            return true;
                        }
                    }
                }

                return false;
            }

            public function __call($name, $params)
            {
                foreach ($this->behaviors as $behavior) {
                    if (method_exists($behavior, $name)) {
                        return $behavior->$name(...$params);
                    }
                }

                throw new \BadMethodCallException('Calling unknown method: ' . static::class . "::$name()");
            }
        }

        class Behavior extends BaseObject
        {
            public function hasMethod($name)
            {
                return method_exists($this, $name);
            }
        }
    }
}

namespace yii\di {
    if (!\Erikwang2013\Hashids\Tests\Support\StubState::yii2IsReal()) {
        /**
         * 最小 yii\di\Container 替身，保留本包依赖的语义：
         *   - 对象定义原样返回并当单例存下；
         *   - 可调用定义按真框架的调用签名 ($container, $params, $config) 调用；
         *   - 只有 setSingleton() 登记过的才缓存实例，set() 每次新建。
         */
        class Container
        {
            /** @var array<string, mixed> */
            private array $definitions = [];

            /** @var array<string, mixed> */
            private array $instances = [];

            /** @var array<string, bool> */
            private array $singletonIds = [];

            /**
             * @param mixed               $definition
             * @param array<int, mixed>   $params
             */
            public function set($class, $definition = [], array $params = []): static
            {
                $this->definitions[$class] = $definition;
                unset($this->instances[$class], $this->singletonIds[$class]);

                return $this;
            }

            /**
             * @param mixed               $definition
             * @param array<int, mixed>   $params
             */
            public function setSingleton($class, $definition = [], array $params = []): static
            {
                $this->set($class, $definition, $params);
                $this->singletonIds[$class] = true;

                return $this;
            }

            public function has($class): bool
            {
                return isset($this->definitions[$class]);
            }

            /**
             * @param array<int, mixed>    $params
             * @param array<string, mixed> $config
             */
            public function get($class, $params = [], $config = [])
            {
                if (isset($this->instances[$class])) {
                    return $this->instances[$class];
                }

                if (!isset($this->definitions[$class])) {
                    throw new \RuntimeException(sprintf('Unknown container id: %s', $class));
                }

                $definition = $this->definitions[$class];

                // 分支顺序与真 yii\di\Container::get() 一致：先问 is_callable，再看 is_object。
                if (is_callable($definition, true)) {
                    $object = $definition($this, $params, $config);
                } elseif (is_object($definition)) {
                    // 真框架在这个分支无条件把它当单例存下来 —— 不看是 set 还是 setSingleton。
                    return $this->instances[$class] = $definition;
                } else {
                    throw new \RuntimeException(
                        sprintf('Unsupported definition type: %s', get_debug_type($definition))
                    );
                }

                if (isset($this->singletonIds[$class])) {
                    $this->instances[$class] = $object;
                }

                return $object;
            }
        }
    }
}

namespace {
    if (!\Erikwang2013\Hashids\Tests\Support\StubState::yii2IsReal()) {
        class Yii
        {
            /** @var \yii\di\Container|null */
            public static $container;

            /** @var object|null */
            public static $app;
        }

        \Erikwang2013\Hashids\Tests\Support\StubState::$yii2Stubbed = true;
    }
}

/*
 * Yii 3 替身。判据是真框架符号 Yiisoft\Di\ServiceProviderInterface 可自动加载
 * （与 Yii 2 不同：Yii 3 的类都走 composer autoload，没有 Yii 2 那种「全局类不在
 * autoload 里」的坑）。整组一起装或不装。
 *
 * 替身只覆盖 src/Yii3 用到的语义；真语义由 tests/Contract/Yii3ContractTest.php
 * 在真 yiisoft/di 上钉死。
 */
namespace Yiisoft\Di {
    if (
        !\Erikwang2013\Hashids\Tests\Support\StubState::yii3IsReal()
        && interface_exists(\Psr\Container\ContainerInterface::class)
    ) {
        interface ServiceProviderInterface
        {
            /**
             * @return array<string, mixed> 服务定义，键是服务名（通常是接口名）
             */
            public function getDefinitions(): array;

            /**
             * @return array<string, callable> 服务扩展
             */
            public function getExtensions(): array;
        }

        /**
         * 最小 Yiisoft\Di\Container 替身，保留本包依赖的语义：
         *   - providers 的 getDefinitions() 被并进定义表；
         *   - get() 返回**并缓存**同一实例（真容器就是单例语义，见其 get() 文档）；
         *   - 定义可以是可调用、类名字符串或现成对象。
         *
         * 与真容器的差异：真 Container::__construct() 收的是 ContainerConfigInterface
         * （ContainerConfig::create()->withProviders([...]) 那种装配方式）。这层差异由
         * 契约测试在真框架上覆盖，替身不假装能验它。
         */
        class Container implements \Psr\Container\ContainerInterface
        {
            /** @var array<string, mixed> */
            private array $definitions = [];

            /** @var array<string, mixed> */
            private array $instances = [];

            /**
             * @param array<string, mixed>                $definitions
             * @param array<int, ServiceProviderInterface> $providers
             */
            public function __construct(array $definitions = [], array $providers = [])
            {
                // 顺序与真容器一致：先 addDefinitions()，再 addProviders()，
                // 所以 provider 里的定义**后写、覆盖**同名的普通定义。
                foreach ($definitions as $id => $definition) {
                    $this->definitions[$id] = $definition;
                }
                foreach ($providers as $provider) {
                    foreach ($provider->getDefinitions() as $id => $definition) {
                        $this->definitions[$id] = $definition;
                    }
                }
            }

            public function has(string $id): bool
            {
                return isset($this->definitions[$id]);
            }

            public function get(string $id): mixed
            {
                if (array_key_exists($id, $this->instances)) {
                    return $this->instances[$id];
                }

                if (!isset($this->definitions[$id])) {
                    throw new \RuntimeException(sprintf('No definition found for [%s]', $id));
                }

                $definition = $this->definitions[$id];

                if (is_string($definition)) {
                    $object = new $definition();
                } elseif (is_callable($definition)) {
                    $object = $definition($this);
                } else {
                    $object = $definition;
                }

                return $this->instances[$id] = $object;
            }
        }

        \Erikwang2013\Hashids\Tests\Support\StubState::$yii3Stubbed = true;
    }
}
