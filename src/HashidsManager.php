<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace Erikwang2013\Hashids;

use Hashids\Hashids;
use InvalidArgumentException;

final class HashidsManager
{
    /** @var array<string, mixed> */
    private array $config;

    private HashidsFactory $factory;

    /** @var array<string, Hashids> */
    private array $connections = [];

    /**
     * @param mixed $config Expected shape: array{default?: string, connections?: array<string, array<string, mixed>>}
     */
    public function __construct(mixed $config, HashidsFactory $factory)
    {
        $this->config = is_array($config) ? $config : [];
        $this->factory = $factory;
    }

    public function getFactory(): HashidsFactory
    {
        return $this->factory;
    }

    /**
     * Resolve a named connection or the default.
     */
    public function connection(?string $name = null): Hashids
    {
        $name = $name ?? $this->getDefaultConnection();

        if ($name === '') {
            throw new InvalidArgumentException('Hashids connection name cannot be empty.');
        }

        if (!isset($this->connections[$name])) {
            $connections = $this->config['connections'] ?? [];
            if (!isset($connections[$name]) || !is_array($connections[$name])) {
                throw new InvalidArgumentException(sprintf('Hashids connection [%s] is not configured.', $name));
            }

            $this->connections[$name] = $this->factory->make($connections[$name]);
        }

        return $this->connections[$name];
    }

    /**
     * Returns the configured default connection name, falling back to 'main'
     * when the value is missing, empty, or non-string. The silent fallback
     * matches vinkla/hashids API contract — callers should validate config
     * at deploy time rather than rely on runtime warnings here.
     */
    public function getDefaultConnection(): string
    {
        $default = $this->config['default'] ?? 'main';

        if (!is_string($default) || $default === '') {
            return 'main';
        }

        return $default;
    }

    /**
     * 切换默认连接名。
     *
     * 对齐 vinkla/hashids（其 HashidsManager 继承的 AbstractManager 暴露此方法）。
     * 只改后续 connection() 的解析目标，**不校验该连接是否已配置** —— 与 Laravel
     * 的 Manager 一致，配置错误留到真正取用时暴露。
     */
    public function setDefaultConnection(string $name): static
    {
        $this->config['default'] = $name;

        return $this;
    }

    /**
     * Dynamically pass methods onto the default connection.
     *
     * @param array<int, mixed> $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->connection()->{$method}(...$parameters);
    }
}
