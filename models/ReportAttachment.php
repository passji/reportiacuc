<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $report_id
 * @property int|null $publication_id ไม่ว่างถ้าเป็นไฟล์แนบของข้อ 6.1 รายการใดรายการหนึ่งโดยเฉพาะ
 * @property int|null $ip_filing_id ไม่ว่างถ้าเป็นไฟล์แนบของข้อ 6.2 รายการใดรายการหนึ่งโดยเฉพาะ
 * @property string $original_filename
 * @property string $stored_filename
 * @property int $file_size
 * @property string $uploaded_at
 */
class ReportAttachment extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%report_attachments}}';
    }

    public function rules()
    {
        return [
            [['report_id', 'publication_id', 'ip_filing_id'], 'integer'],
            [['original_filename', 'stored_filename'], 'required'],
            [['original_filename', 'stored_filename'], 'string', 'max' => 255],
            [['file_size'], 'integer'],
        ];
    }

    public function getProgressReport()
    {
        return $this->hasOne(ProgressReport::class, ['id' => 'report_id']);
    }

    /**
     * ที่อยู่ไฟล์จริงบนดิสก์ — เก็บนอก web root ตั้งใจให้เข้าถึงได้ทาง
     * ReportController::actionDownloadAttachment() (ต้อง login) เท่านั้น ไม่มี URL ตรงถึงไฟล์
     */
    public function getStoragePath(): string
    {
        return Yii::getAlias('@app/uploads/reports/' . $this->report_id . '/' . $this->stored_filename);
    }
}
