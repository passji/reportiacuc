<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string $report_due_date
 * @property string $notify_date
 * @property string|null $sent_at
 * @property string|null $created_by
 * @property string $created_at
 */
class ReminderCycle extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%reminder_cycles}}';
    }

    public function rules()
    {
        return [
            [['name', 'report_due_date', 'notify_date'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['report_due_date', 'notify_date'], 'date', 'format' => 'php:Y-m-d'],
            [['notify_date'], 'validateNotifyDate'],
            [['created_by'], 'string', 'max' => 255],
            [['sent_at', 'created_at'], 'safe'],
        ];
    }

    /**
     * วันที่ส่งอีเมลแจ้งเตือนต้องมาก่อนหรือวันเดียวกับกำหนดส่งรายงาน ไม่งั้นแจ้งเตือนหลังครบกำหนดไปแล้ว
     * ซึ่งไม่มีประโยชน์
     */
    public function validateNotifyDate($attribute, $params)
    {
        if ($this->report_due_date && $this->notify_date && $this->notify_date > $this->report_due_date) {
            $this->addError($attribute, 'วันที่ส่งอีเมลแจ้งเตือนต้องมาก่อนหรือวันเดียวกับกำหนดส่งรายงาน');
        }
    }

    public function isSent(): bool
    {
        return $this->sent_at !== null;
    }

    public function isDue(): bool
    {
        return !$this->isSent() && $this->notify_date <= date('Y-m-d');
    }

    public function getNotifications()
    {
        return $this->hasMany(ReportNotification::class, ['reminder_cycle_id' => 'id']);
    }
}
