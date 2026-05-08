<?php

declare(strict_types=1);

namespace Erikwang2013\Hashids\Laravel\Facades;

use Erikwang2013\Hashids\HashidsManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Hashids\Hashids connection(string|null $name = null)
 * @method static string encode(mixed ...$numbers)
 * @method static array decode(string $hash)
 * @method static string encodeHex(string $str)
 * @method static string decodeHex(string $hash)
 *
 * @see HashidsManager
 */
final class Hashids extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HashidsManager::class;
    }
}
