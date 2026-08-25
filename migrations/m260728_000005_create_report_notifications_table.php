<?php

use yii\db\Migration;

class m260728_000005_create_report_notifications_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%report_notifications}}', [
            'id' => $this->primaryKey(),
            'oid' => $this->string(20)->notNull(),
            'recipient_email' => $this->string(255)->notNull(),
            'trigger_type' => "ENUM('auto_monthly','manual_admin') NOT NULL",
            'triggered_by' => $this->string(255)->null(),
            'sent_status' => "ENUM('sent','failed') NOT NULL",
            'error_message' => $this->text()->null(),
            'sent_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx-report_notifications-oid', '{{%report_notifications}}', 'oid');

        $this->addForeignKey(
            'fk-report_notifications-oid',
            '{{%report_notifications}}',
            'oid',
            '{{%research_projects}}',
            'oid',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-report_notifications-oid', '{{%report_notifications}}');
        $this->dropTable('{{%report_notifications}}');
    }
}
