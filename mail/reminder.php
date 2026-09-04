<?php

use yii\helpers\Html;
use app\helpers\ThaiDate;

/** @var yii\web\View $this */
/** @var app\models\ResearchProject $project */
/** @var string $deadlineDate วันครบกำหนด รูปแบบ Y-m-d */
/** @var string|null $extraNote ข้อความเพิ่มเติมที่แอดมินพิมพ์เอง (ถ้ามี) */

$this->title = 'แจ้งเตือน: รายงานความก้าวหน้าโครงการวิจัย ' . $project->oid;

$appBaseUrl = rtrim((string) (Yii::$app->params['appBaseUrl'] ?? ''), '/');
$link = $appBaseUrl . '/index.php?r=report%2Fcreate&oid=' . urlencode($project->oid);
$piName = $project->m_pro_th ?: 'หัวหน้าโครงการ';
?>
<p>เรียน <?= Html::encode($piName) ?></p>


<p style="font-weight:bold;margin-bottom:8px;">อ้างถึง: ข้อมูลโครงการวิจัยที่ได้รับอนุมัติ</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px;font-size:14px;line-height:1.6;">
    <tr>
        <td>1. รหัสโครงการเลขที่ <?= Html::encode($project->getProjectCode() ?: '-') ?></td>
    </tr>
    <tr>
        <td>
            2. เข้าประชุมครั้งที่ <?= Html::encode($project->meeting_no ?: '-') ?>
            และวันที่พิจารณา <?= Html::encode(ThaiDate::format($project->meeting_date, false)) ?>
        </td>
    </tr>
    <tr>
        <td>3. ชื่อหัวหน้าโครงการ <?= Html::encode($piName) ?></td>
    </tr>
    <tr>
        <td>4. ชื่อโครงการ : ภาษาไทย <?= Html::encode($project->oname ?: '-') ?></td>
    </tr>
    <tr>
        <td>5. ชื่อโครงการ : ภาษาอังกฤษ <?= Html::encode($project->oname_en ?: '-') ?></td>
    </tr>
</table>


<p>
    ตามที่โครงการวิจัยท่าน ได้รับการรับรองจรรยาบรรณการใช้สัตว์เพื่องานทางวิทยาศาสตร์ จากคณะกรรมการกำกับดูแลการดำเนินการต่อสัตว์เพื่องานทางวิทยาศาสตร์
    มหาวิทยาลัยขอนแก่น (คกส.มข.) นั้น ในการนี้ คกส.มข ขอความอนุเคราะห์ให้ท่าน
    รายงานความก้าวหน้าโครงการวิจัยดังกล่าว ตามแบบฟอร์ม ภายในวันที่ <?= Html::encode(ThaiDate::format($deadlineDate, false)) ?>
</p>

<p style="margin:20px 0 8px;font-weight:bold;">แบบฟอร์มรายงานความก้าวหน้าโครงการ</p>
<p style="margin:0 0 20px;">
    <a href="<?= Html::encode($link) ?>"
       style="background-color:#83C933;color:#ffffff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;display:inline-block;">
        แจ้งความก้าวหน้าโครงการ
    </a>
    <br>
    <span style="color:#6c757d;font-size:13px;">
        หากปุ่มด้านบนกดไม่ได้ ให้คัดลอกลิงก์นี้ไปเปิดในเบราว์เซอร์:<br>
        <?= Html::encode($link) ?>
    </span>
</p>


<?php if (!empty($extraNote)): ?>
<div style="margin:16px 0;padding:12px 16px;background-color:#f8f9fa;border-radius:6px;">
    <?= nl2br(Html::encode($extraNote)) ?>
</div>
<?php endif; ?>

<p>จึงเรียนมาเพื่อโปรดดำเนินการต่อไป จักขอบคุณยิ่ง</p>
