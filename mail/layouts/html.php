<?php

use yii\helpers\Html;

/** @var \yii\web\View $this */
/** @var \yii\mail\MessageInterface $message */
/** @var string $content */
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?= Yii::$app->charset ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:'Leelawadee UI','Tahoma',sans-serif;">
<?php $this->beginBody() ?>
<!-- ใช้ table-based layout ตั้งใจ ไม่ใช้ flexbox/grid — client อีเมลหลายตัว (โดยเฉพาะ Outlook
     desktop) รองรับ CSS สมัยใหม่ไม่ดี table ยังเป็นวิธีที่ compatible ที่สุด -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                   style="background-color:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;width:100%;">
                <tr>
                    <td style="background-color:#3a9cbd;padding:24px 32px;">
                        <span style="color:#ffffff;font-size:18px;font-weight:bold;">
                            ระบบรายงานความก้าวหน้าโครงการวิจัย (IACUC)
                        </span>
                        <br>
                        <span style="color:#e6f4f9;font-size:13px;">มหาวิทยาลัยขอนแก่น</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;color:#212529;font-size:15px;line-height:1.6;">
                        <?= $content ?>
                    </td>
                </tr>
                <tr>
                    <td style="background-color:#f8f9fa;padding:16px 32px;border-top:1px solid #e9ecef;">
                        <span style="color:#6c757d;font-size:12px;">
                            คณะกรรมการกำกับดูแลการดำเนินการต่อสัตว์เพื่องานทางวิทยาศาสตร์ มหาวิทยาลัยขอนแก่น<br>
ศูนย์กำกับดูแลการดำเนินการต่อสัตว์เพื่องานทางวิทยาศาสตร์ (SuMoACUCKKU)<br>
มหาวิทยาลัยขอนแก่น 40002<br>
โทรศัพท์ 043-009700, 043-002539 ต่อ 51035<br>
เบอร์ภายใน 51035   <br>
https://sumoacuc.kku.ac.th<br>
email :acuckku@kku.ac.th<br>
                            อีเมลนี้ส่งจากระบบอัตโนมัติ กรุณาอย่าตอบกลับอีเมลฉบับนี้<br>
                            
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
