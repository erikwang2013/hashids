<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace Erikwang2013\Hashids;

use Hashids\Hashids;

final class HashidsFactory
{
    /**
     * Build a Hashids instance from a connection config array.
     *
     * Alphabet validation (duplicate chars, min length, etc.) is delegated to
     * hashids/hashids which provides clear exception messages.
     *
     * @param array{salt?: string|mixed, length?: int|string|mixed, alphabet?: string|mixed} $config
     */
    public function make(array $config): Hashids
    {
        $salt = (string) ($config['salt'] ?? '');
        $length = (int) ($config['length'] ?? 0);

        if (isset($config['alphabet']) && $config['alphabet'] !== '') {
            return new Hashids($salt, $length, (string) $config['alphabet']);
        }

        return new Hashids($salt, $length);
    }
}
