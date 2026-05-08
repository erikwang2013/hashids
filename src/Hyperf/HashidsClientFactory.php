<?php

declare(strict_types=1);

namespace Erikwang2013\Hashids\Hyperf;

use Erikwang2013\Hashids\HashidsManager;
use Hashids\Hashids as HashidsClient;
use Psr\Container\ContainerInterface;

final class HashidsClientFactory
{
    public function __invoke(ContainerInterface $container): HashidsClient
    {
        return $container->get(HashidsManager::class)->connection();
    }
}
