<?php

namespace app\controllers;

use yii\web\Controller;

/**
 * คู่มือการใช้งานแบบหน้าเว็บ (คู่คู่กับไฟล์ .docx ที่ส่งให้แยกต่างหาก) — ตั้งชื่อ action เป็นคำเดียวไม่มี
 * ตัวพิมพ์ใหญ่กลางคำ (usermanual/adminmanual แทน userManual/adminManual) เพราะ Yii แปลงชื่อ action
 * เป็น route โดยแทรก "-" ที่ตัวอักษรใหญ่แต่ละตัว — ถ้าตั้งแบบ camelCase route จะกลายเป็น
 * about/user-manual ซึ่งไม่ตรงกับ about/usermanual ที่ต้องการ
 */
class AboutController extends Controller
{
    public function actionUsermanual()
    {
        return $this->render('usermanual');
    }

    public function actionAdminmanual()
    {
        return $this->render('adminmanual');
    }
}
