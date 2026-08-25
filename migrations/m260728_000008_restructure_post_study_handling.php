<?php

use yii\db\Migration;

/**
 * ข้อ 4.2 เดิมเป็นข้อความอิสระ — เปลี่ยนเป็นตัวเลือก 2 แบบ (ปฏิบัติตามที่รับรองไว้ / มีการ
 * เปลี่ยนแปลง) พร้อมช่องรายละเอียดที่โผล่เฉพาะเมื่อเลือก "มีการเปลี่ยนแปลง" เหมือนรูปแบบ
 * ข้อ 4.1/4.3/4.4/4.5 ที่มีอยู่แล้ว
 */
class m260728_000008_restructure_post_study_handling extends Migration
{
    public function safeUp()
    {
        $this->dropColumn('{{%progress_reports}}', 'post_study_handling');
        $this->addColumn(
            '{{%progress_reports}}',
            'post_study_handling',
            "ENUM('as_approved','changed') NOT NULL DEFAULT 'as_approved'"
        );
        $this->addColumn('{{%progress_reports}}', 'post_study_handling_detail', $this->text()->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%progress_reports}}', 'post_study_handling_detail');
        $this->dropColumn('{{%progress_reports}}', 'post_study_handling');
        $this->addColumn('{{%progress_reports}}', 'post_study_handling', $this->text()->null());
    }
}
