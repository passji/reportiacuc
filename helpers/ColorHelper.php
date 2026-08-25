<?php

namespace app\helpers;

/**
 * ผสมสี hex เข้ากับสีขาวตามสัดส่วนที่กำหนด ให้ได้เฉดที่จางลง — ใช้กับพื้นหลัง segment ของ
 * views/dashboard/_status-bar.php (ตัวสีเข้มเดิมเก็บไว้ใช้กับ legend swatch/hover ต่างหาก)
 */
class ColorHelper
{
    public static function lighten(string $hex, float $amount): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = (int) round($r + (255 - $r) * $amount);
        $g = (int) round($g + (255 - $g) * $amount);
        $b = (int) round($b + (255 - $b) * $amount);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
