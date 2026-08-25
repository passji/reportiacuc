<?php

namespace app\commands;

use yii\console\Controller;
use app\services\ReminderService;

/**
 * รันด้วย: php yii reminder/send-monthly (ตั้ง cron รายเดือนเรียกคำสั่งนี้ — ดู README)
 */
class ReminderController extends Controller
{
    public function actionSendMonthly()
    {
        $projects = ReminderService::pendingQuery()->all();
        $result = ReminderService::sendReminders($projects, 'auto_monthly', null);

        echo "{$result['sent']} ฉบับ ส่งแจ้งเตือนอัตโนมัติเรียบร้อย";
        if ($result['failed'] > 0) {
            echo " ({$result['failed']} ฉบับล้มเหลว)";
        }
        echo "\n";
    }

    /**
     * รันด้วย: php yii reminder/send-scheduled (ตั้ง cron รายวันเรียกคำสั่งนี้ — ดู README)
     * เช็ครอบการส่งรายงาน (reminder_cycles) ที่ถึงกำหนด notify_date แล้วแต่ยังไม่ส่ง ส่งให้ทุกรอบที่พบ
     */
    public function actionSendScheduled()
    {
        $summaries = ReminderService::processDueCycles();

        if (empty($summaries)) {
            echo "ไม่มีรอบที่ถึงกำหนดส่งวันนี้\n";
            return;
        }

        foreach ($summaries as $summary) {
            $cycle = $summary['cycle'];
            $result = $summary['result'];
            echo "รอบ \"{$cycle->name}\": ส่งสำเร็จ {$result['sent']} ฉบับ";
            if ($result['failed'] > 0) {
                echo " ล้มเหลว {$result['failed']} ฉบับ";
            }
            echo "\n";
        }
    }
}
