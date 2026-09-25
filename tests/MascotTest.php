<?php

declare(strict_types=1);

namespace Erikwang2013\Hashids\Tests;

use Erikwang2013\Hashids\Mascot;
use PHPUnit\Framework\TestCase;

final class MascotTest extends TestCase
{
    public function test_art_draws_the_character(): void
    {
        $art = Mascot::art();

        self::assertStringContainsString('●', $art, '天线顶端');
        self::assertStringContainsString('◉', $art, '眼睛');
        self::assertStringContainsString('#', $art, '胸口徽记');
    }

    /**
     * The art is column-aligned by hand; a stray space breaks the silhouette.
     */
    public function test_art_keeps_the_silhouette_aligned(): void
    {
        $lines = explode("\n", Mascot::art());

        foreach ($lines as $i => $line) {
            self::assertSame(rtrim($line), $line, "第 {$i} 行有行尾空白");
        }

        // 头顶天线、嘴巴、胸口 # 共用同一条中轴。
        $bodyStart = mb_strpos($lines[2], '╭');
        $axis = (int) $bodyStart + 5;

        self::assertSame($axis, mb_strpos($lines[0], '●'));
        self::assertSame($axis, mb_strpos($lines[4], '‿'));
        self::assertSame($axis, mb_strpos($lines[5], '#'));
    }

    public function test_greet_wraps_the_art_with_the_name(): void
    {
        $greet = Mascot::greet();

        self::assertStringContainsString(Mascot::art(), $greet);
        self::assertStringContainsString(Mascot::NAME, $greet);
        self::assertStringEndsWith("\n", $greet, 'echo 后不应与下一行粘连');
    }
}
