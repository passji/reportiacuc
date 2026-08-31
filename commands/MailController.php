<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * รันด้วย: php yii mail/test someone@kku.ac.th
 * ใช้ตรวจสอบว่าตั้งค่า SMTP (MAILER_HOST/PORT/USER/PASS ใน .env) ถูกต้องและส่งอีเมลออกได้จริงหรือไม่
 * — เหมาะสำหรับเช็คหลัง deploy ขึ้น server ใหม่ หรือหลัง KKU IT whitelist IP relay ให้แล้ว
 */
class MailController extends Controller
{
    public function actionTest(string $to): int
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->stderr("อีเมลปลายทางไม่ถูกต้อง: {$to}\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $mailer = Yii::$app->mailer;

        // โชว์ค่า config ปัจจุบันก่อนส่งจริง — เผื่อ .env ตั้งไม่ตรงตามที่คิดไว้ (เช่น MAILER_HOST ว่าง
        // จะไม่ error แต่จะเงียบๆ ไปเขียนไฟล์ .eml ที่ runtime/mail/ แทนโดยไม่ส่งจริง)
        $useFileTransport = $mailer->useFileTransport;
        $this->stdout("Mailer config ปัจจุบัน:\n");
        $this->stdout('  MAILER_HOST = ' . (getenv('MAILER_HOST') ?: '(ว่าง)') . "\n");
        $this->stdout('  MAILER_PORT = ' . (getenv('MAILER_PORT') ?: '(ว่าง)') . "\n");
        $this->stdout('  useFileTransport = ' . ($useFileTransport ? 'true' : 'false') . "\n");

        if ($useFileTransport) {
            $this->stdout(
                "\n⚠ MAILER_HOST ว่าง ระบบจะไม่ส่งอีเมลจริง แค่เขียนไฟล์ .eml ไว้ที่ runtime/mail/ เท่านั้น\n"
                    . "  ตั้งค่า MAILER_HOST ใน .env ก่อนถ้าต้องการทดสอบส่งจริงผ่าน SMTP\n",
                Console::FG_YELLOW
            );
        }

        $now = date('Y-m-d H:i:s');
        $sent = false;
        $error = null;

        try {
            $sent = $mailer->compose()
                ->setTo($to)
                ->setSubject('ทดสอบส่งอีเมล — ระบบรายงานความก้าวหน้าโครงการวิจัย (IACUC)')
                ->setTextBody(
                    "นี่คืออีเมลทดสอบจากคำสั่ง php yii mail/test\n"
                        . "ส่งเมื่อ: {$now}\n"
                        . 'จาก host: ' . gethostname() . "\n\n"
                        . 'ถ้าคุณได้รับอีเมลนี้ แปลว่าตั้งค่า SMTP relay ถูกต้องแล้ว'
                )
                ->send();
        } catch (\Throwable $e) {
            $error = $e;
        }

        if ($error !== null) {
            $this->stderr("\n✗ ส่งไม่สำเร็จ — เกิด exception: " . get_class($error) . "\n", Console::FG_RED);
            $this->stderr('  ' . $error->getMessage() . "\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$sent) {
            $this->stderr("\n✗ ส่งไม่สำเร็จ — mailer->send() คืนค่า false (ไม่มี exception แต่ relay ปฏิเสธ)\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($useFileTransport) {
            $this->stdout("\n✓ เขียนไฟล์ .eml สำเร็จ ดูได้ที่ runtime/mail/\n", Console::FG_GREEN);
        } else {
            $this->stdout("\n✓ ส่งอีเมลไปยัง {$to} สำเร็จ ผ่าน " . getenv('MAILER_HOST') . ':' . getenv('MAILER_PORT') . "\n", Console::FG_GREEN);
        }

        return ExitCode::OK;
    }
}
