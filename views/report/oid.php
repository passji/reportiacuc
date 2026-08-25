<?php

/** @var yii\web\View $this */
/** @var app\models\ResearchProject $project */
/** @var app\models\ProgressReport[] $reports */
/** @var yii\data\ActiveDataProvider $reportsProvider */
/** @var app\models\ReportNotification[] $notifications */
/** @var yii\data\ActiveDataProvider $notificationsProvider */

use app\helpers\ThaiDate;
use app\models\ProgressReport;
use app\models\ReportNotification;
use yii\bootstrap5\Html;
use yii\bootstrap5\LinkPager;

$this->title = 'ประวัติการรายงานความก้าวหน้า — ' . $project->oid;
$this->params['breadcrumbs'][] = $this->title;

$statusLabels = [
    'not_started' => 'ยังไม่เริ่มดำเนินการ',
    'in_progress' => 'อยู่ระหว่างดำเนินการ',
    'completed' => 'ดำเนินการเสร็จสิ้น',
    'terminated_early' => 'ยุติโครงการก่อนกำหนด',
    'cancelled' => 'ยกเลิกโครงการ',
];

// เครื่องหมายผลตรวจสอบ — ถูก (เขียว) = ตรวจแล้ว, ผิด (แดง) = ปฏิเสธ, นาฬิกา (เทา) = รอตรวจสอบ
$reviewIcons = [
    'pending' => ['icon' => 'fa-clock', 'class' => 'text-secondary'],
    'approved' => ['icon' => 'fa-check', 'class' => 'text-success'],
    'rejected' => ['icon' => 'fa-xmark', 'class' => 'text-danger'],
];

// รหัสโครงการ (เช่น "จส.มข. 7/61") เป็นข้อมูลที่ผู้ใช้กรอกเองตอนส่งรายงาน อยู่ใน
// progress_reports ไม่ใช่ oid ภายในของเรา — ดึงจากรายงานฉบับล่าสุด ($reports เรียง
// created_at DESC มาแล้วจาก actionOid())
$projectCode = $reports[0]->project_code ?? null;

$fields = [
    'oname' => 'ชื่อโครงการ (ไทย)',
    'oname_en' => 'ชื่อโครงการ (อังกฤษ)',
    'm_pro_th' => 'หัวหน้าโครงการ',
    'm_pro_dept_th' => 'สังกัด/ภาควิชา',
    'md_name' => 'ผู้ประสานงาน',
    'meeting_no' => 'ครั้งที่ประชุม',
    'meeting_date' => 'วันที่ประชุม',
    's_email' => 'อีเมลผู้ยื่นโครงการ',
    's_phone' => 'เบอร์โทรผู้ยื่นโครงการ',
    'updated_at' => 'ดึงข้อมูลล่าสุดเมื่อ',
];

$raw = $project->getRawData();
$val = static fn (string $key): string => (string) (($raw[$key] ?? '') !== '' ? $raw[$key] : '-');

$animalGroups = $project->getApprovedAnimalGroups();
$primaryMale = $animalGroups['male'];
$primaryFemale = $animalGroups['female'];
$secondMale = $animalGroups['second_male'];
$secondFemale = $animalGroups['second_female'];
$hasSecondGroup = $animalGroups['has_second_group'] || $secondMale !== null || $secondFemale !== null;
?>
<div class="report-oid">
    <h1 class="h4 fw-bold mb-1"><?= Html::encode($this->title) ?></h1>
    <p class="text-body-secondary small mb-4">
        <?= Html::encode($project->oname) ?>
        <?php if ($project->m_pro_th): ?> — หัวหน้าโครงการ: <?= Html::encode($project->m_pro_th) ?><?php endif; ?>
    </p>

    <div class="mb-4">
        <?= Html::a('ส่งรายงานฉบับใหม่สำหรับโครงการนี้', ['create', 'oid' => $project->oid], ['class' => 'btn btn-primary']) ?>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent fw-semibold">รายละเอียดโครงการ</div>
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tbody>
                <tr>
                    <th class="text-body-secondary fw-normal" style="width: 240px;">รหัสโครงการ</th>
                    <td><?= Html::encode($projectCode ?: '-') ?></td>
                </tr>
                <?php foreach ($fields as $attribute => $label): ?>
                    <tr>
                        <th class="text-body-secondary fw-normal" style="width: 240px;"><?= Html::encode($label) ?></th>
                        <td><?= Html::encode($project->$attribute ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent fw-semibold">ข้อมูลสัตว์ที่ได้รับการอนุมัติ</div>
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tbody>
                <tr>
                    <th class="text-body-secondary fw-normal" style="width: 240px;">ชนิดสัตว์</th>
                    <td>
                        <?= Html::encode($val('an_type')) ?>
                        <?php if (!empty($raw['an_type_other'])): ?>
                            (<?= Html::encode($raw['an_type_other']) ?>)
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">สายพันธุ์ / ลักษณะ</th>
                    <td><?= Html::encode($val('an_name')) ?> — <?= Html::encode($val('an_sectment')) ?></td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">จำนวนที่อนุมัติ (ตัวผู้)</th>
                    <td><?= Html::encode($primaryMale ?? '-') ?></td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">จำนวนที่อนุมัติ (ตัวเมีย)</th>
                    <td><?= Html::encode($primaryFemale ?? '-') ?></td>
                </tr>
                <?php if ($hasSecondGroup): ?>
                    <tr>
                        <th class="text-body-secondary fw-normal">กลุ่มสัตว์ที่สอง</th>
                        <td><?= Html::encode($val('h_name')) ?></td>
                    </tr>
                    <tr>
                        <th class="text-body-secondary fw-normal">จำนวนที่อนุมัติ (ตัวผู้ — กลุ่มที่สอง)</th>
                        <td><?= Html::encode($secondMale ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th class="text-body-secondary fw-normal">จำนวนที่อนุมัติ (ตัวเมีย — กลุ่มที่สอง)</th>
                        <td><?= Html::encode($secondFemale ?? '-') ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <th class="text-body-secondary fw-normal">แหล่งที่มาของสัตว์</th>
                    <td><?= Html::encode($val('an_resource')) ?></td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">สถานที่เลี้ยง</th>
                    <td><?= Html::encode($val('an_location')) ?></td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">ช่วงเวลาที่ใช้สัตว์</th>
                    <td><?= Html::encode($val('start_ani')) ?> — <?= Html::encode($val('stop_ani')) ?></td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">หมายเหตุ</th>
                    <td><?= Html::encode($val('an_ex')) ?></td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">มติที่ประชุม / สถานะอนุมัติ</th>
                    <td><?= Html::encode($val('meet_note')) ?></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent fw-semibold">
            รายงานที่เคยส่งมาแล้ว (<?= (int) $reportsProvider->getTotalCount() ?> ฉบับ)
        </div>
        <div class="card-body p-0">
            <?php if (empty($reports)): ?>
                <p class="text-body-secondary p-3 mb-0">ยังไม่เคยส่งรายงานความก้าวหน้าสำหรับโครงการนี้</p>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th>วันที่ส่ง</th>
                        <th>สถานะโครงการ ณ ตอนรายงาน</th>
                        <th>ส่งโดย</th>
                        <th>ผลงานตีพิมพ์</th>
                        <th class="text-center">ผลตรวจสอบ</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reports as $report): ?>
                        <?php $reviewIcon = $reviewIcons[$report->review_status] ?? $reviewIcons['pending']; ?>
                        <tr>
                            <td><?= Html::encode(ThaiDate::format($report->created_at)) ?></td>
                            <td><?= Html::encode($statusLabels[$report->status] ?? $report->status) ?></td>
                            <td><?= Html::encode($report->submitted_by_email) ?></td>
                            <td><?= $report->has_publication === 'yes' ? 'มี' : 'ไม่มี' ?></td>
                            <td class="text-center">
                                <i class="fas <?= $reviewIcon['icon'] ?> <?= $reviewIcon['class'] ?>"
                                   title="<?= Html::encode(ProgressReport::REVIEW_STATUS_LABELS[$report->review_status] ?? $report->review_status) ?>"></i>
                            </td>
                            <td class="text-end">
                                <?= Html::a('ดูรายละเอียด', ['view', 'id' => $report->id], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="p-3">
                    <?= LinkPager::widget(['pagination' => $reportsProvider->pagination]) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-transparent fw-semibold">
            บันทึกการส่งอีเมล (<?= (int) $notificationsProvider->getTotalCount() ?> รายการ)
        </div>
        <div class="card-body p-0">
            <?php if (empty($notifications)): ?>
                <p class="text-body-secondary p-3 mb-0">ยังไม่มีการส่งอีเมลสำหรับโครงการนี้</p>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th>วันที่ส่ง</th>
                        <th>ผู้รับ</th>
                        <th>ประเภท</th>
                        <th>หัวข้อ</th>
                        <th>สถานะ</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($notifications as $notification): ?>
                        <tr>
                            <td><?= Html::encode(ThaiDate::format($notification->sent_at)) ?></td>
                            <td><?= Html::encode($notification->recipient_email) ?></td>
                            <td><?= Html::encode(ReportNotification::TRIGGER_TYPE_LABELS[$notification->trigger_type] ?? $notification->trigger_type) ?></td>
                            <td><?= Html::encode($notification->subject ?: '-') ?></td>
                            <td>
                                <span class="badge <?= ReportNotification::SENT_STATUS_BADGE_CLASSES[$notification->sent_status] ?? 'bg-secondary' ?>"
                                      <?php if ($notification->error_message): ?>title="<?= Html::encode($notification->error_message) ?>"<?php endif; ?>>
                                    <?= Html::encode(ReportNotification::SENT_STATUS_LABELS[$notification->sent_status] ?? $notification->sent_status) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="p-3">
                    <?= LinkPager::widget(['pagination' => $notificationsProvider->pagination]) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
