<?php

use yii\db\Migration;

class m260728_000002_create_progress_reports_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%progress_reports}}', [
            'id' => $this->primaryKey(),
            'oid' => $this->string(20)->notNull(),
            'project_code' => $this->string(50)->notNull(),
            'meeting_ref' => $this->string(255)->notNull(),
            'pi_name' => $this->string(255)->notNull(),
            'project_name_th' => $this->text()->notNull(),
            'project_name_en' => $this->text()->notNull(),
            'objective_changed' => "ENUM('same','changed') NOT NULL",
            'objective_change_detail' => $this->text()->null(),
            'status' => "ENUM('not_started','in_progress','completed','terminated_early','cancelled') NOT NULL",
            'expected_start_date' => $this->date()->null(),
            'expected_complete_date' => $this->date()->null(),
            'completed_date' => $this->date()->null(),
            'stop_reason' => $this->text()->null(),
            'animal_requested' => $this->text()->notNull(),
            'animal_used' => $this->text()->notNull(),
            'method_changed' => "ENUM('yes','no') NOT NULL",
            'method_change_detail' => $this->text()->null(),
            'post_study_handling' => $this->text()->null(),
            'adverse_event' => "ENUM('yes','no') NOT NULL",
            'adverse_event_detail' => $this->text()->null(),
            'personnel_changed' => "ENUM('yes','no') NOT NULL",
            'personnel_change_detail' => $this->text()->null(),
            'alt_method_found' => "ENUM('yes','no') NOT NULL",
            'alt_method_detail' => $this->text()->null(),
            'study_summary' => $this->text()->notNull(),
            'has_publication' => "ENUM('yes','no') NOT NULL",
            'submitted_by_email' => $this->string(255)->notNull(),
            'status_flag' => "ENUM('draft','submitted') DEFAULT 'draft'",
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx-progress_reports-oid', '{{%progress_reports}}', 'oid');

        $this->addForeignKey(
            'fk-progress_reports-oid',
            '{{%progress_reports}}',
            'oid',
            '{{%research_projects}}',
            'oid',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-progress_reports-oid', '{{%progress_reports}}');
        $this->dropTable('{{%progress_reports}}');
    }
}
