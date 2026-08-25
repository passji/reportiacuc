<?php

use yii\db\Migration;

/**
 * เพิ่ม trigger_type 'manual_announcement' เพื่อให้ตารางเดียวกันนี้เก็บ log ทั้งการแจ้งเตือน
 * (README เดิม) และข่าวสารที่แอดมินส่งเอง (เพิ่มตามคำขอ) ไม่ต้องแยกตารางใหม่ + เพิ่ม subject
 * ไว้เก็บหัวข้อที่ส่งจริง (สำหรับ reminder หัวข้อคงที่อยู่แล้วไม่จำเป็นต้องใช้ แต่ประกาศไว้เผื่อ)
 */
class m260730_000010_extend_report_notifications_for_announcements extends Migration
{
    public function safeUp()
    {
        $this->alterColumn(
            '{{%report_notifications}}',
            'trigger_type',
            "ENUM('auto_monthly','manual_admin','manual_announcement') NOT NULL"
        );
        $this->addColumn('{{%report_notifications}}', 'subject', $this->string(255)->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%report_notifications}}', 'subject');
        $this->alterColumn(
            '{{%report_notifications}}',
            'trigger_type',
            "ENUM('auto_monthly','manual_admin') NOT NULL"
        );
    }
}
