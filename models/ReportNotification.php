<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $oid
 * @property string $recipient_email
 * @property string $trigger_type
 * @property string|null $subject
 * @property string|null $triggered_by
 * @property string $sent_status
 * @property string|null $error_message
 * @property string $sent_at
 * @property int|null $reminder_cycle_id
 */
class ReportNotification extends ActiveRecord
{
    public const TRIGGER_TYPE_LABELS = [
        'auto_monthly' => 'แจ้งเตือนอัตโนมัติรายเดือน',
        'manual_admin' => 'แอดมินสั่งส่งเอง',
        'manual_announcement' => 'ข่าวสารจากแอดมิน',
        'report_rejected' => 'แจ้งปฏิเสธรายงาน',
        'scheduled_cycle' => 'รอบแจ้งเตือนอัตโนมัติ',
    ];

    public const SENT_STATUS_LABELS = [
        'sent' => 'สำเร็จ',
        'failed' => 'ล้มเหลว',
    ];

    public const SENT_STATUS_BADGE_CLASSES = [
        'sent' => 'bg-success',
        'failed' => 'bg-danger',
    ];

    public static function tableName()
    {
        return '{{%report_notifications}}';
    }

    public function rules()
    {
        return [
            [['oid', 'recipient_email', 'trigger_type', 'sent_status'], 'required'],
            [['error_message'], 'string'],
            [['sent_at'], 'safe'],
            [['trigger_type'], 'in', 'range' => array_keys(self::TRIGGER_TYPE_LABELS)],
            [['sent_status'], 'in', 'range' => array_keys(self::SENT_STATUS_LABELS)],
            [['oid'], 'string', 'max' => 20],
            [['recipient_email', 'triggered_by', 'subject'], 'string', 'max' => 255],
            [['reminder_cycle_id'], 'integer'],
        ];
    }

    public function getResearchProject()
    {
        return $this->hasOne(ResearchProject::class, ['oid' => 'oid']);
    }

    public function getReminderCycle()
    {
        return $this->hasOne(ReminderCycle::class, ['id' => 'reminder_cycle_id']);
    }
}
