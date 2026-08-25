<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\BadRequestHttpException;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

/**
 * Mock login for Phase 1 — checks only that the email ends in @kku.ac.th.
 * Route names (auth/login, auth/logout) and session keys (sso_email, sso_name)
 * match what the real SSONext integration (Phase 8) will use, so swapping this
 * controller's body out later doesn't require touching any other controller.
 *
 * "Login ด้วย Google" (actionGoogle/actionGoogleCallback) เป็นทางเลือกที่สองคู่กับ mock login
 * ด้านบน ไม่ได้แทนที่แผน SSONext — เขียน session key เดียวกัน (sso_email/sso_name) จึงใช้ร่วมกับ
 * ส่วนอื่นของระบบ (SecureController ฯลฯ) ได้ทันทีโดยไม่ต้องแก้ที่อื่น อนุญาตทุกบัญชี Google ไม่จำกัด
 * โดเมน @kku.ac.th (ต่างจากอีเมลใน mock form ที่บังคับ @kku.ac.th)
 */
class AuthController extends Controller
{
    public function actionLogin()
    {
        $model = new \yii\base\DynamicModel(['email']);
        $model->addRule('email', 'required')
              ->addRule('email', 'email')
              ->addRule('email', 'match', [
                  'pattern' => '/@kku\.ac\.th$/',
                  'message' => 'อนุญาตเฉพาะอีเมล @kku.ac.th เท่านั้น',
              ]);

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            Yii::$app->session->set('sso_email', $model->email);
            Yii::$app->session->set('sso_name', explode('@', $model->email)[0]);

            $returnUrl = Yii::$app->session->get('login_return_url', Yii::$app->homeUrl);
            return $this->redirect($returnUrl);
        }

        return $this->render('login', ['model' => $model]);
    }

    public function actionLogout()
    {
        Yii::$app->session->remove('sso_access_token');
        Yii::$app->session->remove('sso_email');
        Yii::$app->session->remove('sso_name');
        // ไม่ใช้ homeUrl (/report/my-reports) เพราะเป็นหน้าที่ต้อง login — ไปแล้วโดน SecureController
        // เด้งกลับมาหน้า login อีกทีอยู่ดี ตรงไปหน้า login เลยตัดขั้นตอน redirect ซ้อนออก
        return $this->redirect(['login']);
    }

    /**
     * เริ่ม OAuth flow — สร้าง state token ของตัวเอง (เก็บใน session) ผูกกับ URL ที่ Google
     * จะ redirect กลับมา กัน CSRF ตามที่ OAuth2 spec แนะนำ (League client จัดการให้อัตโนมัติ
     * ผ่าน getAuthorizationUrl()/getState())
     */
    public function actionGoogle()
    {
        $provider = $this->buildGoogleProvider();
        if ($provider === null) {
            Yii::$app->session->setFlash('error', 'ยังไม่ได้ตั้งค่า Google Login (GOOGLE_CLIENT_ID/GOOGLE_CLIENT_SECRET) กรุณาใช้ Mock Login ด้านล่างไปก่อน');
            return $this->redirect(['login']);
        }

        $authUrl = $provider->getAuthorizationUrl(['scope' => ['email', 'profile']]);
        Yii::$app->session->set('google_oauth_state', $provider->getState());

        return $this->redirect($authUrl);
    }

    /**
     * ปลายทางที่ Google redirect กลับมาหลังผู้ใช้ยินยอม — ตรวจ state กัน CSRF ก่อนแลก code เป็น
     * token แล้วดึงโปรไฟล์ (getResourceOwner) เอาแค่อีเมลมาเขียน session key เดียวกับ mock login
     * (sso_email/sso_name) ปฏิเสธถ้า Google ยังไม่ยืนยันอีเมลนั้น (email_verified=false) กันบัญชี
     * ปลอม/ยังไม่ verify เข้าระบบ
     */
    public function actionGoogleCallback()
    {
        $provider = $this->buildGoogleProvider();
        if ($provider === null) {
            throw new BadRequestHttpException('ยังไม่ได้ตั้งค่า Google Login');
        }

        $request = Yii::$app->request;
        $expectedState = Yii::$app->session->get('google_oauth_state');
        Yii::$app->session->remove('google_oauth_state');

        if ($request->get('error') !== null) {
            Yii::$app->session->setFlash('error', 'ยกเลิกการเข้าสู่ระบบด้วย Google');
            return $this->redirect(['login']);
        }

        $state = $request->get('state');
        if (empty($state) || empty($expectedState) || $state !== $expectedState) {
            throw new BadRequestHttpException('Invalid OAuth state');
        }

        try {
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $request->get('code'),
            ]);
            $googleUser = $provider->getResourceOwner($token);
        } catch (IdentityProviderException $e) {
            Yii::error((string) $e, __METHOD__);
            Yii::$app->session->setFlash('error', 'เข้าสู่ระบบด้วย Google ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
            return $this->redirect(['login']);
        }

        $email = $googleUser->getEmail();
        if (empty($email) || !$googleUser->isEmailTrustworthy()) {
            Yii::$app->session->setFlash('error', 'บัญชี Google นี้ยังไม่ได้ยืนยันอีเมล');
            return $this->redirect(['login']);
        }

        Yii::$app->session->set('sso_email', $email);
        Yii::$app->session->set('sso_name', $googleUser->getName() ?: explode('@', $email)[0]);

        $returnUrl = Yii::$app->session->get('login_return_url', Yii::$app->homeUrl);
        return $this->redirect($returnUrl);
    }

    /**
     * คืน null ถ้ายังไม่ได้ตั้งค่า env var (GOOGLE_CLIENT_ID/GOOGLE_CLIENT_SECRET ว่าง) แทนที่จะ
     * โยน exception ตรงๆ ให้ผู้เรียก (actionGoogle/actionGoogleCallback) ตัดสินใจแสดง flash message
     * ที่เข้าใจง่ายแทน stack trace ของ League client
     */
    private function buildGoogleProvider(): ?Google
    {
        $clientId = (string) getenv('GOOGLE_CLIENT_ID');
        $clientSecret = (string) getenv('GOOGLE_CLIENT_SECRET');
        $redirectUrl = (string) getenv('GOOGLE_REDIRECT_URL');

        if ($clientId === '' || $clientSecret === '' || $redirectUrl === '') {
            return null;
        }

        return new Google([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri' => $redirectUrl,
        ]);
    }
}
