<?php

use yii\db\Migration;

class m260728_000003_create_report_publications_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%report_publications}}', [
            'id' => $this->primaryKey(),
            'report_id' => $this->integer()->notNull(),
            'article_title' => $this->text(),
            'journal_name' => $this->string(255),
            'issue' => $this->string(50),
            'page' => $this->string(50),
            'pub_month' => $this->string(20),
            'pub_year' => $this->string(10),
            'doi' => $this->string(255),
            'level' => "ENUM('national','international')",
            'db_type' => "ENUM('ISI','Scopus','TCI','other')",
            'db_other' => $this->string(255),
            'quartile' => $this->string(20),
            'impact_factor' => $this->string(20),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx-report_publications-report_id', '{{%report_publications}}', 'report_id');

        $this->addForeignKey(
            'fk-report_publications-report_id',
            '{{%report_publications}}',
            'report_id',
            '{{%progress_reports}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-report_publications-report_id', '{{%report_publications}}');
        $this->dropTable('{{%report_publications}}');
    }
}
