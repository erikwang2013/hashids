<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

namespace Erikwang2013\Hashids;

/**
 * 项目宠物「哈希迪 Hashy」。
 *
 * 图形本体见 docs/mascot.svg；终端里没有 SVG，因此这里保留一份等价的 ASCII 版本：
 *
 * ```php
 * echo Mascot::greet();
 * ```
 *
 * 安装 Webman 插件时会自动打印一次问候。
 */
final class Mascot
{
    public const NAME = '哈希迪 Hashy';

    public const TAGLINE = '把数据库自增 ID 换成短小、不可猜测的字符串';

    /**
     * 头顶天线、胸口带 # 的圆角方块小宠物。
     */
    public static function art(): string
    {
        return <<<'ART'
                   ●
                   │
              ╭─────────╮
              │ ◉     ◉ │
              │    ‿    │
              │    #    │
              ╰──┬───┬──╯
                 ╵   ╵
        ART;
    }

    /**
     * 可直接 echo / 写日志的完整问候，末尾带换行。
     */
    public static function greet(): string
    {
        return self::art() . "\n" . self::NAME . ' · ' . self::TAGLINE . "\n";
    }
}
