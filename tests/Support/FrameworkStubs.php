<?php

declare(strict_types=1);

/**
 * Stubs for framework symbols that are not installed (webman, ThinkPHP) or
 * not provided by the installed illuminate split packages (base_path/config
 * helpers live in the full laravel/framework, not in illuminate/support).
 *
 * Every stub is guarded so that a real framework installation wins; tests
 * that depend on the stubs skip when a real framework is present
 * (see StubState::$globalStubs / StubState::$thinkStubbed).
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

        /** @var array<string, mixed> */
        public static array $config = [];

        /** @var array<int, array{0: string, 1: string}> */
        public static array $copied = [];

        /** @var array<int, string> */
        public static array $removed = [];

        public static string $basePath = '';

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
