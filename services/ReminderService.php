<?php

namespace app\services;

use Yii;
use yii\db\ActiveQuery;
use app\models\ResearchProject;
use app\models\ReportNotification;
use app\models\ReminderCycle;
use app\models\ProgressReport;

/**
 * รวม logic การหา "โครงการที่ต้องเตือน" และการส่งอีเมล (แจ้งเตือน/ข่าวสาร) ไว้ที่เดียว —
 * ใช้ร่วมกันทั้ง AdminController (ปุ่มสั่งส่งเอง), commands/ReminderController (cron รายเดือน),
 * และ DashboardController (การ์ดสรุปจำนวนที่รอเตือน) กันไม่ให้นิยาม "รอเตือน" เพี้ยนกันระหว่างจุดต่าง ๆ
 */
class ReminderService
{
    public const PENDING_STATUSES = ['not_started', 'in_progress'];

    /**
     * ตอนทดสอบระบบก่อนขึ้นจริง ไม่อยากให้อีเมลแจ้งเตือน/ข่าวสาร/ปฏิเสธรายงานหลุดไปหาโครงการจริงโดย
     * บังเอิญ — ถ้าตั้ง MAILER_TEST_OVERRIDE_TO ไว้ใน .env (เช่น passji@kku.ac.th) ทุกอีเมลที่ควรจะส่ง
     * "ถึงโครงการ" จะถูกส่งไปที่อีเมลนี้แทนทั้งหมด โดย subject จะขึ้นต้นด้วยผู้รับตัวจริงไว้ให้เห็นว่า
     * "เดิมตั้งใจจะส่งถึงใคร" — ส่วน report_notifications (ประวัติการแจ้งเตือน) ยังบันทึกอีเมลผู้รับจริง
     * ไว้เหมือนเดิม ไม่ได้ถูกเปลี่ยนตาม override นี้ (override มีผลแค่ตอนส่งจริงผ่าน SMTP เท่านั้น)
     * ปล่อยว่างไว้ (ค่า default) = ส่งถึงอีเมลจริงตามปกติ ไม่มีผลอะไร
     *
     * @return array{0: string, 1: string} [$actualTo, $actualSubject]
     */
    private static function applyTestOverride(string $realTo, string $subject): array
    {
        $override = trim((string) getenv('MAILER_TEST_OVERRIDE_TO'));
        if ($override === '') {
            return [$realTo, $subject];
        }

        return [$override, "[ทดสอบ — เดิมจะส่งถึง {$realTo}] {$subject}"];
    }

    /**
     * โครงการที่ "รอเตือน" — พิจารณาจากสถานะของ "รายงานฉบับล่าสุด" ของแต่ละ oid เท่านั้น (ไม่ใช่
     * เคยมีรายงานสถานะปิดจ๊อบในอดีตหรือไม่ — โครงการที่เคยปิดจ๊อบไปแล้วแต่รายงานฉบับล่าสุดกลับมาเป็น
     * "ยังไม่เริ่มโครงการ"/"อยู่ระหว่างดำเนินการ" ต้องนับเป็นรอเตือนด้วย) รวมถึงโครงการที่ยังไม่เคย
     * ส่งรายงานเลยสักฉบับ (ยังไม่เริ่มรายงานเท่ากับรอเตือนเช่นกัน) คืน ActiveQuery ให้ผู้เรียกเลือกเอง
     * ว่าจะ ->all() หรือ ->count()
     */
    public static function pendingQuery(): ActiveQuery
    {
        return ResearchProject::find()
            ->where(
                '(SELECT pr.status FROM {{%progress_reports}} pr'
                . ' WHERE pr.oid = {{%research_projects}}.[[oid]]'
                . ' ORDER BY pr.created_at DESC LIMIT 1) IN (:pendingStatus1, :pendingStatus2)'
                . ' OR NOT EXISTS (SELECT 1 FROM {{%progress_reports}} pr2 WHERE pr2.oid = {{%research_projects}}.[[oid]])',
                [
                    ':pendingStatus1' => self::PENDING_STATUSES[0],
                    ':pendingStatus2' => self::PENDING_STATUSES[1],
                ]
            )
            ->orderBy(['oname' => SORT_ASC]);
    }

    /**
     * ส่งอีเมลแจ้งเตือนไปยังโครงการเดียว แล้ว log ผลลง report_notifications เสมอ ไม่ว่าจะส่งสำเร็จหรือ
     * ไม่ก็ตาม — เนื้อหาอีเมล (mail/reminder.php) สร้างจากข้อมูลของแต่ละโครงการเอง (รหัสโครงการ ชื่อ
     * หัวหน้าโครงการ ชื่อโครงการ ฯลฯ) จึงแตกต่างกันไปตามโครงการที่ส่งโดยอัตโนมัติ ส่วน $subject/
     * $deadlineDate/$extraNote เป็นค่าที่แอดมินปรับได้ (null = ใช้ค่าเริ่มต้น เช่น cron รายเดือนที่ไม่มี
     * แอดมินมากรอก — deadline เริ่มต้น 30 วันนับจากวันนี้)
     */
    public static function sendReminder(
        ResearchProject $project,
        string $triggerType,
        ?string $triggeredBy,
        ?string $subject = null,
        ?string $deadlineDate = null,
        ?string $extraNote = null,
        ?int $reminderCycleId = null
    ): ReportNotification {
        $status = 'sent';
        $error = null;
        $deadlineDate = $deadlineDate ?: date('Y-m-d', strtotime('+30 days'));
        $subject = $subject ?: ('แจ้งเตือน: กรุณารายงานความก้าวหน้าโครงการวิจัย ' . $project->oid);

        try {
            [$mailTo, $mailSubject] = self::applyTestOverride((string) $project->s_email, $subject);
            $sent = Yii::$app->mailer->compose(['html' => 'reminder'], [
                'project' => $project,
                'deadlineDate' => $deadlineDate,
                'extraNote' => $extraNote,
            ])
                ->setTo($mailTo)
                ->setSubject($mailSubject)
                ->send();
            if (!$sent) {
                $status = 'failed';
                $error = 'มิเลอร์ส่งไม่สำเร็จ (mailer->send() คืนค่า false)';
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $error = $e->getMessage();
        }

        $notification = new ReportNotification([
            'oid' => $project->oid,
            'recipient_email' => (string) $project->s_email,
            'trigger_type' => $triggerType,
            'subject' => $subject,
            'triggered_by' => $triggeredBy,
            'sent_status' => $status,
            'error_message' => $error,
            'reminder_cycle_id' => $reminderCycleId,
        ]);
        $notification->save(false);

        return $notification;
    }

    /**
     * ส่งอีเมลของรอบ $cycle ไปยังโครงการที่ยังดำเนินการอยู่ทุกโครงการ ณ ขณะนี้ (ไม่ใช่ snapshot ตอน
     * สร้างรอบ) แล้ว mark ว่ารอบนี้ส่งแล้ว — ใช้ร่วมกันทั้ง cron รายวัน (processDueCycles) และปุ่ม
     * "ส่งตอนนี้เลย" ที่แอดมินกดเองได้โดยไม่ต้องรอถึงวันที่กำหนด
     *
     * @return array{sent:int,failed:int}
     */
    public static function processCycle(ReminderCycle $cycle): array
    {
        $projects = self::pendingQuery()->all();
        $result = ['sent' => 0, 'failed' => 0];
        foreach ($projects as $project) {
            $notification = self::sendReminder(
                $project,
                'scheduled_cycle',
                null,
                null,
                $cycle->report_due_date,
                null,
                $cycle->id
            );
            $result[$notification->sent_status]++;
        }

        $cycle->sent_at = date('Y-m-d H:i:s');
        $cycle->save(false);

        return $result;
    }

    /**
     * เรียกจาก cron รายวัน (reminder/send-scheduled) — เช็ครอบที่ "ถึงกำหนดแล้วแต่ยังไม่ส่ง"
     * (notify_date <= วันนี้ ไม่ใช่ == วันนี้เป๊ะๆ กันกรณี cron พลาดไปวันหนึ่งจะได้ยิงตามในรอบถัดไป
     * แทนที่จะข้ามรอบไปเลย)
     *
     * @return array<int, array{cycle: ReminderCycle, result: array{sent:int,failed:int}}>
     */
    public static function processDueCycles(): array
    {
        $dueCycles = ReminderCycle::find()
            ->where(['sent_at' => null])
            ->andWhere(['<=', 'notify_date', date('Y-m-d')])
            ->orderBy(['notify_date' => SORT_ASC])
            ->all();

        $summaries = [];
        foreach ($dueCycles as $cycle) {
            $summaries[] = ['cycle' => $cycle, 'result' => self::processCycle($cycle)];
        }

        return $summaries;
    }

    /**
     * @param ResearchProject[] $projects
     * @return array{sent:int,failed:int}
     */
    public static function sendReminders(
        array $projects,
        string $triggerType,
        ?string $triggeredBy,
        ?string $subject = null,
        ?string $deadlineDate = null,
        ?string $extraNote = null
    ): array {
        $result = ['sent' => 0, 'failed' => 0];
        foreach ($projects as $project) {
            $notification = self::sendReminder($project, $triggerType, $triggeredBy, $subject, $deadlineDate, $extraNote);
            $result[$notification->sent_status]++;
        }
        return $result;
    }

    /**
     * ส่งอีเมล (หัวข้อ/เนื้อหาที่แอดมินพิมพ์หรือแก้ไขเอง — ไม่ว่าจะเริ่มจากเทมเพลตแจ้งเตือนหรือพิมพ์เอง
     * ล้วนๆ) ไปยังโครงการที่เลือก จาก admin/email หน้าเดียว — $triggerType ใช้แยกหมวดในประวัติการส่ง
     * เท่านั้น (manual_admin = เริ่มจากเทมเพลตแจ้งเตือน, manual_announcement = ข่าวสารทั่วไป/พิมพ์เอง)
     * ไม่ได้เปลี่ยนพฤติกรรมการส่งจริง
     *
     * @param ResearchProject[] $projects
     * @return array{sent:int,failed:int}
     */
    public static function sendAnnouncement(
        array $projects,
        string $subject,
        string $body,
        ?string $triggeredBy,
        string $triggerType = 'manual_announcement'
    ): array {
        $result = ['sent' => 0, 'failed' => 0];

        foreach ($projects as $project) {
            $status = 'sent';
            $error = null;

            try {
                [$mailTo, $mailSubject] = self::applyTestOverride((string) $project->s_email, $subject);
                $sent = Yii::$app->mailer->compose(['html' => 'announcement'], [
                    'project' => $project,
                    'subject' => $subject,
                    'body' => $body,
                ])
                    ->setTo($mailTo)
                    ->setSubject($mailSubject)
                    ->send();
                if (!$sent) {
                    $status = 'failed';
                    $error = 'มิเลอร์ส่งไม่สำเร็จ (mailer->send() คืนค่า false)';
                }
            } catch (\Throwable $e) {
                $status = 'failed';
                $error = $e->getMessage();
            }

            $notification = new ReportNotification([
                'oid' => $project->oid,
                'recipient_email' => (string) $project->s_email,
                'trigger_type' => $triggerType,
                'subject' => $subject,
                'triggered_by' => $triggeredBy,
                'sent_status' => $status,
                'error_message' => $error,
            ]);
            $notification->save(false);

            $result[$status]++;
        }

        return $result;
    }

    /**
     * ส่งอีเมลแจ้งผู้ส่งรายงาน (submitted_by_email — ไม่ใช่อีเมลผู้ประสานงานโครงการทั่วไป) ว่ารายงาน
     * ฉบับนี้ถูกแอดมินปฏิเสธพร้อมเหตุผล แล้ว log ผลลง report_notifications เสมอเหมือนการส่งอีเมลอื่นๆ
     * ในระบบ ใช้ตอนแอดมินกด "ปฏิเสธ" ที่ report/view (ดู ReportController::actionReviewDecision())
     */
    public static function sendRejectionNotice(
        ProgressReport $report,
        string $reason,
        ?string $triggeredBy
    ): ReportNotification {
        $status = 'sent';
        $error = null;
        $subject = 'รายงานความก้าวหน้าโครงการวิจัยถูกปฏิเสธ กรุณาส่งข้อมูลใหม่ ' . $report->oid;

        try {
            [$mailTo, $mailSubject] = self::applyTestOverride((string) $report->submitted_by_email, $subject);
            $sent = Yii::$app->mailer->compose(['html' => 'report-rejected'], [
                'report' => $report,
                'reason' => $reason,
            ])
                ->setTo($mailTo)
                ->setSubject($mailSubject)
                ->send();
            if (!$sent) {
                $status = 'failed';
                $error = 'มิเลอร์ส่งไม่สำเร็จ (mailer->send() คืนค่า false)';
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $error = $e->getMessage();
        }

        $notification = new ReportNotification([
            'oid' => $report->oid,
            'recipient_email' => (string) $report->submitted_by_email,
            'trigger_type' => 'report_rejected',
            'subject' => $subject,
            'triggered_by' => $triggeredBy,
            'sent_status' => $status,
            'error_message' => $error,
        ]);
        $notification->save(false);

        return $notification;
    }
}
