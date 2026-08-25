<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\helpers\Html;

$this->render('_head');
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100" data-bs-theme="light">
<head>
    <?php $this->head() ?>
    <title><?= Html::encode($this->title) ?></title>
</head>
<body id="page-top">
<?php $this->beginBody() ?>

<div id="wrapper">

    <?= $this->render('_sidebar') ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <?= $this->render('_topbar') ?>

            <div class="container-fluid">
                <?php if (!empty($this->params['breadcrumbs'])): ?>
                    <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
                <?php endif ?>
                <?= Alert::widget() ?>
                <?= $content ?>
            </div>

        </div>

        <?= $this->render('_footer') ?>

    </div>

</div>

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
