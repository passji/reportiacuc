<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;

/**
 * Controller ฐานสำหรับหน้าที่ต้อง login ด้วยอีเมล @kku.ac.th ก่อน (เช็คแค่ session key
 * sso_email — ใช้ได้ทั้ง mock login และ SSONext จริงในอนาคต เพราะ session key ชื่อเดียวกัน)
 */
abstract class SecureController extends Controller
{
    public function beforeAction($action)
    {
        if (empty(Yii::$app->session->get('sso_email'))) {
            Yii::$app->session->set('login_return_url', Yii::$app->request->url);
            // beforeAction() must return a bool — Controller::runAction() only
            // skips the action when this is exactly false. Returning the
            // Response object from redirect() is truthy, so it would NOT stop
            // the action from also running. Configure the redirect on the
            // response singleton, then return false to actually stop it.
            $this->redirect(['auth/login']);
            return false;
        }
        return parent::beforeAction($action);
    }
}
