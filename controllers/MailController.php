<?php

namespace app\controllers;

use Yii;
use app\models\Admin;
use app\services\MailTestService;

/**
 * เวอร์ชันเว็บของ php yii mail/test — สำหรับ admin ที่ไม่สะดวก SSH เข้า server เอง เรียกผ่าน URL ได้เลย
 * เช่น /index.php?r=mail/test&to=someone@kku.ac.th (จำกัดเฉพาะ admin เหมือน AdminController เพราะ
 * ถ้าเปิดให้ใครก็ได้ยิงได้ จะกลายเป็น open relay ให้เอาไปสแปมคนอื่นผ่าน SMTP ของเราได้)
 */
class MailController extends SecureController
{
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if (!Admin::isEmailAdmin((string) Yii::$app->session->get('sso_email'))) {
            Yii::$app->session->setFlash('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (สำหรับผู้ดูแลระบบเท่านั้น)');
            $this->redirect(['/report/my-reports']);
            return false;
        }
        return true;
    }

    public function actionTest(?string $to = null)
    {
        $result = null;

        if ($to !== null && $to !== '') {
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                Yii::$app->session->setFlash('error', 'อีเมลปลายทางไม่ถูกต้อง: ' . $to);
            } else {
                $result = MailTestService::send($to);
            }
        }

        return $this->render('test', [
            'to' => $to,
            'result' => $result,
        ]);
    }
}
