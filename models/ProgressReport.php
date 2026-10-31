<?php

namespace app\models;

use yii\db\ActiveRecord;
use app\helpers\ThaiDate;

/**
 * @property int $id
 * @property string $oid
 * @property string $project_code
 * @property string $meeting_ref
 * @property string $pi_name
 * @property string $project_name_th
 * @property string $project_name_en
 * @property string $objective_changed
 * @property string|null $objective_change_detail
 * @property string $status
 * @property string|null $expected_start_date
 * @property string|null $expected_complete_date
 * @property string|null $completed_date
 * @property string|null $stop_reason
 * @property int $animal_requested_male
 * @property int $animal_requested_female
 * @property string|null $animal_requested_note
 * @property int $animal_used_male
 * @property int $animal_used_female
 * @property string $animal_used_unit
 * @property string|null $animal_used_note
 * @property string $method_changed
 * @property string|null $method_change_detail
 * @property string $post_study_handling
 * @property string|null $post_study_handling_detail
 * @property string $adverse_event
 * @property string|null $adverse_event_detail
 * @property string $personnel_changed
 * @property string|null $personnel_change_detail
 * @property string $alt_method_found
 * @property string|null $alt_method_detail
 * @property string $study_summary
 * @property string $has_publication
 * @property string $submitted_by_email
 * @property string $status_flag
 * @property string $created_at
 * @property string $review_status
 * @property string|null $rejection_reason
 * @property string|null $reviewed_at
 * @property string|null $reviewed_by
 *
 */
class ProgressReport extends ActiveRecord
{
    public const UNIT_LABELS = [
        'head' => 'ตัว',
        'ml' => 'มิลลิลิตร',
        'l' => 'ลิตร',
        'kg' => 'กิโลกรัม',
    ];

    public const POST_STUDY_HANDLING_LABELS = [
        'as_approved' => 'ปฏิบัติต่อสัตว์ตามที่แจ้งรายละเอียดในโครงการที่ได้รับการรับรอง',
        'changed' => 'มีการเปลี่ยนแปลงวิธีปฏิบัติต่อสัตว์ (ระบุโดยละเอียด)',
    ];

    public const REVIEW_STATUS_LABELS = [
        'pending' => 'รอตรวจสอบ',
        'approved' => 'ตรวจแล้ว',
        'rejected' => 'ปฏิเสธ',
    ];

    public const REVIEW_STATUS_BADGE_CLASSES = [
        'pending' => 'bg-secondary',
        'approved' => 'bg-success',
        'rejected' => 'bg-danger',
    ];

    /**
     * ชื่อช่องภาษาไทยสำหรับข้อความ error (ทั้งตอน validate ฝั่งเซิร์ฟเวอร์และ JS ฝั่ง client) — ให้ผู้ใช้รู้ว่า
     * error นั้นเป็นของข้อไหน ไม่ใช่ชื่อคอลัมน์ภาษาอังกฤษ
     */
    public function attributeLabels()
    {
        return [
            'oid' => 'ลำดับที่ (oid)',
            'project_code' => 'ข้อ 1: เลขที่โครงการ',
            'meeting_ref' => 'วันที่พิจารณา',
            'pi_name' => 'ข้อ 2: ชื่อหัวหน้าโครงการ',
            'project_name_th' => 'ข้อ 3: ชื่อโครงการ (ภาษาไทย)',
            'project_name_en' => 'ข้อ 4: ชื่อโครงการ (ภาษาอังกฤษ)',
            'objective_changed' => 'ข้อ 1: การเปลี่ยนแปลงวัตถุประสงค์ของโครงการ',
            'objective_change_detail' => 'รายละเอียดการเปลี่ยนแปลงวัตถุประสงค์',
            'status' => 'ข้อ 2: สถานะการดำเนินโครงการ',
            'expected_start_date' => 'วันที่คาดว่าจะเริ่มดำเนินการ',
            'expected_complete_date' => 'วันที่คาดว่าจะเสร็จสิ้น',
            'completed_date' => 'วันที่ดำเนินการเสร็จสิ้น',
            'stop_reason' => 'เหตุผลที่ยังไม่เริ่มดำเนินการ/ยุติ/ยกเลิกโครงการ',
            'animal_requested_male' => 'ข้อ 3.1: จำนวนสัตว์ที่ได้รับอนุมัติ (ตัวผู้)',
            'animal_requested_female' => 'ข้อ 3.1: จำนวนสัตว์ที่ได้รับอนุมัติ (ตัวเมีย)',
            'animal_requested_note' => 'ข้อ 3.1: หมายเหตุสัตว์ที่ได้รับอนุมัติ',
            'animal_used_male' => 'ข้อ 3.2: จำนวนสัตว์ที่ใช้จริง (ตัวผู้)',
            'animal_used_female' => 'ข้อ 3.2: จำนวนสัตว์ที่ใช้จริง (ตัวเมีย)',
            'animal_used_unit' => 'ข้อ 3.2: หน่วยของจำนวนสัตว์ที่ใช้จริง',
            'animal_used_note' => 'ข้อ 3.2: หมายเหตุจำนวนสัตว์ที่ใช้จริง',
            'method_changed' => 'ข้อ 4.1: การเปลี่ยนแปลงวิธีการทดลอง',
            'method_change_detail' => 'รายละเอียดการเปลี่ยนแปลงวิธีการทดลอง',
            'post_study_handling' => 'ข้อ 4.2: การปฏิบัติต่อสัตว์หลังเสร็จสิ้นโครงการ',
            'post_study_handling_detail' => 'รายละเอียดการเปลี่ยนแปลงวิธีปฏิบัติต่อสัตว์',
            'adverse_event' => 'ข้อ 4.3: เหตุการณ์ไม่พึงประสงค์',
            'adverse_event_detail' => 'รายละเอียดเหตุการณ์ไม่พึงประสงค์',
            'personnel_changed' => 'ข้อ 4.4: การเปลี่ยนแปลงผู้ปฏิบัติงาน',
            'personnel_change_detail' => 'รายละเอียดการเปลี่ยนแปลงผู้ปฏิบัติงาน',
            'alt_method_found' => 'ข้อ 4.5: วิธีการทดแทนการใช้สัตว์ทดลอง',
            'alt_method_detail' => 'รายละเอียดวิธีการทดแทน',
            'study_summary' => 'ข้อ 5: สรุปผลการดำเนินงาน',
            'has_publication' => 'ข้อ 6: การตีพิมพ์ เผยแพร่ หรือนำไปใช้ประโยชน์',
            'submitted_by_email' => 'อีเมลผู้ส่งรายงาน',
            'rejection_reason' => 'เหตุผลที่ปฏิเสธ',
            'reviewed_by' => 'ผู้ตรวจสอบ',
        ];
    }

    public static function tableName()
    {
        return '{{%progress_reports}}';
    }

    public function rules()
    {
        return [
            [['oid', 'project_code', 'meeting_ref', 'pi_name', 'project_name_th', 'project_name_en',
                'objective_changed', 'status',
                'animal_requested_male', 'animal_requested_female',
                'animal_used_male', 'animal_used_female', 'animal_used_unit',
                'method_changed', 'post_study_handling', 'adverse_event', 'personnel_changed',
                'alt_method_found', 'study_summary', 'has_publication', 'submitted_by_email'], 'required'],
            [['objective_change_detail', 'stop_reason', 'animal_requested_note', 'animal_used_note',
                'method_change_detail', 'post_study_handling_detail', 'adverse_event_detail',
                'personnel_change_detail', 'alt_method_detail', 'study_summary',
                'project_name_th', 'project_name_en'], 'string'],
            // คอลัมน์เป็น TEXT (65,535 ไบต์; ภาษาไทย 3 ไบต์/ตัวอักษร) — ถ้าไม่จำกัดตรงนี้ ข้อความยาวเกินจะ
            // ผ่าน validate แล้วไปพังตอน save เป็น "เกิดข้อผิดพลาดขณะบันทึกรายงาน" ที่ผู้ใช้ไม่รู้สาเหตุ
            [['objective_change_detail', 'stop_reason', 'animal_requested_note', 'animal_used_note',
                'method_change_detail', 'post_study_handling_detail', 'adverse_event_detail',
                'personnel_change_detail', 'alt_method_detail', 'study_summary', 'rejection_reason'], 'string', 'max' => 16000],
            [['animal_requested_male', 'animal_requested_female', 'animal_used_male', 'animal_used_female'],
                'integer', 'min' => 0, 'max' => 2000000000],
            [['animal_used_unit'], 'in', 'range' => array_keys(self::UNIT_LABELS)],
            [['animal_used_unit'], 'default', 'value' => 'head'],
            [['post_study_handling'], 'in', 'range' => array_keys(self::POST_STUDY_HANDLING_LABELS)],
            [['post_study_handling'], 'default', 'value' => 'as_approved'],
            [['review_status'], 'in', 'range' => array_keys(self::REVIEW_STATUS_LABELS)],
            [['review_status'], 'default', 'value' => 'pending'],
            [['reviewed_by'], 'string', 'max' => 255],
            [['rejection_reason'], 'string'],
            [['created_at', 'reviewed_at'], 'safe'],
            [['expected_start_date', 'expected_complete_date', 'completed_date'], 'validateThaiDate'],
            [['objective_changed'], 'in', 'range' => ['same', 'changed']],
            [['status'], 'in', 'range' => ['not_started', 'in_progress', 'completed', 'completed_closing', 'terminated_early', 'cancelled']],
            [['method_changed', 'adverse_event', 'personnel_changed', 'alt_method_found', 'has_publication'], 'in', 'range' => ['yes', 'no']],
            [['status_flag'], 'in', 'range' => ['draft', 'submitted']],
            [['status_flag'], 'default', 'value' => 'draft'],
            [['oid'], 'string', 'max' => 20],
            [['project_code'], 'string', 'max' => 50],
            [['meeting_ref', 'pi_name', 'submitted_by_email'], 'string', 'max' => 255],

            // ข้อ B.1 — รายละเอียดจำเป็นเมื่อ "มีการเปลี่ยนแปลงวัตถุประสงค์"
            [['objective_change_detail'], 'required', 'when' => function ($model) {
                return $model->objective_changed === 'changed';
            }, 'whenClient' => "function (attribute, value) {
                return $('input[name=\"ProgressReport[objective_changed]\"]:checked').val() === 'changed';
            }"],

            // ข้อ 2 — วันที่/เหตุผลที่จำเป็นขึ้นอยู่กับสถานะโครงการ
            [['expected_start_date'], 'required', 'when' => function ($model) {
                return $model->status === 'not_started';
            }, 'whenClient' => "function (attribute, value) {
                return $('#progressreport-status').val() === 'not_started';
            }"],
            [['expected_complete_date'], 'required', 'when' => function ($model) {
                return $model->status === 'in_progress';
            }, 'whenClient' => "function (attribute, value) {
                return $('#progressreport-status').val() === 'in_progress';
            }"],
            [['completed_date'], 'required', 'when' => function ($model) {
                return in_array($model->status, ['completed', 'completed_closing'], true);
            }, 'whenClient' => "function (attribute, value) {
                var v = $('#progressreport-status').val();
                return v === 'completed' || v === 'completed_closing';
            }"],
            [['stop_reason'], 'required', 'when' => function ($model) {
                return in_array($model->status, ['not_started', 'terminated_early', 'cancelled'], true);
            }, 'whenClient' => "function (attribute, value) {
                var v = $('#progressreport-status').val();
                return v === 'not_started' || v === 'terminated_early' || v === 'cancelled';
            }"],

            // ข้อ 4.1-4.5 — รายละเอียดจำเป็นเมื่อตอบ "ใช่"
            [['method_change_detail'], 'required', 'when' => function ($model) {
                return $model->method_changed === 'yes';
            }, 'whenClient' => "function (attribute, value) {
                return $('input[name=\"ProgressReport[method_changed]\"]:checked').val() === 'yes';
            }"],
            // ข้อ 4.2 — รายละเอียดจำเป็นเมื่อ "มีการเปลี่ยนแปลงวิธีปฏิบัติต่อสัตว์"
            [['post_study_handling_detail'], 'required', 'when' => function ($model) {
                return $model->post_study_handling === 'changed';
            }, 'whenClient' => "function (attribute, value) {
                return $('#progressreport-post_study_handling').val() === 'changed';
            }"],
            [['adverse_event_detail'], 'required', 'when' => function ($model) {
                return $model->adverse_event === 'yes';
            }, 'whenClient' => "function (attribute, value) {
                return $('input[name=\"ProgressReport[adverse_event]\"]:checked').val() === 'yes';
            }"],
            [['personnel_change_detail'], 'required', 'when' => function ($model) {
                return $model->personnel_changed === 'yes';
            }, 'whenClient' => "function (attribute, value) {
                return $('input[name=\"ProgressReport[personnel_changed]\"]:checked').val() === 'yes';
            }"],
            [['alt_method_detail'], 'required', 'when' => function ($model) {
                return $model->alt_method_found === 'yes';
            }, 'whenClient' => "function (attribute, value) {
                return $('input[name=\"ProgressReport[alt_method_found]\"]:checked').val() === 'yes';
            }"],

            // การตรวจสอบของแอดมิน — ต้องระบุเหตุผลเมื่อ "ปฏิเสธ" เท่านั้น
            [['rejection_reason'], 'required', 'when' => function ($model) {
                return $model->review_status === 'rejected';
            }],
        ];
    }

    /**
     * ฟอร์มให้ผู้ใช้พิมพ์วันที่แบบไทย (วว/ดด/ปปปป พ.ศ. เช่น 01/12/2569) ไม่ใช่ ISO ตรงๆ — แปลง/
     * ตรวจสอบตรงนี้ (ไม่ใช่แค่ฝั่ง JS) กันข้อมูลเพี้ยนถ้า JS พัง/ถูกข้าม แล้วทับ $this->$attribute
     * ด้วยค่า ISO ที่แปลงแล้วเลย ผู้เรียกด้านหลัง (save/แสดงผล) จึงยังเห็นเป็น ISO ตามปกติ ใช้ร่วมกับ
     * ReportIpFiling::validateThaiDate() (โครงสร้างเดียวกัน แยกเพราะเป็นคนละ AR)
     */
    public function validateThaiDate($attribute)
    {
        $parsed = ThaiDate::parseThaiInput($this->$attribute);
        if ($parsed === false) {
            $this->addError($attribute, 'รูปแบบวันที่ไม่ถูกต้อง กรุณากรอกเป็น วว/ดด/ปปปป (พ.ศ.) เช่น 01/12/2569');
            return;
        }
        $this->$attribute = $parsed;
    }

    public function getResearchProject()
    {
        return $this->hasOne(ResearchProject::class, ['oid' => 'oid']);
    }

    public function getPublications()
    {
        return $this->hasMany(ReportPublication::class, ['report_id' => 'id']);
    }

    public function getIpFilings()
    {
        return $this->hasMany(ReportIpFiling::class, ['report_id' => 'id']);
    }

    public function getAttachments()
    {
        return $this->hasMany(ReportAttachment::class, ['report_id' => 'id']);
    }
}
