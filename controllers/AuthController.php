<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\BadRequestHttpException;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

/**
 * Route names (auth/login, auth/logout) และ session key (sso_email, sso_name) ตั้งชื่อให้ตรงกับที่
 * SSONext integration จริง (Phase 8) จะใช้ ตอนเปลี่ยนไปใช้ SSO จริงในอนาคตจะได้ไม่ต้องแก้ที่อื่น
 * (SecureController ฯลฯ เช็คแค่ session key เดียวกันนี้ ไม่สนว่ามาจากไหน)
 *
 * ตอนนี้มีทางเข้าเดียวคือ "Login ด้วย Google" (actionGoogle/actionGoogleCallback) — เดิมมี mock
 * login แบบกรอกอีเมล @kku.ac.th เองไม่ตรวจสอบจริงคู่กันไว้สำหรับช่วงพัฒนา Phase 1 ตอนนี้เอาออกแล้ว
 * เพราะใช้งานจริงแล้ว (อนุญาตทุกบัญชี Google ไม่จำกัดโดเมน @kku.ac.th)
 */
class AuthController extends Controller
{
    public function actionLogin()
    {
        return $this->render('login');
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
            Yii::$app->session->setFlash('error', 'ยังไม่ได้ตั้งค่า Google Login (GOOGLE_CLIENT_ID/GOOGLE_CLIENT_SECRET) กรุณาติดต่อผู้ดูแลระบบ');
            return $this->redirect(['login']);
        }

        $authUrl = $provider->getAuthorizationUrl(['scope' => ['email', 'profile']]);
        Yii::$app->session->set('google_oauth_state', $provider->getState());

        return $this->redirect($authUrl);
    }

    /**
     * ปลายทางที่ Google redirect กลับมาหลังผู้ใช้ยินยอม — ตรวจ state กัน CSRF ก่อนแลก code เป็น
     * token แล้วดึงโปรไฟล์ (getResourceOwner) เอาแค่อีเมลมาเขียน session key เดียวกับที่ SSONext
     * จริงจะใช้ (sso_email/sso_name) ปฏิเสธถ้า Google ยังไม่ยืนยันอีเมลนั้น (email_verified=false)
     * กันบัญชีปลอม/ยังไม่ verify เข้าระบบ
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
