<?php

/** @var yii\web\View $this */
/** @var string $startDate */
/** @var string $endDate */
/** @var int $totalProjects */
/** @var int $totalReports */
/** @var array $statusLabels */
/** @var array $statusCounts */
/** @var string|null $statusFilter */
/** @var array $reviewStatusLabels */
/** @var array $reviewStatusCounts */
/** @var string|null $reviewStatusFilter */
/** @var array $projectStatusCounts */
/** @var array $recentProjects */
/** @var yii\data\Pagination $recentProjectsPagination */
/** @var yii\data\Sort $recentProjectsSort */
/** @var app\models\ProgressReport[] $recentReports */
/** @var yii\data\ActiveDataProvider $recentReportsProvider */
/** @var int $animalUsedTotal */
/** @var array $animalUsedByUnit */
/** @var int $publicationsTotal */
/** @var array $publicationsByLevel */
/** @var int $ipFilingsTotal */

use app\helpers\ThaiDate;
use app\models\ProgressReport;
use yii\bootstrap5\Html;
use yii\bootstrap5\LinkPager;

$this->title = 'Dashboard';
$this->params['breadcrumbs'][] = $this->title;

$statusBadgeClasses = [
    'not_started' => 'bg-secondary',
    'in_progress' => 'bg-primary',
    'completed' => 'bg-success',
    'terminated_early' => 'bg-warning text-dark',
    'cancelled' => 'bg-danger',
];
$statusColors = [
    'not_started' => '#6c757d',
    'in_progress' => '#0d6efd',
    'completed' => '#198754',
    'terminated_early' => '#ffc107',
    'cancelled' => '#dc3545',
];
$reviewStatusColors = [
    'pending' => '#6c757d',
    'approved' => '#198754',
    'rejected' => '#dc3545',
];

// params พื้นฐานของลิงก์กรอง (แบบ segmented bar ด้านล่าง) — ต้องรักษาตัวกรองอีกฝั่งไว้เสมอ (สถานะ
// การดำเนินโครงการ/สถานะโครงการ ใช้ query param "status" ร่วมกัน ส่วนผลการตรวจสอบใช้ "review_status")
// ไม่ใส่ page param ใดๆ ไว้ ตัวกรองเปลี่ยนแล้วจะได้รีเซ็ตกลับไปหน้า 1 ของทั้งสองตารางเองโดยไม่ต้องเขียน JS
$baseParamsForStatusFilter = ['index', 'start_date' => $startDate, 'end_date' => $endDate];
if ($reviewStatusFilter !== null) {
    $baseParamsForStatusFilter['review_status'] = $reviewStatusFilter;
}
$baseParamsForReviewFilter = ['index', 'start_date' => $startDate, 'end_date' => $endDate];
if ($statusFilter !== null) {
    $baseParamsForReviewFilter['status'] = $statusFilter;
}
?>
<div class="dashboard-index">
    <h1 class="h4 fw-bold mb-1"><?= Html::encode($this->title) ?></h1>
    <p class="text-body-secondary small mb-3">
        สถิติด้านล่างนับเฉพาะรายงานที่ส่งในช่วงวันที่เลือก (ค่าเริ่มต้น: เดือนปัจจุบัน)
    </p>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <?= Html::beginForm(['index'], 'get', ['class' => 'row g-2 align-items-end']) ?>
                <div class="col-sm-4 col-md-3">
                    <label class="form-label small fw-semibold" for="start_date">ตั้งแต่วันที่</label>
                    <?= Html::input('date', 'start_date', $startDate, ['id' => 'start_date', 'class' => 'form-control']) ?>
                </div>
                <div class="col-sm-4 col-md-3">
                    <label class="form-label small fw-semibold" for="end_date">ถึงวันที่</label>
                    <?= Html::input('date', 'end_date', $endDate, ['id' => 'end_date', 'class' => 'form-control']) ?>
                </div>
                <div class="col-sm-4 col-md-auto d-flex gap-2">
                    <?= Html::submitButton('กรอง', ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('ล้างตัวกรอง', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
            <?= Html::endForm() ?>
            <div class="d-flex gap-2 mt-3">
                <?= Html::a('<i class="fas fa-file-pdf me-1"></i>ดาวน์โหลด PDF', [
                    'export-pdf', 'start_date' => $startDate, 'end_date' => $endDate,
                ], ['class' => 'btn btn-outline-danger btn-sm']) ?>
                <?= Html::a('<i class="fas fa-file-excel me-1"></i>ดาวน์โหลด Excel', [
                    'export-excel', 'start_date' => $startDate, 'end_date' => $endDate,
                ], ['class' => 'btn btn-outline-success btn-sm']) ?>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row g-0 align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">จำนวนโครงการทั้งหมดที่ส่งรายงาน</div>
                            <div class="h5 mb-0 fw-bold text-gray-800"><?= (int) $totalProjects ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-folder-open fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row g-0 align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">จำนวนรายงานที่ส่งเข้ามาทั้งหมด</div>
                            <div class="h5 mb-0 fw-bold text-gray-800"><?= (int) $totalReports ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-lines fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent fw-semibold">สถานะการดำเนินโครงการ (นับจากจำนวนรายงานทั้งหมดในช่วงที่เลือก)</div>
                <div class="card-body">
                    <?= $this->render('_status-bar', [
                        'labels' => $statusLabels,
                        'counts' => $statusCounts,
                        'colors' => $statusColors,
                        'filterParam' => 'status',
                        'currentFilter' => $statusFilter,
                        'baseUrlParams' => $baseParamsForStatusFilter,
                    ]) ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent fw-semibold">ผลการตรวจสอบ (นับจากจำนวนรายงานทั้งหมดในช่วงที่เลือก)</div>
                <div class="card-body">
                    <?= $this->render('_status-bar', [
                        'labels' => $reviewStatusLabels,
                        'counts' => $reviewStatusCounts,
                        'colors' => $reviewStatusColors,
                        'filterParam' => 'review_status',
                        'currentFilter' => $reviewStatusFilter,
                        'baseUrlParams' => $baseParamsForReviewFilter,
                    ]) ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent fw-semibold">สถานะโครงการ (นับจากจำนวนโครงการทั้งหมดในช่วงที่เลือก)</div>
                <div class="card-body">
                    <?= $this->render('_status-bar', [
                        'labels' => $statusLabels,
                        'counts' => $projectStatusCounts,
                        'colors' => $statusColors,
                        'filterParam' => 'status',
                        'currentFilter' => $statusFilter,
                        'baseUrlParams' => $baseParamsForStatusFilter,
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent fw-semibold">สถิติเพิ่มเติม</div>
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tbody>
                <tr>
                    <th class="text-body-secondary fw-normal">จำนวนสัตว์ทดลองที่ใช้จริงรวม (หน่วยตัว)</th>
                    <td><?= (int) $animalUsedTotal ?> ตัว</td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">จำนวนสัตว์ทดลองที่ใช้จริงรวม (หน่วยมิลลิลิตร)</th>
                    <td><?= (int) $animalUsedByUnit['ml'] ?> มิลลิลิตร</td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">จำนวนสัตว์ทดลองที่ใช้จริงรวม (หน่วยลิตร)</th>
                    <td><?= (int) $animalUsedByUnit['l'] ?> ลิตร</td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">จำนวนสัตว์ทดลองที่ใช้จริงรวม (หน่วยกิโลกรัม)</th>
                    <td><?= (int) $animalUsedByUnit['kg'] ?> กิโลกรัม</td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">ผลงานตีพิมพ์ทั้งหมด</th>
                    <td>
                        <?= (int) $publicationsTotal ?> เรื่อง
                        (ระดับชาติ <?= (int) $publicationsByLevel['national'] ?>,
                        นานาชาติ <?= (int) $publicationsByLevel['international'] ?>)
                    </td>
                </tr>
                <tr>
                    <th class="text-body-secondary fw-normal">การยื่นจดทรัพย์สินทางปัญญาทั้งหมด</th>
                    <td><?= (int) $ipFilingsTotal ?> รายการ</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent fw-semibold d-flex align-items-center flex-wrap gap-2">
            <span>โครงการที่ส่งรายงานในช่วงที่เลือก (<?= (int) $recentProjectsPagination->totalCount ?> โครงการ)</span>
            <?php if ($statusFilter !== null): ?>
                <span class="badge bg-info-subtle text-info-emphasis fw-normal">
                    กรองตามสถานะ: <?= Html::encode($statusLabels[$statusFilter]) ?>
                </span>
                <?= Html::a('ล้างตัวกรองสถานะ', [
                    'index', 'start_date' => $startDate, 'end_date' => $endDate,
                    'review_status' => $reviewStatusFilter,
                ], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
            <?php endif; ?>
            <?php if ($reviewStatusFilter !== null): ?>
                <span class="badge bg-info-subtle text-info-emphasis fw-normal">
                    กรองตามผลการตรวจสอบ: <?= Html::encode($reviewStatusLabels[$reviewStatusFilter]) ?>
                </span>
                <?= Html::a('ล้างตัวกรองผลการตรวจสอบ', [
                    'index', 'start_date' => $startDate, 'end_date' => $endDate,
                    'status' => $statusFilter,
                ], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
            <?php endif; ?>
            <?php if ($statusFilter !== null || $reviewStatusFilter !== null): ?>
                <?= Html::a('ล้างตัวกรองทั้งหมด', [
                    'index', 'start_date' => $startDate, 'end_date' => $endDate,
                ], ['class' => 'btn btn-sm btn-outline-secondary ms-auto']) ?>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($recentProjects)): ?>
                <p class="text-body-secondary p-3 mb-0">ไม่พบโครงการที่ส่งรายงานในช่วงวันที่เลือก</p>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th><?= $recentProjectsSort->link('project_code') ?></th>
                        <th><?= $recentProjectsSort->link('oname') ?></th>
                        <th><?= $recentProjectsSort->link('m_pro_th') ?></th>
                        <th><?= $recentProjectsSort->link('latest_status') ?></th>
                        <th><?= $recentProjectsSort->link('latest_review_status') ?></th>
                        <th>จำนวนรายงานในช่วงนี้</th>
                        <th><?= $recentProjectsSort->link('latest_submitted_at') ?></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentProjects as $project): ?>
                        <tr>
                            <td><?= Html::encode($project['project_code'] ?: '-') ?></td>
                            <td><?= Html::encode($project['oname']) ?></td>
                            <td><?= Html::encode($project['m_pro_th']) ?></td>
                            <td>
                                <span class="badge <?= $statusBadgeClasses[$project['latest_status']] ?? 'bg-secondary' ?>">
                                    <?= Html::encode($statusLabels[$project['latest_status']] ?? $project['latest_status'] ?? '-') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= ProgressReport::REVIEW_STATUS_BADGE_CLASSES[$project['latest_review_status']] ?? 'bg-secondary' ?>">
                                    <?= Html::encode($reviewStatusLabels[$project['latest_review_status']] ?? $project['latest_review_status'] ?? '-') ?>
                                </span>
                            </td>
                            <td><?= (int) $project['report_count'] ?></td>
                            <td><?= Html::encode(ThaiDate::format($project['latest_submitted_at'])) ?></td>
                            <td class="text-end">
                                <?= Html::a('ดูประวัติทั้งหมด', ['/report/oid', 'oid' => $project['oid']], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="p-3">
                    <?= LinkPager::widget([
                        'pagination' => $recentProjectsPagination,
                    ]) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-transparent fw-semibold d-flex align-items-center flex-wrap gap-2">
            <span>รายงานล่าสุดในช่วงที่เลือก (<?= (int) $recentReportsProvider->getTotalCount() ?> ฉบับ)</span>
            <?php if ($statusFilter !== null): ?>
                <span class="badge bg-info-subtle text-info-emphasis fw-normal">
                    กรองตามสถานะ: <?= Html::encode($statusLabels[$statusFilter]) ?>
                </span>
                <?= Html::a('ล้างตัวกรองสถานะ', [
                    'index', 'start_date' => $startDate, 'end_date' => $endDate,
                    'review_status' => $reviewStatusFilter,
                ], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
            <?php endif; ?>
            <?php if ($reviewStatusFilter !== null): ?>
                <span class="badge bg-info-subtle text-info-emphasis fw-normal">
                    กรองตามผลการตรวจสอบ: <?= Html::encode($reviewStatusLabels[$reviewStatusFilter]) ?>
                </span>
                <?= Html::a('ล้างตัวกรองผลการตรวจสอบ', [
                    'index', 'start_date' => $startDate, 'end_date' => $endDate,
                    'status' => $statusFilter,
                ], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
            <?php endif; ?>
            <?php if ($statusFilter !== null || $reviewStatusFilter !== null): ?>
                <?= Html::a('ล้างตัวกรองทั้งหมด', [
                    'index', 'start_date' => $startDate, 'end_date' => $endDate,
                ], ['class' => 'btn btn-sm btn-outline-secondary ms-auto']) ?>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($recentReports)): ?>
                <p class="text-body-secondary p-3 mb-0">ยังไม่มีรายงานเข้ามาในระบบ</p>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th><?= $recentReportsProvider->sort->link('project_code') ?></th>
                        <th><?= $recentReportsProvider->sort->link('project_name_th') ?></th>
                        <th><?= $recentReportsProvider->sort->link('pi_name') ?></th>
                        <th><?= $recentReportsProvider->sort->link('status') ?></th>
                        <th><?= $recentReportsProvider->sort->link('review_status') ?></th>
                        <th><?= $recentReportsProvider->sort->link('created_at') ?></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentReports as $report): ?>
                        <tr>
                            <td><?= Html::encode($report->project_code ?: '-') ?></td>
                            <td><?= Html::encode($report->researchProject->oname ?? $report->project_name_th) ?></td>
                            <td><?= Html::encode($report->pi_name) ?></td>
                            <td>
                                <span class="badge <?= $statusBadgeClasses[$report->status] ?? 'bg-secondary' ?>">
                                    <?= Html::encode($statusLabels[$report->status] ?? $report->status) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= ProgressReport::REVIEW_STATUS_BADGE_CLASSES[$report->review_status] ?? 'bg-secondary' ?>">
                                    <?= Html::encode(ProgressReport::REVIEW_STATUS_LABELS[$report->review_status] ?? $report->review_status) ?>
                                </span>
                            </td>
                            <td><?= Html::encode(ThaiDate::format($report->created_at)) ?></td>
                            <td class="text-end">
                                <?= Html::a('ดูรายละเอียด', ['/report/view', 'id' => $report->id], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="p-3">
                    <?= LinkPager::widget([
                        'pagination' => $recentReportsProvider->pagination,
                    ]) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
