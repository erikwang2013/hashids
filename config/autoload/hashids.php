<?php

declare(strict_types=1);

/**
 * Hyperf: publish to config/autoload/hashids.php — ConfigInterface key "hashids".
 */

return [
    'hashids' => [
        'default' => 'main',

        'connections' => [
            'main' => [
                'salt' => '',
                'length' => 0,
            ],

            'alternative' => [
                'salt' => 'your-salt-string',
                'length' => 0,
            ],
        ],
    ],
];
