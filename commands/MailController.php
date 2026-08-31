<?php

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use app\services\MailTestService;

/**
 * รันด้วย: php yii mail/test someone@kku.ac.th
 * ใช้ตรวจสอบว่าตั้งค่า SMTP (MAILER_HOST/PORT/USER/PASS ใน .env) ถูกต้องและส่งอีเมลออกได้จริงหรือไม่
 * — เหมาะสำหรับเช็คหลัง deploy ขึ้น server ใหม่ หรือหลัง KKU IT whitelist IP relay ให้แล้ว
 * (มีเวอร์ชันเว็บด้วยที่ controllers/MailController.php route mail/test สำหรับ admin ที่ไม่สะดวก SSH)
 */
class MailController extends Controller
{
    public function actionTest(string $to): int
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->stderr("อีเมลปลายทางไม่ถูกต้อง: {$to}\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $this->stdout("Mailer config ปัจจุบัน:\n");
        $this->stdout('  MAILER_HOST = ' . (getenv('MAILER_HOST') ?: '(ว่าง)') . "\n");
        $this->stdout('  MAILER_PORT = ' . (getenv('MAILER_PORT') ?: '(ว่าง)') . "\n");

        $result = MailTestService::send($to);

        $this->stdout('  useFileTransport = ' . ($result['useFileTransport'] ? 'true' : 'false') . "\n");

        if ($result['useFileTransport']) {
            $this->stdout(
                "\n⚠ MAILER_HOST ว่าง ระบบจะไม่ส่งอีเมลจริง แค่เขียนไฟล์ .eml ไว้ที่ runtime/mail/ เท่านั้น\n"
                    . "  ตั้งค่า MAILER_HOST ใน .env ก่อนถ้าต้องการทดสอบส่งจริงผ่าน SMTP\n",
                Console::FG_YELLOW
            );
        }

        if (!$result['success']) {
            $this->stderr("\n✗ ส่งไม่สำเร็จ — {$result['error']}\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($result['useFileTransport']) {
            $this->stdout("\n✓ เขียนไฟล์ .eml สำเร็จ ดูได้ที่ runtime/mail/\n", Console::FG_GREEN);
        } else {
            $this->stdout("\n✓ ส่งอีเมลไปยัง {$to} สำเร็จ ผ่าน {$result['host']}:{$result['port']}\n", Console::FG_GREEN);
        }

        return ExitCode::OK;
    }
}
