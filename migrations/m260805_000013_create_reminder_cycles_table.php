<?php

use yii\db\Migration;

/**
 * เพิ่มระบบ "รอบการส่งรายงาน" ที่แอดมินตั้งเองได้ — แต่ละรอบเป็นครั้งเดียว (ไม่ทำซ้ำอัตโนมัติ) มีชื่อ
 * รอบ, กำหนดส่งรายงาน (แสดงในอีเมล), และวันที่จะยิงอีเมลออกจริง (ให้ cron รายวัน
 * `reminder/send-scheduled` เช็คทุกวัน) ไปยังโครงการที่ยังดำเนินการอยู่ (นิยามเดียวกับ
 * ReminderService::pendingQuery()) — sent_at = null คือยังไม่ส่ง
 */
class m260805_000013_create_reminder_cycles_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%reminder_cycles}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'report_due_date' => $this->date()->notNull(),
            'notify_date' => $this->date()->notNull(),
            'sent_at' => $this->dateTime()->null(),
            'created_by' => $this->string(255)->null(),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->createIndex('idx-reminder_cycles-notify_date', '{{%reminder_cycles}}', 'notify_date');

        // ผูก report_notifications แต่ละแถวกลับไปยังรอบที่ยิงมัน (nullable เพราะ trigger_type อื่น
        // เช่น manual_admin/report_rejected ไม่มีรอบ) ไม่ใส่ FK จริงตามแบบ oid ในตารางเดียวกันที่ไม่ได้
        // ผูก FK เช่นกัน (loose coupling ตามที่ทำมาตลอดในระบบนี้)
        $this->alterColumn(
            '{{%report_notifications}}',
            'trigger_type',
            "ENUM('auto_monthly','manual_admin','manual_announcement','report_rejected','scheduled_cycle') NOT NULL"
        );
        $this->addColumn('{{%report_notifications}}', 'reminder_cycle_id', $this->integer()->null());
        $this->createIndex('idx-report_notifications-reminder_cycle_id', '{{%report_notifications}}', 'reminder_cycle_id');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-report_notifications-reminder_cycle_id', '{{%report_notifications}}');
        $this->dropColumn('{{%report_notifications}}', 'reminder_cycle_id');
        $this->alterColumn(
            '{{%report_notifications}}',
            'trigger_type',
            "ENUM('auto_monthly','manual_admin','manual_announcement','report_rejected') NOT NULL"
        );

        $this->dropTable('{{%reminder_cycles}}');
    }
}
