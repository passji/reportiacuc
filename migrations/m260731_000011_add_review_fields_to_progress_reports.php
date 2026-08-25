<?php

use yii\db\Migration;

/**
 * เพิ่มสถานะการตรวจสอบของแอดมิน (แยกจาก status ซึ่งเป็นความคืบหน้าของโครงการ) — ทุกรายงาน
 * เริ่มต้นที่ 'pending' เสมอ แอดมินกดตรวจแล้ว/ปฏิเสธได้ที่ report/view — ปฏิเสธต้องระบุเหตุผล
 * (rejection_reason) ซึ่งจะถูกส่งอีเมลแจ้งผู้ส่งรายงานให้กรอกข้อมูลมาใหม่ จึงต้องเพิ่ม
 * trigger_type ใหม่ 'report_rejected' ใน report_notifications ด้วย (log เดียวกับการแจ้งเตือน/ข่าวสารอื่นๆ)
 */
class m260731_000011_add_review_fields_to_progress_reports extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%progress_reports}}',
            'review_status',
            "ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'"
        );
        $this->addColumn('{{%progress_reports}}', 'rejection_reason', $this->text()->null());
        $this->addColumn('{{%progress_reports}}', 'reviewed_at', $this->dateTime()->null());
        $this->addColumn('{{%progress_reports}}', 'reviewed_by', $this->string(255)->null());

        $this->alterColumn(
            '{{%report_notifications}}',
            'trigger_type',
            "ENUM('auto_monthly','manual_admin','manual_announcement','report_rejected') NOT NULL"
        );
    }

    public function safeDown()
    {
        $this->alterColumn(
            '{{%report_notifications}}',
            'trigger_type',
            "ENUM('auto_monthly','manual_admin','manual_announcement') NOT NULL"
        );

        $this->dropColumn('{{%progress_reports}}', 'reviewed_by');
        $this->dropColumn('{{%progress_reports}}', 'reviewed_at');
        $this->dropColumn('{{%progress_reports}}', 'rejection_reason');
        $this->dropColumn('{{%progress_reports}}', 'review_status');
    }
}
