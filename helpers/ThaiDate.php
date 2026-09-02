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

    /**
     * แปลงวันที่ ISO (เก็บใน DB, ค.ศ.) เป็นข้อความ วว/ดด/ปปปป แบบ พ.ศ. สำหรับใส่ใน input ที่ผู้ใช้
     * พิมพ์วันที่แบบไทยได้ตรงๆ (ดู parseThaiInput() คู่กัน — ตัวนี้แปลง DB → หน้าจอ, อีกตัวแปลงกลับ
     * หน้าจอ → DB) คืนค่าว่างถ้า $isoDate ว่าง/parse ไม่ได้ (ให้ input ว่างไว้แทนที่จะโชว์ค่าพัง)
     */
    public static function toThaiInput(?string $isoDate): string
    {
        if (empty($isoDate)) {
            return '';
        }

        $timestamp = strtotime($isoDate);
        if ($timestamp === false) {
            return '';
        }

        return sprintf('%02d/%02d/%d', (int) date('d', $timestamp), (int) date('m', $timestamp), (int) date('Y', $timestamp) + 543);
    }

    /**
     * แปลงข้อความที่ผู้ใช้พิมพ์แบบไทย (วว/ดด/ปปปป พ.ศ. เช่น "01/12/2569") กลับเป็น ISO (yyyy-mm-dd,
     * ค.ศ.) สำหรับเก็บ DB — ยอมรับ ISO ตรงๆ ด้วยเผื่อกรณีค่าที่ส่งมาไม่ได้ผ่าน input นี้จริง (เช่น
     * ทดสอบ/เรียก API ตรง) คืนค่า:
     *   - null  = ค่าว่าง (ไม่ได้กรอก) ให้ผู้เรียกจัดการเป็นค่าว่างต่อไปตามปกติ
     *   - false = กรอกมาแต่รูปแบบ/วันที่ไม่ถูกต้อง (ให้ผู้เรียก addError)
     *   - string = แปลงสำเร็จ เป็น ISO yyyy-mm-dd พร้อมเก็บ DB
     */
    public static function parseThaiInput(?string $input)
    {
        $input = trim((string) $input);
        if ($input === '') {
            return null;
        }

        // ISO อยู่แล้ว (yyyy-mm-dd) — ผ่านตรงๆ ไม่ต้องแปลง เผื่อ path ที่ไม่ได้มาจาก input นี้
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
            [$y, $m, $d] = array_map('intval', explode('-', $input));
            return checkdate($m, $d, $y) ? $input : false;
        }

        if (!preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $input, $matches)) {
            return false;
        }

        [, $day, $month, $yearBuddhist] = $matches;
        $day = (int) $day;
        $month = (int) $month;
        $yearGregorian = (int) $yearBuddhist - 543;

        if (!checkdate($month, $day, $yearGregorian)) {
            return false;
        }

        return sprintf('%04d-%02d-%02d', $yearGregorian, $month, $day);
    }
}
