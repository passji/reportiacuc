<?php

/** @var yii\web\View $this */
/** @var string $startDate */
/** @var string $endDate */
/** @var int $totalProjects */
/** @var int $totalReports */
/** @var array $statusLabels */
/** @var array $statusCounts */
/** @var array $reviewStatusLabels */
/** @var array $reviewStatusCounts */
/** @var int $animalUsedTotal */
/** @var array $animalUsedByUnit */
/** @var int $publicationsTotal */
/** @var array $publicationsByLevel */
/** @var int $ipFilingsTotal */
/** @var app\models\ProgressReport[] $reports */
/** @var array $projects */

use app\helpers\ThaiDate;
use app\models\ProgressReport;
use yii\bootstrap5\Html;

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
    h2 { font-size: 12pt; margin-top: 18px; margin-bottom: 6px; border-bottom: 1px solid #dee2e6; padding-bottom: 3px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    th, td { border: 1px solid #dee2e6; padding: 5px 7px; font-size: 10pt; text-align: left; }
    th { background-color: #f8f9fc; font-weight: bold; }
    .stat-table td:first-child { width: 65%; color: #495057; }
    .badge { display: inline-block; padding: 2px 7px; border-radius: 3px; color: #fff; font-size: 9pt; }
</style>

<h1>Dashboard — สรุปภาพรวม</h1>
<div class="subtitle">
    ช่วงวันที่: <?= Html::encode(ThaiDate::format($startDate, false)) ?> — <?= Html::encode(ThaiDate::format($endDate, false)) ?>
    &nbsp;|&nbsp; พิมพ์เมื่อ <?= Html::encode(ThaiDate::format(date('Y-m-d H:i:s'))) ?>
</div>

<table class="stat-table">
    <tr><td>จำนวนโครงการทั้งหมดที่ส่งรายงาน</td><td><?= (int) $totalProjects ?></td></tr>
    <tr><td>จำนวนรายงานที่ส่งเข้ามาทั้งหมด</td><td><?= (int) $totalReports ?></td></tr>
</table>

<h2>สถานะการดำเนินโครงการ (นับจากจำนวนรายงานทั้งหมดในช่วงที่เลือก)</h2>
<table class="stat-table">
    <?php foreach ($statusLabels as $key => $label): ?>
        <tr><td><?= Html::encode($label) ?></td><td><?= (int) ($statusCounts[$key] ?? 0) ?></td></tr>
    <?php endforeach; ?>
</table>

<h2>ผลการตรวจสอบ (นับจากจำนวนรายงานทั้งหมดในช่วงที่เลือก)</h2>
<table class="stat-table">
    <?php foreach ($reviewStatusLabels as $key => $label): ?>
        <tr><td><?= Html::encode($label) ?></td><td><?= (int) ($reviewStatusCounts[$key] ?? 0) ?></td></tr>
    <?php endforeach; ?>
</table>

<h2>สถิติเพิ่มเติม</h2>
<table class="stat-table">
    <tr><td>จำนวนสัตว์ทดลองที่ใช้จริงรวม (หน่วยตัว)</td><td><?= (int) $animalUsedByUnit['head'] ?> ตัว</td></tr>
    <tr><td>จำนวนสัตว์ทดลองที่ใช้จริงรวม (หน่วยมิลลิลิตร)</td><td><?= (int) $animalUsedByUnit['ml'] ?> มิลลิลิตร</td></tr>
    <tr><td>จำนวนสัตว์ทดลองที่ใช้จริงรวม (หน่วยลิตร)</td><td><?= (int) $animalUsedByUnit['l'] ?> ลิตร</td></tr>
    <tr><td>จำนวนสัตว์ทดลองที่ใช้จริงรวม (หน่วยกิโลกรัม)</td><td><?= (int) $animalUsedByUnit['kg'] ?> กิโลกรัม</td></tr>
    <tr>
        <td>ผลงานตีพิมพ์ทั้งหมด</td>
        <td>
            <?= (int) $publicationsTotal ?> เรื่อง
            (ระดับชาติ <?= (int) $publicationsByLevel['national'] ?>, นานาชาติ <?= (int) $publicationsByLevel['international'] ?>)
        </td>
    </tr>
    <tr><td>การยื่นจดทรัพย์สินทางปัญญาทั้งหมด</td><td><?= (int) $ipFilingsTotal ?> รายการ</td></tr>
</table>

<h2>โครงการที่ส่งรายงานในช่วงที่เลือก (<?= count($projects) ?> โครงการ)</h2>
<?php if (empty($projects)): ?>
    <p>ไม่พบโครงการที่ส่งรายงานในช่วงวันที่เลือก</p>
<?php else: ?>
    <table>
        <thead>
        <tr>
            <th>รหัสโครงการ</th>
            <th>ชื่อโครงการ</th>
            <th>หัวหน้าโครงการ</th>
            <th>สถานะ</th>
            <th>ผลการตรวจสอบ</th>
            <th>จำนวนรายงานในช่วงนี้</th>
            <th>ส่งล่าสุดเมื่อ</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($projects as $project): ?>
            <tr>
                <td><?= Html::encode($project['project_code'] ?: '-') ?></td>
                <td><?= Html::encode($project['oname']) ?></td>
                <td><?= Html::encode($project['m_pro_th']) ?></td>
                <td><?= Html::encode($statusLabels[$project['latest_status']] ?? $project['latest_status'] ?? '-') ?></td>
                <td>
                    <span class="badge" style="background-color: <?= $reviewStatusColors[$project['latest_review_status']] ?? '#6c757d' ?>;">
                        <?= Html::encode($reviewStatusLabels[$project['latest_review_status']] ?? $project['latest_review_status'] ?? '-') ?>
                    </span>
                </td>
                <td><?= (int) $project['report_count'] ?></td>
                <td><?= Html::encode(ThaiDate::format($project['latest_submitted_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>รายงานทั้งหมดในช่วงที่เลือก (<?= count($reports) ?> ฉบับ)</h2>
<?php if (empty($reports)): ?>
    <p>ยังไม่มีรายงานเข้ามาในระบบ</p>
<?php else: ?>
    <table>
        <thead>
        <tr>
            <th>รหัสโครงการ</th>
            <th>ชื่อโครงการ</th>
            <th>หัวหน้าโครงการ</th>
            <th>สถานะ</th>
            <th>ผลการตรวจสอบ</th>
            <th>วันที่ส่ง</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($reports as $report): ?>
            <tr>
                <td><?= Html::encode($report->project_code ?: '-') ?></td>
                <td><?= Html::encode($report->researchProject->oname ?? $report->project_name_th) ?></td>
                <td><?= Html::encode($report->pi_name) ?></td>
                <td><?= Html::encode($statusLabels[$report->status] ?? $report->status) ?></td>
                <td>
                    <span class="badge" style="background-color: <?= $reviewStatusColors[$report->review_status] ?? '#6c757d' ?>;">
                        <?= Html::encode(ProgressReport::REVIEW_STATUS_LABELS[$report->review_status] ?? $report->review_status) ?>
                    </span>
                </td>
                <td><?= Html::encode(ThaiDate::format($report->created_at)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
