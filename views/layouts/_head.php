<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use app\assets\AppAsset;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(
    ['charset' => Yii::$app->charset],
    'charset',
);
$this->registerMetaTag(
    [
        'name' => 'viewport',
        'content' => 'width=device-width, initial-scale=1',
    ],
);
if (!empty($this->params['meta_description'])) {
    $this->registerMetaTag(
        [
            'name' => 'description',
            'content' => $this->params['meta_description'],
        ],
    );
}
if (!empty($this->params['meta_keywords'])) {
    $this->registerMetaTag(
        [
            'name' => 'keywords',
            'content' => $this->params['meta_keywords'],
        ],
    );
}
// favicon โดนเบราว์เซอร์แคชหนักกว่า asset ทั่วไปมาก (บาง browser จำข้ามการ hard refresh/ข้าม
// cache-busting query string ด้วยซ้ำถ้า URL เคยโหลดมาก่อน) เผื่อไว้ด้วย ?v=<เวลาแก้ไขไฟล์ล่าสุด>
// เหมือน pattern เดียวกับไฟล์ JS/CSS อื่นในระบบ (ดู views/report/create.php) — ถ้าเปลี่ยนโลโก้/favicon
// อีกในอนาคตแล้วยังไม่ขึ้นในแท็บที่เปิดค้างไว้ ให้ปิดแท็บนั้นแล้วเปิดใหม่ หรือ hard refresh
$faviconPath = Yii::getAlias('@webroot/favicon.ico');
$faviconVersion = is_file($faviconPath) ? ('?v=' . filemtime($faviconPath)) : '';
$this->registerLinkTag(
    [
        'rel' => 'icon',
        'type' => 'image/x-icon',
        'href' => Yii::getAlias('@web/favicon.ico') . $faviconVersion,
    ],
);
// ฟอนต์ Sarabun โหลดจากไฟล์ในเครื่อง (web/fonts/sarabun/, ประกาศใน css/site.css)
// ไม่พึ่ง Google Fonts CDN — กันปัญหาเข้า fonts.googleapis.com ไม่ได้ในบางเครือข่าย
