<?php

use yii\db\Migration;

/**
 * ลูกค้าขอให้แนบไฟล์ PDF แยกรายข้อได้ในข้อ 6.1 (ผลงานตีพิมพ์) และ 6.2 (ทรัพย์สินทางปัญญา) — ไม่สร้าง
 * ตารางใหม่ ใช้ report_attachments เดิมที่มีอยู่แล้ว (โครงสร้าง/การดาวน์โหลดผ่าน
 * ReportController::actionDownloadAttachment() ใช้ร่วมกันได้เลย) เพิ่มแค่ FK สองคอลัมน์นี้ที่เป็น
 * nullable ทั้งคู่ — แถวที่ทั้งสองค่าเป็น NULL คือเอกสารแนบระดับรายงานทั้งฉบับแบบเดิม (ข้อ 7),
 * แถวที่ publication_id/ip_filing_id ไม่ใช่ NULL คือไฟล์แนบของรายการนั้นๆ โดยเฉพาะ (ผูกกับแถวเดียว
 * เท่านั้น จึงตั้ง UNIQUE ไว้กันแนบไฟล์ซ้ำมากกว่า 1 ไฟล์ต่อ 1 รายการ — ใช้ REPLACE ไฟล์แทนได้)
 */
class m260902_000014_add_item_link_to_report_attachments extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%report_attachments}}', 'publication_id', $this->integer()->null()->after('report_id'));
        $this->addColumn('{{%report_attachments}}', 'ip_filing_id', $this->integer()->null()->after('publication_id'));

        $this->createIndex('idx-report_attachments-publication_id', '{{%report_attachments}}', 'publication_id', true);
        $this->createIndex('idx-report_attachments-ip_filing_id', '{{%report_attachments}}', 'ip_filing_id', true);

        $this->addForeignKey(
            'fk-report_attachments-publication_id',
            '{{%report_attachments}}',
            'publication_id',
            '{{%report_publications}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk-report_attachments-ip_filing_id',
            '{{%report_attachments}}',
            'ip_filing_id',
            '{{%report_ip_filings}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-report_attachments-publication_id', '{{%report_attachments}}');
        $this->dropForeignKey('fk-report_attachments-ip_filing_id', '{{%report_attachments}}');
        $this->dropColumn('{{%report_attachments}}', 'publication_id');
        $this->dropColumn('{{%report_attachments}}', 'ip_filing_id');
    }
}
