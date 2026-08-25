<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $email
 * @property string $created_at
 */
class Admin extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%admins}}';
    }

    public function rules()
    {
        return [
            [['email'], 'required'],
            [['email'], 'email'],
            [['email'], 'match', 'pattern' => '/@kku\.ac\.th$/', 'message' => 'อนุญาตเฉพาะอีเมล @kku.ac.th เท่านั้น'],
            [['email'], 'unique'],
            [['email'], 'string', 'max' => 255],
            [['created_at'], 'safe'],
        ];
    }

    public static function isEmailAdmin(?string $email): bool
    {
        return !empty($email) && static::find()->where(['email' => $email])->exists();
    }
}
