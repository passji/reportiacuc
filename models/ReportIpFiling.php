<?php

namespace app\models;

use yii\db\ActiveRecord;

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

    public static function tableName()
    {
        return '{{%report_ip_filings}}';
    }

    public function rules()
    {
        return [
            // report_id ไม่ใส่ required — ดูเหตุผลเดียวกับ ReportPublication
            [['report_id'], 'integer'],
            [['filed_date'], 'safe'],
            [['registration_no'], 'string', 'max' => 100],
            [['asset_name'], 'string'],
            [['ip_type'], 'in', 'range' => array_keys(self::IP_TYPE_LABELS)],
        ];
    }

    public function getProgressReport()
    {
        return $this->hasOne(ProgressReport::class, ['id' => 'report_id']);
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
