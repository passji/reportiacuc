<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $oid
 * @property string|null $oname
 * @property string|null $oname_en
 * @property string|null $m_pro_th
 * @property string|null $m_pro_en
 * @property string|null $m_pro_dept_th
 * @property string|null $md_name
 * @property string|null $meeting_no
 * @property string|null $meeting_date
 * @property string|null $s_email
 * @property string|null $s_phone
 * @property string $raw_json
 * @property string $received_at
 * @property string $updated_at
 */
class ResearchProject extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%research_projects}}';
    }

    public function rules()
    {
        return [
            [['oid', 'raw_json'], 'required'],
            [['oname', 'oname_en', 'm_pro_dept_th', 'raw_json'], 'string'],
            [['meeting_date', 'received_at', 'updated_at'], 'safe'],
            [['oid'], 'string', 'max' => 20],
            [['m_pro_th', 'm_pro_en', 'md_name', 's_email'], 'string', 'max' => 255],
            [['meeting_no', 's_phone'], 'string', 'max' => 20],
            [['oid'], 'unique'],
        ];
    }

    public function getProgressReports()
    {
        return $this->hasMany(ProgressReport::class, ['oid' => 'oid']);
    }

    /**
     * รหัสโครงการ (project_code) — เอาจากรายงานฉบับล่าสุดของโครงการนี้ก่อน (ค่าที่ผู้ใช้กรอก/แก้ไขเอง
     * ตอนส่งรายงาน) ถ้ายังไม่เคยมีรายงานเลย ใช้ค่าจากระบบต้นทาง (raw_json.meet_summary) แทน โดยตัด
     * คำนำหน้า "รหัสโครงการเลขที่" ออกก่อน เพราะ meet_summary ส่งมาเป็นข้อความเต็มพร้อมป้ายกำกับ
     * ไม่ใช่รหัสล้วนๆ (เช่น "รหัสโครงการเลขที่ จส.มข. 7/61")
     */
    public function getProjectCode(): string
    {
        $latestReport = $this->getProgressReports()
            ->orderBy(['created_at' => SORT_DESC])
            ->one();
        if ($latestReport && $latestReport->project_code !== '') {
            return $latestReport->project_code;
        }

        $meetSummary = (string) ($this->getRawData()['meet_summary'] ?? '');
        return trim((string) preg_replace('/^รหัสโครงการเลขที่\s*/u', '', $meetSummary));
    }

    /**
     * ระบบ A ไม่ได้ส่งข้อมูลสัตว์ที่อนุมัติมาเป็นคอลัมน์แยก (an_type, an_name, male_total, ...)
     * แต่ฝังมาใน raw_json เท่านั้น — ถอดรหัสไว้ให้หน้าจอเรียกใช้แสดงผลได้
     */
    public function getRawData(): array
    {
        return json_decode((string) $this->raw_json, true) ?: [];
    }

    /**
     * จำนวน/อายุ/น้ำหนักของสัตว์กลุ่มหนึ่ง (ตัวผู้หรือตัวเมีย) จาก raw_json
     * $prefix = '' สำหรับกลุ่มหลัก, 'h_' สำหรับกลุ่มที่สอง (ถ้าโครงการมี)
     */
    private static function formatSexGroup(array $raw, string $prefix, string $sexKey): ?string
    {
        $total = $raw["{$prefix}{$sexKey}_total"] ?? null;
        if ($total === null || $total === '') {
            return null;
        }
        $age = $raw["{$prefix}{$sexKey}_age"] ?? null;
        $ageType = $raw["{$prefix}{$sexKey}_age_type"] ?? '';
        $weight = $raw["{$prefix}{$sexKey}_weight"] ?? ($raw["{$prefix}{$sexKey}_w"] ?? null);
        $weightType = $raw["{$prefix}{$sexKey}_weight_type"] ?? ($raw["{$prefix}{$sexKey}_w_type"] ?? '');

        $parts = ["{$total} ตัว"];
        if ($age !== null && $age !== '') {
            $parts[] = "อายุ {$age} {$ageType}";
        }
        if ($weight !== null && $weight !== '') {
            $parts[] = "น้ำหนัก {$weight} {$weightType}";
        }
        return implode(', ', $parts);
    }

    /**
     * สรุปจำนวนสัตว์ที่อนุมัติ แยกตามกลุ่มหลัก/กลุ่มที่สอง (ตัวผู้/ตัวเมีย)
     * ใช้ทั้งในการ์กแสดงผล (views/report/create.php) และเป็นค่าตั้งต้นของ getApprovedAnimalSummaryText()
     */
    public function getApprovedAnimalGroups(): array
    {
        $raw = $this->getRawData();
        return [
            'male' => self::formatSexGroup($raw, '', 'male'),
            'female' => self::formatSexGroup($raw, '', 'female'),
            'second_male' => self::formatSexGroup($raw, 'h_', 'male'),
            'second_female' => self::formatSexGroup($raw, 'h_', 'female'),
            'has_second_group' => !empty($raw['h_name']),
        ];
    }

    /**
     * ข้อความสรุปสัตว์ที่อนุมัติ แบบข้อความเดียว ใช้เป็นค่าตั้งต้น (แก้ไขได้) ของช่อง
     * animal_requested (ข้อ 3.1) ในฟอร์มรายงานความก้าวหน้า
     */
    public function getApprovedAnimalSummaryText(): string
    {
        $raw = $this->getRawData();
        $groups = $this->getApprovedAnimalGroups();

        $species = trim(($raw['an_type'] ?? '') . (!empty($raw['an_type_other']) ? ' (' . $raw['an_type_other'] . ')' : ''));
        $strain = implode(', ', array_filter([$raw['an_name'] ?? null, $raw['an_sectment'] ?? null], static fn ($v) => !empty($v)));

        $lines = [];
        $header = trim($species . ($strain !== '' ? " ({$strain})" : ''));
        if ($header !== '') {
            $lines[] = $header;
        }
        if ($groups['male'] !== null) {
            $lines[] = "ตัวผู้: {$groups['male']}";
        }
        if ($groups['female'] !== null) {
            $lines[] = "ตัวเมีย: {$groups['female']}";
        }
        if ($groups['has_second_group']) {
            $secondGroupName = $raw['h_name'] ?? '-';
            $lines[] = "กลุ่มที่สอง ({$secondGroupName}):";
            if ($groups['second_male'] !== null) {
                $lines[] = "  ตัวผู้: {$groups['second_male']}";
            }
            if ($groups['second_female'] !== null) {
                $lines[] = "  ตัวเมีย: {$groups['second_female']}";
            }
        }

        return implode("\n", $lines);
    }
}
