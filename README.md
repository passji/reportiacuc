# ระบบรายงานความก้าวหน้าและแจ้งปิดโครงการวิจัย (IACUC Progress Report System)

## ภาพรวมระบบ

ระบบดึงข้อมูลโครงการวิจัยที่ผ่านการรับรองจากระบบภายนอก ("ระบบ A" — ระบบเดิมที่เราเข้าไม่ถึงฐานข้อมูลโดยตรง) แล้วให้ผู้ใช้กรอกแบบฟอร์มรายงานความก้าวหน้า/แจ้งปิดโครงการ ตามแบบฟอร์มของคณะกรรมการกำกับดูแลการดำเนินการต่อสัตว์เพื่องานทางวิทยาศาสตร์ มข. (IACUC)

> **อัปเดตสถาปัตยกรรม:** พบว่าระบบ A มี public GET endpoint ที่คืนข้อมูลโครงการเป็น JSON ได้ทันทีจาก oid โดยไม่ต้อง auth
> `GET https://iacuc.kku.ac.th/offer/detail3/{oid}/nelac`
> จึง**ไม่จำเป็นต้องให้ระบบ A เขียนโค้ด POST ส่งข้อมูลมาอีกต่อไป** — ระบบ A แค่ทำลิงก์พร้อม `oid` แล้วให้ระบบเราไปดึงข้อมูลสดเองตอนต้องใช้ ลดภาระฝั่งระบบ A ได้มาก แต่เนื่องจาก endpoint นี้ไม่มี auth ใครก็ตามที่รู้ oid ก็เปิดฟอร์มได้ จึงต้องมีการยืนยันตัวตนผู้ส่งรายงานแทน (ดูหัวข้อ Security ด้านล่าง)

**Flow หลัก (ปัจจุบัน — ใช้ GET fetch on-demand):**
1. ผู้ใช้กดปุ่ม/ลิงก์ "แจ้งความก้าวหน้า" ในระบบ A → ไปที่ `https://report.icead.kku.ac.th/report/create?oid={oid}` (ระบบ A ไม่ต้องเขียนโค้ด แค่ทำลิงก์)
2. ผู้ใช้ล็อกอินผ่าน KKU SSO (SSONext) ถ้ายังไม่เคยล็อกอินในเซสชันนี้ — ระบบตรวจสอบว่าอีเมลที่ได้ลงท้ายด้วย `@kku.ac.th`
3. ระบบเรา (Yii2) ยิง `GET https://iacuc.kku.ac.th/offer/detail3/{oid}/nelac` ดึงข้อมูลโครงการสด
4. บันทึก/อัปเดตข้อมูลลง `research_projects` โดยใช้ `oid` เป็น key หลัก (upsert, เก็บ raw_json ไว้เป็นหลักฐาน)
5. แสดงฟอร์มพร้อม pre-fill ข้อมูลโครงการ
6. ผู้ใช้กรอกและส่งรายงาน → บันทึกลง MariaDB ผูกกับ `oid` และอีเมลผู้ล็อกอิน (เผื่อ audit ภายหลังว่าใครส่งจริง)

> **หมายเหตุ:** 1 โครงการ (`oid`) รายงานความก้าวหน้าได้หลายครั้ง — แต่ละครั้งที่ส่งฟอร์มจะสร้างแถวใหม่ใน `progress_reports` ไม่ทับของเดิม เพื่อเก็บประวัติการรายงานทั้งหมดของโครงการนั้นไว้

**Flow สำรอง (Optional fallback — POST push จากระบบ A):** เก็บโค้ดไว้ในหัวข้อท้ายไฟล์ เผื่อวันหนึ่งต้องการให้ระบบ A ส่งข้อมูล/แจ้งเตือนแบบ real-time เอง (เช่น ตอนสถานะโครงการเปลี่ยนในระบบ A) โดยไม่ต้องรอผู้ใช้กดลิงก์

## Tech Stack

| ส่วน | เทคโนโลยี |
|---|---|
| Backend | Yii2 (Basic Template) |
| Database | MariaDB (Docker) |
| DB Admin | phpMyAdmin (Docker) |
| Auth ผู้ใช้ | KKU Single Sign On (SSONext, UAT-Stage) |
| แหล่งข้อมูลโครงการ | GET `iacuc.kku.ac.th/offer/detail3/{oid}/nelac` (public, ไม่ต้อง auth) |
| Deployment | Docker Compose, network pattern `proxy_net` / `db_net` |

## Auth: Mock Login (Phase 1) → SSONext จริง (Phase ถัดไป)

**Phase 1 ใช้ mock login ไปก่อน** เพื่อไม่ต้องรอทีม SSONext ออก App ID/Client ID/Secret — แค่ให้ผู้ใช้กรอกอีเมลเองแล้วเช็คโดเมน `@kku.ac.th` โดย**คงชื่อ route (`auth/login`, `auth/logout`) และชื่อ session key (`sso_email`, `sso_name`) ให้เหมือนของจริงทุกอย่าง** เพื่อให้ตอนสลับไปใช้ SSONext จริงในภายหลัง แค่เปลี่ยนเนื้อใน `AuthController` ไฟล์เดียว ไม่ต้องแก้ `ReportController` หรือส่วนอื่นเลย

### Yii2 Controller — AuthController (Mock, ใช้ใน Phase 1)

```php
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

            $returnUrl = Yii::$app->session->get('login_return_url', ['dashboard/index']);
            return $this->redirect($returnUrl);
        }

        return $this->render('login', ['model' => $model]);
    }

    public function actionLogout()
    {
        Yii::$app->session->remove('sso_access_token');
        Yii::$app->session->remove('sso_email');
        Yii::$app->session->remove('sso_name');
        return $this->redirect(['site/index']);
    }
}
```

`views/auth/login.php` — ฟอร์มง่ายๆ ให้กรอกอีเมล ไม่มีรหัสผ่าน (เพราะเป็น mock ชั่วคราว):

```php
<?= \yii\bootstrap5\ActiveForm::begin() ?>
    <?= $form->field($model, 'email')->textInput(['placeholder' => 'name@kku.ac.th']) ?>
    <?= \yii\helpers\Html::submitButton('เข้าสู่ระบบ (Mock)', ['class' => 'btn btn-primary']) ?>
<?= \yii\bootstrap5\ActiveForm::end() ?>
<p class="text-muted small">โหมดนี้เป็น Mock Login ชั่วคราว ยังไม่ได้เชื่อมกับ KKU SSO จริง</p>
```

### เมื่อพร้อมเชื่อม SSONext จริง (แทนที่ไฟล์ AuthController ด้านบน)

ขอ App ID/Client ID/Client Secret จากทีม SSONext ก่อน (ธีรวัฒน์ พูลสวัสดิ์ — teerpo@kku.ac.th, สำนักเทคโนโลยีดิจิทัล มข.) แล้วแจ้ง Redirect URL `https://report.icead.kku.ac.th/auth/callback/login`

**Flow การล็อกอิน (UAT-Stage):**
1. ผู้ใช้กด login → redirect ไป `https://sso-uat-web.kku.ac.th/login?app=<AppID>`
2. ล็อกอินสำเร็จ → SSO redirect กลับมาที่ `.../auth/callback/login?code=<code>`
3. ระบบเราเอา `code` ไปแลก access token ที่ `POST https://sso-uat-api.kku.ac.th/auth.token` พร้อม `clientId`, `clientSecret`, `redirectUrl`
4. ได้ `accessToken` + `email` + ข้อมูลผู้ใช้กลับมาทันที
5. เช็คว่า `email` ลงท้าย `@kku.ac.th` — ถ้าไม่ใช่ ปฏิเสธการเข้าใช้งาน
6. เก็บ `accessToken` + `email` ไว้ใน session ของ Yii2 (ใช้ key เดิม `sso_email`, `sso_access_token` เหมือน mock)

```php
class AuthController extends Controller
{
    public function actionLogin()
    {
        $appId = Yii::$app->params['sso']['appId'];
        return $this->redirect("https://sso-uat-web.kku.ac.th/login?app={$appId}");
    }

    public function actionCallbackLogin()
    {
        $code = Yii::$app->request->get('code');
        if (empty($code)) {
            throw new \yii\web\BadRequestHttpException('ไม่พบ code จาก SSO');
        }

        $ch = curl_init('https://sso-uat-api.kku.ac.th/auth.token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'code'         => $code,
                'redirectUrl'  => Yii::$app->params['sso']['redirectUrl'],
                'clientId'     => Yii::$app->params['sso']['clientId'],
                'clientSecret' => Yii::$app->params['sso']['clientSecret'],
            ]),
        ]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (empty($response['ok'])) {
            throw new \yii\web\ForbiddenHttpException('ล็อกอินไม่สำเร็จ: ' . ($response['error'] ?? 'unknown'));
        }

        if (!str_ends_with($response['email'], '@kku.ac.th')) {
            throw new \yii\web\ForbiddenHttpException('อนุญาตเฉพาะบัญชี @kku.ac.th เท่านั้น');
        }

        Yii::$app->session->set('sso_access_token', $response['accessToken']);
        Yii::$app->session->set('sso_email', $response['email']);
        Yii::$app->session->set('sso_name', $response['firstName'] . ' ' . $response['lastName']);

        $returnUrl = Yii::$app->session->get('login_return_url', ['dashboard/index']);
        return $this->redirect($returnUrl);
    }

    public function actionLogout()
    {
        $appId = Yii::$app->params['sso']['appId'];
        Yii::$app->session->remove('sso_access_token');
        Yii::$app->session->remove('sso_email');
        return $this->redirect("https://sso-uat-web.kku.ac.th/logout?app={$appId}");
    }

    public function actionCallbackLogout()
    {
        return $this->redirect(['site/index']);
    }
}
```

- **หมายเหตุ:** เอกสารที่ได้มาเป็น environment UAT (`sso-uat-web` / `sso-uat-api`) เมื่อขึ้น production ต้องขอ URL ชุด production จากทีม SSONext แล้วสลับใน env variable
- บันทึกอีเมลผู้ล็อกอินไว้คู่กับทุกรายงานที่ส่ง (`submitted_by_email` ใน `progress_reports`) เพื่อ audit ย้อนหลังได้ว่าใครส่งจริง
- (ทางเลือกเสริม) ถ้าต้องการเข้มขึ้น อาจเทียบอีเมลผู้ล็อกอินกับ `s_email`/`cont_email` ที่ดึงมาจากข้อมูลโครงการ ถ้าไม่ตรงให้ระบบขึ้นเตือน/ต้องรอ admin อนุมัติก่อนบันทึกจริง — เก็บเป็น phase ถัดไปได้

### Access filter — บังคับล็อกอินก่อนเข้าฟอร์ม (ใช้ได้ทั้ง mock และ SSO จริง เพราะเช็คแค่ session key)

```php
// controllers/ReportController.php
public function beforeAction($action)
{
    if (empty(Yii::$app->session->get('sso_email'))) {
        Yii::$app->session->set('login_return_url', Yii::$app->request->url);
        return $this->redirect(['auth/login']);
    }
    return parent::beforeAction($action);
}
```

## Database Schema

```sql
-- ตารางเก็บข้อมูลโครงการ (upsert ด้วย oid ทุกครั้งที่ fetch สด)
CREATE TABLE research_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    oid VARCHAR(20) NOT NULL UNIQUE,
    oname TEXT,
    oname_en TEXT,
    m_pro_th VARCHAR(255),
    m_pro_en VARCHAR(255),
    m_pro_dept_th TEXT,
    md_name VARCHAR(255),
    meeting_no VARCHAR(20),
    meeting_date DATETIME NULL,
    s_email VARCHAR(255),
    s_phone VARCHAR(20),
    raw_json JSON NOT NULL,
    received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางรายงานความก้าวหน้า (แม็ปตรงกับฟอร์ม PDF ข้อ 1-5)
-- โครงการหนึ่งรายงานได้หลายรอบ จึงไม่ทับข้อมูลเดิม
CREATE TABLE progress_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    oid VARCHAR(20) NOT NULL,
    project_code VARCHAR(50) NOT NULL,           -- ข้อ 1: รหัสโครงการ
    meeting_ref VARCHAR(255) NOT NULL,            -- ข้อ 2: เข้าประชุมครั้งที่/วันที่พิจารณา
    pi_name VARCHAR(255) NOT NULL,                -- ข้อ 3: ชื่อหัวหน้าโครงการ
    project_name_th TEXT NOT NULL,                -- ข้อ 4
    project_name_en TEXT NOT NULL,                -- ข้อ 5
    objective_changed ENUM('same','changed') NOT NULL,   -- ข้อ B.1
    objective_change_detail TEXT NULL,
    status ENUM('not_started','in_progress','completed','terminated_early','cancelled') NOT NULL, -- ข้อ 2.1
    expected_start_date DATE NULL,
    expected_complete_date DATE NULL,
    completed_date DATE NULL,
    stop_reason TEXT NULL,
    animal_requested TEXT NOT NULL,               -- ข้อ 3.1
    animal_used TEXT NOT NULL,                    -- ข้อ 3.2
    method_changed ENUM('yes','no') NOT NULL,      -- ข้อ 4.1
    method_change_detail TEXT NULL,
    post_study_handling TEXT NULL,                 -- ข้อ 4.2
    adverse_event ENUM('yes','no') NOT NULL,        -- ข้อ 4.3
    adverse_event_detail TEXT NULL,
    personnel_changed ENUM('yes','no') NOT NULL,    -- ข้อ 4.4
    personnel_change_detail TEXT NULL,
    alt_method_found ENUM('yes','no') NOT NULL,     -- ข้อ 4.5
    alt_method_detail TEXT NULL,
    study_summary TEXT NOT NULL,                    -- ข้อ 5
    has_publication ENUM('yes','no') NOT NULL,
    submitted_by_email VARCHAR(255) NOT NULL,       -- อีเมลผู้ล็อกอินที่ส่งรายงานนี้ (audit)
    status_flag ENUM('draft','submitted') DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (oid) REFERENCES research_projects(oid)
);

-- ตารางลูก: ผลงานตีพิมพ์ (ข้อ 6.1) — ทำซ้ำได้หลายรายการ
CREATE TABLE report_publications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    article_title TEXT,
    journal_name VARCHAR(255),
    issue VARCHAR(50),
    page VARCHAR(50),
    pub_month VARCHAR(20),
    pub_year VARCHAR(10),
    doi VARCHAR(255),
    level ENUM('national','international'),
    db_type ENUM('ISI','Scopus','TCI','other'),
    db_other VARCHAR(255),
    quartile VARCHAR(20),
    impact_factor VARCHAR(20),
    FOREIGN KEY (report_id) REFERENCES progress_reports(id)
);

-- ตารางลูก: การยื่นจดทรัพย์สินทางปัญญา (ข้อ 6.2) — ทำซ้ำได้หลายรายการ
CREATE TABLE report_ip_filings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    ip_type ENUM('patent','petty_patent','copyright'),
    filed_date DATE,
    registration_no VARCHAR(100),
    asset_name TEXT,
    FOREIGN KEY (report_id) REFERENCES progress_reports(id)
);

-- ตาราง log การส่งอีเมลแจ้งเตือนให้เจ้าของโครงการส่งรายงาน
CREATE TABLE report_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    oid VARCHAR(20) NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    trigger_type ENUM('auto_monthly','manual_admin') NOT NULL,
    triggered_by VARCHAR(255) NULL,     -- username admin ถ้าเป็น manual
    sent_status ENUM('sent','failed') NOT NULL,
    error_message TEXT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (oid) REFERENCES research_projects(oid)
);
```

## การดึงข้อมูลโครงการจากระบบ A (GET, on-demand)

### Service class — ดึงข้อมูลสดทุกครั้งที่เข้าฟอร์ม

```php
// services/ProjectSourceService.php
class ProjectSourceService
{
    private const SOURCE_URL = 'https://iacuc.kku.ac.th/offer/detail3/%s/nelac';

    public static function fetchAndUpsert(string $oid): ?ResearchProject
    {
        $url = sprintf(self::SOURCE_URL, $oid);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($response)) {
            return null;
        }

        $items = json_decode($response, true);
        $item = $items[0] ?? null;
        if (empty($item['oid'])) {
            return null;
        }

        $project = ResearchProject::findOne(['oid' => $item['oid']]) ?? new ResearchProject();
        $project->setAttributes([
            'oid'           => $item['oid'],
            'oname'         => $item['oname'] ?? null,
            'oname_en'      => $item['oname_en'] ?? null,
            'm_pro_th'      => $item['m_pro_th'] ?? null,
            'm_pro_en'      => $item['m_pro_en'] ?? null,
            'm_pro_dept_th' => $item['m_pro_dept_th'] ?? null,
            'md_name'       => $item['md_name'] ?? null,
            'meeting_no'    => $item['meeting_no'] ?? null,
            'meeting_date'  => $item['meeting_date'] ?? null,
            's_email'       => $item['s_email'] ?? null,
            's_phone'       => $item['s_phone'] ?? null,
            'raw_json'      => json_encode($item, JSON_UNESCAPED_UNICODE),
        ], false);

        return $project->save() ? $project : null;
    }
}
```

### Yii2 Controller — ReportController

```php
public function actionCreate($oid)
{
    // บังคับล็อกอิน @kku.ac.th ผ่าน beforeAction() ด้านบน (ดูหัวข้อ Security)

    $project = ProjectSourceService::fetchAndUpsert($oid);
    if (!$project) {
        throw new \yii\web\NotFoundHttpException('ไม่พบโครงการนี้ หรือระบบต้นทางไม่ตอบสนอง');
    }

    $model = new ProgressReport();
    $model->oid = $project->oid;
    $model->pi_name = $project->m_pro_th;
    $model->project_name_th = $project->oname;
    $model->project_name_en = $project->oname_en;
    $model->submitted_by_email = Yii::$app->session->get('sso_email');

    if ($model->load(Yii::$app->request->post()) && $model->save()) {
        return $this->redirect(['view', 'id' => $model->id]);
    }

    return $this->render('create', ['model' => $model, 'project' => $project]);
}
```

### ลิงก์ฝั่งระบบ A (ไม่ต้องเขียนโค้ดฝั่ง server อีกต่อไป)

```html
<a href="https://report.icead.kku.ac.th/report/create?oid=<?= $oid ?>" target="_blank">
    แจ้งความก้าวหน้าโครงการ
</a>
```

## ระบบแจ้งเตือนอีเมล (รายเดือน + Admin สั่งส่งเอง)

**เงื่อนไขการเลือกโครงการที่ต้องเตือน:** โครงการที่ `research_projects` มีอยู่ แต่รายงานล่าสุด (`progress_reports` ล่าสุดของ `oid` นั้น) ยังไม่มี หรือมี `status` เป็น `not_started` / `in_progress` เท่านั้น (ไม่เตือนโครงการที่ `completed`, `terminated_early`, หรือ `cancelled` แล้ว)

### Console command — รันอัตโนมัติทุกเดือน (cron)

```php
// commands/ReminderController.php
class ReminderController extends \yii\console\Controller
{
    public function actionSendMonthly()
    {
        $pending = $this->getPendingProjects();
        foreach ($pending as $project) {
            $this->sendReminder($project, 'auto_monthly', null);
        }
        echo count($pending) . " ฉบับ ส่งแจ้งเตือนอัตโนมัติเรียบร้อย\n";
    }

    private function getPendingProjects()
    {
        // โครงการที่ไม่มีรายงานเลย หรือรายงานล่าสุดยังไม่ completed/terminated/cancelled
        return ResearchProject::find()
            ->where(['not in', 'oid',
                ProgressReport::find()->select('oid')
                    ->where(['status' => ['completed', 'terminated_early', 'cancelled']])
            ])
            ->all();
    }

    private function sendReminder($project, $type, $admin)
    {
        $status = 'sent';
        $error = null;
        try {
            Yii::$app->mailer->compose('reminder', ['project' => $project])
                ->setTo($project->s_email)
                ->setSubject('แจ้งเตือน: กรุณารายงานความก้าวหน้าโครงการวิจัย ' . $project->oid)
                ->send();
        } catch (\Exception $e) {
            $status = 'failed';
            $error = $e->getMessage();
        }

        (new ReportNotification([
            'oid' => $project->oid,
            'recipient_email' => $project->s_email,
            'trigger_type' => $type,
            'triggered_by' => $admin,
            'sent_status' => $status,
            'error_message' => $error,
        ]))->save(false);
    }
}
```

ตั้ง cron (crontab บน host หรือใน docker-compose ด้วย `ofelia`/scheduler container):
```
0 9 1 * * php /app/yii reminder/send-monthly
```

### รอบการส่งรายงานที่แอดมินตั้งเอง (`admin/notification-settings`) — cron รายวัน

นอกจาก cron รายเดือนข้างต้น แอดมินยังตั้ง "รอบการส่งรายงาน" เองได้ที่หน้า `admin/notification-settings`
(ชื่อรอบ + กำหนดการส่งรายงาน + วันที่ส่งอีเมลแจ้งเตือน — เป็นรอบครั้งเดียว ไม่ทำซ้ำอัตโนมัติ) เก็บไว้ที่ตาราง
`reminder_cycles` ต้องตั้ง cron **รายวัน** แยกต่างหากเพื่อเช็คว่ามีรอบไหนถึงกำหนด `notify_date` แล้วบ้าง:

```
0 9 * * * php /app/yii reminder/send-scheduled
```

คำสั่งนี้เช็คแบบ `notify_date <= วันนี้ และยังไม่เคยส่ง` (ไม่ใช่ `== วันนี้` เป๊ะๆ) เพื่อกันกรณี cron พลาดไป
วันหนึ่ง จะได้ยิงตามในรอบถัดไปแทนที่จะข้ามรอบนั้นไปเลย — แอดมินยังกดปุ่ม "ส่งตอนนี้เลย" ที่หน้าเดียวกันเพื่อ
สั่งส่งรอบใดรอบหนึ่งทันทีได้โดยไม่ต้องรอ cron ด้วย

**Dev environment นี้ต่อ `ofelia` เป็น scheduler container ไว้ใน `docker-compose.yml` แล้วจริง** (ไม่ใช่แค่
ตัวเลือกในเอกสาร) — job ตั้งผ่าน docker labels บน service `app`:
```yaml
labels:
  ofelia.enabled: "true"
  ofelia.job-exec.reminder-send-scheduled.schedule: "0 9 * * *"
  ofelia.job-exec.reminder-send-scheduled.command: "php /app/yii reminder/send-scheduled"
```
ทดสอบแล้วว่า ofelia เรียกคำสั่งอัตโนมัติได้จริงโดยไม่มีคนสั่งเอง (สร้างรอบที่ `notify_date` = วันนี้ ทิ้งไว้
เฉยๆ แล้ว ofelia ยิงส่งเองตามรอบเวลาที่ตั้งไว้) — **ข้อควรระวัง**: ofelia ไม่ hot-reload label ทันทีตอน
container `app` ถูก recreate (เช่นหลัง `docker compose up -d` แก้ label ใหม่) ต้อง
`docker compose restart scheduler` เพื่อบังคับให้อ่าน label ปัจจุบันใหม่ ไม่งั้นจะยังใช้ schedule เก่าที่จำไว้
ตอน container เริ่มทำงานครั้งแรกอยู่

### ปุ่ม Admin สั่งส่งเอง

```php
// controllers/AdminController.php
public function actionSendReminders()
{
    $reminder = new \app\commands\ReminderController('reminder', Yii::$app);
    $pending = $reminder->getPendingProjectsPublic(); // แก้ visibility เป็น public หรือย้าย logic ไป service class

    foreach ($pending as $project) {
        $reminder->sendReminderPublic($project, 'manual_admin', Yii::$app->user->identity->username);
    }

    Yii::$app->session->setFlash('success', count($pending) . ' ฉบับ ส่งแจ้งเตือนเรียบร้อย');
    return $this->redirect(['dashboard/index']);
}
```

> แนะนำแยก logic การหา "โครงการที่ต้องเตือน" + "ส่งอีเมล" ออกเป็น service class (เช่น `services/ReminderService.php`) แล้วให้ทั้ง console command และปุ่ม admin เรียกใช้ร่วมกัน จะได้ไม่ซ้ำโค้ด

## หน้า Dashboard

แสดงจำนวนรายงานที่ส่งเข้ามาและสถานะภาพรวม

```php
// controllers/DashboardController.php
public function actionIndex()
{
    $totalProjects = ResearchProject::find()->count();
    $totalReports  = ProgressReport::find()->count();

    $byStatus = ProgressReport::find()
        ->select(['status', 'COUNT(*) as cnt'])
        ->groupBy('status')
        ->asArray()->all();

    $pendingReminder = ResearchProject::find()
        ->where(['not in', 'oid',
            ProgressReport::find()->select('oid')
                ->where(['status' => ['completed', 'terminated_early', 'cancelled']])
        ])
        ->count();

    $recentReports = ProgressReport::find()
        ->orderBy(['created_at' => SORT_DESC])
        ->limit(10)
        ->all();

    return $this->render('index', compact(
        'totalProjects', 'totalReports', 'byStatus', 'pendingReminder', 'recentReports'
    ));
}
```

**สิ่งที่ต้องมีในหน้า dashboard (view):**
- การ์ดสรุป: จำนวนโครงการทั้งหมด, จำนวนรายงานที่ส่งเข้ามาทั้งหมด, จำนวนโครงการที่ยังไม่รายงาน/ยังไม่เสร็จ (รอเตือน)
- กราฟ/ตารางแยกตามสถานะ (`not_started`, `in_progress`, `completed`, `terminated_early`, `cancelled`)
- ตารางรายงานล่าสุด 10 รายการ พร้อมลิงก์เข้าไปดูรายละเอียด
- ปุ่ม "ส่งอีเมลแจ้งเตือนโครงการที่ยังไม่เสร็จ" (เรียก `AdminController::actionSendReminders`) พร้อม confirm dialog ก่อนส่งจริง
- ตาราง log การแจ้งเตือนล่าสุด (จาก `report_notifications`) ดูว่าส่งไปแล้วกี่ครั้ง สำเร็จ/ล้มเหลว

## Docker Compose

```yaml
services:
  app:
    build: ./docker/php
    volumes:
      - ./:/app
    networks: [proxy_net, db_net]
    environment:
      - DB_DSN=mysql:host=db;dbname=research_report
      - DB_USER=report_app
      - DB_PASS=${DB_PASS}
      - SSO_APP_ID=${SSO_APP_ID:-}
      - SSO_CLIENT_ID=${SSO_CLIENT_ID:-}
      - SSO_CLIENT_SECRET=${SSO_CLIENT_SECRET:-}
      - SSO_REDIRECT_URL=${SSO_REDIRECT_URL:-}

  db:
    image: mariadb:11
    volumes:
      - db_data:/var/lib/mysql
    environment:
      - MARIADB_DATABASE=research_report
      - MARIADB_USER=report_app
      - MARIADB_PASSWORD=${DB_PASS}
      - MARIADB_ROOT_PASSWORD=${DB_ROOT_PASS}
    networks: [db_net]

  phpmyadmin:
    image: phpmyadmin
    environment:
      - PMA_HOST=db
    networks: [db_net]

networks:
  proxy_net: { external: true }
  db_net: { external: true }

volumes:
  db_data:
```

## Environment Variables ที่ต้องเตรียม

| ตัวแปร | ใช้ที่ไหน | หมายเหตุ |
|---|---|---|
| `SSO_APP_ID`, `SSO_CLIENT_ID`, `SSO_CLIENT_SECRET` | ระบบเรา (ใช้ตอน Phase 8) | ได้จากทีม SSONext (teerpo@kku.ac.th) หลังแจ้ง Redirect URL — Phase 1-7 ยังไม่ต้องมี |
| `SSO_REDIRECT_URL` | ระบบเรา (ใช้ตอน Phase 8) | เช่น `https://report.icead.kku.ac.th/auth/callback/login` ต้องแจ้งทีม SSONext ตรงกัน |
| `DB_PASS`, `DB_ROOT_PASS` | Docker | รหัสผ่าน MariaDB |
| `appBaseUrl` (Yii2 params) | ระบบเรา | ใช้ประกอบลิงก์ต่างๆ ในระบบ |
| `MAILER_HOST`, `MAILER_USER`, `MAILER_PASS`, `MAILER_PORT` | ระบบเรา | ตั้งค่า SMTP สำหรับส่งอีเมลแจ้งเตือน (`Yii::$app->mailer`) |
| `REPORT_SYSTEM_API_KEY`, `REPORT_SYSTEM_SECRET` | (เฉพาะกรณีใช้ flow สำรอง) | ดูหัวข้อ "Flow สำรอง" ด้านล่าง |

## แผนขั้นตอนพัฒนา

- [ ] **Phase 1** — Migration schema (5 ตารางข้างต้น) + Mock login (กรอกอีเมลเอง เช็ค `@kku.ac.th`) แทน SSONext จริงไปก่อน
- [ ] **Phase 2** — `ProjectSourceService::fetchAndUpsert()` + `ReportController::actionCreate` ทดสอบ end-to-end ด้วย oid จริง
- [ ] **Phase 3** — ฟอร์มรายงาน (ActiveForm) ครบทุกข้อ 1-6 พร้อม conditional field ด้วย JS (เช่น ข้อ 4.1-4.5 กด "ใช่" ค่อยโชว์ช่องรายละเอียด)
- [ ] **Phase 4** — Dynamic sub-form สำหรับ `report_publications` และ `report_ip_filings` (เพิ่ม/ลบแถวได้)
- [ ] **Phase 5** — Migration ตาราง `report_notifications` + `ReminderService` (logic หา "โครงการที่ต้องเตือน") + console command `reminder/send-monthly` + ตั้ง cron
- [ ] **Phase 6** — หน้า admin: ปุ่มสั่งส่งอีเมลแจ้งเตือนเอง + ตาราง log การแจ้งเตือน
- [ ] **Phase 7** — หน้า Dashboard: การ์ดสรุปจำนวน, กราฟ/ตารางแยกตามสถานะ, รายงานล่าสุด, export
- [ ] **Phase 8** — ติดต่อทีม SSONext ขอ App ID/Client ID/Secret แล้วสลับ `AuthController` จาก mock เป็น SSONext จริง (ไม่กระทบส่วนอื่นเพราะใช้ session key เดิม)
- [ ] **Phase 9 (ทางเลือกเสริม)** — เทียบอีเมลผู้ล็อกอินกับ `s_email`/`cont_email` ของโครงการ ถ้าไม่ตรงให้ admin ต้องอนุมัติก่อนบันทึกจริง

## Flow สำรอง (Optional): POST push จากระบบ A

เผื่อในอนาคตต้องการให้ระบบ A ส่งข้อมูล/แจ้งเตือนแบบ push เอง (เช่น real-time ตอนสถานะเปลี่ยน) แทนการรอผู้ใช้กดลิงก์ ให้ระบบ A ยิง POST มาที่ `api/receive-project` พร้อม header `X-API-KEY` (static key เก็บใน `.env` ทั้งสองฝั่ง) จากนั้นระบบเราตอบกลับ `redirect_url` ที่มี HMAC signed token (อายุ 1 ชั่วโมง) ให้ระบบ A redirect ผู้ใช้มา — โครงสร้าง endpoint และโค้ดตัวอย่างเหมือนที่เคยออกแบบไว้ก่อนหน้านี้ ยังไม่ต้อง implement จนกว่าจะมีความจำเป็นจริง

## หมายเหตุอ้างอิง

โค้ดฝั่ง PHP สำหรับดึงข้อมูลจริงจากฐานข้อมูลระบบ A (query + ส่ง API แบบ push เดิม) อยู่ในไฟล์แยกที่คุยไว้ก่อนหน้า — ให้ Claude Code อ่านไฟล์นั้นประกอบถ้าต้องทำ Flow สำรอง
