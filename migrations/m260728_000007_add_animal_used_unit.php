<?php

use yii\db\Migration;

/**
 * ข้อ 3.2 (จำนวนสัตว์ที่ใช้จริง) ไม่ได้นับเป็น "ตัว" เสมอไป — บางการทดลองรายงานเป็นปริมาณ
 * ตัวอย่างชีวภาพ (เลือด/เนื้อเยื่อ) แทน จึงเพิ่ม unit ให้เลือกได้ ใช้ร่วมกันทั้งตัวผู้/ตัวเมีย
 */
class m260728_000007_add_animal_used_unit extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%progress_reports}}',
            'animal_used_unit',
            "ENUM('head','ml','l','kg') NOT NULL DEFAULT 'head'"
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%progress_reports}}', 'animal_used_unit');
    }
}
