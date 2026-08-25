<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\helpers\Html;

$isLoggedIn = !empty(Yii::$app->session->get('sso_email'));
$sessionEmail = (string) Yii::$app->session->get('sso_email', '');
?>
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle me-3" type="button" aria-label="Toggle sidebar">
        <i class="fa fa-bars"></i>
    </button>

    <ul class="navbar-nav ms-auto align-items-center">

        <li class="nav-item">
            <?= Html::button(
                '&#127769;',
                [
                    'id' => 'theme-toggle',
                    'class' => 'btn btn-link nav-link fs-5',
                    'aria-label' => 'Switch to dark mode',
                ]
            ) ?>
        </li>

        <div class="topbar-divider d-none d-sm-block"></div>

        <?php if ($isLoggedIn): ?>
            <li class="nav-item dropdown no-arrow">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="me-2 d-none d-lg-inline text-gray-600 small"><?= Html::encode($sessionEmail) ?></span>
                    <i class="fas fa-circle-user fa-fw fs-4 text-gray-400"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                    <li>
                        <?= Html::beginForm(['/auth/logout'], 'post') ?>
                            <?= Html::submitButton(
                                '<i class="fas fa-right-from-bracket fa-sm fa-fw me-2 text-gray-400"></i>ออกจากระบบ',
                                ['class' => 'dropdown-item']
                            ) ?>
                        <?= Html::endForm() ?>
                    </li>
                </ul>
            </li>
        <?php else: ?>
            <li class="nav-item">
                <?= Html::a(
                    '<i class="fas fa-right-to-bracket fa-fw"></i> เข้าสู่ระบบ',
                    ['/auth/login'],
                    ['class' => 'nav-link']
                ) ?>
            </li>
        <?php endif; ?>

    </ul>

</nav>
