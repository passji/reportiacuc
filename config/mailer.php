<?php

$params = require __DIR__ . '/params.php';

$mailerHost = getenv('MAILER_HOST') ?: '';

$config = [
    'class' => \yii\symfonymailer\Mailer::class,
    'viewPath' => '@app/mail',
    'messageConfig' => [
        'from' => [$params['senderEmail'] => $params['senderName']],
    ],
];

if ($mailerHost !== '') {
    // ตั้งค่า SMTP จริงแล้ว (MAILER_HOST ไม่ว่าง) — ส่งอีเมลออกจริงผ่าน relay นี้ relay ภายใน
    // มหาวิทยาลัยส่วนใหญ่ (พอร์ต 25) ไม่ต้องยืนยันตัวตน username/password จึงเว้นว่างได้
    $config['useFileTransport'] = false;
    $config['transport'] = [
        'scheme' => 'smtp',
        'host' => $mailerHost,
        'port' => getenv('MAILER_PORT') ?: '25',
        'username' => getenv('MAILER_USER') ?: '',
        'password' => getenv('MAILER_PASS') ?: '',
    ];
} else {
    // ยังไม่ได้ตั้งค่า SMTP จริง — เขียนเป็นไฟล์ .eml ไว้ที่ runtime/mail/ แทน (ปลอดภัยสำหรับ dev)
    $config['useFileTransport'] = true;
}

return $config;
