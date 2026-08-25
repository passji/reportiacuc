<?php

namespace app\controllers;

use Yii;
use yii\db\Query;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;
use yii\data\Sort;
use app\models\ProgressReport;
use app\models\Admin;
use app\helpers\DateHelper;
use app\helpers\ThaiDate;
use app\helpers\PdfHelper;
use Mpdf\Output\Destination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DashboardController extends SecureController
{
    /**
     * Dashboard ย้ายเข้ากลุ่มเมนู ADMIN แล้ว — จำกัดสิทธิ์จริงไม่ใช่แค่ซ่อนเมนู เหมือน
     * AdminController::beforeAction()
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if (!Admin::isEmailAdmin((string) Yii::$app->session->get('sso_email'))) {
            Yii::$app->session->setFlash('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (สำหรับผู้ดูแลระบบเท่านั้น)');
            $this->redirect(['/report/my-reports']);
            return false;
        }
        return true;
    }

    public function actionIndex()
    {
        [$startDate, $endDate] = $this->resolveDateRange();
        $stats = $this->buildStats($startDate, $endDate);

        // กรองตามสถานะได้จากการคลิก slice ใน "สถานะการดำเนินโครงการ" / "ผลการตรวจสอบ" — รับเฉพาะ key
        // ที่รู้จักเท่านั้น กันค่าแปลกปลอมหลุดเข้า WHERE
        $statusFilter = Yii::$app->request->get('status');
        if (!isset($stats['statusLabels'][$statusFilter])) {
            $statusFilter = null;
        }

        $reviewStatusFilter = Yii::$app->request->get('review_status');
        if (!isset(ProgressReport::REVIEW_STATUS_LABELS[$reviewStatusFilter])) {
            $reviewStatusFilter = null;
        }

        // แสดงเป็นรายโครงการ (group by oid) แบบเดียวกับ ReportController::actionIndex() — หนึ่งแถวต่อ
        // หนึ่งโครงการ ไม่ใช่ต่อรายงาน แทนที่การแสดงแบบรายฉบับรายงานแบบเดิม
        $sort = new Sort([
            'sortParam' => 'recent-sort',
            'attributes' => [
                'project_code' => ['label' => 'รหัสโครงการ'],
                'oname' => ['label' => 'ชื่อโครงการ'],
                'm_pro_th' => ['label' => 'หัวหน้าโครงการ'],
                'latest_status' => ['label' => 'สถานะ'],
                'latest_review_status' => ['label' => 'ผลการตรวจสอบ'],
                'latest_submitted_at' => ['label' => 'ส่งล่าสุดเมื่อ'],
            ],
            'defaultOrder' => ['latest_submitted_at' => SORT_DESC],
        ]);

        $query = $this->recentProjectsQuery($startDate, $endDate);
        if ($statusFilter !== null) {
            $query->andHaving(['latest_status' => $statusFilter]);
        }
        if ($reviewStatusFilter !== null) {
            $query->andHaving(['latest_review_status' => $reviewStatusFilter]);
        }
        $query->orderBy($sort->getOrders());

        // ->count() บน query ที่มี groupBy() จะได้จำนวนต่อกลุ่ม ไม่ใช่จำนวนกลุ่มทั้งหมด ต้อง clone
        // คิวรีเดิม (ก่อนใส่ offset/limit) มาห่อเป็น derived table แล้วนับอีกชั้น เหมือนที่
        // ReportController::actionIndex() ทำ
        $countQuery = clone $query;
        $totalCount = (new Query())->from(['t' => $countQuery])->count();

        $pagination = new Pagination([
            'totalCount' => $totalCount,
            'pageParam' => 'recent-page',
            'pageSize' => 25,
        ]);

        $recentProjects = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        // รายชื่อรายงานแบบรายฉบับ (หนึ่งแถวต่อหนึ่งรายงาน) — เอากลับมาแสดงคู่กับตารางรายโครงการ
        // ด้านบน ตามที่ผู้ใช้ขอ ใช้ pageParam/sortParam คนละชุดกันไม่ให้เลขหน้า/การเรียงชนกัน
        $reportsQuery = $this->recentReportsQuery($startDate, $endDate);
        if ($statusFilter !== null) {
            $reportsQuery->andWhere(['status' => $statusFilter]);
        }
        if ($reviewStatusFilter !== null) {
            $reportsQuery->andWhere(['review_status' => $reviewStatusFilter]);
        }

        $recentReportsProvider = new ActiveDataProvider([
            'query' => $reportsQuery,
            'pagination' => [
                'pageParam' => 'report-page',
                'pageSize' => 25,
            ],
            'sort' => [
                'sortParam' => 'report-sort',
                'attributes' => [
                    'project_code' => ['label' => 'รหัสโครงการ'],
                    // เรียงตามคอลัมน์ project_name_th ของ progress_reports เอง — คอลัมน์ที่แสดงจริง
                    // ในตาราง (researchProject.oname ?? project_name_th) มาจาก related table ที่ join
                    // ไม่ถึง จึงใช้ค่านี้แทนโดยประมาณ
                    'project_name_th' => [
                        'asc' => ['project_name_th' => SORT_ASC],
                        'desc' => ['project_name_th' => SORT_DESC],
                        'label' => 'ชื่อโครงการ',
                    ],
                    'pi_name' => ['label' => 'หัวหน้าโครงการ'],
                    'status' => ['label' => 'สถานะ'],
                    'review_status' => ['label' => 'ผลการตรวจสอบ'],
                    'created_at' => ['label' => 'วันที่ส่ง'],
                ],
                'defaultOrder' => ['created_at' => SORT_DESC],
            ],
        ]);

        return $this->render('index', array_merge($stats, [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'statusFilter' => $statusFilter,
            'reviewStatusFilter' => $reviewStatusFilter,
            'recentProjects' => $recentProjects,
            'recentProjectsPagination' => $pagination,
            'recentReports' => $recentReportsProvider->getModels(),
            'recentReportsProvider' => $recentReportsProvider,
            'recentProjectsSort' => $sort,
        ]));
    }

    /**
     * ส่งออกรายงานเดียวกับที่เห็นบนหน้า Dashboard (สรุปสถิติ + รายงานทั้งหมดในช่วงที่เลือก ไม่จำกัดแค่
     * หน้าแรกของ pagination) เป็น PDF — ใช้ mPDF พร้อมฟอนต์ Sarabun ที่ฝังไว้เอง (ไม่ใช่ฟอนต์ core ของ
     * mPDF เพราะฟอนต์ default ของ mPDF สำหรับภาษาไทย ('garuda') ไม่ได้ติดมาด้วย)
     */
    public function actionExportPdf()
    {
        [$startDate, $endDate] = $this->resolveDateRange();
        $stats = $this->buildStats($startDate, $endDate);
        $reports = $this->recentReportsQuery($startDate, $endDate)->orderBy(['created_at' => SORT_DESC])->all();
        $projects = $this->recentProjectsQuery($startDate, $endDate)
            ->orderBy(['latest_submitted_at' => SORT_DESC])
            ->all();

        $html = $this->renderPartial('_export-pdf', array_merge($stats, [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reports' => $reports,
            'projects' => $projects,
        ]));

        $mpdf = PdfHelper::create();
        $mpdf->WriteHTML($html);
        $pdfContent = $mpdf->Output('', Destination::STRING_RETURN);

        return Yii::$app->response->sendContentAsFile(
            $pdfContent,
            "dashboard-{$startDate}_ถึง_{$endDate}.pdf",
            ['mimeType' => 'application/pdf']
        );
    }

    /**
     * ส่งออกรายงานเดียวกันเป็น Excel (.xlsx) — 3 ชีท: สรุปภาพรวม, รายการโครงการ (group by โครงการ
     * แบบเดียวกับตารางบนหน้าเว็บ) และ รายงาน (รายฉบับรายงานทั้งหมดในช่วงที่เลือก)
     */
    public function actionExportExcel()
    {
        [$startDate, $endDate] = $this->resolveDateRange();
        $stats = $this->buildStats($startDate, $endDate);
        $reports = $this->recentReportsQuery($startDate, $endDate)->orderBy(['created_at' => SORT_DESC])->all();
        $projects = $this->recentProjectsQuery($startDate, $endDate)
            ->orderBy(['latest_submitted_at' => SORT_DESC])
            ->all();

        $spreadsheet = new Spreadsheet();

        $summary = $spreadsheet->getActiveSheet();
        $summary->setTitle('สรุปภาพรวม');
        $summary->setCellValue('A1', 'Dashboard — สรุปภาพรวม');
        $summary->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $summary->setCellValue('A2', 'ช่วงวันที่: ' . ThaiDate::format($startDate, false) . ' — ' . ThaiDate::format($endDate, false));

        $row = 4;
        $summaryRows = [
            ['จำนวนโครงการทั้งหมดที่ส่งรายงาน', $stats['totalProjects']],
            ['จำนวนรายงานที่ส่งเข้ามาทั้งหมด', $stats['totalReports']],
        ];
        foreach ($summaryRows as [$label, $value]) {
            $summary->setCellValue("A{$row}", $label);
            $summary->setCellValue("B{$row}", $value);
            $row++;
        }

        $row++;
        $summary->setCellValue("A{$row}", 'สถานะการดำเนินโครงการ (นับจากจำนวนรายงานทั้งหมดในช่วงที่เลือก)');
        $summary->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        foreach ($stats['statusLabels'] as $key => $label) {
            $summary->setCellValue("A{$row}", $label);
            $summary->setCellValue("B{$row}", $stats['statusCounts'][$key]);
            $row++;
        }

        $row++;
        $summary->setCellValue("A{$row}", 'ผลการตรวจสอบ (นับจากจำนวนรายงานทั้งหมดในช่วงที่เลือก)');
        $summary->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        foreach ($stats['reviewStatusLabels'] as $key => $label) {
            $summary->setCellValue("A{$row}", $label);
            $summary->setCellValue("B{$row}", $stats['reviewStatusCounts'][$key]);
            $row++;
        }

        $row++;
        $summary->setCellValue("A{$row}", 'สถิติเพิ่มเติม');
        $summary->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        $extraRows = [
            ['จำนวนสัตว์ทดลองที่ใช้จริงรวม (หน่วยตัว)', $stats['animalUsedByUnit']['head']],
            ['จำนวนสัตว์ทดลองที่ใช้จริงรวม (หน่วยมิลลิลิตร)', $stats['animalUsedByUnit']['ml']],
            ['จำนวนสัตว์ทดลองที่ใช้จริงรวม (หน่วยลิตร)', $stats['animalUsedByUnit']['l']],
            ['จำนวนสัตว์ทดลองที่ใช้จริงรวม (หน่วยกิโลกรัม)', $stats['animalUsedByUnit']['kg']],
            ['ผลงานตีพิมพ์ทั้งหมด (ระดับชาติ)', $stats['publicationsByLevel']['national']],
            ['ผลงานตีพิมพ์ทั้งหมด (นานาชาติ)', $stats['publicationsByLevel']['international']],
            ['การยื่นจดทรัพย์สินทางปัญญาทั้งหมด', $stats['ipFilingsTotal']],
        ];
        foreach ($extraRows as [$label, $value]) {
            $summary->setCellValue("A{$row}", $label);
            $summary->setCellValue("B{$row}", $value);
            $row++;
        }
        $summary->getColumnDimension('A')->setWidth(50);
        $summary->getColumnDimension('B')->setWidth(15);

        $projectsSheet = $spreadsheet->createSheet();
        $projectsSheet->setTitle('รายการโครงการ');
        $projectHeaders = ['รหัสโครงการ', 'ชื่อโครงการ', 'หัวหน้าโครงการ', 'สถานะ', 'ผลการตรวจสอบ', 'จำนวนรายงานในช่วงนี้', 'ส่งล่าสุดเมื่อ'];
        foreach ($projectHeaders as $i => $header) {
            $projectsSheet->setCellValue([$i + 1, 1], $header);
        }
        $projectsSheet->getStyle('A1:G1')->getFont()->setBold(true);

        $pr = 2;
        foreach ($projects as $project) {
            $projectsSheet->setCellValue([1, $pr], $project['project_code'] ?: '-');
            $projectsSheet->setCellValue([2, $pr], $project['oname']);
            $projectsSheet->setCellValue([3, $pr], $project['m_pro_th']);
            $projectsSheet->setCellValue([4, $pr], $stats['statusLabels'][$project['latest_status']] ?? $project['latest_status']);
            $projectsSheet->setCellValue([5, $pr], $stats['reviewStatusLabels'][$project['latest_review_status']] ?? $project['latest_review_status']);
            $projectsSheet->setCellValue([6, $pr], (int) $project['report_count']);
            $projectsSheet->setCellValue([7, $pr], ThaiDate::format($project['latest_submitted_at']));
            $pr++;
        }
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
            $projectsSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $detail = $spreadsheet->createSheet();
        $detail->setTitle('รายงาน');
        $headers = ['รหัสโครงการ', 'ชื่อโครงการ', 'หัวหน้าโครงการ', 'สถานะ', 'ผลการตรวจสอบ', 'วันที่ส่ง'];
        foreach ($headers as $i => $header) {
            $detail->setCellValue([$i + 1, 1], $header);
        }
        $detail->getStyle('A1:F1')->getFont()->setBold(true);

        $r = 2;
        foreach ($reports as $report) {
            $detail->setCellValue([1, $r], $report->project_code ?: '-');
            $detail->setCellValue([2, $r], $report->researchProject->oname ?? $report->project_name_th);
            $detail->setCellValue([3, $r], $report->pi_name);
            $detail->setCellValue([4, $r], $stats['statusLabels'][$report->status] ?? $report->status);
            $detail->setCellValue([5, $r], ProgressReport::REVIEW_STATUS_LABELS[$report->review_status] ?? $report->review_status);
            $detail->setCellValue([6, $r], ThaiDate::format($report->created_at));
            $r++;
        }
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
            $detail->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $tmpFile = Yii::getAlias('@runtime') . '/dashboard-export-' . uniqid() . '.xlsx';
        $writer->save($tmpFile);
        $content = file_get_contents($tmpFile);
        unlink($tmpFile);

        return Yii::$app->response->sendContentAsFile(
            $content,
            "dashboard-{$startDate}_ถึง_{$endDate}.xlsx",
            ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    private function resolveDateRange(): array
    {
        $startDate = DateHelper::parseOrDefault(Yii::$app->request->get('start_date'), date('Y-m-01'));
        $endDate = DateHelper::parseOrDefault(Yii::$app->request->get('end_date'), date('Y-m-d'));

        // สลับให้ start <= end เสมอ เผื่อผู้ใช้กรอกช่วงกลับด้าน (แบบเดียวกับ report/index)
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    /**
     * รายชื่อรายงานแบบรายฉบับ (หนึ่งแถวต่อหนึ่งรายงาน) — ใช้ทั้งตาราง "รายงานล่าสุด" บนหน้าเว็บ (ผ่าน
     * ActiveDataProvider ใน actionIndex()) และไฟล์ export (PDF/Excel) ส่วนตารางรายโครงการ (group by
     * oid) แยกไปใช้ recentProjectsQuery() ต่างหาก ไม่ใส่ orderBy() ในนี้ตายตัว ผู้เรียกต้อง orderBy()
     * เพิ่มเองที่ปลายทาง (ActiveDataProvider ใช้ Sort component กำหนดให้ ส่วน export actions ใส่เอง)
     */
    private function recentReportsQuery(string $startDate, string $endDate)
    {
        return ProgressReport::find()
            ->with('researchProject')
            ->where(['between', 'created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    }

    /**
     * รายชื่อโครงการแบบเดียวกับ ReportController::actionIndex() — หนึ่งแถวต่อหนึ่งโครงการ (ไม่ใช่ต่อ
     * รายงาน) กรองด้วยช่วงเวลาที่มีการส่งรายงานเข้ามา (report_count/latest_submitted_at คำนวณจาก
     * รายงานในช่วงนี้เท่านั้น) แต่ latest_status/latest_review_status ใช้รายงานฉบับล่าสุดของโครงการ
     * แบบไม่จำกัดช่วงวันที่ (เหมือน report/index) เพื่อสะท้อนสถานะปัจจุบันจริงของโครงการ ไม่ใช่แค่
     * สถานะ ณ ช่วงที่เลือกดู
     */
    private function recentProjectsQuery(string $startDate, string $endDate): Query
    {
        return (new Query())
            ->select([
                'rp.oid',
                'rp.oname',
                'rp.m_pro_th',
                'project_code' => '(SELECT pr2.project_code FROM {{%progress_reports}} pr2'
                    . ' WHERE pr2.oid = rp.oid ORDER BY pr2.created_at DESC LIMIT 1)',
                'latest_status' => '(SELECT pr3.status FROM {{%progress_reports}} pr3'
                    . ' WHERE pr3.oid = rp.oid ORDER BY pr3.created_at DESC LIMIT 1)',
                'latest_review_status' => '(SELECT pr4.review_status FROM {{%progress_reports}} pr4'
                    . ' WHERE pr4.oid = rp.oid ORDER BY pr4.created_at DESC LIMIT 1)',
                'report_count' => 'COUNT(pr.id)',
                'latest_submitted_at' => 'MAX(pr.created_at)',
            ])
            ->from(['pr' => '{{%progress_reports}}'])
            ->innerJoin(['rp' => '{{%research_projects}}'], 'rp.oid = pr.oid')
            ->where(['between', 'pr.created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy(['rp.oid', 'rp.oname', 'rp.m_pro_th']);
    }

    /**
     * สถิติทั้งหมดของ Dashboard (ยกเว้นรายชื่อรายงาน — แยกไว้ต่างหากเพราะหน้าเว็บใช้แบบแบ่งหน้า
     * ส่วนไฟล์ export ใช้แบบเต็มไม่แบ่งหน้า) ใช้ร่วมกันทั้ง actionIndex/actionExportPdf/actionExportExcel
     */
    private function buildStats(string $startDate, string $endDate): array
    {
        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';

        $totalProjects = (new Query())
            ->from('{{%progress_reports}}')
            ->select('oid')
            ->distinct()
            ->where(['between', 'created_at', $startDateTime, $endDateTime])
            ->count();

        $totalReports = ProgressReport::find()
            ->where(['between', 'created_at', $startDateTime, $endDateTime])
            ->count();

        $statusLabels = [
            'not_started' => 'ยังไม่เริ่มดำเนินการ',
            'in_progress' => 'อยู่ระหว่างดำเนินการ',
            'completed' => 'ดำเนินการเสร็จสิ้น',
            'terminated_early' => 'ยุติโครงการก่อนกำหนด',
            'cancelled' => 'ยกเลิกโครงการ',
        ];

        // นับ "จำนวนรายงาน" ต่อสถานะตรงๆ ในช่วงเวลาที่เลือก (ตัวเลขในกราฟจะตรงกับที่นับได้จากตาราง
        // "รายงานล่าสุดในช่วงที่เลือก" ด้านล่าง) — เดิมเคยนับแบบ dedupe ต่อโครงการโดยใช้สถานะของรายงาน
        // ฉบับล่าสุดของแต่ละ oid ทำให้โครงการที่ส่งหลายฉบับในช่วงเดียวกันถูกนับแค่ 1 ครั้ง ตัวเลขเลย
        // น้อยกว่าที่นับได้จริงจากรายการ ผู้ใช้แจ้งว่าดูขัดแย้งกับรายการด้านล่างจึงเปลี่ยนมานับทุกรายงาน
        $statusCountsRaw = (new Query())
            ->select(['status', 'cnt' => 'COUNT(*)'])
            ->from('{{%progress_reports}}')
            ->where(['between', 'created_at', $startDateTime, $endDateTime])
            ->groupBy('status')
            ->all();

        $statusCounts = array_fill_keys(array_keys($statusLabels), 0);
        foreach ($statusCountsRaw as $row) {
            if (isset($statusCounts[$row['status']])) {
                $statusCounts[$row['status']] = (int) $row['cnt'];
            }
        }

        // เช่นเดียวกับ statusCounts ด้านบน — นับจำนวนรายงานต่อ "ผลการตรวจสอบ" ตรงๆ ในช่วงเวลาที่เลือก
        $reviewStatusCountsRaw = (new Query())
            ->select(['review_status', 'cnt' => 'COUNT(*)'])
            ->from('{{%progress_reports}}')
            ->where(['between', 'created_at', $startDateTime, $endDateTime])
            ->groupBy('review_status')
            ->all();

        $reviewStatusCounts = array_fill_keys(array_keys(ProgressReport::REVIEW_STATUS_LABELS), 0);
        foreach ($reviewStatusCountsRaw as $row) {
            if (isset($reviewStatusCounts[$row['review_status']])) {
                $reviewStatusCounts[$row['review_status']] = (int) $row['cnt'];
            }
        }

        // ต่างจาก statusCounts ด้านบน (นับ "รายงาน") — อันนี้นับ "โครงการ" ตาม latest_status ของแต่ละ
        // oid (ประชากรเดียวกับที่ totalProjects นับ จึงรวมกันได้เท่ากับ totalProjects พอดี) ห่อ
        // recentProjectsQuery() เป็น derived table แล้ว group ต่อด้วย latest_status
        $projectStatusCountsRaw = (new Query())
            ->select(['latest_status', 'cnt' => 'COUNT(*)'])
            ->from(['t' => $this->recentProjectsQuery($startDate, $endDate)])
            ->groupBy('latest_status')
            ->all();

        $projectStatusCounts = array_fill_keys(array_keys($statusLabels), 0);
        foreach ($projectStatusCountsRaw as $row) {
            if (isset($projectStatusCounts[$row['latest_status']])) {
                $projectStatusCounts[$row['latest_status']] = (int) $row['cnt'];
            }
        }

        // รวมจำนวนสัตว์ใช้จริงแยกตามหน่วย — บวกข้ามหน่วยกันไม่ได้เพราะคนละมิติ (ดูเหตุผลเดียวกับที่
        // views/report/view.php ใช้ตอนคำนวณ "จำนวนที่เหลือ") จึง GROUP BY หน่วยแล้วแสดงแยกทีละหน่วย
        // แทนที่จะรวมเป็นก้อนเดียว
        $animalUsedByUnitRaw = (new Query())
            ->select(['animal_used_unit', 'total' => 'SUM(animal_used_male + animal_used_female)'])
            ->from('{{%progress_reports}}')
            ->where(['between', 'created_at', $startDateTime, $endDateTime])
            ->groupBy('animal_used_unit')
            ->all();

        $animalUsedByUnit = array_fill_keys(array_keys(ProgressReport::UNIT_LABELS), 0);
        foreach ($animalUsedByUnitRaw as $row) {
            if (isset($animalUsedByUnit[$row['animal_used_unit']])) {
                $animalUsedByUnit[$row['animal_used_unit']] = (int) $row['total'];
            }
        }
        $animalUsedTotal = $animalUsedByUnit['head'];

        // ผลงานตีพิมพ์/ทรัพย์สินทางปัญญา ผูกกับรายงานผ่าน report_id — กรองตามช่วงเวลาต้อง join
        // กลับไปที่ progress_reports.created_at ของรายงานฉบับนั้นๆ
        $publicationsTotal = (new Query())
            ->from(['rpub' => '{{%report_publications}}'])
            ->innerJoin(['pr' => '{{%progress_reports}}'], 'pr.id = rpub.report_id')
            ->where(['between', 'pr.created_at', $startDateTime, $endDateTime])
            ->count();

        $publicationsByLevel = ['national' => 0, 'international' => 0];
        $publicationsByLevelRaw = (new Query())
            ->select(['level' => 'rpub.level', 'cnt' => 'COUNT(*)'])
            ->from(['rpub' => '{{%report_publications}}'])
            ->innerJoin(['pr' => '{{%progress_reports}}'], 'pr.id = rpub.report_id')
            ->where(['between', 'pr.created_at', $startDateTime, $endDateTime])
            ->groupBy('rpub.level')
            ->all();
        foreach ($publicationsByLevelRaw as $row) {
            if (isset($publicationsByLevel[$row['level']])) {
                $publicationsByLevel[$row['level']] = (int) $row['cnt'];
            }
        }

        $ipFilingsTotal = (new Query())
            ->from(['rip' => '{{%report_ip_filings}}'])
            ->innerJoin(['pr' => '{{%progress_reports}}'], 'pr.id = rip.report_id')
            ->where(['between', 'pr.created_at', $startDateTime, $endDateTime])
            ->count();

        return [
            'totalProjects' => $totalProjects,
            'totalReports' => $totalReports,
            'statusLabels' => $statusLabels,
            'statusCounts' => $statusCounts,
            'reviewStatusLabels' => ProgressReport::REVIEW_STATUS_LABELS,
            'reviewStatusCounts' => $reviewStatusCounts,
            'projectStatusCounts' => $projectStatusCounts,
            'animalUsedTotal' => $animalUsedTotal,
            'animalUsedByUnit' => $animalUsedByUnit,
            'publicationsTotal' => $publicationsTotal,
            'publicationsByLevel' => $publicationsByLevel,
            'ipFilingsTotal' => $ipFilingsTotal,
        ];
    }
}
