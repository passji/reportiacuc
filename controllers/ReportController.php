<?php

namespace app\controllers;

use Yii;
use yii\base\DynamicModel;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;
use yii\db\Query;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use app\models\ResearchProject;
use app\models\ProgressReport;
use app\models\ReportPublication;
use app\models\ReportIpFiling;
use app\models\ReportAttachment;
use app\models\ReportNotification;
use app\models\Admin;
use app\helpers\DateHelper;
use app\helpers\PdfHelper;
use app\helpers\ThaiDate;
use app\services\ProjectSourceService;
use app\services\ReminderService;
use Mpdf\Output\Destination;

class ReportController extends SecureController
{

    public function actionIndex()
    {
        // หน้านี้ (รายการโครงการ — ทุกโครงการในระบบ) ย้ายเข้ากลุ่มเมนู ADMIN แล้ว จำกัดสิทธิ์จริง
        // ต่างจาก actionMyReports() ที่ผู้ใช้ทั่วไปยังเข้าดูรายงานของตัวเองได้ตามปกติ
        if (!Admin::isEmailAdmin((string) Yii::$app->session->get('sso_email'))) {
            Yii::$app->session->setFlash('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (สำหรับผู้ดูแลระบบเท่านั้น)');
            return $this->redirect(['/report/my-reports']);
        }

        $defaultStart = date('Y-m-01');
        $defaultEnd = date('Y-m-d');

        $startDate = DateHelper::parseOrDefault(Yii::$app->request->get('start_date'), $defaultStart);
        $endDate = DateHelper::parseOrDefault(Yii::$app->request->get('end_date'), $defaultEnd);

        // สลับให้ start <= end เสมอ เผื่อผู้ใช้กรอกช่วงกลับด้าน
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $search = trim((string) Yii::$app->request->get('search', ''));

        // หนึ่งแถวต่อหนึ่งโครงการ (ไม่ใช่ต่อรายงาน) — โครงการเดียวส่งหลายฉบับในช่วงเดียวกันได้
        // รหัสโครงการ (project_code) มาจากรายงานฉบับล่าสุดของ oid นั้น — เป็นข้อมูลที่ผู้ใช้กรอก
        // เอง อยู่ใน progress_reports ไม่ใช่ oid ภายในของเราใน research_projects
        $query = (new Query())
            ->select([
                'rp.oid',
                'rp.oname',
                'rp.m_pro_th',
                'project_code' => '(SELECT pr2.project_code FROM {{%progress_reports}} pr2'
                    . ' WHERE pr2.oid = rp.oid ORDER BY pr2.created_at DESC LIMIT 1)',
                'latest_status' => '(SELECT pr3.status FROM {{%progress_reports}} pr3'
                    . ' WHERE pr3.oid = rp.oid ORDER BY pr3.created_at DESC LIMIT 1)',
                'report_count' => 'COUNT(pr.id)',
                'latest_submitted_at' => 'MAX(pr.created_at)',
            ])
            ->from(['pr' => '{{%progress_reports}}'])
            ->innerJoin(['rp' => '{{%research_projects}}'], 'rp.oid = pr.oid')
            ->where(['between', 'pr.created_at', $startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy(['rp.oid', 'rp.oname', 'rp.m_pro_th']);

        // ค้นหาจากชื่อโครงการ (research_projects.oname) หรือรหัสโครงการ (progress_reports.project_code
        // เช่น "จส.มข. 7/61") — ไม่ใช่ oid ภายในของระบบเรา
        if ($search !== '') {
            $query->andWhere(['or',
                ['like', 'rp.oname', $search],
                ['like', 'pr.project_code', $search],
            ]);
        }

        // ->count() บน Query ที่มี groupBy() จะได้จำนวนต่อกลุ่ม ไม่ใช่จำนวนกลุ่มทั้งหมด ต้อง clone
        // คิวรีเดิม (ก่อนใส่ orderBy/limit) มาห่อเป็น derived table แล้วนับอีกชั้นเพื่อให้ได้จำนวน
        // โครงการทั้งหมดที่จะใช้คำนวณ pagination
        $countQuery = clone $query;
        $totalCount = (new Query())->from(['t' => $countQuery])->count();

        $pagination = new Pagination([
            'totalCount' => $totalCount,
            'pageSize' => 25,
        ]);

        $projects = $query
            ->orderBy(['latest_submitted_at' => SORT_DESC])
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        // โครงการที่ยังไม่มีรายงานเลยสักฉบับ — INNER JOIN ด้านบนไม่มีทางแสดงกลุ่มนี้ได้
        // (ไม่มีแถว progress_reports ให้ join) จึงต้องคิวรีแยกต่างหาก ไม่กรองตามช่วงวันที่/คำค้นหา
        // เพราะยังไม่มีรายงานให้กรองตามวันที่ หรือรหัสโครงการที่จะค้นหาเลย
        // pageParam แยกจาก pagination ของตารางบน ("page") กันไม่ให้เลขหน้าชนกันเวลาทั้งสองตาราง
        // อยู่ในหน้าเดียวกัน
        $projectsWithoutReportsProvider = new ActiveDataProvider([
            'query' => ResearchProject::find()
                ->where(
                    'NOT EXISTS (SELECT 1 FROM {{%progress_reports}} pr WHERE pr.oid = {{%research_projects}}.[[oid]])'
                )
                ->orderBy(['oname' => SORT_ASC]),
            'pagination' => [
                'pageParam' => 'no-report-page',
                'pageSize' => 25,
            ],
            'sort' => false,
        ]);
        $projectsWithoutReports = $projectsWithoutReportsProvider->getModels();

        return $this->render('index', [
            'projects' => $projects,
            'pagination' => $pagination,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'search' => $search,
            'projectsWithoutReports' => $projectsWithoutReports,
            'projectsWithoutReportsProvider' => $projectsWithoutReportsProvider,
        ]);
    }

    /**
     * รายงานทั้งหมดที่ผู้ใช้ที่ login อยู่เป็นคนส่งเอง (submitted_by_email ตรงกับ session) —
     * ต่างจาก actionIndex() ซึ่งแสดงเป็นรายโครงการ (group by oid) หน้านี้แสดงเป็นรายฉบับรายงาน
     */
    public function actionMyReports()
    {
        $reportsProvider = new ActiveDataProvider([
            'query' => ProgressReport::find()
                ->where(['submitted_by_email' => (string) Yii::$app->session->get('sso_email')])
                ->with('researchProject')
                ->orderBy(['created_at' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 25,
            ],
            'sort' => false,
        ]);

        return $this->render('my-reports', [
            'reports' => $reportsProvider->getModels(),
            'reportsProvider' => $reportsProvider,
        ]);
    }

    public function actionCreate($oid)
    {
        $isPost = Yii::$app->request->isPost;
        $existedBefore = ResearchProject::find()->where(['oid' => $oid])->exists();

        $project = ProjectSourceService::fetchAndUpsert($oid);
        if (!$project) {
            throw new NotFoundHttpException('ไม่พบโครงการนี้ หรือระบบต้นทางไม่ตอบสนอง');
        }

        // แสดง flash เฉพาะตอนเปิดฟอร์มครั้งแรก (GET) — ไม่ต้องซ้ำทุกครั้งที่กด "ส่งรายงาน" แล้ว validate ไม่ผ่าน
        if (!$isPost) {
            Yii::$app->session->setFlash(
                'success',
                $existedBefore
                    ? 'อัปเดตข้อมูลโครงการจากระบบ A เรียบร้อยแล้ว'
                    : 'บันทึกข้อมูลโครงการใหม่จากระบบ A เรียบร้อยแล้ว'
            );
        }

        $model = new ProgressReport();
        $model->oid = $project->oid;
        $model->pi_name = (string) $project->m_pro_th;
        $model->project_name_th = (string) $project->oname;
        $model->project_name_en = (string) $project->oname_en;
        $model->meeting_ref = trim(sprintf('ครั้งที่ %s วันที่ %s', $project->meeting_no, ThaiDate::format($project->meeting_date)));
        $model->project_code = (string) ($project->getRawData()['meet_summary'] ?? '');

        $rawProjectData = $project->getRawData();
        $model->animal_requested_male = (int) ($rawProjectData['male_total'] ?? 0);
        $model->animal_requested_female = (int) ($rawProjectData['female_total'] ?? 0);
        $model->animal_requested_note = $project->getApprovedAnimalSummaryText();
        $model->animal_used_unit = 'head';
        $model->post_study_handling = 'as_approved';

        $model->submitted_by_email = (string) Yii::$app->session->get('sso_email');
        $model->status_flag = 'submitted';

        $publications = [new ReportPublication()];
        $ipFilings = [new ReportIpFiling()];
        $attachmentErrors = [];

        if ($model->load(Yii::$app->request->post())) {
            // ข้อ 3.1 ห้ามแก้ไขจากฟอร์ม — ต้องอ้างอิงข้อมูลที่อนุมัติจากระบบ A เท่านั้น
            // บังคับค่าฝั่งเซิร์ฟเวอร์ทับสิ่งที่โพสต์มาเสมอ (readonly ใน view เป็นแค่ UI hint,
            // กันไม่ให้ผู้ใช้แก้ไขผ่าน DOM/ยิง POST ตรงมาเปลี่ยนตัวเลขที่อนุมัติได้จริง ๆ)
            $model->animal_requested_male = (int) ($rawProjectData['male_total'] ?? 0);
            $model->animal_requested_female = (int) ($rawProjectData['female_total'] ?? 0);
            $model->animal_requested_note = $project->getApprovedAnimalSummaryText();

            $publications = $this->buildSubModels(
                ReportPublication::class,
                (array) Yii::$app->request->post('ReportPublication', [])
            );
            $ipFilings = $this->buildSubModels(
                ReportIpFiling::class,
                (array) Yii::$app->request->post('ReportIpFiling', [])
            );

            // เอกสารแนบ (PDF) — ไม่ใช่ attribute ของ ProgressReport เองจึงตรวจสอบแยกผ่าน
            // DynamicModel ชั่วคราว ใช้ FileValidator ของ Yii2 (เช็คทั้งนามสกุลและ MIME
            // type จริงของไฟล์ ไม่ใช่เชื่อชื่อไฟล์จากฝั่ง client เฉย ๆ)
            $attachmentFiles = UploadedFile::getInstancesByName('attachments');
            $attachmentsModel = new DynamicModel(['attachments' => $attachmentFiles]);
            $attachmentsModel->addRule('attachments', 'file', [
                'extensions' => 'pdf',
                'mimeTypes' => 'application/pdf',
                'checkExtensionByMimeType' => true,
                'maxSize' => 10 * 1024 * 1024,
                'maxFiles' => 10,
                'skipOnEmpty' => true,
            ]);

            $valid = $model->validate();
            $valid = $attachmentsModel->validate() && $valid;
            $attachmentErrors = $attachmentsModel->getErrors('attachments');

            // ไฟล์ PDF แนบแยกรายข้อ 6.1/6.2 — คนละ input จาก attachments[] ด้านบน (ผูกกับ index
            // ของแต่ละแถว ไม่ใช่ระดับทั้งฉบับ) ดึงเป็น array คู่ index เดียวกับ $publications/$ipFilings
            // ไว้ก่อน จะได้ใช้ได้ทั้งตอน validate และตอน save
            $publicationFiles = [];
            foreach ($publications as $i => $pub) {
                $publicationFiles[$i] = UploadedFile::getInstanceByName("ReportPublication[$i][pdf_file]");
                if ($publicationFiles[$i] !== null) {
                    $valid = $this->validateOptionalPdf($publicationFiles[$i], 'ไฟล์แนบผลงานตีพิมพ์รายการที่ ' . ($i + 1), $attachmentErrors) && $valid;
                }
            }
            $ipFilingFiles = [];
            foreach ($ipFilings as $i => $ip) {
                $ipFilingFiles[$i] = UploadedFile::getInstanceByName("ReportIpFiling[$i][pdf_file]");
                if ($ipFilingFiles[$i] !== null) {
                    $valid = $this->validateOptionalPdf($ipFilingFiles[$i], 'ไฟล์แนบทรัพย์สินทางปัญญารายการที่ ' . ($i + 1), $attachmentErrors) && $valid;
                }
            }

            // ข้อ 6.1/6.2 เป็นสาขาของข้อ 6 — validate ก็ต่อเมื่อตอบ "มีการตีพิมพ์เผยแพร่" เท่านั้น
            // และข้ามแถวที่ผู้ใช้ไม่ได้กรอกอะไรเลย (isBlank) ไม่บังคับให้กรอกครบทุกแถว
            if ($model->has_publication === 'yes') {
                foreach ($publications as $pub) {
                    if (!$pub->isBlank()) {
                        $valid = $pub->validate() && $valid;
                    }
                }
                foreach ($ipFilings as $ip) {
                    if (!$ip->isBlank()) {
                        $valid = $ip->validate() && $valid;
                    }
                }
            }

            if ($valid) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    if (!$model->save(false)) {
                        throw new \RuntimeException('บันทึกรายงานไม่สำเร็จ');
                    }

                    if ($model->has_publication === 'yes') {
                        foreach ($publications as $i => $pub) {
                            if ($pub->isBlank()) {
                                continue;
                            }
                            $pub->report_id = $model->id;
                            if (!$pub->save(false)) {
                                throw new \RuntimeException('บันทึกผลงานตีพิมพ์ไม่สำเร็จ');
                            }
                            if ($publicationFiles[$i] ?? null) {
                                $this->saveItemAttachment($publicationFiles[$i], $model->id, ['publication_id' => $pub->id]);
                            }
                        }
                        foreach ($ipFilings as $i => $ip) {
                            if ($ip->isBlank()) {
                                continue;
                            }
                            $ip->report_id = $model->id;
                            if (!$ip->save(false)) {
                                throw new \RuntimeException('บันทึกข้อมูลทรัพย์สินทางปัญญาไม่สำเร็จ');
                            }
                            if ($ipFilingFiles[$i] ?? null) {
                                $this->saveItemAttachment($ipFilingFiles[$i], $model->id, ['ip_filing_id' => $ip->id]);
                            }
                        }
                    }

                    if ($attachmentFiles) {
                        foreach ($attachmentFiles as $file) {
                            $this->saveItemAttachment($file, $model->id, []);
                        }
                    }

                    $transaction->commit();
                    return $this->redirect(['view', 'id' => $model->id]);
                } catch (\Throwable $e) {
                    $transaction->rollBack();
                    Yii::error((string) $e, __METHOD__);
                    Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาดขณะบันทึกรายงาน กรุณาลองใหม่อีกครั้ง');
                }
            }
        }

        return $this->render('create', [
            'project' => $project,
            'model' => $model,
            'publications' => $publications,
            'ipFilings' => $ipFilings,
            'attachmentErrors' => $attachmentErrors,
        ]);
    }

    public function actionView($id)
    {
        $model = ProgressReport::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('ไม่พบรายงานนี้');
        }

        return $this->render('view', ['model' => $model, 'rows' => $this->buildReportRows($model)]);
    }

    /**
     * ส่งออกรายงานฉบับเดียวกับที่เห็นบน report/view เป็น PDF — เปิดให้ทุกคนที่เข้าดูหน้า report/view
     * ได้อยู่แล้ว ไม่ได้จำกัดเฉพาะ admin เหมือนหน้า export อื่น ๆ ในระบบ เพราะตัวหน้า report/view เอง
     * ก็ไม่ได้ gate ไว้เฉพาะ admin เช่นกัน
     */
    public function actionExportPdf($id)
    {
        $model = ProgressReport::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('ไม่พบรายงานนี้');
        }

        $html = $this->renderPartial('_view-export-pdf', [
            'model' => $model,
            'rows' => $this->buildReportRows($model),
        ]);

        $mpdf = PdfHelper::create();
        $mpdf->WriteHTML($html);
        $pdfContent = $mpdf->Output('', Destination::STRING_RETURN);

        return Yii::$app->response->sendContentAsFile(
            $pdfContent,
            "report-{$model->id}.pdf",
            ['mimeType' => 'application/pdf']
        );
    }

    /**
     * สร้าง array แถวข้อมูลรายงาน (ข้อ 1-6) แบบเดียวกับที่ report/view.php แสดง — แยกออกมาจากตัว view
     * เพื่อให้ actionExportPdf() เอาไปสร้าง PDF ด้วยข้อมูลชุดเดียวกันได้โดยไม่ต้องเขียน logic
     * เงื่อนไข (ข้อไหนต้องมีรายละเอียดเพิ่มเมื่อไหร่) ซ้ำอีกชุด
     */
    private function buildReportRows(ProgressReport $model): array
    {
        $statusLabels = [
            'not_started' => 'ยังไม่เริ่มดำเนินการ',
            'in_progress' => 'อยู่ระหว่างดำเนินการ',
            'completed' => 'ดำเนินการเสร็จสิ้น',
            'terminated_early' => 'ยุติโครงการก่อนกำหนด',
            'cancelled' => 'ยกเลิกโครงการ',
        ];
        $yesNoLabels = ['yes' => 'ใช่', 'no' => 'ไม่ใช่'];

        $rows = [
            'ข้อ 1: รหัสโครงการ' => $model->project_code,
            'ข้อ 2: เข้าประชุมครั้งที่ / วันที่พิจารณา' => $model->meeting_ref,
            'ข้อ 3: ชื่อหัวหน้าโครงการ' => $model->pi_name,
            'ข้อ 4: ชื่อโครงการ (ภาษาไทย)' => $model->project_name_th,
            'ข้อ 5: ชื่อโครงการ (ภาษาอังกฤษ)' => $model->project_name_en,
            'มีการเปลี่ยนแปลงวัตถุประสงค์หรือไม่' => $model->objective_changed === 'changed' ? 'มีการเปลี่ยนแปลง' : 'เหมือนเดิม',
        ];
        if ($model->objective_changed === 'changed') {
            $rows['รายละเอียดการเปลี่ยนแปลงวัตถุประสงค์'] = $model->objective_change_detail;
        }
        $rows['ข้อ 2.1: สถานะการดำเนินโครงการ'] = $statusLabels[$model->status] ?? $model->status;
        if ($model->status === 'not_started') {
            $rows['วันที่คาดว่าจะเริ่มดำเนินการ'] = ThaiDate::format($model->expected_start_date, false);
        } elseif ($model->status === 'in_progress') {
            $rows['วันที่คาดว่าจะเสร็จสิ้น'] = ThaiDate::format($model->expected_complete_date, false);
        } elseif ($model->status === 'completed') {
            $rows['วันที่ดำเนินการเสร็จสิ้น'] = ThaiDate::format($model->completed_date, false);
        }
        if (in_array($model->status, ['not_started', 'terminated_early', 'cancelled'], true)) {
            $rows['เหตุผลที่ยังไม่เริ่มดำเนินการ/ยุติ/ยกเลิกโครงการ'] = $model->stop_reason;
        }
        $rows['ข้อ 3.1: จำนวนสัตว์ที่ขอ/ได้รับอนุมัติ'] = sprintf(
            'ตัวผู้ %d ตัว, ตัวเมีย %d ตัว',
            $model->animal_requested_male,
            $model->animal_requested_female
        );
        if ($model->animal_requested_note) {
            $rows['หมายเหตุ (ข้อ 3.1)'] = $model->animal_requested_note;
        }
        $usedUnitLabel = ProgressReport::UNIT_LABELS[$model->animal_used_unit] ?? $model->animal_used_unit;
        $rows['ข้อ 3.2: จำนวนสัตว์ที่ใช้จริง'] = sprintf(
            'ตัวผู้ %d %s, ตัวเมีย %d %s',
            $model->animal_used_male,
            $usedUnitLabel,
            $model->animal_used_female,
            $usedUnitLabel
        );
        if ($model->animal_used_note) {
            $rows['หมายเหตุ (ข้อ 3.2)'] = $model->animal_used_note;
        }
        // เทียบ "จำนวนที่เหลือ" ได้เฉพาะตอนหน่วยเป็น "ตัว" เหมือนข้อ 3.1 — ถ้าหน่วยอื่น (มล./ล./กก.)
        // ตัวเลขคนละมิติกัน ลบกันไม่ได้
        if ($model->animal_used_unit === 'head') {
            $rows['จำนวนที่เหลือ (ตัวผู้ / ตัวเมีย)'] = sprintf(
                '%d / %d ตัว',
                $model->animal_requested_male - $model->animal_used_male,
                $model->animal_requested_female - $model->animal_used_female
            );
        }
        $rows['ข้อ 4.1: มีการเปลี่ยนแปลงวิธีการทดลองหรือไม่'] = $yesNoLabels[$model->method_changed] ?? $model->method_changed;
        if ($model->method_changed === 'yes') {
            $rows['รายละเอียดการเปลี่ยนแปลงวิธีการทดลอง'] = $model->method_change_detail;
        }
        $rows['ข้อ 4.2: การปฏิบัติต่อสัตว์หลังจากเสร็จสิ้นโครงการ'] =
            ProgressReport::POST_STUDY_HANDLING_LABELS[$model->post_study_handling] ?? $model->post_study_handling;
        if ($model->post_study_handling === 'changed') {
            $rows['รายละเอียดการเปลี่ยนแปลงวิธีปฏิบัติต่อสัตว์'] = $model->post_study_handling_detail;
        }
        $rows['ข้อ 4.3: มีเหตุการณ์ไม่พึงประสงค์เกิดขึ้นหรือไม่'] = $yesNoLabels[$model->adverse_event] ?? $model->adverse_event;
        if ($model->adverse_event === 'yes') {
            $rows['รายละเอียดเหตุการณ์ไม่พึงประสงค์'] = $model->adverse_event_detail;
        }
        $rows['ข้อ 4.4: มีการเปลี่ยนแปลงผู้ปฏิบัติงานหรือไม่'] = $yesNoLabels[$model->personnel_changed] ?? $model->personnel_changed;
        if ($model->personnel_changed === 'yes') {
            $rows['รายละเอียดการเปลี่ยนแปลงผู้ปฏิบัติงาน'] = $model->personnel_change_detail;
        }
        $rows['ข้อ 4.5: พบวิธีการทดแทนการใช้สัตว์ทดลองหรือไม่'] = $yesNoLabels[$model->alt_method_found] ?? $model->alt_method_found;
        if ($model->alt_method_found === 'yes') {
            $rows['รายละเอียดวิธีการทดแทน'] = $model->alt_method_detail;
        }
        $rows['ข้อ 5: สรุปผลการดำเนินงาน'] = $model->study_summary;
        $rows['ข้อ 6: การตีพิมพ์ เผยแพร่ หรือนำไปใช้ประโยชน์'] = $model->has_publication === 'yes'
            ? 'มีการตีพิมพ์เผยแพร่'
            : 'ไม่มีการตีพิมพ์เผยแพร่';
        $rows['ส่งรายงานโดย'] = $model->submitted_by_email;
        $rows['วันที่ส่งรายงาน'] = ThaiDate::format($model->created_at);

        return $rows;
    }

    /**
     * แอดมินกด "ตรวจแล้ว"/"ปฏิเสธ" ที่ report/view — ปฏิเสธต้องระบุเหตุผล (บังคับทั้งฝั่ง client
     * ใน view และ server ผ่าน rules() ของ ProgressReport) แล้วส่งอีเมลแจ้งผู้ส่งรายงานอัตโนมัติ
     */
    public function actionReviewDecision($id)
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException();
        }

        $model = ProgressReport::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('ไม่พบรายงานนี้');
        }

        if (!Admin::isEmailAdmin((string) Yii::$app->session->get('sso_email'))) {
            Yii::$app->session->setFlash('error', 'คุณไม่มีสิทธิ์ตรวจสอบรายงาน (สำหรับผู้ดูแลระบบเท่านั้น)');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $decision = Yii::$app->request->post('decision');
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            throw new BadRequestHttpException();
        }

        $model->review_status = $decision;
        $model->rejection_reason = $decision === 'rejected'
            ? trim((string) Yii::$app->request->post('rejection_reason', ''))
            : null;

        if (!$model->validate(['review_status', 'rejection_reason'])) {
            Yii::$app->session->setFlash('error', 'กรุณาระบุเหตุผลก่อนปฏิเสธรายงาน');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $model->reviewed_at = date('Y-m-d H:i:s');
        $model->reviewed_by = (string) Yii::$app->session->get('sso_email');
        $model->save(false);

        if ($decision === 'rejected') {
            $notification = ReminderService::sendRejectionNotice(
                $model,
                $model->rejection_reason,
                (string) Yii::$app->session->get('sso_email')
            );
            Yii::$app->session->setFlash(
                $notification->sent_status === 'sent' ? 'success' : 'warning',
                $notification->sent_status === 'sent'
                    ? 'บันทึกการปฏิเสธ และส่งอีเมลแจ้งผู้ส่งรายงานเรียบร้อยแล้ว'
                    : 'บันทึกการปฏิเสธแล้ว แต่ส่งอีเมลแจ้งผู้ส่งรายงานไม่สำเร็จ'
            );
        } else {
            Yii::$app->session->setFlash('success', 'บันทึกผลตรวจสอบเรียบร้อยแล้ว');
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    public function actionDownloadAttachment($id)
    {
        $attachment = ReportAttachment::findOne($id);
        if (!$attachment || !is_file($attachment->getStoragePath())) {
            throw new NotFoundHttpException('ไม่พบไฟล์แนบนี้');
        }

        return Yii::$app->response->sendFile(
            $attachment->getStoragePath(),
            $attachment->original_filename,
            ['mimeType' => 'application/pdf']
        );
    }

    /**
     * ดึง/อัปเดตข้อมูลโครงการจากระบบ A เข้า research_projects เฉยๆ โดยไม่ผ่านฟอร์มรายงาน — ใช้กรณี
     * โครงการได้รับอนุมัติแล้วแต่ยังไม่มีใครเปิดหน้า report/create เลยสักครั้ง (เช่น แอดมินอยากลงทะเบียน
     * โครงการไว้ล่วงหน้าเพื่อให้ค้นหา/ส่งอีเมลแจ้งเตือนได้ ทั้งที่ยังไม่มีรายงานความก้าวหน้าฉบับแรก)
     */
    public function actionFetch($oid)
    {
        $existedBefore = ResearchProject::find()->where(['oid' => $oid])->exists();

        $project = ProjectSourceService::fetchAndUpsert($oid);
        if (!$project) {
            Yii::$app->session->setFlash('error', 'ไม่พบโครงการนี้ หรือระบบต้นทางไม่ตอบสนอง กรุณาตรวจสอบรหัส oid อีกครั้ง');
            return $this->redirect(['index']);
        }

        Yii::$app->session->setFlash(
            'success',
            $existedBefore
                ? 'อัปเดตข้อมูลโครงการจากระบบ A เรียบร้อยแล้ว'
                : 'ดึงข้อมูลโครงการใหม่เข้าระบบเรียบร้อยแล้ว (ยังไม่มีการบันทึกรายงานความก้าวหน้า — กด "ส่งรายงานฉบับใหม่" ได้เมื่อพร้อม)'
        );

        return $this->redirect(['oid', 'oid' => $project->oid]);
    }

    public function actionOid($oid)
    {
        $project = ResearchProject::findOne(['oid' => $oid]);
        if (!$project) {
            throw new NotFoundHttpException(
                'ยังไม่เคยดึงข้อมูลโครงการนี้ กรุณาเปิดผ่านลิงก์ "แจ้งความก้าวหน้าโครงการ" ก่อน'
            );
        }

        $reportsProvider = new ActiveDataProvider([
            'query' => ProgressReport::find()
                ->where(['oid' => $oid])
                ->orderBy(['created_at' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 25,
            ],
            'sort' => false,
        ]);

        // บันทึกการส่งอีเมล (reminder/ปฏิเสธรายงาน/ประกาศ) ของโครงการนี้ — pageParam แยกจาก
        // reportsProvider ด้านบน กันไม่ให้เลขหน้าชนกันเวลาทั้งสองตารางอยู่ในหน้าเดียวกัน
        $notificationsProvider = new ActiveDataProvider([
            'query' => ReportNotification::find()
                ->where(['oid' => $oid])
                ->orderBy(['sent_at' => SORT_DESC]),
            'pagination' => [
                'pageParam' => 'notif-page',
                'pageSize' => 25,
            ],
            'sort' => false,
        ]);

        return $this->render('oid', [
            'project' => $project,
            'reports' => $reportsProvider->getModels(),
            'reportsProvider' => $reportsProvider,
            'notifications' => $notificationsProvider->getModels(),
            'notificationsProvider' => $notificationsProvider,
        ]);
    }

    /**
     * สร้าง array ของ sub-model (ReportPublication/ReportIpFiling) จากข้อมูล POST ที่ผูก index
     * ไว้แล้ว เช่น ReportPublication[0][article_title] — โหลดด้วย formName ว่างเพราะ POST
     * ถูก key ด้วย index อยู่แล้ว ไม่ต้องเผื่อ prefix ชื่อ model ซ้ำอีกชั้น
     *
     * @return ReportPublication[]|ReportIpFiling[]
     */
    private function buildSubModels(string $class, array $postData): array
    {
        if (empty($postData)) {
            return [new $class()];
        }

        $models = [];
        foreach ($postData as $attributes) {
            $instance = new $class();
            $instance->load((array) $attributes, '');
            $models[] = $instance;
        }

        return $models;
    }

    /**
     * ตรวจไฟล์ PDF แนบรายข้อ (ข้อ 6.1/6.2) ด้วย FileValidator เดียวกับที่ใช้กับ attachments[]
     * ระดับทั้งฉบับ — คนละ error bucket กัน ($errors อ้างอิงแบบ by-reference ต่อท้ายเข้า
     * $attachmentErrors ของผู้เรียก) ใส่ label นำหน้าบอกว่าไฟล์นี้เป็นของรายการไหน กันสับสนตอนแสดงผล
     * รวมกับ error ของ attachments[] ในช่องเดียวกัน
     */
    private function validateOptionalPdf(UploadedFile $file, string $label, array &$errors): bool
    {
        $model = new DynamicModel(['file' => $file]);
        $model->addRule('file', 'file', [
            'extensions' => 'pdf',
            'mimeTypes' => 'application/pdf',
            'checkExtensionByMimeType' => true,
            'maxSize' => 10 * 1024 * 1024,
        ]);

        if ($model->validate()) {
            return true;
        }

        foreach ($model->getErrors('file') as $error) {
            $errors[] = "{$label}: {$error}";
        }

        return false;
    }

    /**
     * บันทึกไฟล์แนบ (PDF) ลงดิสก์ + สร้างแถว report_attachments — ใช้ร่วมกันทั้งเอกสารแนบระดับทั้งฉบับ
     * (attachments[] เดิม, $extraAttrs ว่าง) และไฟล์แนบรายข้อ 6.1/6.2 ($extraAttrs มี publication_id
     * หรือ ip_filing_id) เก็บไฟล์จริงไว้ที่โฟลเดอร์เดียวกันตาม report_id เสมอไม่ว่าจะผูกกับรายการย่อย
     * หรือไม่ (ดาวน์โหลดผ่าน ReportController::actionDownloadAttachment() เส้นทางเดียวกันหมด)
     */
    private function saveItemAttachment(UploadedFile $file, int $reportId, array $extraAttrs): void
    {
        $dir = Yii::getAlias('@app/uploads/reports/' . $reportId);
        FileHelper::createDirectory($dir, 0775);

        $storedFilename = Yii::$app->security->generateRandomString() . '.pdf';
        if (!$file->saveAs($dir . '/' . $storedFilename)) {
            throw new \RuntimeException('บันทึกไฟล์แนบไม่สำเร็จ');
        }

        $attachment = new ReportAttachment(array_merge([
            'report_id' => $reportId,
            'original_filename' => $file->name,
            'stored_filename' => $storedFilename,
            'file_size' => $file->size,
        ], $extraAttrs));
        if (!$attachment->save()) {
            throw new \RuntimeException('บันทึกข้อมูลไฟล์แนบไม่สำเร็จ');
        }
    }
}
