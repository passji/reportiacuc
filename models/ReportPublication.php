<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $report_id
 * @property string|null $article_title
 * @property string|null $journal_name
 * @property string|null $issue
 * @property string|null $page
 * @property string|null $pub_month
 * @property string|null $pub_year
 * @property string|null $doi
 * @property string|null $level
 * @property string|null $db_type
 * @property string|null $db_other
 * @property string|null $quartile
 * @property string|null $impact_factor
 */
class ReportPublication extends ActiveRecord
{
    public const LEVEL_LABELS = [
        'national' => 'ระดับชาติ',
        'international' => 'ระดับนานาชาติ',
    ];

    public const DB_TYPE_LABELS = [
        'ISI' => 'ISI',
        'Scopus' => 'Scopus',
        'TCI' => 'TCI',
        'other' => 'ในฐานข้อมูลอื่นๆ',
    ];

    public static function tableName()
    {
        return '{{%report_publications}}';
    }

    public function rules()
    {
        return [
            // report_id ไม่ใส่ required — เป็น FK ภายในที่ตั้งค่าโปรแกรมหลัง parent save() เสร็จ
            // เท่านั้น ตอน validate() ชุด sub-model ยังไม่มี parent id ให้ผูก
            [['report_id'], 'integer'],
            [['article_title', 'journal_name', 'doi', 'db_other', 'quartile', 'impact_factor'], 'string'],
            [['issue', 'page', 'pub_month', 'pub_year'], 'string', 'max' => 50],
            [['level'], 'in', 'range' => array_keys(self::LEVEL_LABELS)],
            [['db_type'], 'in', 'range' => array_keys(self::DB_TYPE_LABELS)],
        ];
    }

    public function getProgressReport()
    {
        return $this->hasOne(ProgressReport::class, ['id' => 'report_id']);
    }

    public function getAttachment()
    {
        return $this->hasOne(ReportAttachment::class, ['publication_id' => 'id']);
    }

    /**
     * แถวว่าง (ผู้ใช้ไม่ได้กรอกอะไรเลย) — ใช้บอกคอนโทรลเลอร์ว่าไม่ต้อง validate/save แถวนี้
     */
    public function isBlank(): bool
    {
        return $this->article_title === null || trim((string) $this->article_title) === '';
    }
}
