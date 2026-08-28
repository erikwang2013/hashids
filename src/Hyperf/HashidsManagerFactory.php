<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace Erikwang2013\Hashids\Hyperf;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

final class HashidsManagerFactory
{
    public function __invoke(ContainerInterface $container): HashidsManager
    {
        return new HashidsManager($container->get(ConfigInterface::class)->get('hashids', []), new HashidsFactory());
    }
}
