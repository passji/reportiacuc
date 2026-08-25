<?php

use yii\db\Migration;

class m260728_000001_create_research_projects_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%research_projects}}', [
            'id' => $this->primaryKey(),
            'oid' => $this->string(20)->notNull()->unique(),
            'oname' => $this->text(),
            'oname_en' => $this->text(),
            'm_pro_th' => $this->string(255),
            'm_pro_en' => $this->string(255),
            'm_pro_dept_th' => $this->text(),
            'md_name' => $this->string(255),
            'meeting_no' => $this->string(20),
            'meeting_date' => $this->dateTime()->null(),
            's_email' => $this->string(255),
            's_phone' => $this->string(20),
            'raw_json' => $this->json()->notNull(),
            'received_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function safeDown()
    {
        $this->dropTable('{{%research_projects}}');
    }
}
