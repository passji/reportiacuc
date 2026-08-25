<?php

use yii\db\Migration;

class m260728_000004_create_report_ip_filings_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%report_ip_filings}}', [
            'id' => $this->primaryKey(),
            'report_id' => $this->integer()->notNull(),
            'ip_type' => "ENUM('patent','petty_patent','copyright')",
            'filed_date' => $this->date(),
            'registration_no' => $this->string(100),
            'asset_name' => $this->text(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx-report_ip_filings-report_id', '{{%report_ip_filings}}', 'report_id');

        $this->addForeignKey(
            'fk-report_ip_filings-report_id',
            '{{%report_ip_filings}}',
            'report_id',
            '{{%progress_reports}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-report_ip_filings-report_id', '{{%report_ip_filings}}');
        $this->dropTable('{{%report_ip_filings}}');
    }
}
