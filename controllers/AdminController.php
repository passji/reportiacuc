<?php

namespace app\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Query;
use yii\web\BadRequestHttpException;
use app\models\ResearchProject;
use app\models\ReportNotification;
use app\models\ProgressReport;
use app\models\ReminderCycle;
use app\models\Admin;
use app\helpers\DateHelper;
use app\helpers\ThaiDate;
use app\helpers\PdfHelper;
use app\services\ReminderService;
use Mpdf\Output\Destination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AdminController extends SecureController
{
    /**
     * ทุก action ในนี้เป็นของ admin ล้วน — เช็คสิทธิ์เพิ่มต่อจาก SecureController (login)
     * ครั้งเดียวตรงนี้ ครอบคลุมทุก action รวมถึงที่จะเพิ่มในอนาคตด้วย
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if (!Admin::isEmailAdmin((string) Yii::$app->session->get('sso_email'))) {
            Yii::$app->session->setFlash('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (สำหรับผู้ดูแลระบบเท่านั้น)');
            // ไม่ redirect ไป dashboard/index เพราะตอนนี้กลายเป็นหน้า admin-only ด้วยเช่นกัน
            // (จะวน redirect loop) — ไปหน้า "รายงานของฉัน" ซึ่งเป็นหน้าเปิดสำหรับผู้ใช้ทั่วไปแทน
            $this->redirect(['/report/my-reports']);
            return false;
        }
        return true;
    }

    public function actionEmail()
    {
        $statusFilter = Yii::$app->request->get('status', 'pending');
        if (!in_array($statusFilter, ['pending', 'all'], true)) {
            $statusFilter = 'pending';
        }
        $startDate = trim((string) Yii::$app->request->get('start_date', ''));
        $endDate = trim((string) Yii::$app->request->get('end_date', ''));

        // ครอบ query ที่มี correlated subquery (latest_status/latest_submitted_at) ไว้เป็น derived
        // table ชั้นนอก เพราะ WHERE จะอ้างอิง alias ของ subquery ตรงๆ ไม่ได้ใน MySQL/MariaDB
        $inner = (new Query())
            ->select([
                'rp.oid',
                'rp.oname',
                'rp.m_pro_th',
                'rp.s_email',
                'latest_status' => '(SELECT pr.status FROM {{%progress_reports}} pr'
                    . ' WHERE pr.oid = rp.oid ORDER BY pr.created_at DESC LIMIT 1)',
                'latest_submitted_at' => '(SELECT pr.created_at FROM {{%progress_reports}} pr'
                    . ' WHERE pr.oid = rp.oid ORDER BY pr.created_at DESC LIMIT 1)',
            ])
            ->from(['rp' => '{{%research_projects}}']);

        $query = (new Query())->from(['t' => $inner]);

        if ($statusFilter === 'pending') {
            $query->andWhere(['or',
                ['in', 't.latest_status', ReminderService::PENDING_STATUSES],
                ['t.latest_status' => null],
            ]);
        }
        if ($startDate !== '') {
            $query->andWhere(['>=', 't.latest_submitted_at', $startDate . ' 00:00:00']);
        }
        if ($endDate !== '') {
            $query->andWhere(['<=', 't.latest_submitted_at', $endDate . ' 23:59:59']);
        }

        $projects = $query->orderBy(['t.oname' => SORT_ASC])->all();

        // เลขที่โครงการ (project_code) มี logic fallback ซับซ้อน (ใช้ค่าจากรายงานฉบับล่าสุดก่อน ถ้า
        // ยังไม่เคยมีรายงานเลยค่อย parse จาก raw_json.meet_summary) — เรียกผ่าน
        // ResearchProject::getProjectCode() ที่มี logic นี้อยู่แล้ว แทนที่จะเขียนซ้ำเป็น correlated
        // subquery ที่จะไม่ได้ fallback ให้โครงการที่ยังไม่เคยส่งรายงานเลย (ซึ่งเป็นกลุ่มหลักของหน้านี้)
        $projectModels = ResearchProject::find()
            ->where(['oid' => array_column($projects, 'oid')])
            ->indexBy('oid')
            ->all();
        $projectCodes = [];
        foreach ($projectModels as $oid => $projectModel) {
            $projectCodes[$oid] = $projectModel->getProjectCode();
        }

        // ตัวกรองของ "ประวัติการแจ้งเตือน / ข่าวสาร" แยกชื่อ GET param จากตัวกรองของ "ค้นหาโครงการ"
        // ด้านบน (start_date/end_date) เพราะเป็นคนละส่วน คนละความหมาย อยู่ในหน้าเดียวกัน — default เป็น
        // เดือนปัจจุบัน เหมือนตัวกรองช่วงวันที่หน้าอื่น ๆ ในระบบ (dashboard, report/index)
        $notifStartDate = DateHelper::parseOrDefault(Yii::$app->request->get('notif_start_date'), date('Y-m-01'));
        $notifEndDate = DateHelper::parseOrDefault(Yii::$app->request->get('notif_end_date'), date('Y-m-d'));
        if ($notifStartDate > $notifEndDate) {
            [$notifStartDate, $notifEndDate] = [$notifEndDate, $notifStartDate];
        }
        $notifSearch = trim((string) Yii::$app->request->get('notif_search', ''));

        $notificationsQuery = ReportNotification::find()
            ->alias('n')
            ->joinWith(['researchProject rp'])
            ->orderBy(['n.sent_at' => SORT_DESC]);

        $notificationsQuery->andWhere(['between', 'n.sent_at', $notifStartDate . ' 00:00:00', $notifEndDate . ' 23:59:59']);

        if ($notifSearch !== '') {
            // ค้นหาได้ทั้งชื่อโครงการ (research_projects.oname) และอีเมลผู้รับ (report_notifications.recipient_email)
            $notificationsQuery->andWhere(['or',
                ['like', 'rp.oname', $notifSearch],
                ['like', 'n.recipient_email', $notifSearch],
            ]);
        }

        $notificationsProvider = new ActiveDataProvider([
            'query' => $notificationsQuery,
            'pagination' => [
                'pageSize' => 25,
            ],
            'sort' => false,
        ]);

        return $this->render('email', [
            'projects' => $projects,
            'projectCodes' => $projectCodes,
            'statusFilter' => $statusFilter,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'notifStartDate' => $notifStartDate,
            'notifEndDate' => $notifEndDate,
            'notifSearch' => $notifSearch,
            'notifications' => $notificationsProvider->getModels(),
            'notificationsProvider' => $notificationsProvider,
        ]);
    }

    public function actionSendEmail()
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException();
        }

        $request = Yii::$app->request;
        $subject = trim((string) $request->post('subject', ''));
        $body = trim((string) $request->post('body', ''));
        $oids = (array) $request->post('oids', []);
        // 'template' บอกว่าแอดมินเริ่มจากเทมเพลตไหน — 'reminder' ส่งผ่าน mail/reminder.php ที่ประกอบ
        // เนื้อหาจากข้อมูลของแต่ละโครงการเอง (รหัสโครงการ ชื่อหัวหน้าโครงการ ฯลฯ) จึงต่างกันไปตาม
        // โครงการที่เลือกโดยอัตโนมัติ ส่วน $body ในโหมดนี้เป็นแค่ข้อความเสริมท้ายอีเมล ไม่ใช่เนื้อหาหลัก
        $template = $request->post('template', 'blank');
        $triggeredBy = (string) Yii::$app->session->get('sso_email');

        if (empty($oids)) {
            Yii::$app->session->setFlash('error', 'กรุณาเลือกโครงการอย่างน้อย 1 โครงการ');
            return $this->redirect(['email']);
        }
        $projects = ResearchProject::find()->where(['oid' => $oids])->all();

        if ($template === 'reminder') {
            $deadlineDate = trim((string) $request->post('deadline_date', ''));
            if ($deadlineDate === '') {
                Yii::$app->session->setFlash('error', 'กรุณาระบุวันครบกำหนดส่งรายงาน');
                return $this->redirect(['email']);
            }
            $result = ReminderService::sendReminders(
                $projects,
                'manual_admin',
                $triggeredBy,
                $subject !== '' ? $subject : null,
                $deadlineDate,
                $body !== '' ? $body : null
            );
        } else {
            if ($subject === '' || $body === '') {
                Yii::$app->session->setFlash('error', 'กรุณากรอกหัวข้อและเนื้อหา');
                return $this->redirect(['email']);
            }
            $result = ReminderService::sendAnnouncement($projects, $subject, $body, $triggeredBy, 'manual_announcement');
        }

        Yii::$app->session->setFlash(
            $result['failed'] > 0 ? 'warning' : 'success',
            "ส่งอีเมลสำเร็จ {$result['sent']} ฉบับ" .
                ($result['failed'] > 0 ? " ล้มเหลว {$result['failed']} ฉบับ" : '')
        );

        return $this->redirect(['email']);
    }

    public function actionReviewQueue()
    {
        [$statusFilter, $startDate, $endDate] = $this->resolveReviewQueueFilters();

        $reportsProvider = new ActiveDataProvider([
            'query' => $this->buildReviewQueueQuery($statusFilter, $startDate, $endDate),
            'pagination' => [
                'pageSize' => 25,
            ],
            'sort' => false,
        ]);

        return $this->render('review-queue', [
            'reports' => $reportsProvider->getModels(),
            'reportsProvider' => $reportsProvider,
            'statusFilter' => $statusFilter,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * ส่งออกรายการเดียวกับที่เห็นบนหน้า "ตรวจสอบรายงาน" (ตามตัวกรองสถานะ+ช่วงวันที่ที่เลือก ไม่จำกัด
     * แค่หน้าแรกของ pagination) เป็น PDF
     */
    public function actionReviewQueueExportPdf()
    {
        [$statusFilter, $startDate, $endDate] = $this->resolveReviewQueueFilters();
        $reports = $this->buildReviewQueueQuery($statusFilter, $startDate, $endDate)->all();

        $html = $this->renderPartial('_review-queue-export-pdf', [
            'reports' => $reports,
            'statusFilter' => $statusFilter,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $mpdf = PdfHelper::create();
        $mpdf->WriteHTML($html);
        $pdfContent = $mpdf->Output('', Destination::STRING_RETURN);

        return Yii::$app->response->sendContentAsFile(
            $pdfContent,
            'review-queue-' . date('Y-m-d') . '.pdf',
            ['mimeType' => 'application/pdf']
        );
    }

    /**
     * ส่งออกรายการเดียวกันเป็น Excel (.xlsx)
     */
    public function actionReviewQueueExportExcel()
    {
        [$statusFilter, $startDate, $endDate] = $this->resolveReviewQueueFilters();
        $reports = $this->buildReviewQueueQuery($statusFilter, $startDate, $endDate)->all();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('ตรวจสอบรายงาน');

        $headers = ['รหัสโครงการ', 'ชื่อโครงการ', 'หัวหน้าโครงการ', 'ส่งเมื่อ', 'ส่งโดย', 'สถานะการตรวจสอบ', 'หมายเหตุ'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 1], $header);
        }
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $r = 2;
        foreach ($reports as $report) {
            $note = '';
            if ($report->review_status === 'rejected' && $report->rejection_reason) {
                $note = $report->rejection_reason;
            } elseif ($report->reviewed_at) {
                $note = 'โดย ' . ($report->reviewed_by ?: '-') . ' เมื่อ ' . ThaiDate::format($report->reviewed_at);
            }

            $sheet->setCellValue([1, $r], $report->project_code ?: '-');
            $sheet->setCellValue([2, $r], $report->researchProject->oname ?? $report->project_name_th);
            $sheet->setCellValue([3, $r], $report->pi_name);
            $sheet->setCellValue([4, $r], ThaiDate::format($report->created_at));
            $sheet->setCellValue([5, $r], $report->submitted_by_email);
            $sheet->setCellValue([6, $r], ProgressReport::REVIEW_STATUS_LABELS[$report->review_status] ?? $report->review_status);
            $sheet->setCellValue([7, $r], $note);
            $r++;
        }
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tmpFile = Yii::getAlias('@runtime') . '/review-queue-export-' . uniqid() . '.xlsx';
        $writer->save($tmpFile);
        $content = file_get_contents($tmpFile);
        unlink($tmpFile);

        return Yii::$app->response->sendContentAsFile(
            $content,
            'review-queue-' . date('Y-m-d') . '.xlsx',
            ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    private function resolveReviewQueueFilters(): array
    {
        $statusFilter = Yii::$app->request->get('status', 'pending');
        if (!in_array($statusFilter, ['pending', 'approved', 'rejected', 'all'], true)) {
            $statusFilter = 'pending';
        }

        // ตัวกรองช่วงวันที่ของคิวตรวจสอบไม่ default เป็นเดือนปัจจุบันเหมือนหน้าอื่น ๆ (dashboard,
        // report/index) เพราะจุดประสงค์ของคิวคือต้องเห็นรายการ "รอตรวจสอบ" ทั้งหมดไม่ว่าส่งมาเมื่อไหร่
        // — ถ้า default เป็นเดือนนี้ รายงานเก่าที่ยังไม่ได้ตรวจจะถูกซ่อนไปโดยไม่ตั้งใจ ว่างไว้ = ไม่กรอง
        $startDate = DateHelper::parseOrDefault(Yii::$app->request->get('start_date'), '');
        $endDate = DateHelper::parseOrDefault(Yii::$app->request->get('end_date'), '');
        if ($startDate !== '' && $endDate !== '' && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$statusFilter, $startDate, $endDate];
    }

    private function buildReviewQueueQuery(string $statusFilter, string $startDate, string $endDate): ActiveQuery
    {
        $query = ProgressReport::find()->with('researchProject');
        if ($statusFilter !== 'all') {
            $query->where(['review_status' => $statusFilter]);
        }
        if ($startDate !== '') {
            $query->andWhere(['>=', 'created_at', $startDate . ' 00:00:00']);
        }
        if ($endDate !== '') {
            $query->andWhere(['<=', 'created_at', $endDate . ' 23:59:59']);
        }

        return $query->orderBy(['created_at' => SORT_DESC]);
    }

    public function actionSettings()
    {
        $admins = Admin::find()->orderBy(['created_at' => SORT_ASC])->all();

        return $this->render('settings', [
            'admins' => $admins,
        ]);
    }

    public function actionAddAdmin()
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException();
        }

        $admin = new Admin(['email' => trim((string) Yii::$app->request->post('email', ''))]);
        if ($admin->save()) {
            Yii::$app->session->setFlash('success', 'เพิ่ม admin เรียบร้อยแล้ว');
        } else {
            Yii::$app->session->setFlash('error', implode(' ', $admin->getFirstErrors()));
        }

        return $this->redirect(['settings']);
    }

    public function actionRemoveAdmin($id)
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException();
        }

        if (Admin::find()->count() <= 1) {
            Yii::$app->session->setFlash('error', 'ต้องมี admin อย่างน้อย 1 คนเสมอ ไม่สามารถลบคนสุดท้ายได้');
            return $this->redirect(['settings']);
        }

        $admin = Admin::findOne($id);
        if ($admin) {
            $admin->delete();
            Yii::$app->session->setFlash('success', 'ลบ admin เรียบร้อยแล้ว');
        }

        return $this->redirect(['settings']);
    }

    public function actionNotificationSettings()
    {
        $cycles = ReminderCycle::find()
            ->with('notifications')
            ->orderBy(['notify_date' => SORT_ASC])
            ->all();

        return $this->render('notification-settings', [
            'cycles' => $cycles,
        ]);
    }

    public function actionAddReminderCycle()
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException();
        }

        $cycle = new ReminderCycle([
            'name' => trim((string) Yii::$app->request->post('name', '')),
            'report_due_date' => trim((string) Yii::$app->request->post('report_due_date', '')),
            'notify_date' => trim((string) Yii::$app->request->post('notify_date', '')),
            'created_by' => (string) Yii::$app->session->get('sso_email'),
        ]);

        if ($cycle->save()) {
            Yii::$app->session->setFlash('success', 'สร้างรอบการส่งรายงานเรียบร้อยแล้ว');
        } else {
            Yii::$app->session->setFlash('error', implode(' ', $cycle->getFirstErrors()));
        }

        return $this->redirect(['notification-settings']);
    }

    public function actionRemoveReminderCycle($id)
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException();
        }

        $cycle = ReminderCycle::findOne($id);
        if (!$cycle) {
            return $this->redirect(['notification-settings']);
        }

        // รอบที่ส่งไปแล้วต้องเก็บไว้เป็นหลักฐาน (audit trail) เหมือนกับที่ระบบนี้ไม่ให้ลบประวัติการ
        // ตรวจสอบรายงานที่ตัดสินใจไปแล้ว — ลบได้เฉพาะรอบที่ยังไม่ส่ง
        if ($cycle->isSent()) {
            Yii::$app->session->setFlash('error', 'ลบไม่ได้ — รอบนี้ส่งอีเมลไปแล้ว');
            return $this->redirect(['notification-settings']);
        }

        $cycle->delete();
        Yii::$app->session->setFlash('success', 'ลบรอบการส่งรายงานเรียบร้อยแล้ว');

        return $this->redirect(['notification-settings']);
    }

    /**
     * ปุ่ม "ส่งตอนนี้เลย" — สั่งส่งรอบนี้ทันทีโดยไม่ต้องรอถึง notify_date หรือรอ cron รายวัน
     * (ใช้ทดสอบ หรือกรณีต้องการส่งเร่งด่วนก่อนกำหนด)
     */
    public function actionSendCycleNow($id)
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException();
        }

        $cycle = ReminderCycle::findOne($id);
        if (!$cycle) {
            return $this->redirect(['notification-settings']);
        }

        if ($cycle->isSent()) {
            Yii::$app->session->setFlash('error', 'รอบนี้ส่งอีเมลไปแล้ว ส่งซ้ำไม่ได้');
            return $this->redirect(['notification-settings']);
        }

        $result = ReminderService::processCycle($cycle);
        Yii::$app->session->setFlash(
            $result['failed'] > 0 ? 'warning' : 'success',
            "ส่งอีเมลรอบ \"{$cycle->name}\" สำเร็จ {$result['sent']} ฉบับ" .
                ($result['failed'] > 0 ? " ล้มเหลว {$result['failed']} ฉบับ" : '')
        );

        return $this->redirect(['notification-settings']);
    }
}
