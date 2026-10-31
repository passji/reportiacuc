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

    /**
     * ชื่อช่องภาษาไทยสำหรับข้อความ error (ทั้งตอน validate ฝั่งเซิร์ฟเวอร์และ JS ฝั่ง client) — ให้ผู้ใช้รู้ว่า
     * error นั้นเป็นของข้อไหน ไม่ใช่ชื่อคอลัมน์ภาษาอังกฤษ
     */
    public function attributeLabels()
    {
        return [
            'article_title' => 'ชื่อบทความวิจัย',
            'journal_name' => 'ชื่อวารสาร',
            'issue' => 'ฉบับที่',
            'page' => 'หน้าที่',
            'pub_month' => 'เดือนที่ตีพิมพ์',
            'pub_year' => 'ปีที่ตีพิมพ์',
            'doi' => 'DOI',
            'level' => 'ระดับ',
            'db_type' => 'ฐานข้อมูล',
            'db_other' => 'ฐานข้อมูลอื่นๆ',
            'quartile' => 'Quartile / กลุ่มที่',
            'impact_factor' => 'Impact Factor',
        ];
    }

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
            // ความยาวต้องตรงกับคอลัมน์ใน DB (STRICT mode จะโยน "Data too long" ตอน save ซึ่งผู้ใช้เห็นเป็น
            // "เกิดข้อผิดพลาดขณะบันทึกรายงาน" โดยไม่รู้ว่าช่องไหน) — article_title เป็น TEXT (65,535 ไบต์,
            // ภาษาไทย 3 ไบต์/ตัวอักษร) จึงจำกัด 16,000 ตัวอักษร
            [['article_title'], 'string', 'max' => 16000],
            [['journal_name', 'doi', 'db_other'], 'string', 'max' => 255],
            [['quartile', 'impact_factor'], 'string', 'max' => 20],
            [['issue', 'page'], 'string', 'max' => 50],
            [['pub_month'], 'string', 'max' => 20],
            [['pub_year'], 'string', 'max' => 10],
            // dropdown ที่ไม่ได้เลือกส่งค่า '' มา — ENUM ใน DB รับ '' ไม่ได้ (Data truncated) ต้องแปลงเป็น NULL
            [['level', 'db_type'], 'default', 'value' => null],
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
