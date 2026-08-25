<?php

use yii\db\Migration;

/**
 * ข้อ 3.1/3.2 เดิมเป็นข้อความอิสระ (animal_requested/animal_used TEXT) — แยกเป็นตัวเลข
 * ตัวผู้/ตัวเมียแบบมีโครงสร้าง เพื่อให้คำนวณ "จำนวนที่เหลือ" (และอื่นๆ ในอนาคต เช่น
 * น้ำหนักที่เหลือ) ได้ตรงไปตรงมา ส่วนบริบทอื่น (ชนิด/สายพันธุ์ ฯลฯ) เก็บเป็นหมายเหตุแยก
 */
class m260728_000006_split_progress_reports_animal_fields extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%progress_reports}}', 'animal_requested_male', $this->integer()->notNull()->defaultValue(0));
        $this->addColumn('{{%progress_reports}}', 'animal_requested_female', $this->integer()->notNull()->defaultValue(0));
        $this->addColumn('{{%progress_reports}}', 'animal_requested_note', $this->text()->null());
        $this->addColumn('{{%progress_reports}}', 'animal_used_male', $this->integer()->notNull()->defaultValue(0));
        $this->addColumn('{{%progress_reports}}', 'animal_used_female', $this->integer()->notNull()->defaultValue(0));
        $this->addColumn('{{%progress_reports}}', 'animal_used_note', $this->text()->null());

        $this->dropColumn('{{%progress_reports}}', 'animal_requested');
        $this->dropColumn('{{%progress_reports}}', 'animal_used');
    }

    public function safeDown()
    {
        $this->addColumn('{{%progress_reports}}', 'animal_requested', $this->text()->notNull());
        $this->addColumn('{{%progress_reports}}', 'animal_used', $this->text()->notNull());

        $this->dropColumn('{{%progress_reports}}', 'animal_requested_male');
        $this->dropColumn('{{%progress_reports}}', 'animal_requested_female');
        $this->dropColumn('{{%progress_reports}}', 'animal_requested_note');
        $this->dropColumn('{{%progress_reports}}', 'animal_used_male');
        $this->dropColumn('{{%progress_reports}}', 'animal_used_female');
        $this->dropColumn('{{%progress_reports}}', 'animal_used_note');
    }
}
