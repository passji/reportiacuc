<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'วัตถุประสงค์ของการรายงานความก้าวหน้าโครงการ';
$this->params['breadcrumbs'][] = $this->title;
$this->params['meta_description'] = 'วัตถุประสงค์ของการรายงานความก้าวหน้าโครงการเลี้ยงและใช้สัตว์เพื่องานทางวิทยาศาสตร์';
?>
<div class="site-about">
    <?= $this->render('_about-content') ?>

    <div class="mx-auto mt-4" style="max-width: 860px;">
        <?= Html::a(
            '<i class="fas fa-house me-1"></i>กลับหน้าแรก',
            Yii::$app->homeUrl,
            ['class' => 'btn btn-outline-primary'],
        ) ?>
    </div>
</div>
