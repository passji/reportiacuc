<?php

return [
    'class' => \yii\db\Connection::class,
    'dsn' => getenv('DB_DSN') ?: 'mysql:host=localhost;dbname=yii2basic',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
