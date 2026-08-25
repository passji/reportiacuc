<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    // ค่า default (site/index) เป็นหน้า boilerplate ของ Yii เฉยๆ ไม่เกี่ยวอะไรกับระบบนี้เลย —
    // ผู้ใช้ login แล้วโดน redirect ไปหน้านั้น (ไม่มี login_return_url เดิมค้างอยู่ตอน login ตรงๆ
    // ไม่ได้ผ่านหน้าที่ redirect มา) แล้วดูเหมือนยังไม่ login เพราะเนื้อหาไม่ใช่ของแอปเราเลย ตั้งเป็น
    // "รายงานของฉัน" แทน ใช้ได้ทั้งผู้ใช้ทั่วไปและแอดมิน (มีสิทธิ์เข้าหน้านี้เสมอไม่ว่า role ไหน)
    'homeUrl' => ['/report/my-reports'],
    'bootstrap' => ['log'],
    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => require __DIR__ . '/mailer.php',
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'lkPBF5H7eCAUPg9zJH0MdNpc24TMsZge',
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'user' => [
            'identityClass' => \app\models\User::class,
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => \yii\mail\MailerInterface::class,
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        /*
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
        */
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
