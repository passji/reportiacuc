<?php

namespace app\services;

use Yii;

/**
 * ส่งอีเมลทดสอบเพื่อเช็คว่าตั้งค่า SMTP (MAILER_HOST/PORT/USER/PASS ใน .env) ถูกต้องและส่งออกได้จริง
 * — ใช้ร่วมกันทั้ง commands/MailController (php yii mail/test) และ controllers/MailController
 * (route mail/test บนเว็บ สำหรับ admin ที่ไม่สะดวก SSH เข้า server) กันโค้ดซ้ำ
 */
class MailTestService
{
    public static function send(string $to): array
    {
        $mailer = Yii::$app->mailer;
        $useFileTransport = $mailer->useFileTransport;
        $now = date('Y-m-d H:i:s');

        $sent = false;
        $error = null;

        try {
            $sent = $mailer->compose()
                ->setTo($to)
                ->setSubject('ทดสอบส่งอีเมล — ระบบรายงานความก้าวหน้าโครงการวิจัย (IACUC)')
                ->setTextBody(
                    "นี่คืออีเมลทดสอบจากระบบ\n"
                        . "ส่งเมื่อ: {$now}\n"
                        . 'จาก host: ' . gethostname() . "\n\n"
                        . 'ถ้าคุณได้รับอีเมลนี้ แปลว่าตั้งค่า SMTP relay ถูกต้องแล้ว'
                )
                ->send();
        } catch (\Throwable $e) {
            $error = $e;
        }

        return [
            'to' => $to,
            'useFileTransport' => $useFileTransport,
            'host' => (string) getenv('MAILER_HOST'),
            'port' => (string) getenv('MAILER_PORT'),
            'success' => $error === null && $sent,
            'error' => $error !== null
                ? get_class($error) . ': ' . $error->getMessage()
                : ($sent ? null : 'mailer->send() คืนค่า false (ไม่มี exception แต่ relay ปฏิเสธ)'),
        ];
    }
}
