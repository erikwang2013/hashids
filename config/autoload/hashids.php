<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

/**
 * Hyperf: publish to config/autoload/hashids.php
 *
 * Hyperf 的 ConfigFactory 按**文件名**归并，所以这个文件的内容整体挂在 `hashids` 键下：
 * `config('hashids')` 返回的就是下面这个数组本身 —— 必须**扁平**（根级 default +
 * connections），不要再套一层 `'hashids' =>`，否则 connections 不可达，
 * 取连接时会抛 "Hashids connection [main] is not configured"。
 */

return [
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

    /*
    |--------------------------------------------------------------------------
    | Security Warning
    |--------------------------------------------------------------------------
    |
    | Always set a unique, random salt per connection before deploying.
    | An empty or guessable salt makes your hashids trivially reversible.
    | Use env('HASHIDS_SALT') or an equally strong source per environment.
    |
    */
];
