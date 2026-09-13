<?php

/** @var yii\web\View $this */
/** @var yii\base\DynamicModel $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'เข้าสู่ระบบ';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="auth-login d-flex align-items-center justify-content-center py-5">
    <div class="card border-0 shadow-sm" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h4 fw-bold mb-1"><?= Html::encode($this->title) ?></h1>
            <p class="text-body-secondary small mb-4">
                ระบบรายงานความก้าวหน้าโครงการวิจัย (IACUC)
            </p>

            <?php
            // ไอคอน Google เป็น inline SVG ("G" หลายสีตามที่ Google แนะนำสำหรับปุ่ม sign-in) แทนการใช้
            // FontAwesome (class fab) — ฟอนต์ brands ของ FontAwesome (fa-brands-400) ไม่ได้ถูก
            // self-host ไว้ในโปรเจกต์นี้ (มีแค่ fa-solid-900.woff2 ใน web/fonts/fontawesome/ ที่อื่น
            // ในระบบใช้แต่ fas ทั้งหมด) ไอคอน fab เลยไม่ขึ้นเป็นรูปเลย
            $googleIconSvg = <<<SVG
                <svg width="18" height="18" viewBox="0 0 48 48" class="me-2" aria-hidden="true">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.9-2.26 5.36-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                </svg>
                SVG;
            ?>
            <div class="d-grid mb-3">
                <?= Html::a(
                    $googleIconSvg . 'เข้าสู่ระบบด้วย Google',
                    ['auth/google'],
                    ['class' => 'btn btn-outline-secondary d-flex align-items-center justify-content-center']
                ) ?>
            </div>

            <div class="d-flex align-items-center gap-2 my-3">
                <hr class="flex-grow-1">
                <span class="text-body-secondary small">หรือ</span>
                <hr class="flex-grow-1">
            </div>

            <p class="text-body-secondary small mb-2">เข้าสู่ระบบด้วยอีเมล @kku.ac.th (Mock)</p>

            <?php $form = ActiveForm::begin(['id' => 'auth-login-form']); ?>

            <div class="mb-3">
                <?= $form->field($model, 'email')->textInput([
                    'class' => 'form-control',
                    'placeholder' => 'name@kku.ac.th',
                    'autofocus' => true,
                ]) ?>
            </div>

            <div class="d-grid">
                <?= Html::submitButton('เข้าสู่ระบบ (Mock)', ['class' => 'btn btn-primary']) ?>
            </div>

            <?php ActiveForm::end(); ?>

            <p class="text-body-secondary small mt-3 mb-0">
                โหมดนี้เป็น Mock Login ชั่วคราว ยังไม่ได้เชื่อมกับ KKU SSO จริง (ไม่ต้องกรอกรหัสผ่าน)
            </p>
        </div>
    </div>
</div>
