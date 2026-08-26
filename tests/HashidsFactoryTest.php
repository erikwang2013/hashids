<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace Erikwang2013\Hashids\Tests;

use Erikwang2013\Hashids\HashidsFactory;
use PHPUnit\Framework\TestCase;

final class HashidsFactoryTest extends TestCase
{
    public function test_make_with_minimal_config(): void
    {
        $factory = new HashidsFactory();
        $hashids = $factory->make(['salt' => 'test', 'length' => 8]);

        $hash = $hashids->encode(1, 2, 3);
        self::assertSame([1, 2, 3], $hashids->decode($hash));
    }

    public function test_make_with_custom_alphabet(): void
    {
        $factory = new HashidsFactory();
        $hashids = $factory->make([
            'salt' => 'test',
            'length' => 8,
            'alphabet' => 'abcdefghijklmnopqrstuvwxyz',
        ]);

        $hash = $hashids->encode(42);
        self::assertMatchesRegularExpression('/^[a-z]+$/', $hash);
    }

    public function test_make_without_alphabet_key(): void
    {
        $factory = new HashidsFactory();
        $hashids = $factory->make(['salt' => 'x']);

        $hash = $hashids->encode(1);
        self::assertIsString($hash);
    }

    public function test_make_with_empty_alphabet_uses_default(): void
    {
        $factory = new HashidsFactory();
        $hashids = $factory->make([
            'salt' => 'test',
            'length' => 0,
            'alphabet' => '',
        ]);

        $hash = $hashids->encode(123);
        self::assertIsString($hash);
        self::assertNotEmpty($hash);
    }

    public function test_make_with_empty_config(): void
    {
        $factory = new HashidsFactory();
        $hashids = $factory->make([]);

        $hash = $hashids->encode(1);
        self::assertIsString($hash);
        self::assertNotEmpty($hash);
    }

    public function test_make_with_numeric_string_length_is_cast_to_int(): void
    {
        $factory = new HashidsFactory();
        $hashids = $factory->make(['salt' => 's', 'length' => '12']);

        $hash = $hashids->encode(7);
        self::assertGreaterThanOrEqual(12, strlen($hash));
    }

    public function test_make_with_negative_length_does_not_throw(): void
    {
        $factory = new HashidsFactory();
        $hashids = $factory->make(['salt' => 's', 'length' => -5]);

        self::assertIsString($hashids->encode(1));
    }

    public function test_make_with_duplicate_alphabet_chars_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new HashidsFactory())->make(['salt' => 's', 'length' => 8, 'alphabet' => 'aabc']);
    }

    public function test_make_with_too_short_alphabet_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new HashidsFactory())->make(['salt' => 's', 'length' => 8, 'alphabet' => 'ab']);
    }

    public function test_make_with_numeric_alphabet_value_is_cast_to_string_and_validated(): void
    {
        // (string) 123456789 = '123456789'：唯一字符不足 16，hashids 校验抛异常
        $this->expectException(\InvalidArgumentException::class);

        (new HashidsFactory())->make(['salt' => 's', 'length' => 0, 'alphabet' => 123456789]);
    }

    public function test_same_config_produces_deterministic_hashes(): void
    {
        $factory = new HashidsFactory();
        $config = ['salt' => 'same-salt', 'length' => 8];

        self::assertSame(
            $factory->make($config)->encode(1, 2, 3),
            $factory->make($config)->encode(1, 2, 3)
        );
    }

    public function test_different_salt_produces_different_hashes(): void
    {
        $factory = new HashidsFactory();

        $hashA = $factory->make(['salt' => 'salt-a', 'length' => 8])->encode(42);
        $hashB = $factory->make(['salt' => 'salt-b', 'length' => 8])->encode(42);

        self::assertNotSame($hashA, $hashB);
    }

    public function test_encode_decode_hex_roundtrip(): void
    {
        $factory = new HashidsFactory();
        $hashids = $factory->make(['salt' => 'hex', 'length' => 0]);

        $hash = $hashids->encodeHex('deadbeef');

        self::assertIsString($hash);
        self::assertSame('deadbeef', $hashids->decodeHex($hash));
    }

    public function test_decode_handles_empty_and_invalid_input(): void
    {
        $factory = new HashidsFactory();
        $hashids = $factory->make(['salt' => 's', 'length' => 0]);

        self::assertSame([], $hashids->decode(''));
        self::assertSame([], $hashids->decode('!!!!'));
        self::assertSame('', $hashids->decodeHex(''));
    }

    public function test_encode_zero_and_extreme_integers(): void
    {
        $factory = new HashidsFactory();
        $hashids = $factory->make(['salt' => 's', 'length' => 0]);

        self::assertSame([0], $hashids->decode($hashids->encode(0)));
        self::assertSame([PHP_INT_MAX], $hashids->decode($hashids->encode(PHP_INT_MAX)));
    }
}
