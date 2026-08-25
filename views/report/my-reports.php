<?php

/** @var yii\web\View $this */
/** @var app\models\ProgressReport[] $reports */
/** @var yii\data\ActiveDataProvider $reportsProvider */

use app\helpers\ThaiDate;
use app\models\ProgressReport;
use yii\bootstrap5\Html;
use yii\bootstrap5\LinkPager;

$this->title = 'รายงานของฉัน';
$this->params['breadcrumbs'][] = $this->title;

$statusLabels = [
    'not_started' => 'ยังไม่เริ่มดำเนินการ',
    'in_progress' => 'อยู่ระหว่างดำเนินการ',
    'completed' => 'ดำเนินการเสร็จสิ้น',
    'terminated_early' => 'ยุติโครงการก่อนกำหนด',
    'cancelled' => 'ยกเลิกโครงการ',
];
$statusBadgeClasses = [
    'not_started' => 'bg-secondary',
    'in_progress' => 'bg-primary',
    'completed' => 'bg-success',
    'terminated_early' => 'bg-warning text-dark',
    'cancelled' => 'bg-danger',
];
?>
<div class="report-my-reports">
    <h1 class="h4 fw-bold mb-1"><?= Html::encode($this->title) ?></h1>
    <p class="text-body-secondary small mb-4">
        รายงานความก้าวหน้าทุกฉบับที่คุณเคยส่งเข้าระบบ เรียงจากล่าสุด
    </p>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent fw-semibold">
            พบ <?= (int) $reportsProvider->getTotalCount() ?> ฉบับ
        </div>
        <div class="card-body p-0">
            <?php if (empty($reports)): ?>
                <p class="text-body-secondary p-3 mb-0">คุณยังไม่เคยส่งรายงานความก้าวหน้าเลย</p>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th>รหัสโครงการ</th>
                        <th>ชื่อโครงการ</th>
                        <th>สถานะการดำเนินโครงการ</th>
                        <th>ผลการตรวจสอบ</th>
                        <th>ส่งเมื่อ</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?= Html::encode($report->project_code ?: '-') ?></td>
                            <td><?= Html::encode($report->researchProject->oname ?? $report->project_name_th) ?></td>
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
</div>
