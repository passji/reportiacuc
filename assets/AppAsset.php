<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace app\assets;

use yii\bootstrap5\BootstrapAsset;
use yii\bootstrap5\BootstrapPluginAsset;
use yii\web\AssetBundle;
use yii\web\View;
use yii\web\YiiAsset;

/**
 * Main application asset bundle.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/vendor/fontawesome.min.css',
        'css/vendor/sb-admin-2.css',
        'css/site.css',
    ];
    public $js = [
        'js/color-mode.js',
        'js/sidebar.js',
    ];
    public $jsOptions = [
        'position' => View::POS_HEAD,
    ];
    public $depends = [
        YiiAsset::class,
        BootstrapAsset::class,
        // เดิม BootstrapPluginAsset (bootstrap.bundle.js — จำเป็นสำหรับ dropdown/collapse ฯลฯ)
        // ถูก register อัตโนมัติผ่าน widget yii\bootstrap5\NavBar/Nav ใน _header.php เดิม —
        // ตอนนี้ sidebar/topbar เขียน markup Bootstrap 5 เอง (ไม่ผ่าน widget) จึงต้อง
        // ประกาศ dependency นี้ตรงๆ ไม่งั้น dropdown ผู้ใช้ในหน้า topbar จะกดไม่ทำงาน
        BootstrapPluginAsset::class,
    ];
}
