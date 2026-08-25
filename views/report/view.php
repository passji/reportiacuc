<?php

/** @var yii\web\View $this */
/** @var app\models\ProgressReport $model */
/** @var array $rows ข้อมูล ข้อ 1-6 สร้างจาก ReportController::buildReportRows() ใช้ร่วมกับ actionExportPdf() */

use app\helpers\ThaiDate;
use app\models\ProgressReport;
use app\models\ReportPublication;
use app\models\ReportIpFiling;
use app\models\Admin;
use yii\bootstrap5\Html;

$this->title = 'รายงานความก้าวหน้าโครงการ #' . $model->id;
$this->params['breadcrumbs'][] = $this->title;

$this->registerJsFile('@web/js/vendor/sweetalert2.all.min.js');
$this->registerJsFile('@web/js/confirm-submit.js');

$isAdmin = Admin::isEmailAdmin((string) Yii::$app->session->get('sso_email'));

// ข้อมูลโครงการ (จากระบบ A) — เอามาแสดงในหน้านี้ด้วยแบบเดียวกับที่ report/oid ใช้ ให้ดูรายงานฉบับเดียว
// รู้บริบทโครงการครบโดยไม่ต้องสลับไปหน้าประวัติ
$project = $model->researchProject;
if ($project) {
    $projectFields = [
        'oname' => 'ชื่อโครงการ (ไทย)',
        'oname_en' => 'ชื่อโครงการ (อังกฤษ)',
        'm_pro_th' => 'หัวหน้าโครงการ',
        'm_pro_dept_th' => 'สังกัด/ภาควิชา',
        'md_name' => 'ผู้ประสานงาน',
        'meeting_no' => 'ครั้งที่ประชุม',
        'meeting_date' => 'วันที่ประชุม',
        's_email' => 'อีเมลผู้ยื่นโครงการ',
        's_phone' => 'เบอร์โทรผู้ยื่นโครงการ',
        'updated_at' => 'ดึงข้อมูลล่าสุดเมื่อ',
    ];

    $raw = $project->getRawData();
    $val = static fn (string $key): string => (string) (($raw[$key] ?? '') !== '' ? $raw[$key] : '-');

    $animalGroups = $project->getApprovedAnimalGroups();
    $primaryMale = $animalGroups['male'];
    $primaryFemale = $animalGroups['female'];
    $secondMale = $animalGroups['second_male'];
    $secondFemale = $animalGroups['second_female'];
    $hasSecondGroup = $animalGroups['has_second_group'] || $secondMale !== null || $secondFemale !== null;
}
?>
<div class="report-view">
    <div class="alert alert-success">
        บันทึกรายงานความก้าวหน้าเรียบร้อยแล้ว (รหัสโครงการอ้างอิง oid: <?= Html::encode($model->oid) ?>)
    </div>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h1 class="h4 fw-bold mb-0"><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="fas fa-file-pdf me-1"></i>ดาวน์โหลด PDF', ['export-pdf', 'id' => $model->id], ['class' => 'btn btn-outline-danger btn-sm']) ?>
    </div>

    <?php if ($project): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-semibold">ข้อมูลโครงการ</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tbody>
                    <tr>
                        <th class="text-body-secondary fw-normal" style="width: 320px;">รหัสโครงการ</th>
                        <td><?= Html::encode($project->getProjectCode() ?: '-') ?></td>
                    </tr>
                    <?php foreach ($projectFields as $attribute => $label): ?>
                        <tr>
                            <th class="text-body-secondary fw-normal" style="width: 320px;"><?= Html::encode($label) ?></th>
                            <td><?= Html::encode($project->$attribute ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-semibold">ข้อมูลสัตว์ที่ได้รับการอนุมัติ</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tbody>
                    <tr>
                        <th class="text-body-secondary fw-normal" style="width: 320px;">ชนิดสัตว์</th>
                        <td>
                            <?= Html::encode($val('an_type')) ?>
                            <?php if (!empty($raw['an_type_other'])): ?>
                                (<?= Html::encode($raw['an_type_other']) ?>)
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-body-secondary fw-normal">สายพันธุ์ / ลักษณะ</th>
                        <td><?= Html::encode($val('an_name')) ?> — <?= Html::encode($val('an_sectment')) ?></td>
                    </tr>
                    <tr>
                        <th class="text-body-secondary fw-normal">จำนวนที่อนุมัติ (ตัวผู้)</th>
                        <td><?= Html::encode($primaryMale ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th class="text-body-secondary fw-normal">จำนวนที่อนุมัติ (ตัวเมีย)</th>
                        <td><?= Html::encode($primaryFemale ?? '-') ?></td>
                    </tr>
                    <?php if ($hasSecondGroup): ?>
                        <tr>
                            <th class="text-body-secondary fw-normal">กลุ่มสัตว์ที่สอง</th>
                            <td><?= Html::encode($val('h_name')) ?></td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">จำนวนที่อนุมัติ (ตัวผู้ — กลุ่มที่สอง)</th>
                            <td><?= Html::encode($secondMale ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">จำนวนที่อนุมัติ (ตัวเมีย — กลุ่มที่สอง)</th>
                            <td><?= Html::encode($secondFemale ?? '-') ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th class="text-body-secondary fw-normal">แหล่งที่มาของสัตว์</th>
                        <td><?= Html::encode($val('an_resource')) ?></td>
                    </tr>
                    <tr>
                        <th class="text-body-secondary fw-normal">สถานที่เลี้ยง</th>
                        <td><?= Html::encode($val('an_location')) ?></td>
                    </tr>
                    <tr>
                        <th class="text-body-secondary fw-normal">ช่วงเวลาที่ใช้สัตว์</th>
                        <td><?= Html::encode($val('start_ani')) ?> — <?= Html::encode($val('stop_ani')) ?></td>
                    </tr>
                    <tr>
                        <th class="text-body-secondary fw-normal">หมายเหตุ</th>
                        <td><?= Html::encode($val('an_ex')) ?></td>
                    </tr>
                    <tr>
                        <th class="text-body-secondary fw-normal">มติที่ประชุม / สถานะอนุมัติ</th>
                        <td><?= Html::encode($val('meet_note')) ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tbody>
                <?php
                // ไล่สีป้ายหัวข้อตามช่วง: "มีการเปลี่ยนแปลงวัตถุประสงค์หรือไม่" ถึง "ข้อ 6" (รวมแถวย่อย
                // ระหว่างทางที่ไม่มีคำว่า "ข้อ" นำหน้า เช่น "รายละเอียด..."/"วันที่คาดว่า...") เป็นสีเขียวเข้ม
                // ส่วน "ส่งรายงานโดย"/"วันที่ส่งรายงาน" ท้ายสุดเป็นสีแดง — อาศัยลำดับการ insert เข้า $rows
                // ด้านบนซึ่งตรงกับลำดับที่ต้องการเป๊ะอยู่แล้ว
                $inGreenSection = false;
                $redLabels = ['ส่งรายงานโดย', 'วันที่ส่งรายงาน'];
                ?>
                <?php foreach ($rows as $label => $value): ?>
                    <?php
                    if ($label === 'มีการเปลี่ยนแปลงวัตถุประสงค์หรือไม่') {
                        $inGreenSection = true;
                    }
                    $labelClass = 'fw-normal';
                    if (in_array($label, $redLabels, true)) {
                        $labelClass .= ' text-danger';
                    } elseif ($inGreenSection) {
                        $labelClass .= ' text-success-emphasis';
                    } else {
                        $labelClass .= ' text-body-secondary';
                    }
                    if (str_starts_with($label, 'ข้อ 6:')) {
                        $inGreenSection = false;
                    }
                    ?>
                    <tr>
                        <th class="<?= $labelClass ?>" style="width: 320px;"><?= Html::encode($label) ?></th>
                        <td><?= nl2br(Html::encode((string) ($value ?: '-'))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($model->has_publication === 'yes'): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-semibold">ผลงานตีพิมพ์ (ข้อ 6.1)</div>
            <div class="card-body">
                <?php if (!$model->publications): ?>
                    <p class="text-body-secondary mb-0">ยังไม่ได้ระบุรายละเอียดผลงานตีพิมพ์</p>
                <?php endif; ?>
                <?php foreach ($model->publications as $index => $pub): ?>
                    <?php if ($index > 0): ?><hr><?php endif; ?>
                    <table class="table table-sm mb-0">
                        <tbody>
                        <tr>
                            <th class="text-body-secondary fw-normal" style="width: 320px;">6.1.1 ชื่อบทความ</th>
                            <td><?= Html::encode($pub->article_title ?: '-') ?></td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">ระดับ</th>
                            <td><?= Html::encode(ReportPublication::LEVEL_LABELS[$pub->level] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">6.1.2 วารสาร</th>
                            <td>
                                <?= Html::encode($pub->journal_name ?: '-') ?>
                                <?php if ($pub->issue): ?> ฉบับที่ <?= Html::encode($pub->issue) ?><?php endif; ?>
                                <?php if ($pub->page): ?> หน้าที่ <?= Html::encode($pub->page) ?><?php endif; ?>
                                <?php if ($pub->pub_month || $pub->pub_year): ?>
                                    เดือน <?= Html::encode($pub->pub_month) ?> ปี <?= Html::encode($pub->pub_year) ?>
                                <?php endif; ?>
                                <?php if ($pub->doi): ?> DOI: <?= Html::encode($pub->doi) ?><?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">6.1.3 ฐานข้อมูล</th>
                            <td>
                                <?= Html::encode(ReportPublication::DB_TYPE_LABELS[$pub->db_type] ?? '-') ?>
                                <?php if ($pub->db_other): ?> (<?= Html::encode($pub->db_other) ?>)<?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">6.1.4 Quartile / Impact Factor</th>
                            <td><?= Html::encode($pub->quartile ?: '-') ?> / <?= Html::encode($pub->impact_factor ?: '-') ?></td>
                        </tr>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($model->has_publication === 'yes'): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-semibold">การยื่นจดทรัพย์สินทางปัญญา (ข้อ 6.2)</div>
            <div class="card-body">
                <?php if (!$model->ipFilings): ?>
                    <p class="text-body-secondary mb-0">ยังไม่ได้ระบุรายละเอียดการยื่นจดทรัพย์สินทางปัญญา</p>
                <?php endif; ?>
                <?php foreach ($model->ipFilings as $index => $ip): ?>
                    <?php if ($index > 0): ?><hr><?php endif; ?>
                    <table class="table table-sm mb-0">
                        <tbody>
                        <tr>
                            <th class="text-body-secondary fw-normal" style="width: 320px;">ประเภท</th>
                            <td><?= Html::encode(ReportIpFiling::IP_TYPE_LABELS[$ip->ip_type] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">6.2.1 วันที่ยื่นจด</th>
                            <td><?= Html::encode($ip->filed_date ?: '-') ?></td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">6.2.2 เลขที่จดทะเบียน</th>
                            <td><?= Html::encode($ip->registration_no ?: '-') ?></td>
                        </tr>
                        <tr>
                            <th class="text-body-secondary fw-normal">ชื่อทรัพย์สิน</th>
                            <td><?= Html::encode($ip->asset_name ?: '-') ?></td>
                        </tr>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($model->attachments): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-semibold">เอกสารแนบ</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                    <?php foreach ($model->attachments as $attachment): ?>
                        <?php $sizeKb = round($attachment->file_size / 1024); ?>
                        <tr>
                            <td><?= Html::encode($attachment->original_filename) ?></td>
                            <td class="text-body-secondary small"><?= $sizeKb ?> KB</td>
                            <td class="text-end">
                                <?= Html::a('ดาวน์โหลด', ['download-attachment', 'id' => $attachment->id], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent fw-semibold d-flex align-items-center justify-content-between">
            <span>ผลการตรวจสอบ</span>
            <span class="badge <?= ProgressReport::REVIEW_STATUS_BADGE_CLASSES[$model->review_status] ?? 'bg-secondary' ?>">
                <?= Html::encode(ProgressReport::REVIEW_STATUS_LABELS[$model->review_status] ?? $model->review_status) ?>
            </span>
        </div>
        <div class="card-body">
            <?php if ($model->review_status === 'rejected' && $model->rejection_reason): ?>
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1">เหตุผลที่ปฏิเสธ:</div>
                    <?= nl2br(Html::encode($model->rejection_reason)) ?>
                </div>
            <?php endif; ?>
            <?php if ($model->reviewed_at): ?>
                <p class="text-body-secondary small mb-3">
                    ตรวจสอบโดย <?= Html::encode($model->reviewed_by ?: '-') ?>
                    เมื่อ <?= Html::encode(ThaiDate::format($model->reviewed_at)) ?>
                </p>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
            <p class="text-body-secondary small mb-2">สำหรับเจ้าหน้าที่ตรวจสอบ:</p>
            <div class="d-flex flex-wrap gap-2 align-items-start">
                <?= Html::beginForm(
                    ['review-decision', 'id' => $model->id],
                    'post',
                    ['data-confirm-message' => 'ยืนยันว่ารายงานนี้ตรวจแล้ว?']
                ) ?>
                    <?= Html::hiddenInput('decision', 'approved') ?>
                    <?= Html::submitButton('ตรวจแล้ว', ['class' => 'btn btn-success']) ?>
                <?= Html::endForm() ?>

                <?= Html::beginForm(
                    ['review-decision', 'id' => $model->id],
                    'post',
                    [
                        'class' => 'd-flex flex-wrap gap-2 align-items-start flex-grow-1',
                        'data-confirm-message' => 'ยืนยันปฏิเสธรายงานนี้? ระบบจะส่งอีเมลแจ้งผู้ส่งรายงานโดยอัตโนมัติ',
                        'data-confirm-icon' => 'warning',
                    ]
                ) ?>
                    <?= Html::hiddenInput('decision', 'rejected') ?>
                    <?= Html::textarea('rejection_reason', '', [
                        'class' => 'form-control',
                        'rows' => 2,
                        'style' => 'min-width: 320px;',
                        'placeholder' => 'ระบุเหตุผล/สิ่งที่ต้องแก้ไข (จำเป็นสำหรับการปฏิเสธ)',
                        'required' => true,
                    ]) ?>
                    <?= Html::submitButton('ปฏิเสธ', ['class' => 'btn btn-outline-danger']) ?>
                <?= Html::endForm() ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?= Html::a('ส่งรายงานฉบับใหม่สำหรับโครงการนี้', ['create', 'oid' => $model->oid], ['class' => 'btn btn-outline-primary']) ?>
    <?= Html::a('ดูประวัติการรายงานทั้งหมดของโครงการนี้', ['oid', 'oid' => $model->oid], ['class' => 'btn btn-outline-secondary']) ?>
</div>
