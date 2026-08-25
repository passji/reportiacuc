<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\ProgressReport $report */
/** @var string $reason เหตุผลที่แอดมินปฏิเสธ (ระบุจากฟอร์มตรวจสอบ) */

$this->title = 'รายงานความก้าวหน้าโครงการวิจัยถูกปฏิเสธ กรุณาส่งข้อมูลใหม่ — ' . $report->oid;

$appBaseUrl = rtrim((string) (Yii::$app->params['appBaseUrl'] ?? ''), '/');
$link = $appBaseUrl . '/index.php?r=report%2Fcreate&oid=' . urlencode($report->oid);
$piName = $report->pi_name ?: 'หัวหน้าโครงการ';
?>
<p>เรียน <?= Html::encode($piName) ?> (<?= Html::encode($report->project_name_th) ?>)</p>
<p>
    รายงานความก้าวหน้าโครงการวิจัยฉบับที่ท่านส่งเข้าระบบ ได้รับการตรวจสอบแล้ว และคณะกรรมการฯ
    ขอปฏิเสธรายงานฉบับนี้ เนื่องจาก
</p>

<div style="margin:16px 0;padding:16px;background-color:#fdecea;border-left:4px solid #e74a3b;border-radius:6px;">
    <?= nl2br(Html::encode($reason)) ?>
</div>

<p>กรุณาเข้าสู่ระบบเพื่อกรอกและส่งรายงานความก้าวหน้าโครงการฉบับใหม่ตามคำแนะนำข้างต้นโดยเร็วที่สุด</p>

<p style="margin:20px 0;">
    <a href="<?= Html::encode($link) ?>"
       style="background-color:#83C933;color:#ffffff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;display:inline-block;">
        ส่งรายงานความก้าวหน้าโครงการใหม่
    </a>
    <br>
    <span style="color:#6c757d;font-size:13px;">
        หากปุ่มด้านบนกดไม่ได้ ให้คัดลอกลิงก์นี้ไปเปิดในเบราว์เซอร์:<br>
        <?= Html::encode($link) ?>
    </span>
</p>

<p>จึงเรียนมาเพื่อโปรดดำเนินการต่อไป จักขอบคุณยิ่ง</p>
