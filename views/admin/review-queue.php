<?php

/** @var yii\web\View $this */
/** @var app\models\ProgressReport[] $reports */
/** @var yii\data\ActiveDataProvider $reportsProvider */
/** @var string $statusFilter */
/** @var string $startDate */
/** @var string $endDate */

use app\helpers\ThaiDate;
use app\models\ProgressReport;
use yii\bootstrap5\Html;
use yii\bootstrap5\LinkPager;
use yii\helpers\StringHelper;

$this->title = 'ตรวจสอบรายงาน';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-review-queue">
    <h1 class="h4 fw-bold mb-4"><?= Html::encode($this->title) ?></h1>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <?= Html::beginForm(['review-queue'], 'get', ['class' => 'row g-2 align-items-end']) ?>
                <div class="col-sm-4 col-md-3">
                    <label class="form-label small fw-semibold" for="status">สถานะการตรวจสอบ</label>
                    <?= Html::dropDownList('status', $statusFilter, [
                        'pending' => 'รอตรวจสอบ',
                        'approved' => 'ตรวจแล้ว',
                        'rejected' => 'ปฏิเสธ',
                        'all' => 'ทุกสถานะ',
                    ], ['id' => 'status', 'class' => 'form-select']) ?>
                </div>
                <div class="col-sm-3 col-md-2">
                    <label class="form-label small fw-semibold" for="start_date">ส่งตั้งแต่วันที่</label>
                    <?= Html::input('date', 'start_date', $startDate, ['id' => 'start_date', 'class' => 'form-control']) ?>
                </div>
                <div class="col-sm-3 col-md-2">
                    <label class="form-label small fw-semibold" for="end_date">ถึงวันที่</label>
                    <?= Html::input('date', 'end_date', $endDate, ['id' => 'end_date', 'class' => 'form-control']) ?>
                </div>
                <div class="col-sm-4 col-md-auto d-flex gap-2">
                    <?= Html::submitButton('ค้นหา', ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('ล้างตัวกรอง', ['review-queue'], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
            <?= Html::endForm() ?>
            <div class="d-flex gap-2 mt-3">
                <?= Html::a('<i class="fas fa-file-pdf me-1"></i>ดาวน์โหลด PDF', [
                    'review-queue-export-pdf', 'status' => $statusFilter, 'start_date' => $startDate, 'end_date' => $endDate,
                ], ['class' => 'btn btn-outline-danger btn-sm']) ?>
                <?= Html::a('<i class="fas fa-file-excel me-1"></i>ดาวน์โหลด Excel', [
                    'review-queue-export-excel', 'status' => $statusFilter, 'start_date' => $startDate, 'end_date' => $endDate,
                ], ['class' => 'btn btn-outline-success btn-sm']) ?>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent fw-semibold">
            พบ <?= (int) $reportsProvider->getTotalCount() ?> รายการ
        </div>
        <div class="card-body p-0">
            <?php if (empty($reports)): ?>
                <p class="text-body-secondary p-3 mb-0">ไม่พบรายงานตามเงื่อนไขที่เลือก</p>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th>รหัสโครงการ</th>
                        <th>ชื่อโครงการ</th>
                        <th>หัวหน้าโครงการ</th>
                        <th>ส่งเมื่อ</th>
                        <th>ส่งโดย</th>
                        <th>สถานะการตรวจสอบ</th>
                        <th>หมายเหตุ</th>
                        <th></th>
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
                                <span class="badge <?= ProgressReport::REVIEW_STATUS_BADGE_CLASSES[$report->review_status] ?? 'bg-secondary' ?>">
                                    <?= Html::encode(ProgressReport::REVIEW_STATUS_LABELS[$report->review_status] ?? $report->review_status) ?>
                                </span>
                            </td>
                            <td class="small">
                                <?php if ($report->review_status === 'rejected' && $report->rejection_reason): ?>
                                    <span title="<?= Html::encode($report->rejection_reason) ?>">
                                        <?= Html::encode(StringHelper::truncate($report->rejection_reason, 40)) ?>
                                    </span>
                                <?php elseif ($report->reviewed_at): ?>
                                    <span class="text-body-secondary">
                                        โดย <?= Html::encode($report->reviewed_by ?: '-') ?><br>
                                        เมื่อ <?= Html::encode(ThaiDate::format($report->reviewed_at)) ?>
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?= Html::a('ดูรายละเอียด', ['/report/view', 'id' => $report->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
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
