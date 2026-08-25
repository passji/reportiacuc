<?php

/** @var yii\web\View $this */
/** @var app\models\Admin[] $admins */

use app\helpers\ThaiDate;
use yii\bootstrap5\Html;

$this->title = 'ตั้งค่า ADMIN';
$this->params['breadcrumbs'][] = $this->title;

$this->registerJsFile('@web/js/vendor/sweetalert2.all.min.js');
$this->registerJsFile('@web/js/confirm-submit.js');

$canRemove = count($admins) > 1;
?>
<div class="admin-settings">
    <h1 class="h4 fw-bold mb-4"><?= Html::encode($this->title) ?></h1>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent fw-semibold">เพิ่ม admin</div>
        <div class="card-body">
            <?= Html::beginForm(['add-admin'], 'post', ['class' => 'row g-2 align-items-end']) ?>
                <div class="col-sm-6 col-md-4">
                    <label class="form-label small fw-semibold" for="admin-email">อีเมล (@kku.ac.th)</label>
                    <?= Html::textInput('email', '', [
                        'id' => 'admin-email',
                        'class' => 'form-control',
                        'required' => true,
                        'placeholder' => 'name@kku.ac.th',
                    ]) ?>
                </div>
                <div class="col-sm-4 col-md-auto">
                    <?= Html::submitButton('เพิ่ม admin', ['class' => 'btn btn-primary']) ?>
                </div>
            <?= Html::endForm() ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent fw-semibold">
            รายชื่อ admin (<?= count($admins) ?> คน)
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead>
                <tr>
                    <th>อีเมล</th>
                    <th>เพิ่มเมื่อ</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?= Html::encode($admin->email) ?></td>
                        <td><?= Html::encode(ThaiDate::format($admin->created_at)) ?></td>
                        <td class="text-end">
                            <?php if ($canRemove): ?>
                                <?= Html::beginForm(['remove-admin', 'id' => $admin->id], 'post', [
                                    'data-confirm-message' => 'ยืนยันลบ admin นี้? (' . $admin->email . ')',
                                    'data-confirm-icon' => 'warning',
                                ]) ?>
                                    <?= Html::submitButton('ลบ', ['class' => 'btn btn-sm btn-outline-danger']) ?>
                                <?= Html::endForm() ?>
                            <?php else: ?>
                                <span class="text-body-secondary small" title="ต้องมี admin อย่างน้อย 1 คนเสมอ">ลบไม่ได้ (คนสุดท้าย)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
