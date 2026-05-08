<?php

declare(strict_types=1);

namespace Erikwang2013\Hashids\Hyperf;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Hashids\Hashids as HashidsClient;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                HashidsFactory::class => HashidsFactory::class,
                HashidsManager::class => HashidsManagerFactory::class,
                HashidsClient::class => HashidsClientFactory::class,
            ],
            'publish' => [
                [
                    'id' => 'hashids',
                    'description' => 'The configuration file for erikwang2013/hashids.',
                    'source' => dirname(__DIR__, 2) . '/config/autoload/hashids.php',
                    'destination' => BASE_PATH . '/config/autoload/hashids.php',
                ],
            ],
        ];
    }
}
