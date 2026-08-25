<?php

/** @var yii\web\View $this */
/** @var app\models\ProgressReport[] $reports */
/** @var string $statusFilter */
/** @var string $startDate */
/** @var string $endDate */

use app\helpers\ThaiDate;
use app\models\ProgressReport;
use yii\bootstrap5\Html;
use yii\helpers\StringHelper;

$statusFilterLabels = [
    'pending' => 'รอตรวจสอบ',
    'approved' => 'ตรวจแล้ว',
    'rejected' => 'ปฏิเสธ',
    'all' => 'ทุกสถานะ',
];
$reviewStatusColors = [
    'pending' => '#6c757d',
    'approved' => '#198754',
    'rejected' => '#dc3545',
];
?>
<!-- เอกสารนี้เรนเดอร์ผ่าน mPDF โดยตรง (ไม่ใช่ layout ปกติของเว็บ) — ใช้ CSS ชุดจำกัดที่ mPDF รองรับ
     เท่านั้น ไม่พึ่งพา Bootstrap/FontAwesome ที่ใช้ในหน้าเว็บปกติ -->
<style>
    body { font-family: sarabun; font-size: 11pt; color: #212529; }
    h1 { font-size: 16pt; margin-bottom: 2px; }
    .subtitle { color: #6c757d; font-size: 10pt; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #dee2e6; padding: 5px 7px; font-size: 10pt; text-align: left; }
    th { background-color: #f8f9fc; font-weight: bold; }
    .badge { display: inline-block; padding: 2px 7px; border-radius: 3px; color: #fff; font-size: 9pt; }
</style>

<h1>ตรวจสอบรายงาน</h1>
<div class="subtitle">
    สถานะ: <?= Html::encode($statusFilterLabels[$statusFilter] ?? $statusFilter) ?>
    &nbsp;|&nbsp;
    ช่วงวันที่ส่ง:
    <?= $startDate !== '' ? Html::encode(ThaiDate::format($startDate, false)) : 'ไม่จำกัด' ?>
    —
    <?= $endDate !== '' ? Html::encode(ThaiDate::format($endDate, false)) : 'ไม่จำกัด' ?>
    &nbsp;|&nbsp; พิมพ์เมื่อ <?= Html::encode(ThaiDate::format(date('Y-m-d H:i:s'))) ?>
</div>

<p>พบ <?= count($reports) ?> รายการ</p>

<?php if (empty($reports)): ?>
    <p>ไม่พบรายงานตามเงื่อนไขที่เลือก</p>
<?php else: ?>
    <table>
        <thead>
        <tr>
            <th>รหัสโครงการ</th>
            <th>ชื่อโครงการ</th>
            <th>หัวหน้าโครงการ</th>
            <th>ส่งเมื่อ</th>
            <th>ส่งโดย</th>
            <th>สถานะการตรวจสอบ</th>
            <th>หมายเหตุ</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($reports as $report): ?>
            <tr>
                <td><?= Html::encode($report->project_code ?: '-') ?></td>
                <td><?= Html::encode($report->researchProject->oname ?? $report->project_name_th) ?></td>
                <td><?= Html::encode($report->pi_name) ?></td>
                <td><?= Html::encode(ThaiDate::format($report->created_at)) ?></td>
                <td><?= Html::encode($report->submitted_by_email) ?></td>
                <td>
                    <span class="badge" style="background-color: <?= $reviewStatusColors[$report->review_status] ?? '#6c757d' ?>;">
                        <?= Html::encode(ProgressReport::REVIEW_STATUS_LABELS[$report->review_status] ?? $report->review_status) ?>
                    </span>
                </td>
                <td>
                    <?php if ($report->review_status === 'rejected' && $report->rejection_reason): ?>
                        <?= Html::encode(StringHelper::truncate($report->rejection_reason, 60)) ?>
                    <?php elseif ($report->reviewed_at): ?>
                        โดย <?= Html::encode($report->reviewed_by ?: '-') ?> เมื่อ <?= Html::encode(ThaiDate::format($report->reviewed_at)) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
