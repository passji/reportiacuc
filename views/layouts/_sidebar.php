<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Admin;

$isLoggedIn = !empty(Yii::$app->session->get('sso_email'));
$isAdmin = Admin::isEmailAdmin((string) Yii::$app->session->get('sso_email'));
$route = Yii::$app->controller->route;

// "Home" ชี้ไปที่ homeUrl (report/my-reports ตาม config/web.php) ไม่ใช่ site/index แบบเดิม (หน้า
// boilerplate ของ Yii เฉยๆ ไม่เกี่ยวกับระบบนี้เลย ต้นเหตุของปัญหา "login แล้วดูเหมือนไม่ login" ก่อน
// หน้านี้) ซ้ำกับ "รายงานของฉัน" โดยตั้งใจ (active พร้อมกันทั้งคู่ตอนอยู่หน้านั้น) เพราะผู้ใช้คาดหวังให้
// มีรายการ "Home" อยู่เสมอ
$navItems = [
    ['label' => 'Home', 'icon' => 'fa-house', 'url' => Yii::$app->homeUrl, 'route' => 'report/my-reports', 'visible' => true],
    ['label' => 'รายงานของฉัน', 'icon' => 'fa-file-lines', 'url' => ['/report/my-reports'], 'route' => 'report/my-reports', 'visible' => $isLoggedIn],
];
$staffToolsItems = [
    ['label' => 'ตรวจสอบรายงาน', 'icon' => 'fa-clipboard-check', 'url' => ['/admin/review-queue'], 'route' => 'admin/review-queue'],    
    ['label' => 'รายการโครงการ', 'icon' => 'fa-folder-open', 'url' => ['/report/index'], 'route' => 'report/index'],
    ['label' => 'Email', 'icon' => 'fa-envelope-open-text', 'url' => ['/admin/email'], 'route' => 'admin/email'],
    ['label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'url' => ['/dashboard/index'], 'route' => 'dashboard/index'],
    ['label' => 'ตั้งค่าแจ้งเตือน', 'icon' => 'fa-bell', 'url' => ['/admin/notification-settings'], 'route' => 'admin/notification-settings'],
    ['label' => 'ตั้งค่า ADMIN', 'icon' => 'fa-user-shield', 'url' => ['/admin/settings'], 'route' => 'admin/settings'],
];
$infoItems = [
    ['label' => 'About', 'icon' => 'fa-circle-info', 'url' => ['/site/about'], 'route' => 'site/about'],
];

?>
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= Html::encode(Url::to(Yii::$app->homeUrl)) ?>">
        <div class="sidebar-brand-icon">
            <i class="fas fa-notes-medical"></i>
        </div>
        <div class="sidebar-brand-text mx-3">IACUC</div>
    </a>

    <hr class="sidebar-divider my-0">

    <?php foreach ($navItems as $item): ?>
        <?php if (!$item['visible']) {
            continue;
        } ?>
        <li class="nav-item<?= $route === $item['route'] ? ' active' : '' ?>">
            <?= Html::a(
                '<i class="fas fa-fw ' . $item['icon'] . '"></i><span>' . Html::encode($item['label']) . '</span>',
                $item['url'],
                ['class' => 'nav-link']
            ) ?>
        </li>
    <?php endforeach; ?>

    <?php if ($isAdmin): ?>
        <hr class="sidebar-divider">
        <div class="sidebar-heading">เครื่องมือเจ้าหน้าที่</div>

        <?php foreach ($staffToolsItems as $item): ?>
            <li class="nav-item<?= $route === $item['route'] ? ' active' : '' ?>">
                <?= Html::a(
                    '<i class="fas fa-fw ' . $item['icon'] . '"></i><span>' . Html::encode($item['label']) . '</span>',
                    $item['url'],
                    ['class' => 'nav-link']
                ) ?>
            </li>
        <?php endforeach; ?>
    <?php endif; ?>

    <hr class="sidebar-divider">

    <?php foreach ($infoItems as $item): ?>
        <li class="nav-item<?= $route === $item['route'] ? ' active' : '' ?>">
            <?= Html::a(
                '<i class="fas fa-fw ' . $item['icon'] . '"></i><span>' . Html::encode($item['label']) . '</span>',
                $item['url'],
                ['class' => 'nav-link']
            ) ?>
        </li>
    <?php endforeach; ?>

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle" type="button" aria-label="Toggle sidebar"></button>
    </div>

</ul>
