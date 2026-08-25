<?php

namespace app\helpers;

use Yii;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use yii\helpers\FileHelper;

/**
 * สร้าง instance ของ mPDF พร้อมฟอนต์ Sarabun ที่ฝังไว้เอง — ใช้ร่วมกันทุกที่ที่ export PDF ในระบบ
 * (ฟอนต์ default ของ mPDF สำหรับภาษาไทย ('garuda') ไม่ได้ติดมาด้วย จึงต้องเซ็ตฟอนต์เองเสมอ)
 */
class PdfHelper
{
    public static function create(array $options = []): Mpdf
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        // mPDF default tempDir อยู่ใต้ vendor/ ซึ่ง www-data เขียนไม่ได้ (ตั้งใจให้ vendor เป็น
        // read-only) — ชี้ไปที่ runtime/ แทน เหมือนกับที่ระบบอีเมลใช้ runtime/mail อยู่แล้ว
        $tempDir = Yii::getAlias('@runtime/mpdf');
        FileHelper::createDirectory($tempDir, 0775);

        return new Mpdf(array_merge([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'tempDir' => $tempDir,
            'fontDir' => array_merge($fontDirs, [Yii::getAlias('@app/fonts/sarabun')]),
            'fontdata' => $fontData + [
                'sarabun' => [
                    'R' => 'Sarabun-Regular.ttf',
                    'B' => 'Sarabun-Bold.ttf',
                    'I' => 'Sarabun-Italic.ttf',
                    'BI' => 'Sarabun-BoldItalic.ttf',
                ],
            ],
            'default_font' => 'sarabun',
        ], $options));
    }
}
