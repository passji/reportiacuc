<?php

namespace app\models;

use yii\db\ActiveRecord;

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
            [['animal_requested_male', 'animal_requested_female', 'animal_used_male', 'animal_used_female'],
                'integer', 'min' => 0],
            [['animal_used_unit'], 'in', 'range' => array_keys(self::UNIT_LABELS)],
            [['animal_used_unit'], 'default', 'value' => 'head'],
            [['post_study_handling'], 'in', 'range' => array_keys(self::POST_STUDY_HANDLING_LABELS)],
            [['post_study_handling'], 'default', 'value' => 'as_approved'],
            [['review_status'], 'in', 'range' => array_keys(self::REVIEW_STATUS_LABELS)],
            [['review_status'], 'default', 'value' => 'pending'],
            [['reviewed_by'], 'string', 'max' => 255],
            [['rejection_reason'], 'string'],
            [['expected_start_date', 'expected_complete_date', 'completed_date', 'created_at', 'reviewed_at'], 'safe'],
            [['objective_changed'], 'in', 'range' => ['same', 'changed']],
            [['status'], 'in', 'range' => ['not_started', 'in_progress', 'completed', 'terminated_early', 'cancelled']],
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

            // ข้อ 2.1 — วันที่/เหตุผลที่จำเป็นขึ้นอยู่กับสถานะโครงการ
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
                return $model->status === 'completed';
            }, 'whenClient' => "function (attribute, value) {
                return $('#progressreport-status').val() === 'completed';
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
