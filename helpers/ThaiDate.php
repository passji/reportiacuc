<?php

namespace app\helpers;

use IntlDateFormatter;

/**
 * แปลงวันที่/เวลาเป็นรูปแบบไทย (ปี พ.ศ., ชื่อเดือนไทย) — ค่าที่เก็บใน DB เป็น UTC
 * (ทั้ง PHP และ MariaDB ใน container นี้ตั้งเป็น UTC) จึงแปลงเป็นเวลาไทย (Asia/Bangkok,
 * UTC+7) ให้ในตัวด้วย ไม่ใช่แค่เปลี่ยนปีเป็น พ.ศ. เฉยๆ
 */
class ThaiDate
{
    public static function format(?string $datetime, bool $withTime = true): string
    {
        if (empty($datetime)) {
            return '-';
        }

        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return $datetime;
        }

        $formatter = new IntlDateFormatter(
            'th_TH',
            IntlDateFormatter::LONG,
            IntlDateFormatter::SHORT,
            'Asia/Bangkok',
            IntlDateFormatter::TRADITIONAL
        );
        $formatter->setPattern($withTime ? 'd MMMM yyyy เวลา HH:mm น.' : 'd MMMM yyyy');

        return $formatter->format($timestamp);
    }
}
