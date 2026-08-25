<?php

use yii\db\Migration;

/**
 * เอกสารแนบ (PDF) ประกอบรายงานความก้าวหน้า — ไม่ใช่ข้อในแบบฟอร์มทางการ เพิ่มตามคำขอ
 * ผู้ใช้เพื่อแนบเอกสารสนับสนุนได้ ไฟล์จริงเก็บนอก web root (@app/uploads/reports/{id}/)
 * ตารางนี้เก็บแค่ metadata
 */
class m260730_000009_create_report_attachments_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%report_attachments}}', [
            'id' => $this->primaryKey(),
            'report_id' => $this->integer()->notNull(),
            'original_filename' => $this->string(255)->notNull(),
            'stored_filename' => $this->string(255)->notNull(),
            'file_size' => $this->integer()->notNull(),
            'uploaded_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx-report_attachments-report_id', '{{%report_attachments}}', 'report_id');

        $this->addForeignKey(
            'fk-report_attachments-report_id',
            '{{%report_attachments}}',
            'report_id',
            '{{%progress_reports}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-report_attachments-report_id', '{{%report_attachments}}');
        $this->dropTable('{{%report_attachments}}');
    }
}
