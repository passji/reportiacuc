<?php

/** @var yii\web\View $this */
/** @var app\models\ProgressReport $model */
/** @var array $rows ข้อมูล ข้อ 1-6 สร้างจาก ReportController::buildReportRows() ชุดเดียวกับที่ report/view ใช้ */

use app\helpers\ThaiDate;
use app\models\ProgressReport;
use app\models\ReportPublication;
use app\models\ReportIpFiling;
use yii\bootstrap5\Html;

$project = $model->researchProject;
if ($project) {
    $projectFields = [
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
}

$reviewStatusColors = [
    'pending' => '#6c757d',
    'approved' => '#198754',
    'rejected' => '#dc3545',
];
$redLabels = ['ส่งรายงานโดย', 'วันที่ส่งรายงาน'];
?>
<!-- เอกสารนี้เรนเดอร์ผ่าน mPDF โดยตรง (ไม่ใช่ layout ปกติของเว็บ) — ใช้ CSS ชุดจำกัดที่ mPDF รองรับ
     เท่านั้น ไม่พึ่งพา Bootstrap/FontAwesome ที่ใช้ในหน้าเว็บปกติ สีป้ายหัวข้อ (เขียวเข้ม/แดง) ใช้ค่า hex
     เดียวกับที่ตรวจสอบแล้วว่าตรงกับสีจริงบนหน้าเว็บ (text-success-emphasis / text-danger โหมดสว่าง) -->
<style>
    body { font-family: sarabun; font-size: 11pt; color: #212529; }
    h1 { font-size: 16pt; margin-bottom: 2px; }
    h2 { font-size: 12pt; margin-top: 16px; margin-bottom: 6px; border-bottom: 1px solid #dee2e6; padding-bottom: 3px; }
    .subtitle { color: #6c757d; font-size: 10pt; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    th, td { border: 1px solid #dee2e6; padding: 5px 7px; font-size: 10pt; text-align: left; vertical-align: top; }
    th { background-color: #f8f9fc; font-weight: normal; color: #495057; width: 35%; }
    .label-green { color: #0a3622; font-weight: bold; }
    .label-red { color: #e74a3b; font-weight: bold; }
    .badge { display: inline-block; padding: 2px 7px; border-radius: 3px; color: #fff; font-size: 9pt; }
</style>

<h1><?= Html::encode('รายงานความก้าวหน้าโครงการ #' . $model->id) ?></h1>
<div class="subtitle">
    รหัสโครงการอ้างอิง oid: <?= Html::encode($model->oid) ?>
    &nbsp;|&nbsp; พิมพ์เมื่อ <?= Html::encode(ThaiDate::format(date('Y-m-d H:i:s'))) ?>
</div>

<?php if ($project): ?>
    <h2>ข้อมูลโครงการ</h2>
    <table>
        <tr>
            <th>รหัสโครงการ</th>
            <td><?= Html::encode($project->getProjectCode() ?: '-') ?></td>
        </tr>
        <?php foreach ($projectFields as $attribute => $label): ?>
            <tr>
                <th><?= Html::encode($label) ?></th>
                <td><?= Html::encode($project->$attribute ?? '-') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>ข้อมูลสัตว์ที่ได้รับการอนุมัติ</h2>
    <table>
        <tr>
            <th>ชนิดสัตว์</th>
            <td>
                <?= Html::encode($val('an_type')) ?>
                <?php if (!empty($raw['an_type_other'])): ?>(<?= Html::encode($raw['an_type_other']) ?>)<?php endif; ?>
            </td>
        </tr>
        <tr><th>สายพันธุ์ / ลักษณะ</th><td><?= Html::encode($val('an_name')) ?> — <?= Html::encode($val('an_sectment')) ?></td></tr>
        <tr><th>จำนวนที่อนุมัติ (ตัวผู้)</th><td><?= Html::encode($primaryMale ?? '-') ?></td></tr>
        <tr><th>จำนวนที่อนุมัติ (ตัวเมีย)</th><td><?= Html::encode($primaryFemale ?? '-') ?></td></tr>
        <?php if ($hasSecondGroup): ?>
            <tr><th>กลุ่มสัตว์ที่สอง</th><td><?= Html::encode($val('h_name')) ?></td></tr>
            <tr><th>จำนวนที่อนุมัติ (ตัวผู้ — กลุ่มที่สอง)</th><td><?= Html::encode($secondMale ?? '-') ?></td></tr>
            <tr><th>จำนวนที่อนุมัติ (ตัวเมีย — กลุ่มที่สอง)</th><td><?= Html::encode($secondFemale ?? '-') ?></td></tr>
        <?php endif; ?>
        <tr><th>แหล่งที่มาของสัตว์</th><td><?= Html::encode($val('an_resource')) ?></td></tr>
        <tr><th>สถานที่เลี้ยง</th><td><?= Html::encode($val('an_location')) ?></td></tr>
        <tr><th>ช่วงเวลาที่ใช้สัตว์</th><td><?= Html::encode($val('start_ani')) ?> — <?= Html::encode($val('stop_ani')) ?></td></tr>
        <tr><th>หมายเหตุ</th><td><?= Html::encode($val('an_ex')) ?></td></tr>
        <tr><th>มติที่ประชุม / สถานะอนุมัติ</th><td><?= Html::encode($val('meet_note')) ?></td></tr>
    </table>
<?php endif; ?>

<h2>รายละเอียดรายงาน</h2>
<table>
    <?php
    // ไล่สีป้ายหัวข้อชุดเดียวกับ report/view.php เป๊ะ ๆ — ดูคอมเมนต์อธิบาย logic เต็มที่นั่น
    $inGreenSection = false;
    ?>
    <?php foreach ($rows as $label => $value): ?>
        <?php
        if ($label === 'มีการเปลี่ยนแปลงวัตถุประสงค์หรือไม่') {
            $inGreenSection = true;
        }
        $thClass = '';
        if (in_array($label, $redLabels, true)) {
            $thClass = 'label-red';
        } elseif ($inGreenSection) {
            $thClass = 'label-green';
        }
        if (str_starts_with($label, 'ข้อ 6:')) {
            $inGreenSection = false;
        }
        ?>
        <tr>
            <th class="<?= $thClass ?>"><?= Html::encode($label) ?></th>
            <td><?= nl2br(Html::encode((string) ($value ?: '-'))) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<?php if ($model->has_publication === 'yes' && $model->publications): ?>
    <h2>ผลงานตีพิมพ์ (ข้อ 6.1)</h2>
    <?php foreach ($model->publications as $pub): ?>
        <table>
            <tr><th>ชื่อบทความ</th><td><?= Html::encode($pub->article_title ?: '-') ?></td></tr>
            <tr><th>ระดับ</th><td><?= Html::encode(ReportPublication::LEVEL_LABELS[$pub->level] ?? '-') ?></td></tr>
            <tr>
                <th>วารสาร</th>
                <td>
                    <?= Html::encode($pub->journal_name ?: '-') ?>
                    <?php if ($pub->issue): ?> ฉบับที่ <?= Html::encode($pub->issue) ?><?php endif; ?>
                    <?php if ($pub->page): ?> หน้าที่ <?= Html::encode($pub->page) ?><?php endif; ?>
                    <?php if ($pub->doi): ?> DOI: <?= Html::encode($pub->doi) ?><?php endif; ?>
                </td>
            </tr>
        </table>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($model->has_publication === 'yes' && $model->ipFilings): ?>
    <h2>การยื่นจดทรัพย์สินทางปัญญา (ข้อ 6.2)</h2>
    <?php foreach ($model->ipFilings as $ip): ?>
        <table>
            <tr><th>ประเภท</th><td><?= Html::encode(ReportIpFiling::IP_TYPE_LABELS[$ip->ip_type] ?? '-') ?></td></tr>
            <tr><th>วันที่ยื่นจด</th><td><?= Html::encode($ip->filed_date ?: '-') ?></td></tr>
            <tr><th>เลขที่จดทะเบียน</th><td><?= Html::encode($ip->registration_no ?: '-') ?></td></tr>
            <tr><th>ชื่อทรัพย์สิน</th><td><?= Html::encode($ip->asset_name ?: '-') ?></td></tr>
        </table>
    <?php endforeach; ?>
<?php endif; ?>

<h2>ผลการตรวจสอบ</h2>
<p>
    <span class="badge" style="background-color: <?= $reviewStatusColors[$model->review_status] ?? '#6c757d' ?>;">
        <?= Html::encode(ProgressReport::REVIEW_STATUS_LABELS[$model->review_status] ?? $model->review_status) ?>
    </span>
</p>
<?php if ($model->review_status === 'rejected' && $model->rejection_reason): ?>
    <p><strong>เหตุผลที่ปฏิเสธ:</strong><br><?= nl2br(Html::encode($model->rejection_reason)) ?></p>
<?php endif; ?>
<?php if ($model->reviewed_at): ?>
    <p>ตรวจสอบโดย <?= Html::encode($model->reviewed_by ?: '-') ?> เมื่อ <?= Html::encode(ThaiDate::format($model->reviewed_at)) ?></p>
<?php endif; ?>
