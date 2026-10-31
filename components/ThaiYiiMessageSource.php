<?php

namespace app\components;

use yii\i18n\PhpMessageSource;

/**
 * แปลข้อความ error มาตรฐานของ Yii ("{attribute} cannot be blank." ฯลฯ — ทั้งฝั่ง server และข้อความที่ Yii
 * สร้างให้ JS ฝั่ง client) เป็นภาษาไทยโดยใช้ไฟล์แปลที่มากับเฟรมเวิร์ก (@yii/messages/th) — ตั้ง
 * Yii::$app->language เป็น 'th' ไม่ได้เพราะจะกระทบ Formatter/locale อื่น (ปฏิทินพุทธศักราชของ intl)
 * จึงบังคับให้ category 'yii' ตัวเดียวโหลดภาษาไทยเสมอ ไม่ว่า language ของแอปจะเป็นอะไร
 */
class ThaiYiiMessageSource extends PhpMessageSource
{
    protected function loadMessages($category, $language)
    {
        $messages = parent::loadMessages($category, 'th');

        // ไฟล์แปลของ Yii ต่อ {attribute} ติดกับข้อความไทยเลย ("{attribute}ต้องไม่ว่างเปล่า") — เติมช่องว่างคั่น
        // เพราะชื่อช่องของเราเป็นประโยคยาว (เช่น "ข้อ 5 สรุปผลการดำเนินงาน") อ่านติดกันแล้วเข้าใจยาก
        foreach ($messages as $source => $translation) {
            if (is_string($translation) && strpos($translation, '{attribute}') === 0 && strpos($translation, '{attribute} ') !== 0) {
                $messages[$source] = '{attribute} ' . substr($translation, strlen('{attribute}'));
            }
        }

        return $messages;
    }
}
