<?php

namespace app\models;

use yii\db\ActiveRecord;
use app\helpers\ThaiDate;

/**
 * @property int $id
 * @property int $report_id
 * @property string|null $ip_type
 * @property string|null $filed_date
 * @property string|null $registration_no
 * @property string|null $asset_name
 */
class ReportIpFiling extends ActiveRecord
{
    public const IP_TYPE_LABELS = [
        'patent' => 'สิทธิบัตร',
        'petty_patent' => 'อนุสิทธิบัตร',
        'copyright' => 'ลิขสิทธิ์',
    ];

    /**
     * ชื่อช่องภาษาไทยสำหรับข้อความ error (ทั้งตอน validate ฝั่งเซิร์ฟเวอร์และ JS ฝั่ง client) — ให้ผู้ใช้รู้ว่า
     * error นั้นเป็นของข้อไหน ไม่ใช่ชื่อคอลัมน์ภาษาอังกฤษ
     */
    public function attributeLabels()
    {
        return [
            'ip_type' => 'ประเภททรัพย์สินทางปัญญา',
            'filed_date' => 'วันที่ยื่นจด',
            'registration_no' => 'เลขที่จดทะเบียน',
            'asset_name' => 'ชื่อทรัพย์สิน',
        ];
    }

    public static function tableName()
    {
        return '{{%report_ip_filings}}';
    }

    public function rules()
    {
        return [
            // report_id ไม่ใส่ required — ดูเหตุผลเดียวกับ ReportPublication
            [['report_id'], 'integer'],
            [['filed_date'], 'validateThaiDate'],
            [['registration_no'], 'string', 'max' => 100],
            [['asset_name'], 'string', 'max' => 16000], // TEXT = 65,535 ไบต์ (ไทย 3 ไบต์/ตัวอักษร)
            // dropdown ที่ไม่ได้เลือกส่งค่า '' มา — ENUM ใน DB รับ '' ไม่ได้ (Data truncated) ต้องแปลงเป็น NULL
            [['ip_type'], 'default', 'value' => null],
            [['ip_type'], 'in', 'range' => array_keys(self::IP_TYPE_LABELS)],
        ];
    }

    /**
     * ฟอร์มให้ผู้ใช้พิมพ์วันที่แบบไทย (วว/ดด/ปปปป พ.ศ. เช่น 01/12/2569) ไม่ใช่ ISO ตรงๆ — แปลง/
     * ตรวจสอบตรงนี้ (ไม่ใช่แค่ฝั่ง JS) กันข้อมูลเพี้ยนถ้า JS พัง/ถูกข้าม แล้วทับ $this->filed_date
     * ด้วยค่า ISO ที่แปลงแล้วเลย ผู้เรียกด้านหลัง (save/แสดงผล) จึงยังเห็นเป็น ISO ตามปกติ
     */
    public function validateThaiDate($attribute)
    {
        $parsed = ThaiDate::parseThaiInput($this->$attribute);
        if ($parsed === false) {
            $this->addError($attribute, 'รูปแบบวันที่ไม่ถูกต้อง กรุณากรอกเป็น วว/ดด/ปปปป (พ.ศ.) เช่น 01/12/2569');
            return;
        }
        $this->$attribute = $parsed;
    }

    public function getProgressReport()
    {
        return $this->hasOne(ProgressReport::class, ['id' => 'report_id']);
    }

    public function getAttachment()
    {
        return $this->hasOne(ReportAttachment::class, ['ip_filing_id' => 'id']);
    }

    /**
     * แถวว่าง — ใช้บอกคอนโทรลเลอร์ว่าไม่ต้อง validate/save แถวนี้
     */
    public function isBlank(): bool
    {
        $noRegNo = $this->registration_no === null || trim((string) $this->registration_no) === '';
        $noAssetName = $this->asset_name === null || trim((string) $this->asset_name) === '';
        return $noRegNo && $noAssetName;
    }
}
