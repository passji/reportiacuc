<?php

use yii\db\Migration;

/**
 * เพิ่มระบบสิทธิ์ admin จริง (ก่อนหน้านี้ทุกคนที่ login ด้วย @kku.ac.th เข้าหน้า admin/* ได้หมด
 * ไม่มีการแยกสิทธิ์เลย) — ตารางนี้เป็น allowlist อีเมล admin เพียวๆ ไม่มีข้อมูลอื่น
 * seed อีเมลแรกไว้กันล็อกเคาต์ตัวเองออกจากหน้า admin ทันทีที่บังคับสิทธิ์
 */
class m260801_000012_create_admins_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%admins}}', [
            'id' => $this->primaryKey(),
            'email' => $this->string(255)->notNull(),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx-admins-email', '{{%admins}}', 'email', true);

        $this->insert('{{%admins}}', ['email' => 'imanann@kku.ac.th']);
    }

    public function safeDown()
    {
        $this->dropTable('{{%admins}}');
    }
}
