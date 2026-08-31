<?php

/** @var yii\web\View $this */
/** @var string|null $to */
/** @var array|null $result */

use yii\bootstrap5\Html;

$this->title = 'ทดสอบส่งอีเมล';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="mail-test">
    <h1 class="h4 fw-bold mb-1"><?= Html::encode($this->title) ?></h1>
    <p class="text-body-secondary small mb-4">
        ใช้เช็คว่าตั้งค่า SMTP (MAILER_HOST/PORT ใน .env) ถูกต้องและส่งอีเมลออกได้จริงหรือไม่
        — เรียกผ่าน URL ตรงๆ ได้เช่น <code>?r=mail/test&amp;to=someone@kku.ac.th</code>
    </p>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <?= Html::beginForm(['test'], 'get', ['class' => 'row g-2 align-items-end']) ?>
                <div class="col-sm-6 col-md-4">
                    <label class="form-label small fw-semibold" for="mail-test-to">ส่งไปที่ (อีเมล)</label>
                    <?= Html::textInput('to', $to, [
                        'id' => 'mail-test-to',
                        'class' => 'form-control',
                        'required' => true,
                        'placeholder' => 'name@kku.ac.th',
                    ]) ?>
                </div>
                <div class="col-sm-4 col-md-auto">
                    <?= Html::submitButton('ส่งทดสอบ', ['class' => 'btn btn-primary']) ?>
                </div>
            <?= Html::endForm() ?>
        </div>
    </div>

    <?php if ($result !== null): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">ผลการทดสอบ</div>
            <div class="card-body">
                <table class="table table-sm mb-3">
                    <tbody>
                    <tr>
                        <th class="text-body-secondary fw-normal">ส่งไปที่</th>
                        <td><?= Html::encode($result['to']) ?></td>
                    </tr>
                    <tr>
                        <th class="text-body-secondary fw-normal">MAILER_HOST / PORT</th>
                        <td><?= Html::encode($result['host'] ?: '(ว่าง)') ?> : <?= Html::encode($result['port'] ?: '(ว่าง)') ?></td>
                    </tr>
                    <tr>
                        <th class="text-body-secondary fw-normal">โหมด</th>
                        <td>
                            <?php if ($result['useFileTransport']): ?>
                                <span class="badge bg-warning text-dark">เขียนไฟล์ .eml (ยังไม่ตั้งค่า MAILER_HOST)</span>
                            <?php else: ?>
                                <span class="badge bg-info-subtle text-info-emphasis">ส่งจริงผ่าน SMTP</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <?php if ($result['success']): ?>
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-circle-check me-1"></i>
                        <?= $result['useFileTransport']
                            ? 'เขียนไฟล์ .eml สำเร็จ ดูได้ที่ runtime/mail/ บน server'
                            : 'ส่งอีเมลสำเร็จ' ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-circle-exclamation me-1"></i>
                        ส่งไม่สำเร็จ — <?= Html::encode($result['error']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
