<?php

/** @var yii\web\View $this */
/** @var app\models\ReminderCycle[] $cycles */

use app\helpers\ThaiDate;
use yii\bootstrap5\Html;

$this->title = 'ตั้งค่าแจ้งเตือน';
$this->params['breadcrumbs'][] = $this->title;

$this->registerJsFile('@web/js/vendor/sweetalert2.all.min.js');
$this->registerJsFile('@web/js/confirm-submit.js');
?>
<div class="admin-notification-settings">
    <h1 class="h4 fw-bold mb-4"><?= Html::encode($this->title) ?></h1>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent fw-semibold">สร้างรอบการส่งรายงาน</div>
        <div class="card-body">
            <p class="text-body-secondary small mb-3">
                แต่ละรอบเป็นการตั้งค่าแบบครั้งเดียว — เมื่อถึง "วันที่ส่งอีเมลแจ้งเตือน" ระบบจะส่งอีเมล
                แจ้งเตือนอัตโนมัติไปยังโครงการที่ยังดำเนินการอยู่ทุกโครงการ (ยังไม่เริ่ม/อยู่ระหว่างดำเนินการ/
                ยังไม่เคยส่งรายงานเลย) โดยระบุ "กำหนดการส่งรายงาน" ไว้ในอีเมล
            </p>
            <?= Html::beginForm(['add-reminder-cycle'], 'post', ['class' => 'row g-2 align-items-end']) ?>
                <div class="col-sm-6 col-md-4">
                    <label class="form-label small fw-semibold" for="cycle-name">ชื่อรอบการส่งรายงาน</label>
                    <?= Html::textInput('name', '', [
                        'id' => 'cycle-name',
                        'class' => 'form-control',
                        'required' => true,
                        'placeholder' => 'เช่น รอบที่ 1/2569',
                    ]) ?>
                </div>
                <div class="col-sm-3 col-md-3">
                    <label class="form-label small fw-semibold" for="cycle-due-date">กำหนดการส่งรายงาน</label>
                    <?= Html::input('date', 'report_due_date', '', ['id' => 'cycle-due-date', 'class' => 'form-control', 'required' => true]) ?>
                </div>
                <div class="col-sm-3 col-md-3">
                    <label class="form-label small fw-semibold" for="cycle-notify-date">วันที่ส่งอีเมลแจ้งเตือน</label>
                    <?= Html::input('date', 'notify_date', '', ['id' => 'cycle-notify-date', 'class' => 'form-control', 'required' => true]) ?>
                </div>
                <div class="col-sm-4 col-md-auto">
                    <?= Html::submitButton('สร้างรอบ', ['class' => 'btn btn-primary']) ?>
                </div>
            <?= Html::endForm() ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent fw-semibold">
            รอบการส่งรายงานทั้งหมด (<?= count($cycles) ?> รอบ)
        </div>
        <div class="card-body p-0">
            <?php if (empty($cycles)): ?>
                <p class="text-body-secondary p-3 mb-0">ยังไม่มีรอบการส่งรายงาน</p>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th>ชื่อรอบการส่งรายงาน</th>
                        <th>กำหนดการส่งรายงาน</th>
                        <th>วันที่ส่งอีเมลแจ้งเตือน</th>
                        <th>สถานะ</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cycles as $cycle): ?>
                        <tr>
                            <td><?= Html::encode($cycle->name) ?></td>
                            <td><?= Html::encode(ThaiDate::format($cycle->report_due_date, false)) ?></td>
                            <td><?= Html::encode(ThaiDate::format($cycle->notify_date, false)) ?></td>
                            <td>
                                <?php if ($cycle->isSent()): ?>
                                    <?php
                                    $sentCount = 0;
                                    $failedCount = 0;
                                    foreach ($cycle->notifications as $notification) {
                                        $notification->sent_status === 'sent' ? $sentCount++ : $failedCount++;
                                    }
                                    ?>
                                    <span class="badge bg-success">
                                        ส่งแล้ว (สำเร็จ <?= $sentCount ?><?php if ($failedCount > 0): ?>, ล้มเหลว <?= $failedCount ?><?php endif; ?>)
                                    </span>
                                    <div class="text-body-secondary small mt-1">
                                        เมื่อ <?= Html::encode(ThaiDate::format($cycle->sent_at)) ?>
                                    </div>
                                <?php elseif ($cycle->isDue()): ?>
                                    <span class="badge bg-warning text-dark">ถึงกำหนดแล้ว รอ cron</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">รอถึงกำหนด</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($cycle->isSent()): ?>
                                    <span class="text-body-secondary small" title="รอบที่ส่งไปแล้วแก้ไข/ลบไม่ได้ (เก็บไว้เป็นหลักฐาน)">ส่งแล้ว</span>
                                <?php else: ?>
                                    <div class="d-flex gap-2 justify-content-end">
                                        <?= Html::beginForm(['send-cycle-now', 'id' => $cycle->id], 'post', [
                                            'data-confirm-message' => 'ยืนยันส่งอีเมลรอบ "' . $cycle->name . '" ตอนนี้เลย โดยไม่ต้องรอถึงกำหนด?',
                                        ]) ?>
                                            <?= Html::submitButton('ส่งตอนนี้เลย', ['class' => 'btn btn-sm btn-outline-primary']) ?>
                                        <?= Html::endForm() ?>
                                        <?= Html::beginForm(['remove-reminder-cycle', 'id' => $cycle->id], 'post', [
                                            'data-confirm-message' => 'ยืนยันลบรอบ "' . $cycle->name . '"?',
                                            'data-confirm-icon' => 'warning',
                                        ]) ?>
                                            <?= Html::submitButton('ลบ', ['class' => 'btn btn-sm btn-outline-danger']) ?>
                                        <?= Html::endForm() ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
