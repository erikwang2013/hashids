<?php

declare(strict_types=1);

namespace Erikwang2013\Hashids\Hyperf;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

final class HashidsManagerFactory
{
    public function __invoke(ContainerInterface $container): HashidsManager
    {
        $config = $container->get(ConfigInterface::class)->get('hashids', []);

        return new HashidsManager(is_array($config) ? $config : [], new HashidsFactory());
    }
}
