<?php

declare(strict_types=1);

namespace Erikwang2013\Hashids\Tests;

use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class HashidsManagerTest extends TestCase
{
    public function test_encodes_and_decodes_with_default_connection(): void
    {
        $manager = new HashidsManager([
            'default' => 'main',
            'connections' => [
                'main' => ['salt' => 'unit-test', 'length' => 8],
            ],
        ], new HashidsFactory());

        $hash = $manager->encode(1, 2, 3);
        self::assertSame([1, 2, 3], $manager->decode($hash));
    }

    public function test_named_connection(): void
    {
        $manager = new HashidsManager([
            'default' => 'a',
            'connections' => [
                'a' => ['salt' => 'one', 'length' => 6],
                'b' => ['salt' => 'two', 'length' => 6],
            ],
        ], new HashidsFactory());

        $hashA = $manager->connection('a')->encode(42);
        $hashB = $manager->connection('b')->encode(42);
        self::assertNotSame($hashA, $hashB);
    }

    public function test_unknown_connection_throws(): void
    {
        $manager = new HashidsManager([
            'default' => 'main',
            'connections' => [
                'main' => ['salt' => 'x', 'length' => 4],
            ],
        ], new HashidsFactory());

        $this->expectException(InvalidArgumentException::class);
        $manager->connection('missing');
    }
}
