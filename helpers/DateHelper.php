<?php

namespace app\helpers;

/**
 * แปลง string วันที่จาก query param เป็น 'Y-m-d' ที่ถูกต้อง — ถ้าว่างหรือรูปแบบผิด (ผู้ใช้แก้ URL
 * เองมั่ว ๆ) ใช้ค่า default แทนเงียบ ๆ ไม่ error — ใช้ร่วมกันในทุกหน้าที่มีตัวกรองช่วงวันที่
 * (เดิมเป็น private method ซ้ำกันอยู่ที่ ReportController เท่านั้น ย้ายมาไว้ที่นี่ให้ใช้ร่วมกันได้)
 */
class DateHelper
{
    public static function parseOrDefault(?string $value, string $default): string
    {
        if (empty($value)) {
            return $default;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);
        $errors = \DateTime::getLastErrors();
        if (!$date || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))) {
            return $default;
        }

        return $date->format('Y-m-d');
    }
}
