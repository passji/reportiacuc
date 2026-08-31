<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'ระบบรายงานความก้าวหน้าโครงการวิจัยที่ได้รับการรับรองจรรยาบรรณการดำเนินการต่อสัตว์เพื่องานทางวิทยาศาสตร์';
$this->params['meta_description'] = 'ระบบรายงานความก้าวหน้าโครงการวิจัยที่ได้รับการรับรองจรรยาบรรณการดำเนินการต่อสัตว์เพื่องานทางวิทยาศาสตร์ (IACUC) มหาวิทยาลัยขอนแก่น';

$isLoggedIn = !empty(Yii::$app->session->get('sso_email'));
?>
<div class="site-index">

    <div class="hero-banner text-white rounded-4 p-5 mb-4 position-relative overflow-hidden">
        <div class="position-relative">
            <h1 class="display-6 fw-bold mb-3"><?= Html::encode($this->title) ?></h1>
            <p class="lead opacity-75 mb-4 hero-lead">
                ศูนย์สัตว์ทดลองภาคตะวันออกเฉียงเหนือ มหาวิทยาลัยขอนแก่น
            </p>
            <div class="d-flex gap-2 flex-wrap">
                <?php if ($isLoggedIn): ?>
                    <?= Html::a(
                        '<i class="fas fa-file-lines me-1"></i>ไปที่รายงานของฉัน',
                        ['/report/my-reports'],
                        ['class' => 'btn btn-light btn-lg fw-semibold px-4'],
                    ) ?>
                <?php else: ?>
                    <?= Html::a(
                        '<i class="fas fa-right-to-bracket me-1"></i>เข้าสู่ระบบ',
                        ['/auth/login'],
                        ['class' => 'btn btn-light btn-lg fw-semibold px-4'],
                    ) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?= $this->render('_about-content') ?>

</div>
