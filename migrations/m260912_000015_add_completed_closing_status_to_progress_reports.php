<?php

use yii\db\Migration;

/**
 * ลูกค้าขอเพิ่มตัวเลือกสถานะ "ดำเนินการเสร็จสิ้นและขอแจ้งปิดโครงการ" (completed_closing) ในข้อ 2.1
 * แยกจาก "completed" เดิม เพราะสื่อความหมายว่าผู้ใช้ต้องการแจ้งปิดโครงการไปพร้อมกันด้วย — status เป็น
 * ENUM ที่ระดับ DB (ดู m260728_000002_create_progress_reports_table) ต้อง MODIFY เพิ่มค่าใหม่เข้าไป
 * ก่อน ไม่งั้น insert/update ด้วยค่านี้จะพังที่ชั้น DB แม้ผ่าน validation ฝั่ง PHP แล้วก็ตาม
 */
class m260912_000015_add_completed_closing_status_to_progress_reports extends Migration
{
    public function safeUp()
    {
        $this->alterColumn(
            '{{%progress_reports}}',
            'status',
            "ENUM('not_started','in_progress','completed','completed_closing','terminated_early','cancelled') NOT NULL"
        );
    }

    public function safeDown()
    {
        $this->alterColumn(
            '{{%progress_reports}}',
            'status',
            "ENUM('not_started','in_progress','completed','terminated_early','cancelled') NOT NULL"
        );
    }
}
