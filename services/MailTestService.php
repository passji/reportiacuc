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
        /** @var \yii\symfonymailer\Mailer $mailer */
        $mailer = Yii::$app->mailer;
        $useFileTransport = $mailer->useFileTransport;
        $now = date('Y-m-d H:i:s');

        $message = $mailer->compose()
            ->setTo($to)
            ->setSubject('ทดสอบส่งอีเมล — ระบบรายงานความก้าวหน้าโครงการวิจัย (IACUC)')
            ->setTextBody(
                "นี่คืออีเมลทดสอบจากระบบ\n"
                    . "ส่งเมื่อ: {$now}\n"
                    . 'จาก host: ' . gethostname() . "\n\n"
                    . 'ถ้าคุณได้รับอีเมลนี้ แปลว่าตั้งค่า SMTP relay ถูกต้องแล้ว'
            );

        $sent = false;
        $error = null;

        try {
            if ($useFileTransport) {
                // path นี้แค่ file_put_contents() ไม่มีปัญหา exception ถูกกลืนแบบ path ด้านล่าง
                $sent = $message->send();
            } else {
                // ไม่เรียก $message->send() ตรงๆ เพราะ yii2-symfonymailer's Mailer::sendMessage()
                // ดัก exception จริงไว้เอง (catch (\Exception $e) { log แล้ว return false; }) ทำให้
                // error จริงจาก SMTP (เช่น "554 poor MTA reputation") ไปไม่ถึงโค้ดเรา เห็นแค่ false
                // เฉยๆ — เรียก Symfony mailer ชั้นล่างตรงๆ แทน ให้ exception จริงหลุดออกมาให้ catch เอง
                $mailer->getSymfonyMailer()->send($message->getSymfonyEmail());
                $sent = true;
            }
        } catch (\Throwable $e) {
            $error = $e;
        }

        return [
            'to' => $to,
            'useFileTransport' => $useFileTransport,
            'host' => (string) getenv('MAILER_HOST'),
            'port' => (string) getenv('MAILER_PORT'),
            'success' => $error === null && $sent,
            'error' => $error !== null ? get_class($error) . ': ' . $error->getMessage() : null,
        ];
    }
}
